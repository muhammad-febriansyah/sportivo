<?php

use App\Enums\BookingStatus;
use App\Enums\DayType;
use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Field;
use App\Models\PricingRule;
use App\Services\AvailabilityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Grid ketersediaan — docs/01-prd.md Modul 5 dan docs/03-user-stories.md US-01.
 */
beforeEach(function () {
    $this->availability = app(AvailabilityService::class);
    // 15 Juli 2026 = Rabu. Waktu dibekukan agar "slot lewat waktu" pasti.
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));
    $this->tanggal = Carbon::parse('2026-07-15');

    $this->cabang = Branch::factory()->create([
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '12:00'],
            'weekend' => ['open' => '08:00', 'close' => '12:00'],
        ],
    ]);
    $this->lapangan = Field::factory()->forBranch($this->cabang)->create(['name' => 'Lapangan A']);

    PricingRule::factory()->forField($this->lapangan)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '12:00:00')
        ->price(150_000)
        ->create();
});

afterEach(function () {
    Carbon::setTestNow();
});

test('grid memakai jam operasional cabang', function () {
    $grid = $this->availability->grid($this->cabang, $this->tanggal);

    expect($grid['hours'])->toBe(['08:00', '09:00', '10:00', '11:00']);
});

test('jam operasional weekend bisa berbeda', function () {
    $this->cabang->update(['operating_hours' => [
        'weekday' => ['open' => '08:00', 'close' => '12:00'],
        'weekend' => ['open' => '07:00', 'close' => '10:00'],
    ]]);

    // 18 Juli 2026 = Sabtu.
    $grid = $this->availability->grid($this->cabang->fresh(), Carbon::parse('2026-07-18'));

    expect($grid['hours'])->toBe(['07:00', '08:00', '09:00']);
});

test('slot tersedia menampilkan harga', function () {
    $grid = $this->availability->grid($this->cabang, $this->tanggal);

    expect($grid['slots'][$this->lapangan->id]['11:00'])
        ->toMatchArray(['state' => 'available', 'price' => 150_000]);
});

/**
 * Slot yang sudah lewat tidak bisa dibooking (docs/01-prd.md Modul 5).
 */
test('slot yang sudah lewat ditandai past', function () {
    // now = 10:00, jadi 08:00 dan 09:00 sudah lewat.
    $grid = $this->availability->grid($this->cabang, $this->tanggal);

    expect($grid['slots'][$this->lapangan->id]['08:00']['state'])->toBe('past')
        ->and($grid['slots'][$this->lapangan->id]['09:00']['state'])->toBe('past')
        ->and($grid['slots'][$this->lapangan->id]['11:00']['state'])->toBe('available');
});

test('slot tanpa harga ditandai no_price', function () {
    $lapanganTanpaHarga = Field::factory()->forBranch($this->cabang)->create();

    $grid = $this->availability->grid($this->cabang, $this->tanggal);

    expect($grid['slots'][$lapanganTanpaHarga->id]['11:00'])
        ->toMatchArray(['state' => 'no_price', 'price' => null]);
});

test('slot terbooking DP ditandai dp', function () {
    Booking::factory()->forField($this->lapangan)
        ->on($this->tanggal, '11:00')
        ->status(BookingStatus::ConfirmedDp)
        ->create(['customer_name' => 'Budi']);

    $grid = $this->availability->grid($this->cabang, $this->tanggal);

    expect($grid['slots'][$this->lapangan->id]['11:00']['state'])->toBe('dp')
        ->and($grid['slots'][$this->lapangan->id]['11:00']['customer_name'])->toBe('Budi');
});

test('slot lunas ditandai paid', function () {
    Booking::factory()->forField($this->lapangan)
        ->on($this->tanggal, '11:00')
        ->status(BookingStatus::Paid)
        ->create();

    $grid = $this->availability->grid($this->cabang, $this->tanggal);

    expect($grid['slots'][$this->lapangan->id]['11:00']['state'])->toBe('paid');
});

test('booking multi-jam menutup seluruh jamnya', function () {
    Booking::factory()->forField($this->lapangan)
        ->on($this->tanggal, '10:00', 2)
        ->status(BookingStatus::Paid)
        ->create();

    $grid = $this->availability->grid($this->cabang, $this->tanggal);

    // 10:00 sudah lewat (now = 10:00), tapi 11:00 harus tertutup booking.
    expect($grid['slots'][$this->lapangan->id]['11:00']['state'])->toBe('paid');
});

test('booking dibatalkan tidak menutup slot', function () {
    Booking::factory()->forField($this->lapangan)
        ->on($this->tanggal, '11:00')
        ->status(BookingStatus::Cancelled)
        ->create();

    $grid = $this->availability->grid($this->cabang, $this->tanggal);

    expect($grid['slots'][$this->lapangan->id]['11:00']['state'])->toBe('available');
});

test('booking no_show tidak menutup slot', function () {
    Booking::factory()->forField($this->lapangan)
        ->on($this->tanggal, '11:00')
        ->status(BookingStatus::NoShow)
        ->create();

    $grid = $this->availability->grid($this->cabang, $this->tanggal);

    expect($grid['slots'][$this->lapangan->id]['11:00']['state'])->toBe('available');
});

