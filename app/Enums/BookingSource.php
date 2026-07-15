<?php

namespace App\Enums;

enum BookingSource: string
{
    case Online = 'online';
    case Walkin = 'walkin';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Walkin => 'Walk-in',
        };
    }

    /**
     * Hanya booking online yang punya batas waktu bayar; walk-in dicatat
     * kasir saat pelanggan sudah di lokasi. Lihat docs/01-prd.md Modul 6.
     */
    public function expiresWhenUnpaid(): bool
    {
        return $this === self::Online;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
