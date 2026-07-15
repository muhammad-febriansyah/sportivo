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

    /**
     * Slot ditutup admin untuk maintenance atau event privat (Modul 12).
     */
    public static function blocked(string $reason): self
    {
        return new self("Slot ini diblokir: {$reason}");
    }
}
