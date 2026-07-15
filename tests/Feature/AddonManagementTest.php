<?php

use App\Actions\CreateBookingAction;
use App\Actions\CreateBookingData;
use App\Enums\BookingSource;
use App\Enums\DayType;
use App\Exceptions\AddonUnavailableException;
use App\Models\Addon;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Field;
use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Add-on perlengkapan — docs/01-prd.md Modul 11.
 */
beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14 08:00:00'));
    $this->tanggal = Carbon::parse('2026-07-15');

    $this->cabangA = Branch::factory()->create();
    $this->cabangB = Branch::factory()->create();
    $this->lapangan = Field::factory()->forBranch($this->cabangA)->create();

    PricingRule::factory()->forField($this->lapangan)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '23:00:00')
        ->price(150_000)
        ->create();

    $this->customer = Customer::factory()->create();
    $this->action = app(CreateBookingAction::class);
});

afterEach(fn () => Carbon::setTestNow());

function dataAddon(array $ubah = []): array
{
    return array_merge([
        'branch_id' => test()->cabangA->id,
        'name' => 'Rompi (10 pcs)',
        'price' => 25000,
        'stock' => null,
        'is_active' => true,
    ], $ubah);
}

test('kasir ditolak mengakses master add-on', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();

    $this->actingAs($kasir)->get(route('addons.index'))->assertForbidden();
});

test('admin bisa membuka master add-on', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    $this->actingAs($admin)->get(route('addons.index'))->assertOk();
});

test('admin hanya melihat add-on cabangnya', function () {
    $admin = User::factory()->admin($this->cabangA)->create();
    $punyaA = Addon::factory()->forBranch($this->cabangA)->create();
    Addon::factory()->forBranch($this->cabangB)->create();

    $this->actingAs($admin)
        ->get(route('addons.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('addons.data', 1)
            ->where('addons.data.0.id', $punyaA->id)
        );
});

test('admin bisa membuat add-on di cabangnya', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    $this->actingAs($admin)
        ->post(route('addons.store'), dataAddon())
        ->assertRedirect(route('addons.index'));

    expect(Addon::first()->branch_id)->toBe($this->cabangA->id);
});

/**
 * Menembus lewat payload.
 */
test('admin tidak bisa membuat add-on di cabang lain', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    $this->actingAs($admin)->post(route('addons.store'), dataAddon([
        'branch_id' => $this->cabangB->id,
    ]));

    // branch_id dipaksa ke cabang admin, bukan cabang yang dikirim.
    expect(Addon::first()->branch_id)->toBe($this->cabangA->id);
});

test('admin ditolak mengedit add-on cabang lain', function () {
    $admin = User::factory()->admin($this->cabangA)->create();
    $addonB = Addon::factory()->forBranch($this->cabangB)->price(25000)->create();

    $this->actingAs($admin)
        ->put(route('addons.update', $addonB), dataAddon(['price' => 1]))
        ->assertForbidden();

    expect($addonB->fresh()->price)->toBe(25000);
});

test('add-on hanya di-soft-delete', function () {
    $admin = User::factory()->admin($this->cabangA)->create();
    $addon = Addon::factory()->forBranch($this->cabangA)->create();

    $this->actingAs($admin)
        ->delete(route('addons.destroy', $addon))
        ->assertRedirect(route('addons.index'));

    expect(Addon::find($addon->id))->toBeNull()
        ->and(Addon::withTrashed()->find($addon->id))->not->toBeNull();
});

/**
 * Integrasi dengan booking.
 */
function bookingDenganAddon(array $addons): Booking
{
    return test()->action->execute(new CreateBookingData(
        fieldId: test()->lapangan->id,
        customerId: test()->customer->id,
        date: test()->tanggal,
        startTime: '19:00',
        durationHours: 1,
        source: BookingSource::Walkin,
        addons: $addons,
    ));
}

test('add-on menambah total booking', function () {
    $rompi = Addon::factory()->forBranch($this->cabangA)->price(25_000)->create();

    $booking = bookingDenganAddon([$rompi->id => 2]);

    expect($booking->subtotal_field)->toBe(150_000)
        ->and($booking->subtotal_addons)->toBe(50_000)
        ->and($booking->total)->toBe(200_000);
});

