<?php

namespace App\Exceptions;

use App\Models\Field;
use Exception;
use Illuminate\Support\Carbon;

/**
 * Tidak ada pricing rule yang menampung jam yang diminta.
 *
 * Slot seperti ini tampil sebagai "harga belum diatur" di grid dan TIDAK boleh
 * dibooking — lihat docs/01-prd.md Modul 4.
 */
class PriceNotConfiguredException extends Exception
{
    public static function for(Field $field, Carbon $date, string $startTime): self
    {
        return new self(sprintf(
            'Harga belum diatur untuk %s pada %s jam %s.',
            $field->name,
            $date->format('d M Y'),
            substr($startTime, 0, 5),
        ));
    }
}
