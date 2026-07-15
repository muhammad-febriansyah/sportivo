<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Kebijakan reschedule & pembatalan. Semua ambangnya per cabang, diatur di
 * branch_settings — lihat docs/01-prd.md Modul 8 dan Modul 15.
 *
 * Dipakai di dua tempat sekaligus: UI (menyembunyikan tombol) dan Action
 * (menolak permintaan). UI saja tidak cukup — endpoint bisa dipanggil langsung.
 */
class BookingRuleService
{
    /**
     * Booking masih boleh di-reschedule?
     *
     * @return array{allowed: bool, reason: string|null}
     */
    public function canReschedule(Booking $booking, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        if (! $booking->status->holdsSlot()) {
            return ['allowed' => false, 'reason' => 'Booking ini sudah dibatalkan atau ditandai tidak hadir.'];
        }

        $setting = $booking->branch->setting;
        $maks = $setting?->max_reschedule ?? 1;

        if ($booking->reschedule_count >= $maks) {
            return [
                'allowed' => false,
                'reason' => "Booking ini sudah di-reschedule {$booking->reschedule_count} kali (maksimal {$maks} kali).",
            ];
        }

        $batasHari = $setting?->reschedule_limit_days ?? 1;

        if ($now->greaterThan($this->deadlineFor($booking, $batasHari))) {
            return [
                'allowed' => false,
                'reason' => "Reschedule hanya bisa dilakukan paling lambat H-{$batasHari} sebelum jadwal main.",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Booking masih boleh dibatalkan, dan apakah DP dikembalikan?
     *
     * Pembatalan sendiri selalu boleh selama slot masih ditahan; yang berubah
     * hanyalah nasib DP-nya.
     *
     * @return array{allowed: bool, reason: string|null, refunds_dp: bool}
     */
    public function canCancel(Booking $booking, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        if (! $booking->status->holdsSlot()) {
            return [
                'allowed' => false,
                'reason' => 'Booking ini sudah dibatalkan atau ditandai tidak hadir.',
                'refunds_dp' => false,
            ];
        }

        $batasHari = $booking->branch->setting?->cancel_refund_limit_days ?? 2;

        return [
            'allowed' => true,
            'reason' => null,
            // Batas terlewat = DP hangus (docs/01-prd.md Modul 8).
            'refunds_dp' => $now->lessThanOrEqualTo($this->deadlineFor($booking, $batasHari)),
        ];
    }

    /**
     * Batas waktu H-n: n hari sebelum tanggal main, pada jam mulai booking.
     *
     * Memakai jam mulai (bukan tengah malam) agar batasnya konsisten dengan
     * kapan lapangan benar-benar dipakai.
     *
     * Mengembalikan CarbonInterface, bukan Carbon: AppServiceProvider memasang
     * Date::use(CarbonImmutable::class) sehingga cast tanggal model bersifat immutable.
     */
    public function deadlineFor(Booking $booking, int $days): CarbonInterface
    {
        return $booking->booking_date
            ->copy()
            ->setTimeFromTimeString($booking->start_time)
            ->subDays($days);
    }
}
