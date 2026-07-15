<?php

use App\Enums\DayType;
use App\Exceptions\PriceNotConfiguredException;
use App\Models\Field;
use App\Models\PricingRule;
use App\Services\PricingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Prioritas test nomor 1 — lihat docs/05-tech-conventions.md bagian Testing.
 *
 * Semua harga booking berasal dari service ini; kasir tidak pernah mengetik
 * harga. Salah di sini berarti salah tagih ke pelanggan.
 */
beforeEach(function () {
    $this->pricing = app(PricingService::class);
    // 15 Juli 2026 = Rabu; 18 Juli 2026 = Sabtu.
    $this->rabu = Carbon::parse('2026-07-15');
    $this->sabtu = Carbon::parse('2026-07-18');
});

test('harga weekday terbaca', function () {
    $field = Field::factory()->create();
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->price(150_000)
        ->create();

    expect($this->pricing->resolve($field, $this->rabu, '09:00'))->toBe(150_000);
});

test('harga weekend terbaca', function () {
    $field = Field::factory()->create();
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekend)
        ->between('08:00:00', '23:00:00')
        ->price(250_000)
        ->create();

    expect($this->pricing->resolve($field, $this->sabtu, '09:00'))->toBe(250_000);
});

test('rule weekday tidak berlaku di akhir pekan', function () {
    $field = Field::factory()->create();
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '23:00:00')
        ->price(150_000)
        ->create();

    expect(fn () => $this->pricing->resolve($field, $this->sabtu, '09:00'))
        ->toThrow(PriceNotConfiguredException::class);
});

/**
 * Aturan inti: hari spesifik MENANG atas weekday/weekend.
 */
test('hari spesifik menang atas weekday', function () {
    $field = Field::factory()->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '23:00:00')
        ->price(150_000)
        ->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Wednesday)
        ->between('08:00:00', '23:00:00')
        ->price(99_000)
        ->create();

    // Rabu — rule Wednesday harus menang walau rule weekday juga cocok.
    expect($this->pricing->resolve($field, $this->rabu, '09:00'))->toBe(99_000);
});

test('hari spesifik menang atas weekend', function () {
    $field = Field::factory()->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekend)
        ->between('08:00:00', '23:00:00')
        ->price(250_000)
        ->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Saturday)
        ->between('08:00:00', '23:00:00')
        ->price(300_000)
        ->create();

    expect($this->pricing->resolve($field, $this->sabtu, '09:00'))->toBe(300_000);
});

test('hari spesifik hari lain tidak mempengaruhi', function () {
    $field = Field::factory()->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '23:00:00')
        ->price(150_000)
        ->create();

    // Rule hari Senin tidak boleh terpakai di hari Rabu.
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Monday)
        ->between('08:00:00', '23:00:00')
        ->price(50_000)
        ->create();

    expect($this->pricing->resolve($field, $this->rabu, '09:00'))->toBe(150_000);
});

/**
 * Prime time: rentang jam menentukan harga.
 */
test('rentang jam berbeda memberi harga berbeda', function () {
    $field = Field::factory()->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->price(150_000)
        ->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('17:00:00', '23:00:00')
        ->price(250_000)
        ->create();

    expect($this->pricing->resolve($field, $this->rabu, '09:00'))->toBe(150_000)
        ->and($this->pricing->resolve($field, $this->rabu, '19:00'))->toBe(250_000);
});

/**
 * start_time inklusif, end_time eksklusif.
 *
 * Jam tepat di batas awal pernah gagal karena kolom TIME MySQL mengembalikan
 * "08:00:00" sementara input berbentuk "08:00" — perbandingan string mentah
 * membuat "08:00:00" <= "08:00" bernilai false.
 */
test('jam tepat di batas awal termasuk rule', function () {
    $field = Field::factory()->create();
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->price(150_000)
        ->create();

    expect($this->pricing->resolve($field, $this->rabu, '08:00'))->toBe(150_000);
});

