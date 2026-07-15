<?php

namespace App\Models;

use App\Concerns\ScopesToBranch;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $code
 * @property int $branch_id
 * @property int $field_id
 * @property int $customer_id
 * @property int|null $recurring_booking_id
 * @property Carbon $booking_date
 * @property string $start_time
 * @property string $end_time
 * @property int $duration_hours
 * @property string $branch_name
 * @property string $field_name
 * @property string $customer_name
 * @property string $customer_phone
 * @property int $price_per_hour
 * @property bool $is_member_price
 * @property int $subtotal_field
 * @property int $subtotal_addons
 * @property int $total
 * @property int $dp_amount
 * @property int $paid_amount
 * @property BookingStatus $status
 * @property BookingSource $source
 * @property Carbon|null $expired_at
 * @property array{date: string, start_time: string, end_time: string}|null $rescheduled_from
 * @property int $reschedule_count
 * @property Carbon|null $checked_in_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancel_reason
 * @property int|null $created_by
 */
#[Fillable([
    'code', 'branch_id', 'field_id', 'customer_id', 'recurring_booking_id',
    'booking_date', 'start_time', 'end_time', 'duration_hours',
    'branch_name', 'field_name', 'customer_name', 'customer_phone',
    'price_per_hour', 'is_member_price',
    'subtotal_field', 'subtotal_addons', 'total', 'dp_amount', 'paid_amount',
    'status', 'source', 'expired_at', 'rescheduled_from', 'reschedule_count',
    'checked_in_at', 'cancelled_at', 'cancel_reason', 'created_by',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, ScopesToBranch;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'status' => BookingStatus::class,
            'source' => BookingSource::class,
            'is_member_price' => 'boolean',
            'price_per_hour' => 'integer',
            'subtotal_field' => 'integer',
            'subtotal_addons' => 'integer',
            'total' => 'integer',
            'dp_amount' => 'integer',
            'paid_amount' => 'integer',
            'duration_hours' => 'integer',
            'reschedule_count' => 'integer',
            'rescheduled_from' => 'array',
            'expired_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Kode booking unik: SPV-{YYMMDD}-{4 karakter}.
     * Lihat docs/01-prd.md Modul 6.
     */
    public static function generateCode(Carbon $date): string
    {
        return sprintf(
            'SPV-%s-%s',
            $date->format('ymd'),
            Str::upper(Str::random(4)),
        );
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Field, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<BookingAddon, $this>
     */
    public function addons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }

    /**
     * Sisa tagihan yang belum dibayar.
     */
    public function outstanding(): int
    {
        return max(0, $this->total - $this->paid_amount);
    }

    /**
     * Booking yang masih menahan slot — dipakai pemeriksaan bentrok.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function holdingSlot(Builder $query): void
    {
        $query->whereNotIn('status', BookingStatus::releasingSlot());
    }

    /**
     * Booking yang bentrok dengan rentang waktu tertentu di satu lapangan.
     *
     * Dua rentang bertabrakan bila yang satu mulai sebelum yang lain selesai
     * DAN selesai setelah yang lain mulai. Lihat docs/02-erd.md.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function conflictingWith(
        Builder $query,
        int $fieldId,
        Carbon $date,
        string $startTime,
        string $endTime,
    ): void {
        $query->where('field_id', $fieldId)
            ->whereDate('booking_date', $date)
            ->holdingSlot()
            ->where(fn (Builder $q) => $q
                ->where('start_time', '<', self::normalizeTime($endTime))
                ->where('end_time', '>', self::normalizeTime($startTime))
            );
    }

    /**
     * Samakan bentuk jam ke "HH:MM:SS" sebelum dibandingkan — kolom TIME MySQL
     * mengembalikan "08:00:00" sementara input berbentuk "08:00".
     */
    public static function normalizeTime(string $time): string
    {
        return substr($time, 0, 5).':00';
    }
}
