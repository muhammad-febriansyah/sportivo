<?php

namespace App\Services;

use App\Models\Field;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FieldService
{
    /**
     * Daftar lapangan yang boleh dilihat $user.
     *
     * Scope visibleTo dipanggil eksplisit di sini — pembatasan cabang tidak boleh
     * hanya mengandalkan UI. Lihat docs/05-tech-conventions.md bagian Authorization.
     *
     * @param  array{search?: string|null, branch_id?: int|null, status?: string|null, sort?: string|null, direction?: string|null}  $filters
     * @return LengthAwarePaginator<int, Field>
     */
    public function paginate(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'name';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return Field::query()
            ->visibleTo($user)
            ->select(['id', 'branch_id', 'name', 'surface_type', 'size', 'status'])
            ->with('branch:id,name')
            ->when(
                $filters['search'] ?? null,
                fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")
            )
            ->when(
                $filters['branch_id'] ?? null,
                fn ($query, int $branchId) => $query->where('branch_id', $branchId)
            )
            ->when(
                $filters['status'] ?? null,
                fn ($query, string $status) => $query->where('status', $status)
            )
            ->orderBy(
                in_array($sort, ['name', 'status', 'surface_type'], true) ? $sort : 'name',
                $direction
            )
            ->paginate($perPage)
            ->withQueryString();
    }
}
