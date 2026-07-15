<?php

use App\Actions\CreateBookingAction;
use App\Actions\CreateBookingData;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\DayType;
use App\Exceptions\PriceNotConfiguredException;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Field;
use App\Models\PricingRule;
use Illuminate\Support\Carbon;

/**
 * Prioritas test nomor 2 — lihat docs/05-tech-conventions.md bagian Testing.
 * Anti double booking adalah US-16 di docs/03-user-stories.md.
 */
beforeEach(function () {
    $this->action = app(CreateBookingAction::class);
    // 15 Juli 2026 = Rabu.
    $this->tanggal = Carbon::parse('2026-07-15');

    $this->cabang = Branch::factory()->create();
    $this->lapangan = Field::factory()->forBranch($this->cabang)->create(['name' => 'Lapangan A']);
    $this->customer = Customer::factory()->create(['name' => 'Budi']);

    PricingRule::factory()->forField($this->lapangan)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '23:00:00')
        ->price(150_000, 120_000)
        ->create();
});

function dataBooking(array $ubah = []): CreateBookingData
{
    return new CreateBookingData(
        fieldId: $ubah['fieldId'] ?? test()->lapangan->id,
        customerId: $ubah['customerId'] ?? test()->customer->id,
        date: $ubah['date'] ?? test()->tanggal,
        startTime: $ubah['startTime'] ?? '19:00',
        durationHours: $ubah['durationHours'] ?? 1,
        source: $ubah['source'] ?? BookingSource::Walkin,
        createdBy: $ubah['createdBy'] ?? null,
        dpAmount: $ubah['dpAmount'] ?? null,
        payFull: $ubah['payFull'] ?? false,
    );
}

test('booking berhasil dibuat dengan harga dari mesin harga', function () {
    $booking = $this->action->execute(dataBooking());

    expect($booking->price_per_hour)->toBe(150_000)
        ->and($booking->subtotal_field)->toBe(150_000)
        ->and($booking->total)->toBe(150_000)
        ->and($booking->status)->toBe(BookingStatus::Pending);
});

test('durasi berjam-jam dihitung benar', function () {
    $booking = $this->action->execute(dataBooking(['durationHours' => 2]));

    expect($booking->start_time)->toBe('19:00')
        ->and($booking->end_time)->toBe('21:00')
        ->and($booking->duration_hours)->toBe(2)
        ->and($booking->subtotal_field)->toBe(300_000)
        ->and($booking->total)->toBe(300_000);
});

test('kolom snapshot terisi saat booking dibuat', function () {
    $booking = $this->action->execute(dataBooking());

    expect($booking->branch_name)->toBe($this->cabang->name)
        ->and($booking->field_name)->toBe('Lapangan A')
        ->and($booking->customer_name)->toBe('Budi')
        ->and($booking->customer_phone)->toBe($this->customer->phone);
});

/**
 * Snapshot: mengubah master data TIDAK boleh mengubah booking lama.
 */
test('booking lama tidak berubah saat master data diedit', function () {
    $booking = $this->action->execute(dataBooking());

    $this->lapangan->update(['name' => 'Lapangan Berubah']);
    $this->customer->update(['name' => 'Nama Berubah']);
    PricingRule::where('field_id', $this->lapangan->id)->update(['price' => 999_000]);

    $booking->refresh();

    expect($booking->field_name)->toBe('Lapangan A')
        ->and($booking->customer_name)->toBe('Budi')
        ->and($booking->price_per_hour)->toBe(150_000);
});

test('kode booking mengikuti format SPV-YYMMDD-XXXX', function () {
    $booking = $this->action->execute(dataBooking());

    expect($booking->code)->toMatch('/^SPV-260715-[A-Z0-9]{4}$/');
});

test('member mendapat harga member', function () {
    $member = Customer::factory()->member()->create();

    $booking = $this->action->execute(dataBooking(['customerId' => $member->id]));

    expect($booking->price_per_hour)->toBe(120_000)
        ->and($booking->is_member_price)->toBeTrue();
});

/**
 * Membership kedaluwarsa otomatis kembali ke harga umum.
 */
test('member kedaluwarsa memakai harga umum', function () {
    $member = Customer::factory()->expiredMember()->create();

    $booking = $this->action->execute(dataBooking(['customerId' => $member->id]));

    expect($booking->price_per_hour)->toBe(150_000)
        ->and($booking->is_member_price)->toBeFalse();
});

test('dp mengikuti persentase pengaturan cabang', function () {
    // Default dp_percentage = 50.
    $booking = $this->action->execute(dataBooking());

    expect($booking->dp_amount)->toBe(75_000);
});

test('dp mengikuti pengaturan cabang yang diubah', function () {
    $this->cabang->setting->update(['dp_percentage' => 30]);

    $booking = $this->action->execute(dataBooking());

    expect($booking->dp_amount)->toBe(45_000);
});

test('bayar lunas membuat dp sama dengan total', function () {
    $booking = $this->action->execute(dataBooking(['payFull' => true]));

    expect($booking->dp_amount)->toBe($booking->total);
});

