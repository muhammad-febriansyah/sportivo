<?php

use App\Actions\CancelBookingAction;
use App\Actions\RescheduleBookingAction;
use App\Enums\BookingStatus;
use App\Enums\DayType;
use App\Exceptions\BookingRuleViolationException;
use App\Exceptions\SlotUnavailableException;
use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Field;
use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Prioritas test nomor 5 — kebijakan cancel/reschedule, batas H-n dihormati,
 * DP hangus/refund benar (docs/05-tech-conventions.md bagian Testing).
 */
beforeEach(function () {
    $this->reschedule = app(RescheduleBookingAction::class);
    $this->cancel = app(CancelBookingAction::class);

    // Sekarang: 10 Juli 2026. Booking: 15 Juli 2026 (Rabu) jam 19:00.
    Carbon::setTestNow(Carbon::parse('2026-07-10 08:00:00'));

    $this->cabang = Branch::factory()->create();
    $this->lapangan = Field::factory()->forBranch($this->cabang)->create(['name' => 'Lapangan A']);

    PricingRule::factory()->forField($this->lapangan)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->price(150_000)
        ->create();

    PricingRule::factory()->forField($this->lapangan)
        ->dayType(DayType::Weekday)
        ->between('17:00:00', '23:00:00')
        ->price(250_000)
        ->create();

    $this->booking = Booking::factory()->forField($this->lapangan)
        ->on('2026-07-15', '19:00')
        ->status(BookingStatus::ConfirmedDp)
        ->create([
            'price_per_hour' => 250_000,
            'subtotal_field' => 250_000,
            'total' => 250_000,
            'dp_amount' => 125_000,
            'paid_amount' => 125_000,
        ]);
});

afterEach(fn () => Carbon::setTestNow());

test('reschedule memindahkan jadwal', function () {
    $baru = $this->reschedule->execute($this->booking, Carbon::parse('2026-07-16'), '19:00');

    expect($baru->booking_date->toDateString())->toBe('2026-07-16')
        ->and(substr($baru->start_time, 0, 5))->toBe('19:00')
        ->and(substr($baru->end_time, 0, 5))->toBe('20:00');
});

/**
 * Histori jadwal lama harus tersimpan (docs/01-prd.md Modul 8).
 */
test('jadwal lama disimpan sebagai histori', function () {
    $baru = $this->reschedule->execute($this->booking, Carbon::parse('2026-07-16'), '10:00');

    expect($baru->rescheduled_from)->toMatchArray([
        'date' => '2026-07-15',
        'start_time' => '19:00',
        'end_time' => '20:00',
        'field_name' => 'Lapangan A',
    ])->and($baru->reschedule_count)->toBe(1);
});

/**
 * Harga dihitung ulang dari mesin harga, bukan dibawa dari booking lama.
 */
test('pindah ke jam lebih murah menurunkan total', function () {
    // 19:00 = 250rb (prime time), 10:00 = 150rb.
    $baru = $this->reschedule->execute($this->booking, Carbon::parse('2026-07-16'), '10:00');

    expect($baru->price_per_hour)->toBe(150_000)
        ->and($baru->total)->toBe(150_000);
});

test('pindah ke jam lebih mahal menaikkan total', function () {
    $murah = Booking::factory()->forField($this->lapangan)
        ->on('2026-07-15', '10:00')
        ->status(BookingStatus::ConfirmedDp)
        ->create(['price_per_hour' => 150_000, 'subtotal_field' => 150_000, 'total' => 150_000]);

    $baru = $this->reschedule->execute($murah, Carbon::parse('2026-07-16'), '19:00');

    expect($baru->price_per_hour)->toBe(250_000)
        ->and($baru->total)->toBe(250_000);
});

test('reschedule bisa berpindah lapangan di cabang yang sama', function () {
    $lapanganLain = Field::factory()->forBranch($this->cabang)->create(['name' => 'Lapangan B']);
    PricingRule::factory()->forField($lapanganLain)
        ->dayType(DayType::Weekday)->between('08:00:00', '23:00:00')->price(100_000)->create();

    $baru = $this->reschedule->execute(
        $this->booking,
        Carbon::parse('2026-07-16'),
        '19:00',
        $lapanganLain->id,
    );

    expect($baru->field_id)->toBe($lapanganLain->id)
        ->and($baru->field_name)->toBe('Lapangan B')
        ->and($baru->price_per_hour)->toBe(100_000);
});

/**
 * Batas H-n: default reschedule_limit_days = 1.
 */
test('reschedule ditolak setelah lewat batas H-1', function () {
    // Booking 15 Juli 19:00, batas H-1 = 14 Juli 19:00. Sekarang 14 Juli 20:00.
    Carbon::setTestNow(Carbon::parse('2026-07-14 20:00:00'));

    expect(fn () => $this->reschedule->execute($this->booking, Carbon::parse('2026-07-16'), '19:00'))
        ->toThrow(BookingRuleViolationException::class, 'H-1');

    expect($this->booking->fresh()->booking_date->toDateString())->toBe('2026-07-15');
});

