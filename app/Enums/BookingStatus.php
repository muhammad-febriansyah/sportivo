<?php

namespace App\Enums;

/**
 * Alur status: pending → confirmed_dp → paid → completed
 * Cabang keluar: cancelled, no_show
 *
 * Lihat docs/01-prd.md Modul 6 dan docs/02-erd.md tabel bookings.
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case ConfirmedDp = 'confirmed_dp';
    case Paid = 'paid';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::ConfirmedDp => 'DP Terbayar',
            self::Paid => 'Lunas',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::NoShow => 'Tidak Hadir',
        };
    }

    /**
     * Status yang TIDAK menahan slot. Booking dengan status ini tidak dihitung
     * sebagai bentrok — slotnya bebas dipakai orang lain.
     *
     * Lihat docs/02-erd.md bagian Aturan bentrok.
     *
     * @return array<int, self>
     */
    public static function releasingSlot(): array
    {
        return [self::Cancelled, self::NoShow];
    }

    /**
     * Booking yang masih menahan slot.
     */
    public function holdsSlot(): bool
    {
        return ! in_array($this, self::releasingSlot(), true);
    }

    /**
     * Booking yang sudah dibayar sebagian atau penuh.
     */
    public function isPaidAtLeastPartially(): bool
    {
        return in_array($this, [self::ConfirmedDp, self::Paid, self::Completed], true);
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
