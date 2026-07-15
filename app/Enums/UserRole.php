<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Kasir = 'kasir';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin Cabang',
            self::Kasir => 'Kasir',
        };
    }

    /**
     * Owner tidak terikat cabang — lihat docs/02-erd.md tabel users.
     */
    public function requiresBranch(): bool
    {
        return $this !== self::Owner;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