test('beberapa add-on dijumlahkan', function () {
    $rompi = Addon::factory()->forBranch($this->cabangA)->price(25_000)->create();
    $bola = Addon::factory()->forBranch($this->cabangA)->price(50_000)->create();

    $booking = bookingDenganAddon([$rompi->id => 2, $bola->id => 1]);

    expect($booking->subtotal_addons)->toBe(100_000)
        ->and($booking->total)->toBe(250_000)
        ->and($booking->addons)->toHaveCount(2);
});

/**
 * Snapshot: mengubah master add-on tidak mengubah tagihan booking lama.
 */
test('nama dan harga add-on di-snapshot ke booking', function () {
    $rompi = Addon::factory()->forBranch($this->cabangA)
        ->price(25_000)
        ->create(['name' => 'Rompi Lama']);

    $booking = bookingDenganAddon([$rompi->id => 1]);

    $rompi->update(['name' => 'Rompi Baru', 'price' => 999_000]);

    $baris = $booking->addons()->first();

    expect($baris->addon_name)->toBe('Rompi Lama')
        ->and($baris->addon_price)->toBe(25_000)
        ->and($baris->subtotal)->toBe(25_000)
        ->and($booking->fresh()->total)->toBe(175_000);
});

/**
 * Harga add-on diambil server, tidak dari klien.
 */
test('add-on cabang lain ditolak', function () {
    $addonB = Addon::factory()->forBranch($this->cabangB)->create();

    expect(fn () => bookingDenganAddon([$addonB->id => 1]))
        ->toThrow(AddonUnavailableException::class);

    expect(Booking::count())->toBe(0);
});

test('add-on nonaktif ditolak', function () {
    $nonaktif = Addon::factory()->forBranch($this->cabangA)->inactive()->create();

    expect(fn () => bookingDenganAddon([$nonaktif->id => 1]))
        ->toThrow(AddonUnavailableException::class);
});

test('add-on yang tidak ada ditolak', function () {
    expect(fn () => bookingDenganAddon([99999 => 1]))
        ->toThrow(AddonUnavailableException::class);
});

/**
 * Stok null = tidak dibatasi.
 */
test('stok tidak mencukupi ditolak', function () {
    $terbatas = Addon::factory()->forBranch($this->cabangA)->stock(3)->create(['name' => 'Bola']);

    expect(fn () => bookingDenganAddon([$terbatas->id => 5]))
        ->toThrow(AddonUnavailableException::class, 'Tersisa 3');

    expect(Booking::count())->toBe(0);
});

test('stok pas diterima', function () {
    $terbatas = Addon::factory()->forBranch($this->cabangA)->stock(3)->create();

    $booking = bookingDenganAddon([$terbatas->id => 3]);

    expect($booking->addons)->toHaveCount(1);
});

test('stok null tidak dibatasi', function () {
    $unlimited = Addon::factory()->forBranch($this->cabangA)->stock(null)->create();

    $booking = bookingDenganAddon([$unlimited->id => 999]);

    expect($booking->addons)->toHaveCount(1);
});

test('booking tanpa add-on tetap berjalan', function () {
    $booking = bookingDenganAddon([]);

    expect($booking->subtotal_addons)->toBe(0)
        ->and($booking->total)->toBe(150_000)
        ->and($booking->addons)->toHaveCount(0);
});

/**
 * DP dihitung dari total termasuk add-on, bukan hanya sewa lapangan.
 */
test('dp dihitung dari total termasuk add-on', function () {
    $rompi = Addon::factory()->forBranch($this->cabangA)->price(50_000)->create();

    $booking = bookingDenganAddon([$rompi->id => 1]);

    // Total 200rb, dp_percentage default 50% = 100rb.
    expect($booking->total)->toBe(200_000)
        ->and($booking->dp_amount)->toBe(100_000);
});

test('qty nol diabaikan', function () {
    $rompi = Addon::factory()->forBranch($this->cabangA)->price(25_000)->create();

    $booking = bookingDenganAddon([$rompi->id => 0]);

    expect($booking->subtotal_addons)->toBe(0)
        ->and($booking->addons)->toHaveCount(0);
});