test('jam tepat di batas akhir masuk rule berikutnya', function () {
    $field = Field::factory()->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->price(150_000)
        ->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('17:00:00', '23:00:00')
        ->price(250_000)
        ->create();

    // 17:00 eksklusif di rule pertama, inklusif di rule kedua.
    expect($this->pricing->resolve($field, $this->rabu, '17:00'))->toBe(250_000);
});

/**
 * Harga member: fallback ke harga umum bila member_price tidak diatur.
 */
test('member memakai member_price bila diatur', function () {
    $field = Field::factory()->create();
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '23:00:00')
        ->price(150_000, 120_000)
        ->create();

    expect($this->pricing->resolve($field, $this->rabu, '09:00', isMember: true))->toBe(120_000)
        ->and($this->pricing->resolve($field, $this->rabu, '09:00', isMember: false))->toBe(150_000);
});

test('member jatuh ke harga umum bila member_price kosong', function () {
    $field = Field::factory()->create();
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '23:00:00')
        ->price(150_000, null)
        ->create();

    expect($this->pricing->resolve($field, $this->rabu, '09:00', isMember: true))->toBe(150_000);
});

test('non-member tidak pernah dapat member_price', function () {
    $field = Field::factory()->create();
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '23:00:00')
        ->price(150_000, 1_000)
        ->create();

    expect($this->pricing->resolve($field, $this->rabu, '09:00', isMember: false))->toBe(150_000);
});

/**
 * Gap = exception, bukan harga 0. Slot tanpa harga tidak boleh dibooking.
 */
test('jam di luar rule melempar exception', function () {
    $field = Field::factory()->create();
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->price(150_000)
        ->create();

    expect(fn () => $this->pricing->resolve($field, $this->rabu, '19:00'))
        ->toThrow(PriceNotConfiguredException::class);
});

test('lapangan tanpa rule sama sekali melempar exception', function () {
    $field = Field::factory()->create();

    expect(fn () => $this->pricing->resolve($field, $this->rabu, '09:00'))
        ->toThrow(PriceNotConfiguredException::class);
});

test('exception menyebut nama lapangan', function () {
    $field = Field::factory()->create(['name' => 'Lapangan Z']);

    expect(fn () => $this->pricing->resolve($field, $this->rabu, '19:00'))
        ->toThrow(PriceNotConfiguredException::class, 'Lapangan Z');
});

test('resolveOrNull mengembalikan null alih-alih exception', function () {
    $field = Field::factory()->create();

    expect($this->pricing->resolveOrNull($field, $this->rabu, '09:00'))->toBeNull();
});

test('rule lapangan lain tidak terpakai', function () {
    $lapanganA = Field::factory()->create();
    $lapanganB = Field::factory()->create();

    PricingRule::factory()->forField($lapanganB)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '23:00:00')
        ->price(150_000)
        ->create();

    expect(fn () => $this->pricing->resolve($lapanganA, $this->rabu, '09:00'))
        ->toThrow(PriceNotConfiguredException::class);
});

test('rule yang dihapus tidak terpakai', function () {
    $field = Field::factory()->create();
    $rule = PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '23:00:00')
        ->price(150_000)
        ->create();

    $rule->delete();

    expect(fn () => $this->pricing->resolve($field->fresh(), $this->rabu, '09:00'))
        ->toThrow(PriceNotConfiguredException::class);
});

/**
 * Grid wajib memakai resolveMany — dilarang query per slot
 * (docs/05-tech-conventions.md bagian Performa).
 */
test('resolveMany memberi harga tiap jam tanpa query berulang', function () {
    $field = Field::factory()->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->price(150_000)
        ->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('17:00:00', '23:00:00')
        ->price(250_000)
        ->create();

    $field->load('pricingRules');

    DB::enableQueryLog();
    $harga = $this->pricing->resolveMany($field, $this->rabu, ['08:00', '12:00', '19:00', '23:00']);
    $jumlahQuery = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($harga)->toBe([
        '08:00' => 150_000,
        '12:00' => 150_000,
        '19:00' => 250_000,
        '23:00' => null,
    ])->and($jumlahQuery)->toBe(0);
});
