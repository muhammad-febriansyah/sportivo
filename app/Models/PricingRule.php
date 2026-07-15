<?php

namespace App\Models;

use App\Enums\DayType;
use Database\Factories\PricingRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $field_id
 * @property DayType $day_type
 * @property string $start_time
 * @property string $end_time
 * @property int $price
 * @property int|null $member_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Field $field
 */
#[Fillable([
    'field_id',
    'day_type',
    'start_time',
    'end_time',
    'price',
    'member_price',
])]
class PricingRule extends Model
{
    /** @use HasFactory<PricingRuleFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_type' => DayType::class,
            'price' => 'integer',
            'member_price' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Field, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    /**
     * Harga yang berlaku untuk penyewa. Member memakai member_price bila diatur,
     * selain itu jatuh kembali ke harga umum — lihat docs/01-prd.md Modul 4.
     */
    public function priceFor(bool $isMember): int
    {
        if ($isMember && $this->member_price !== null) {
            return $this->member_price;
        }

        return $this->price;
    }

    /**
     * Rentang waktu rule ini menampung jam yang diminta.
     * start_time inklusif, end_time eksklusif.
     */
    public function covers(string $time): bool
    {
        $time = self::normalizeTime($time);

        return self::normalizeTime($this->start_time) <= $time
            && $time < self::normalizeTime($this->end_time);
    }

    /**
     * Samakan bentuk jam ke "HH:MM:SS" sebelum dibandingkan.
     *
     * Kolom TIME MySQL mengembalikan "08:00:00" sementara input form berbentuk
     * "08:00". Perbandingan string mentah keduanya salah: "08:00:00" <= "08:00"
     * bernilai false karena string lebih pendek dianggap lebih kecil — akibatnya
     * jam tepat di batas awal rule tidak menemukan harga.
     */
    public static function normalizeTime(string $time): string
    {
        return substr($time, 0, 5).':00';
    }
}
