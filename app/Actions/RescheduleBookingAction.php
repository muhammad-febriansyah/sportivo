<?php

namespace App\Actions;

use App\Exceptions\BookingRuleViolationException;
use App\Exceptions\PriceNotConfiguredException;
use App\Exceptions\SlotUnavailableException;
use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Services\BookingRuleService;
use App\Services\PricingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pindah jadwal booking.
 *
 * Memakai pola lock yang sama persis dengan CreateBookingAction — reschedule
 * juga bisa berlomba dengan booking lain memperebutkan slot tujuan
 * (docs/05-tech-conventions.md bagian Transaksi Kritikal).
 */
class RescheduleBookingAction
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly BookingRuleService $rules,
    ) {}

    /**
     * @throws BookingRuleViolationException
     * @throws SlotUnavailableException
     * @throws PriceNotConfiguredException
     */
    public function execute(
        Booking $booking,
        Carbon $tanggalBaru,
        string $jamMulaiBaru,
        ?int $lapanganBaruId = null,
    ): Booking {
        $izin = $this->rules->canReschedule($booking);

        if (! $izin['allowed']) {
            throw BookingRuleViolationException::make($izin['reason']);
        }

        $field = $lapanganBaruId
            ? $booking->branch->fields()->findOrFail($lapanganBaruId)
            : $booking->field;

        $jamSelesaiBaru = $this->endTime($jamMulaiBaru, $booking->duration_hours);

        return DB::transaction(function () use ($booking, $field, $tanggalBaru, $jamMulaiBaru, $jamSelesaiBaru): Booking {
            $bentrok = Booking::query()
                ->conflictingWith($field->id, $tanggalBaru, $jamMulaiBaru, $jamSelesaiBaru)
                // Booking ini sendiri tidak boleh dianggap bentrok dengan dirinya.
                ->whereKeyNot($booking->id)
                ->lockForUpdate()
                ->exists();

            if ($bentrok) {
                throw SlotUnavailableException::make();
            }

            $blokir = BlockedSlot::query()
                ->where('branch_id', $field->branch_id)
                ->where(fn ($q) => $q->whereNull('field_id')->orWhere('field_id', $field->id))
                ->overlapping($tanggalBaru, $jamMulaiBaru, $jamSelesaiBaru)
                ->first();

            if ($blokir !== null) {
                throw SlotUnavailableException::blocked($blokir->reason);
            }

            // Harga dihitung ulang: slot baru bisa lebih mahal atau lebih murah
            // (docs/01-prd.md Modul 8). Status member tetap mengikuti snapshot
            // booking asli agar tidak berubah gara-gara membership kedaluwarsa
            // di antara booking dan reschedule.
            $hargaBaru = $this->pricing->resolve(
                $field,
                $tanggalBaru,
                $jamMulaiBaru,
                $booking->is_member_price,
            );

            $subtotalBaru = $hargaBaru * $booking->duration_hours;
            $totalBaru = $subtotalBaru + $booking->subtotal_addons;

            $booking->update([
                // Jadwal lama disimpan sebagai histori.
                'rescheduled_from' => [
                    'date' => $booking->booking_date->toDateString(),
                    'start_time' => substr($booking->start_time, 0, 5),
                    'end_time' => substr($booking->end_time, 0, 5),
                    'field_name' => $booking->field_name,
                ],
                'reschedule_count' => $booking->reschedule_count + 1,

                'field_id' => $field->id,
                'field_name' => $field->name,
                'booking_date' => $tanggalBaru->toDateString(),
                'start_time' => $jamMulaiBaru,
                'end_time' => $jamSelesaiBaru,

                'price_per_hour' => $hargaBaru,
                'subtotal_field' => $subtotalBaru,
                'total' => $totalBaru,
            ]);

            return $booking->fresh();
        });
    }

    private function endTime(string $startTime, int $durationHours): string
    {
        $jam = (int) substr($startTime, 0, 2);

        return sprintf('%02d:%s', $jam + $durationHours, substr($startTime, 3, 2));
    }
}