test('dp tidak boleh melebihi total', function () {
    $booking = $this->action->execute(dataBooking(['dpAmount' => 999_999_999]));

    expect($booking->dp_amount)->toBe($booking->total);
});

test('booking online punya batas waktu bayar', function () {
    $booking = $this->action->execute(dataBooking(['source' => BookingSource::Online]));

    expect($booking->expired_at)->not->toBeNull()
        ->and($booking->expired_at->isFuture())->toBeTrue()
        ->and(now()->diffInMinutes($booking->expired_at))->toBeLessThanOrEqual(15);
});

test('booking walk-in tidak punya batas waktu bayar', function () {
    $booking = $this->action->execute(dataBooking(['source' => BookingSource::Walkin]));

    expect($booking->expired_at)->toBeNull();
});

test('hold online mengikuti pengaturan cabang', function () {
    $this->cabang->setting->update(['online_hold_minutes' => 30]);

    $booking = $this->action->execute(dataBooking(['source' => BookingSource::Online]));

    expect(now()->diffInMinutes($booking->expired_at))->toBeGreaterThan(20);
});

/**
 * Slot tanpa harga tidak boleh dibooking — gagal terdengar, bukan diam-diam gratis.
 */
test('slot tanpa harga ditolak', function () {
    $lapanganTanpaHarga = Field::factory()->forBranch($this->cabang)->create();

    expect(fn () => $this->action->execute(dataBooking(['fieldId' => $lapanganTanpaHarga->id])))
        ->toThrow(PriceNotConfiguredException::class);

    expect(Booking::count())->toBe(0);
});

/**
 * US-16: booking bentrok harus ditolak.
 */
test('booking pada slot yang sama ditolak', function () {
    $this->action->execute(dataBooking());

    expect(fn () => $this->action->execute(dataBooking()))
        ->toThrow(SlotUnavailableException::class);

    expect(Booking::count())->toBe(1);
});

test('booking yang tumpang tindih sebagian ditolak', function () {
    // 19:00–21:00 sudah ada.
    $this->action->execute(dataBooking(['durationHours' => 2]));

    // 20:00–21:00 menabrak bagian akhir.
    expect(fn () => $this->action->execute(dataBooking(['startTime' => '20:00'])))
        ->toThrow(SlotUnavailableException::class);

    expect(Booking::count())->toBe(1);
});

test('booking bersebelahan tanpa tumpang tindih diterima', function () {
    // 19:00–20:00.
    $this->action->execute(dataBooking());

    // 20:00–21:00 — batas akhir eksklusif, jadi tidak bentrok.
    $kedua = $this->action->execute(dataBooking(['startTime' => '20:00']));

    expect($kedua)->not->toBeNull()
        ->and(Booking::count())->toBe(2);
});

test('lapangan berbeda pada jam sama diterima', function () {
    $lapanganLain = Field::factory()->forBranch($this->cabang)->create();
    PricingRule::factory()->forField($lapanganLain)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '23:00:00')
        ->price(150_000)
        ->create();

    $this->action->execute(dataBooking());
    $this->action->execute(dataBooking(['fieldId' => $lapanganLain->id]));

    expect(Booking::count())->toBe(2);
});

test('tanggal berbeda pada jam sama diterima', function () {
    $this->action->execute(dataBooking());
    // 16 Juli 2026 = Kamis, masih weekday.
    $this->action->execute(dataBooking(['date' => Carbon::parse('2026-07-16')]));

    expect(Booking::count())->toBe(2);
});

/**
 * Booking cancelled/no_show TIDAK menahan slot.
 */
test('booking yang dibatalkan tidak menghalangi slot', function () {
    $booking = $this->action->execute(dataBooking());
    $booking->update(['status' => BookingStatus::Cancelled]);

    $baru = $this->action->execute(dataBooking());

    expect($baru)->not->toBeNull()
        ->and(Booking::count())->toBe(2);
});

test('booking no_show tidak menghalangi slot', function () {
    $booking = $this->action->execute(dataBooking());
    $booking->update(['status' => BookingStatus::NoShow]);

    $baru = $this->action->execute(dataBooking());

    expect($baru)->not->toBeNull();
});

test('booking pending tetap menahan slot', function () {
    $this->action->execute(dataBooking());

    expect(fn () => $this->action->execute(dataBooking()))
        ->toThrow(SlotUnavailableException::class);
});

/**
 * Pemeriksaan bentrok harus memakai lock baris.
 *
 * Test race condition sungguhan (dua proses paralel) ada di
 * tests/Concurrency/DoubleBookingTest.php — RefreshDatabase membungkus test
 * dalam transaksi sehingga datanya tidak terlihat proses lain.
 */
test('pemeriksaan bentrok memakai lockForUpdate', function () {
    DB::enableQueryLog();

    try {
        $this->action->execute(dataBooking());
    } finally {
        $log = DB::getQueryLog();
        DB::disableQueryLog();
    }

    $adaLock = collect($log)->contains(
        fn (array $q): bool => str_contains(strtolower($q['query']), 'for update')
            && str_contains(strtolower($q['query']), 'bookings')
    );

    expect($adaLock)->toBeTrue();
});
