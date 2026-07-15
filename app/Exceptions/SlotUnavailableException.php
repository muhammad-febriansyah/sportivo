<?php

namespace App\Exceptions;

use Exception;

/**
 * Slot sudah ditahan booking lain.
 *
 * Dilempar dari dalam transaksi setelah pemeriksaan bentrok ber-lock, jadi
 * inilah yang menahan double booking saat dua request datang bersamaan.
 * Lihat docs/03-user-stories.md US-16.
 */
class SlotUnavailableException extends Exception
{
    public static function make(): self
    {
        return new self('Slot sudah terisi, silakan pilih jam lain.');
    }
}
