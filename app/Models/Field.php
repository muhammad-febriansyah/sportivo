<?php

namespace App\Models;

use App\Concerns\ScopesToBranch;
use App\Enums\FieldStatus;
use App\Enums\SurfaceType;
use Database\Factories\FieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property SurfaceType $surface_type
 * @property string|null $size
 * @property string|null $description
 * @property string|null $photo_path
 * @property FieldStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Branch $branch
 */
#[Fillable([
    'branch_id',
    'name',
    'surface_type',
    'size',
    'description',
    'photo_path',
    'status',
])]
class Field extends Model
{
    /** @use HasFactory<FieldFactory> */
    use HasFactory, ScopesToBranch, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'surface_type' => SurfaceType::class,
            'status' => FieldStatus::class,
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
     * Lapangan yang boleh tampil di grid booking publik.
     * Status maintenance/nonaktif disembunyikan — lihat docs/03-user-stories.md US-08.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function publiclyBookable(Builder $query): void
    {
        $query->where('status', FieldStatus::Active);
    }
}
