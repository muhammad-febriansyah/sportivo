<?php

namespace App\Actions;

use App\Enums\BookingSource;
use Illuminate\Support\Carbon;

/**
 * Masukan untuk CreateBookingAction.
 */
class CreateBookingData
{
    public function __construct(
        public int $fieldId,
        public int $customerId,
        public Carbon $date,
        public string $startTime,
        public int $durationHours,
        public BookingSource $source,
        public ?int $createdBy = null,
        /** Null = pakai persentase DP dari pengaturan cabang. */
        public ?int $dpAmount = null,
        public bool $payFull = false,
    ) {}

    /**
     * Jam selesai dihitung dari durasi — slot selalu kelipatan 1 jam.
     */
    public function endTime(): string
    {
        $jam = (int) substr($this->startTime, 0, 2);
        $menit = substr($this->startTime, 3, 2);

        return sprintf('%02d:%s', $jam + $this->durationHours, $menit);
    }
}
