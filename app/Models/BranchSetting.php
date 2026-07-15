<?php

namespace App\Models;

use Database\Factories\BranchSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $dp_percentage
 * @property int $reschedule_limit_days
 * @property int $cancel_refund_limit_days
 * @property int $max_reschedule
 * @property int $online_hold_minutes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'branch_id',
    'dp_percentage',
    'reschedule_limit_days',
    'cancel_refund_limit_days',
    'max_reschedule',
    'online_hold_minutes',
])]
class BranchSetting extends Model
{
    /** @use HasFactory<BranchSettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dp_percentage' => 'integer',
            'reschedule_limit_days' => 'integer',
            'cancel_refund_limit_days' => 'integer',
            'max_reschedule' => 'integer',
            'online_hold_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
