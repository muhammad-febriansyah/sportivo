<?php

namespace App\Exceptions;

use Exception;

/**
 * Add-on tidak bisa disewa: tidak ada di cabang tersebut, nonaktif, atau
 * stoknya kurang. Lihat docs/01-prd.md Modul 11.
 */
class AddonUnavailableException extends Exception
{
    public static function notFound(int $addonId): self
    {
        return new self("Add-on #{$addonId} tidak tersedia di cabang ini.");
    }

    public static function outOfStock(string $name, int $stock): self
    {
        return new self("Stok {$name} tidak mencukupi. Tersisa {$stock}.");
    }
}
