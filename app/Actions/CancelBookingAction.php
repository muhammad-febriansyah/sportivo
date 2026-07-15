<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Exceptions\BookingRuleViolationException;
use App\Models\Booking;
use App\Services\BookingRuleService;
use Illuminate\Support\Facades\DB;

/**
 * Pembatalan booking.
 *
 * Booking TIDAK dihapus — statusnya diubah menjadi cancelled agar riwayat
 * keuangan tetap utuh (docs/05-tech-conventions.md). Slotnya otomatis lepas
 * karena status cancelled tidak lagi menahan slot.
 */
class CancelBookingAction
{
    public function __construct(private readonly BookingRuleService $rules) {}

    /**
     * @return array{booking: Booking, refunds_dp: bool}
     *
     * @throws BookingRuleViolationException
     */
    public function execute(Booking $booking, ?string $reason = null): array
    {
        $izin = $this->rules->canCancel($booking);

        if (! $izin['allowed']) {
            throw BookingRuleViolationException::make($izin['reason']);
        }

        DB::transaction(function () use ($booking, $reason): void {
            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);
        });

        // Pencatatan refund DP menunggu Modul 7 (Pembayaran): refund dicatat
        // sebagai booking_payment ber-amount negatif. Untuk sekarang keputusan
        // hangus/refund dikembalikan agar UI dan kasir tahu apa yang berlaku.
        return [
            'booking' => $booking->fresh(),
            'refunds_dp' => $izin['refunds_dp'],
        ];
    }
}