test('reschedule diterima tepat sebelum batas', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14 18:00:00'));

    $baru = $this->reschedule->execute($this->booking, Carbon::parse('2026-07-16'), '19:00');

    expect($baru->booking_date->toDateString())->toBe('2026-07-16');
});

test('batas reschedule mengikuti pengaturan cabang', function () {
    $this->cabang->setting->update(['reschedule_limit_days' => 3]);
    // Batas jadi 12 Juli 19:00. Sekarang 13 Juli — sudah lewat.
    Carbon::setTestNow(Carbon::parse('2026-07-13 08:00:00'));

    expect(fn () => $this->reschedule->execute($this->booking->fresh(), Carbon::parse('2026-07-16'), '19:00'))
        ->toThrow(BookingRuleViolationException::class, 'H-3');
});

/**
 * Maksimal reschedule per booking, configurable.
 */
test('reschedule ditolak setelah mencapai batas maksimal', function () {
    $this->reschedule->execute($this->booking, Carbon::parse('2026-07-16'), '19:00');

    // Default max_reschedule = 1.
    expect(fn () => $this->reschedule->execute($this->booking->fresh(), Carbon::parse('2026-07-17'), '19:00'))
        ->toThrow(BookingRuleViolationException::class, 'maksimal 1 kali');
});

test('batas maksimal reschedule mengikuti pengaturan cabang', function () {
    $this->cabang->setting->update(['max_reschedule' => 2]);

    $this->reschedule->execute($this->booking, Carbon::parse('2026-07-16'), '19:00');
    $kedua = $this->reschedule->execute($this->booking->fresh(), Carbon::parse('2026-07-17'), '19:00');

    expect($kedua->reschedule_count)->toBe(2);
});

test('booking yang dibatalkan tidak bisa di-reschedule', function () {
    $this->booking->update(['status' => BookingStatus::Cancelled]);

    expect(fn () => $this->reschedule->execute($this->booking->fresh(), Carbon::parse('2026-07-16'), '19:00'))
        ->toThrow(BookingRuleViolationException::class);
});

/**
 * Reschedule memakai pemeriksaan bentrok yang sama dengan booking baru.
 */
test('reschedule ke slot yang sudah terisi ditolak', function () {
    Booking::factory()->forField($this->lapangan)
        ->on('2026-07-16', '19:00')
        ->status(BookingStatus::Paid)
        ->create();

    expect(fn () => $this->reschedule->execute($this->booking, Carbon::parse('2026-07-16'), '19:00'))
        ->toThrow(SlotUnavailableException::class);
});

test('reschedule ke slot yang diblokir ditolak', function () {
    BlockedSlot::factory()->forField($this->lapangan)
        ->on('2026-07-16', '19:00:00', '21:00:00')
        ->create();

    expect(fn () => $this->reschedule->execute($this->booking, Carbon::parse('2026-07-16'), '19:00'))
        ->toThrow(SlotUnavailableException::class);
});

/**
 * Booking tidak boleh dianggap bentrok dengan dirinya sendiri.
 */
test('reschedule ke jam yang sama pada tanggal sama tidak dianggap bentrok', function () {
    $baru = $this->reschedule->execute($this->booking, Carbon::parse('2026-07-15'), '19:00');

    expect($baru->reschedule_count)->toBe(1);
});

