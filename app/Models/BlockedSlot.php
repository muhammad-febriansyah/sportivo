<?php

namespace App\Models;

use App\Concerns\ScopesToBranch;
use Database\Factories\BlockedSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int|null $field_id
 * @property Carbon $block_date
 * @property string $start_time
 * @property string $end_time
 * @property string $reason
 * @property int $created_by
 */
#[Fillable(['branch_id', 'field_id', 'block_date', 'start_time', 'end_time', 'reason', 'created_by'])]
class BlockedSlot extends Model
{
    /** @use HasFactory<BlockedSlotFactory> */
    use HasFactory, ScopesToBranch;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'block_date' => 'date',
        ];
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
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Blokir ini menutup lapangan tertentu?
     * field_id null berarti seluruh lapangan di cabang tersebut ikut tertutup.
     */
    public function blocksField(int $fieldId): bool
    {
        return $this->field_id === null || $this->field_id === $fieldId;
    }

    /**
     * Blokir menutup jam yang diminta. start inklusif, end eksklusif.
     */
    public function covers(string $time): bool
    {
        $time = Booking::normalizeTime($time);

        return Booking::normalizeTime($this->start_time) <= $time
            && $time < Booking::normalizeTime($this->end_time);
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function overlapping(
        Builder $query,
        Carbon $date,
        string $startTime,
        string $endTime,
    ): void {
        $query->whereDate('block_date', $date)
            ->where(fn (Builder $q) => $q
                ->where('start_time', '<', Booking::normalizeTime($endTime))
                ->where('end_time', '>', Booking::normalizeTime($startTime))
            );
    }
}
