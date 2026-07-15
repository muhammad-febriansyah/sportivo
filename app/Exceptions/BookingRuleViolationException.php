<?php

namespace App\Exceptions;

use Exception;

/**
 * Permintaan melanggar kebijakan cabang (batas H-n, maksimal reschedule, dst).
 *
 * Berbeda dari SlotUnavailableException: slotnya mungkin kosong, tapi
 * kebijakannya yang tidak mengizinkan. Lihat docs/01-prd.md Modul 8.
 */
class BookingRuleViolationException extends Exception
{
    public static function make(string $reason): self
    {
        return new self($reason);
    }
}