test('reschedule memakai lockForUpdate', function () {
    DB::enableQueryLog();

    try {
        $this->reschedule->execute($this->booking, Carbon::parse('2026-07-16'), '19:00');
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

/**
 * Pembatalan: booking tidak dihapus, statusnya berubah.
 */
test('pembatalan mengubah status bukan menghapus baris', function () {
    $hasil = $this->cancel->execute($this->booking, 'Hujan deras');

    expect($hasil['booking']->status)->toBe(BookingStatus::Cancelled)
        ->and($hasil['booking']->cancel_reason)->toBe('Hujan deras')
        ->and($hasil['booking']->cancelled_at)->not->toBeNull()
        ->and(Booking::find($this->booking->id))->not->toBeNull();
});

test('slot lepas setelah booking dibatalkan', function () {
    $this->cancel->execute($this->booking);

    $masihMenahan = Booking::query()
        ->conflictingWith($this->lapangan->id, Carbon::parse('2026-07-15'), '19:00', '20:00')
        ->exists();

    expect($masihMenahan)->toBeFalse();
});

/**
 * DP: batas default cancel_refund_limit_days = 2.
 */
test('pembatalan sebelum batas mengembalikan DP', function () {
    // Batas H-2 = 13 Juli 19:00. Sekarang 10 Juli.
    $hasil = $this->cancel->execute($this->booking);

    expect($hasil['refunds_dp'])->toBeTrue();
});

test('pembatalan setelah batas membuat DP hangus', function () {
    // Sekarang 14 Juli — sudah lewat batas H-2 (13 Juli 19:00).
    Carbon::setTestNow(Carbon::parse('2026-07-14 08:00:00'));

    $hasil = $this->cancel->execute($this->booking);

    expect($hasil['refunds_dp'])->toBeFalse();
});

test('pembatalan tepat pada batas masih mengembalikan DP', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-13 19:00:00'));

    $hasil = $this->cancel->execute($this->booking);

    expect($hasil['refunds_dp'])->toBeTrue();
});

test('batas refund mengikuti pengaturan cabang', function () {
    $this->cabang->setting->update(['cancel_refund_limit_days' => 5]);
    // Batas jadi 10 Juli 19:00. Sekarang 10 Juli 20:00 — sudah lewat.
    Carbon::setTestNow(Carbon::parse('2026-07-10 20:00:00'));

    $hasil = $this->cancel->execute($this->booking->fresh());

    expect($hasil['refunds_dp'])->toBeFalse();
});

test('booking yang sudah dibatalkan tidak bisa dibatalkan lagi', function () {
    $this->cancel->execute($this->booking);

    expect(fn () => $this->cancel->execute($this->booking->fresh()))
        ->toThrow(BookingRuleViolationException::class);
});

/**
 * Endpoint HTTP.
 */
test('kasir ditolak membatalkan booking', function () {
    $kasir = User::factory()->kasir($this->cabang)->create();

    $this->actingAs($kasir)
        ->post(route('bookings.cancel', $this->booking), ['reason' => 'coba'])
        ->assertForbidden();

    expect($this->booking->fresh()->status)->toBe(BookingStatus::ConfirmedDp);
});

test('admin bisa membatalkan booking', function () {
    $admin = User::factory()->admin($this->cabang)->create();

    $this->actingAs($admin)
        ->post(route('bookings.cancel', $this->booking), ['reason' => 'Hujan'])
        ->assertRedirect(route('bookings.show', $this->booking));

    expect($this->booking->fresh()->status)->toBe(BookingStatus::Cancelled);
});

test('kasir bisa reschedule booking cabangnya', function () {
    $kasir = User::factory()->kasir($this->cabang)->create();

    $this->actingAs($kasir)
        ->put(route('bookings.reschedule', $this->booking), [
            'booking_date' => '2026-07-16',
            'start_time' => '19:00',
        ])
        ->assertRedirect();

    expect($this->booking->fresh()->booking_date->toDateString())->toBe('2026-07-16');
});

test('kasir ditolak reschedule booking cabang lain', function () {
    $cabangLain = Branch::factory()->create();
    $kasir = User::factory()->kasir($cabangLain)->create();

    $this->actingAs($kasir)
        ->put(route('bookings.reschedule', $this->booking), [
            'booking_date' => '2026-07-16',
            'start_time' => '19:00',
        ])
        ->assertForbidden();
});

/**
 * Pindah cabang bukan reschedule — harganya, DP-nya, dan kepemilikan datanya
 * berbeda.
 */
test('reschedule ke lapangan cabang lain ditolak', function () {
    $cabangLain = Branch::factory()->create();
    $lapanganLain = Field::factory()->forBranch($cabangLain)->create();
    $admin = User::factory()->admin($this->cabang)->create();

    $this->actingAs($admin)
        ->put(route('bookings.reschedule', $this->booking), [
            'booking_date' => '2026-07-16',
            'start_time' => '19:00',
            'field_id' => $lapanganLain->id,
        ])
        ->assertSessionHasErrors('field_id');
});

test('halaman detail mengirim kebijakan reschedule dan cancel', function () {
    $admin = User::factory()->admin($this->cabang)->create();

    $this->actingAs($admin)
        ->get(route('bookings.show', $this->booking))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('rescheduleRule.allowed', true)
            ->where('cancelRule.refunds_dp', true)
        );
});

/**
 * Batas cancel (H-2) dan batas reschedule (H-1) berbeda, jadi ada jendela
 * waktu di mana DP sudah hangus tapi reschedule masih boleh.
 */
test('DP hangus lebih dulu daripada batas reschedule tertutup', function () {
    // 14 Juli 08:00 — batas refund H-2 (13 Juli 19:00) sudah lewat,
    // batas reschedule H-1 (14 Juli 19:00) belum.
    Carbon::setTestNow(Carbon::parse('2026-07-14 08:00:00'));
    $admin = User::factory()->admin($this->cabang)->create();

    $this->actingAs($admin)
        ->get(route('bookings.show', $this->booking))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('cancelRule.refunds_dp', false)
            ->where('rescheduleRule.allowed', true)
        );
});

test('halaman detail menutup reschedule setelah batas H-1 lewat', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14 20:00:00'));
    $admin = User::factory()->admin($this->cabang)->create();

    $this->actingAs($admin)
        ->get(route('bookings.show', $this->booking))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('cancelRule.refunds_dp', false)
            ->where('rescheduleRule.allowed', false)
        );
});
