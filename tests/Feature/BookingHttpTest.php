<?php

use App\Enums\BookingStatus;
use App\Enums\DayType;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Field;
use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14 08:00:00'));
    // 15 Juli 2026 = Rabu, sehari setelah waktu uji.
    $this->tanggal = '2026-07-15';

    $this->cabangA = Branch::factory()->create();
    $this->cabangB = Branch::factory()->create();

    $this->lapanganA = Field::factory()->forBranch($this->cabangA)->create();
    $this->lapanganB = Field::factory()->forBranch($this->cabangB)->create();

    foreach ([$this->lapanganA, $this->lapanganB] as $f) {
        PricingRule::factory()->forField($f)
            ->dayType(DayType::Weekday)
            ->between('08:00:00', '23:00:00')
            ->price(150_000)
            ->create();
    }

    $this->customer = Customer::factory()->create();
});

afterEach(fn () => Carbon::setTestNow());

function dataBookingHttp(array $ubah = []): array
{
    return array_merge([
        'field_id' => test()->lapanganA->id,
        'customer_id' => test()->customer->id,
        'booking_date' => test()->tanggal,
        'start_time' => '19:00',
        'duration_hours' => 1,
        'pay_full' => false,
    ], $ubah);
}

test('tamu diarahkan ke halaman login', function () {
    $this->get(route('bookings.grid'))->assertRedirect(route('login'));
    $this->get(route('bookings.index'))->assertRedirect(route('login'));
});

test('kasir bisa membuka grid', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();

    $this->actingAs($kasir)->get(route('bookings.grid'))->assertOk();
});

test('kasir ditolak membuka grid cabang lain', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();

    $this->actingAs($kasir)
        ->get(route('bookings.grid', ['branch_id' => $this->cabangB->id]))
        ->assertForbidden();
});

test('owner bisa membuka grid cabang mana pun', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->get(route('bookings.grid', ['branch_id' => $this->cabangB->id]))
        ->assertOk();
});

test('kasir hanya melihat booking cabangnya di daftar', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();

    $punyaA = Booking::factory()->forField($this->lapanganA)->create();
    Booking::factory()->forField($this->lapanganB)->create();

    $this->actingAs($kasir)
        ->get(route('bookings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.id', $punyaA->id)
        );
});

test('kasir ditolak melihat detail booking cabang lain', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();
    $bookingB = Booking::factory()->forField($this->lapanganB)->create();

    $this->actingAs($kasir)
        ->get(route('bookings.show', $bookingB))
        ->assertForbidden();
});

test('kasir bisa membuat booking di cabangnya', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();

    $this->actingAs($kasir)
        ->post(route('bookings.store'), dataBookingHttp())
        ->assertRedirect();

    expect(Booking::count())->toBe(1)
        ->and(Booking::first()->created_by)->toBe($kasir->id);
});

/**
 * Menembus lewat payload: kasir mengirim field_id cabang lain.
 */
test('kasir tidak bisa membuat booking di lapangan cabang lain', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();

    $this->actingAs($kasir)
        ->post(route('bookings.store'), dataBookingHttp([
            'field_id' => $this->lapanganB->id,
        ]))
        ->assertSessionHasErrors('field_id');

    expect(Booking::count())->toBe(0);
});

test('booking tanggal lampau ditolak', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();

    $this->actingAs($kasir)
        ->post(route('bookings.store'), dataBookingHttp([
            'booking_date' => '2026-07-01',
        ]))
        ->assertSessionHasErrors('booking_date');
});

/**
 * Harga tidak boleh datang dari klien — selalu dihitung server.
 */
test('harga yang dikirim klien diabaikan', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();

    $this->actingAs($kasir)->post(route('bookings.store'), dataBookingHttp([
        'price_per_hour' => 1,
        'total' => 1,
    ]))->assertRedirect();

    $booking = Booking::first();

    expect($booking->price_per_hour)->toBe(150_000)
        ->and($booking->total)->toBe(150_000);
});

test('booking bentrok ditolak dengan pesan', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();

    $this->actingAs($kasir)->post(route('bookings.store'), dataBookingHttp());

    $this->actingAs($kasir)
        ->post(route('bookings.store'), dataBookingHttp())
        ->assertSessionHasErrors('start_time');

    expect(Booking::count())->toBe(1);
});

test('booking di lapangan tanpa harga ditolak', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();
    $lapanganTanpaHarga = Field::factory()->forBranch($this->cabangA)->create();

    $this->actingAs($kasir)
        ->post(route('bookings.store'), dataBookingHttp([
            'field_id' => $lapanganTanpaHarga->id,
        ]))
        ->assertSessionHasErrors('start_time');

    expect(Booking::count())->toBe(0);
});

/**
 * US-06: check-in butuh tagihan lunas.
 */
test('check-in ditolak bila masih ada sisa tagihan', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();
    $booking = Booking::factory()->forField($this->lapanganA)
        ->status(BookingStatus::ConfirmedDp)
        ->create(['total' => 150_000, 'paid_amount' => 75_000]);

    $this->actingAs($kasir)
        ->post(route('bookings.check-in', $booking))
        ->assertSessionHasErrors('check_in');

    expect($booking->fresh()->checked_in_at)->toBeNull();
});

test('check-in berhasil bila sudah lunas', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();
    $booking = Booking::factory()->forField($this->lapanganA)
        ->status(BookingStatus::Paid)
        ->create(['total' => 150_000, 'paid_amount' => 150_000]);

    $this->actingAs($kasir)->post(route('bookings.check-in', $booking));

    expect($booking->fresh()->checked_in_at)->not->toBeNull();
});

test('check-in ditolak bila booking belum dibayar sama sekali', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();
    $booking = Booking::factory()->forField($this->lapanganA)
        ->status(BookingStatus::Pending)
        ->create(['total' => 0, 'paid_amount' => 0]);

    $this->actingAs($kasir)
        ->post(route('bookings.check-in', $booking))
        ->assertSessionHasErrors('check_in');
});

test('kasir ditolak check-in booking cabang lain', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();
    $bookingB = Booking::factory()->forField($this->lapanganB)
        ->status(BookingStatus::Paid)
        ->create(['total' => 150_000, 'paid_amount' => 150_000]);

    $this->actingAs($kasir)
        ->post(route('bookings.check-in', $bookingB))
        ->assertForbidden();
});

/**
 * Pembatalan berdampak ke uang, jadi bukan wewenang kasir.
 */
test('kasir tidak boleh membatalkan booking', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();
    $booking = Booking::factory()->forField($this->lapanganA)->create();

    $this->actingAs($kasir)
        ->get(route('bookings.show', $booking))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('canCancel', false));
});

test('admin boleh membatalkan booking', function () {
    $admin = User::factory()->admin($this->cabangA)->create();
    $booking = Booking::factory()->forField($this->lapanganA)->create();

    $this->actingAs($admin)
        ->get(route('bookings.show', $booking))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('canCancel', true));
});
