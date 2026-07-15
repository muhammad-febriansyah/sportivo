<?php

namespace App\Enums;

enum FieldStatus: string
{
    case Active = 'active';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Maintenance => 'Maintenance',
            self::Inactive => 'Nonaktif',
        };
    }

    /**
     * Hanya lapangan aktif yang muncul di grid booking publik.
     * Status maintenance menyembunyikan lapangan dari publik, tapi booking
     * yang sudah ada tetap tampil di internal — lihat docs/03-user-stories.md US-08.
     */
    public function bookableByPublic(): bool
    {
        return $this === self::Active;
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