test('slot yang diblokir ditandai blocked', function () {
    BlockedSlot::factory()->forField($this->lapangan)
        ->on($this->tanggal, '11:00:00', '12:00:00')
        ->create(['reason' => 'Maintenance rumput']);

    $grid = $this->availability->grid($this->cabang, $this->tanggal);

    expect($grid['slots'][$this->lapangan->id]['11:00'])
        ->toMatchArray(['state' => 'blocked', 'reason' => 'Maintenance rumput']);
});

/**
 * Blokir se-cabang (field_id null) menutup SEMUA lapangan.
 */
test('blokir se-cabang menutup semua lapangan', function () {
    $lapanganKedua = Field::factory()->forBranch($this->cabang)->create();
    PricingRule::factory()->forField($lapanganKedua)
        ->dayType(DayType::Weekday)->between('08:00:00', '12:00:00')->price(150_000)->create();

    BlockedSlot::factory()->wholeBranch($this->cabang)
        ->on($this->tanggal, '11:00:00', '12:00:00')
        ->create();

    $grid = $this->availability->grid($this->cabang, $this->tanggal);

    expect($grid['slots'][$this->lapangan->id]['11:00']['state'])->toBe('blocked')
        ->and($grid['slots'][$lapanganKedua->id]['11:00']['state'])->toBe('blocked');
});

test('blokir lapangan lain tidak menutup lapangan ini', function () {
    $lapanganLain = Field::factory()->forBranch($this->cabang)->create();

    BlockedSlot::factory()->forField($lapanganLain)
        ->on($this->tanggal, '11:00:00', '12:00:00')
        ->create();

    $grid = $this->availability->grid($this->cabang, $this->tanggal);

    expect($grid['slots'][$this->lapangan->id]['11:00']['state'])->toBe('available');
});

/**
 * US-01: halaman publik tidak boleh membocorkan identitas penyewa.
 */
test('grid publik menyembunyikan identitas penyewa', function () {
    Booking::factory()->forField($this->lapangan)
        ->on($this->tanggal, '11:00')
        ->status(BookingStatus::Paid)
        ->create(['customer_name' => 'Budi']);

    $grid = $this->availability->grid($this->cabang, $this->tanggal, publicOnly: true);

    expect($grid['slots'][$this->lapangan->id]['11:00']['state'])->toBe('paid')
        ->and($grid['slots'][$this->lapangan->id]['11:00']['customer_name'])->toBeNull()
        ->and($grid['slots'][$this->lapangan->id]['11:00']['booking_code'])->toBeNull();
});

test('grid internal menampilkan identitas penyewa', function () {
    Booking::factory()->forField($this->lapangan)
        ->on($this->tanggal, '11:00')
        ->status(BookingStatus::Paid)
        ->create(['customer_name' => 'Budi']);

    $grid = $this->availability->grid($this->cabang, $this->tanggal, publicOnly: false);

    expect($grid['slots'][$this->lapangan->id]['11:00']['customer_name'])->toBe('Budi');
});

test('alasan blokir disembunyikan di grid publik', function () {
    BlockedSlot::factory()->forField($this->lapangan)
        ->on($this->tanggal, '11:00:00', '12:00:00')
        ->create(['reason' => 'Event privat PT ABC']);

    $grid = $this->availability->grid($this->cabang, $this->tanggal, publicOnly: true);

    expect($grid['slots'][$this->lapangan->id]['11:00']['reason'])->toBeNull();
});

/**
 * US-08: lapangan maintenance hilang dari grid publik, tetap ada di internal.
 */
test('lapangan maintenance disembunyikan dari grid publik', function () {
    Field::factory()->forBranch($this->cabang)->maintenance()->create();

    $publik = $this->availability->grid($this->cabang, $this->tanggal, publicOnly: true);
    $internal = $this->availability->grid($this->cabang, $this->tanggal, publicOnly: false);

    expect($publik['fields'])->toHaveCount(1)
        ->and($internal['fields'])->toHaveCount(2);
});

/**
 * WAJIB: maksimal 3–4 query, dilarang query per slot
 * (docs/05-tech-conventions.md bagian Performa).
 */
test('grid memakai maksimal 4 query berapa pun jumlah slotnya', function () {
    // 5 lapangan × 4 jam = 20 slot. Bila query per slot, jumlahnya meledak.
    $lapangan = Field::factory()->count(4)->forBranch($this->cabang)->create();

    foreach ($lapangan as $f) {
        PricingRule::factory()->forField($f)
            ->dayType(DayType::Weekday)->between('08:00:00', '12:00:00')->price(150_000)->create();
    }

    Booking::factory()->forField($this->lapangan)
        ->on($this->tanggal, '11:00')->status(BookingStatus::Paid)->create();
    BlockedSlot::factory()->wholeBranch($this->cabang)
        ->on($this->tanggal, '09:00:00', '10:00:00')->create();

    DB::enableQueryLog();
    $grid = $this->availability->grid($this->cabang, $this->tanggal);
    $jumlahQuery = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($grid['fields'])->toHaveCount(5)
        ->and($jumlahQuery)->toBeLessThanOrEqual(4);
});
