<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Field;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Grid ketersediaan slot: baris = jam, kolom = lapangan.
 *
 * WAJIB maksimal 3–4 query per request — dilarang query per slot
 * (docs/05-tech-conventions.md bagian Performa, docs/01-prd.md Modul 5).
 */
class AvailabilityService
{
    public function __construct(private readonly PricingService $pricing) {}

    /**
     * @return array{
     *     hours: array<int, string>,
     *     fields: array<int, array{id: int, name: string, status: string}>,
     *     slots: array<int, array<string, array<string, mixed>>>
     * }
     */
    public function grid(Branch $branch, Carbon $date, bool $publicOnly = false): array
    {
        $hours = $this->operatingHours($branch, $date);

        // Query 1: lapangan + aturan harganya (eager load, bukan per slot).
        $fields = Field::query()
            ->with('pricingRules')
            ->where('branch_id', $branch->id)
            ->when($publicOnly, fn ($q) => $q->publiclyBookable())
            ->orderBy('name')
            ->get();

        $fieldIds = $fields->pluck('id');

        // Query 2: booking hari itu, dikelompokkan per lapangan.
        $bookings = Booking::query()
            ->select(['id', 'code', 'field_id', 'start_time', 'end_time', 'status', 'customer_name'])
            ->whereIn('field_id', $fieldIds)
            ->whereDate('booking_date', $date)
            ->holdingSlot()
            ->get()
            ->groupBy('field_id');

        // Query 3: blokir hari itu (termasuk blokir se-cabang, field_id null).
        $blocked = BlockedSlot::query()
            ->select(['id', 'field_id', 'start_time', 'end_time', 'reason'])
            ->where('branch_id', $branch->id)
            ->whereDate('block_date', $date)
            ->get();

        $slots = [];

        foreach ($fields as $field) {
            // resolveMany memakai relasi yang sudah dimuat — 0 query tambahan.
            $harga = $this->pricing->resolveMany($field, $date, $hours);
            $bookingLapangan = $bookings->get($field->id) ?? collect();

            $perJam = [];

            foreach ($hours as $jam) {
                $perJam[$jam] = $this->buildSlot(
                    $field->id,
                    $date,
                    $jam,
                    $harga[$jam] ?? null,
                    $bookingLapangan,
                    $blocked,
                    $publicOnly,
                );
            }

            $slots[$field->id] = $perJam;
        }

        return [
            'hours' => $hours,
            'fields' => $fields->map(fn (Field $f): array => [
                'id' => $f->id,
                'name' => $f->name,
                'status' => $f->status->value,
            ])->all(),
            'slots' => $slots,
        ];
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @param  Collection<int, BlockedSlot>  $blocked
     * @return array<string, mixed>
     */
    private function buildSlot(
        int $fieldId,
        Carbon $date,
        string $jam,
        ?int $harga,
        Collection $bookings,
        Collection $blocked,
        bool $publicOnly,
    ): array {
        // Slot yang sudah lewat tidak bisa dibooking (docs/01-prd.md Modul 5).
        $waktuSlot = $date->copy()->setTimeFromTimeString($jam);

        if ($waktuSlot->isPast()) {
            return ['state' => 'past', 'price' => $harga];
        }

        $blokir = $blocked->first(
            fn (BlockedSlot $b): bool => $b->blocksField($fieldId) && $b->covers($jam)
        );

        if ($blokir !== null) {
            return [
                'state' => 'blocked',
                'price' => $harga,
                'reason' => $publicOnly ? null : $blokir->reason,
            ];
        }

        $booking = $bookings->first(
            fn (Booking $b): bool => Booking::normalizeTime($b->start_time) <= Booking::normalizeTime($jam)
                && Booking::normalizeTime($jam) < Booking::normalizeTime($b->end_time)
        );

        if ($booking !== null) {
            return [
                'state' => $booking->status === BookingStatus::Pending ? 'pending' : (
                    $booking->status->isPaidAtLeastPartially() && $booking->status !== BookingStatus::ConfirmedDp
                        ? 'paid'
                        : 'dp'
                ),
                'price' => $harga,
                // Identitas penyewa tidak boleh bocor ke halaman publik (US-01).
                'booking_id' => $publicOnly ? null : $booking->id,
                'booking_code' => $publicOnly ? null : $booking->code,
                'customer_name' => $publicOnly ? null : $booking->customer_name,
            ];
        }

        // Slot tanpa harga tidak bisa dibooking (docs/01-prd.md Modul 4).
        if ($harga === null) {
            return ['state' => 'no_price', 'price' => null];
        }

        return ['state' => 'available', 'price' => $harga];
    }

    /**
     * Jam operasional cabang untuk tanggal tersebut, dibulatkan per jam.
     *
     * @return array<int, string>
     */
    private function operatingHours(Branch $branch, Carbon $date): array
    {
        $tipe = $date->isWeekend() ? 'weekend' : 'weekday';
        $jam = $branch->operating_hours[$tipe] ?? ['open' => '08:00', 'close' => '23:00'];

        $mulai = (int) substr($jam['open'], 0, 2);
        $selesai = (int) substr($jam['close'], 0, 2);

        $hasil = [];

        // Slot terakhir dimulai satu jam sebelum tutup — durasi slot 1 jam.
        for ($h = $mulai; $h < $selesai; $h++) {
            $hasil[] = sprintf('%02d:00', $h);
        }

        return $hasil;
    }
}
