<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris add-on pada sebuah booking.
 *
 * addon_name dan addon_price adalah snapshot — mengubah master add-on tidak
 * boleh mengubah tagihan booking yang sudah jadi (docs/02-erd.md).
 *
 * @property int $id
 * @property int $booking_id
 * @property int $addon_id
 * @property string $addon_name
 * @property int $addon_price
 * @property int $qty
 * @property int $subtotal
 */
#[Fillable(['booking_id', 'addon_id', 'addon_name', 'addon_price', 'qty', 'subtotal'])]
class BookingAddon extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'addon_price' => 'integer',
            'qty' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Addon, $this>
     */
    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
    }
}
