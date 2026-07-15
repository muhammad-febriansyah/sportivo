<?php

namespace App\Enums;

use Illuminate\Support\Carbon;

/**
 * Tipe hari untuk aturan harga.
 *
 * Hari spesifik (monday..sunday) MENANG atas weekday/weekend saat resolusi harga.
 * Lihat docs/01-prd.md Modul 4.
 */
enum DayType: string
{
    case Weekday = 'weekday';
    case Weekend = 'weekend';
    case Monday = 'monday';
    case Tuesday = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday = 'thursday';
    case Friday = 'friday';
    case Saturday = 'saturday';
    case Sunday = 'sunday';

    public function label(): string
    {
        return match ($this) {
            self::Weekday => 'Weekday (Senin–Jumat)',
            self::Weekend => 'Weekend (Sabtu–Minggu)',
            self::Monday => 'Senin',
            self::Tuesday => 'Selasa',
            self::Wednesday => 'Rabu',
            self::Thursday => 'Kamis',
            self::Friday => 'Jumat',
            self::Saturday => 'Sabtu',
            self::Sunday => 'Minggu',
        };
    }

    public function isSpecificDay(): bool
    {
        return ! in_array($this, [self::Weekday, self::Weekend], true);
    }

    /**
     * Nomor hari ISO: 1 = Senin … 7 = Minggu. Null untuk weekday/weekend.
     */
    public function isoDayOfWeek(): ?int
    {
        return match ($this) {
            self::Monday => 1,
            self::Tuesday => 2,
            self::Wednesday => 3,
            self::Thursday => 4,
            self::Friday => 5,
            self::Saturday => 6,
            self::Sunday => 7,
            default => null,
        };
    }

    /**
     * Hari spesifik untuk tanggal tertentu.
     */
    public static function specificFor(Carbon $date): self
    {
        return match ($date->dayOfWeekIso) {
            1 => self::Monday,
            2 => self::Tuesday,
            3 => self::Wednesday,
            4 => self::Thursday,
            5 => self::Friday,
            6 => self::Saturday,
            default => self::Sunday,
        };
    }

    /**
     * Weekday atau weekend untuk tanggal tertentu.
     */
    public static function generalFor(Carbon $date): self
    {
        return $date->isWeekend() ? self::Weekend : self::Weekday;
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
