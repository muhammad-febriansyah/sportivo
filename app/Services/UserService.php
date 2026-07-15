<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * Daftar user internal untuk halaman manajemen user.
     *
     * Tidak di-scope per cabang: hanya owner yang boleh mengakses manajemen user,
     * dan owner melihat seluruh cabang. Lihat UserPolicy dan docs/03-user-stories.md US-15.
     *
     * @param  array{search?: string|null, sort?: string|null, direction?: string|null}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'name';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return User::query()
            ->select(['id', 'branch_id', 'name', 'email', 'phone', 'is_active'])
            ->with(['branch:id,name', 'roles:id,name'])
            ->when(
                $filters['search'] ?? null,
                fn ($query, string $search) => $query->where(
                    fn ($q) => $q
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                )
            )
            ->orderBy(
                in_array($sort, ['name', 'email', 'is_active'], true) ? $sort : 'name',
                $direction
            )
            ->paginate($perPage)
            ->withQueryString();
    }
}
