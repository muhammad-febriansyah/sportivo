<?php

namespace App\Enums;

enum SurfaceType: string
{
    case Sintetis = 'sintetis';
    case Vinyl = 'vinyl';
    case Interlock = 'interlock';

    public function label(): string
    {
        return match ($this) {
            self::Sintetis => 'Rumput Sintetis',
            self::Vinyl => 'Vinyl',
            self::Interlock => 'Interlock',
        };
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
