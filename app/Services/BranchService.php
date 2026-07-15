<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BranchService
{
    /**
     * @param  array{search?: string|null, sort?: string|null, direction?: string|null}  $filters
     * @return LengthAwarePaginator<int, Branch>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'name';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return Branch::query()
            ->select(['id', 'name', 'code', 'address', 'province_id', 'city_id', 'phone', 'is_active'])
            ->with(['province:id,name', 'city:id,name'])
            ->withCount('users')
            ->when(
                $filters['search'] ?? null,
                fn ($query, string $search) => $query->where(
                    fn ($q) => $q
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                )
            )
            ->orderBy(
                in_array($sort, ['name', 'code', 'is_active'], true) ? $sort : 'name',
                $direction
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Alasan cabang tidak boleh dihapus. Array kosong berarti boleh dihapus.
     *
     * CATATAN: docs/01-prd.md Modul 2 mensyaratkan cabang dengan booking aktif
     * tidak boleh dihapus. Tabel `bookings` baru ada di Modul 6, jadi pemeriksaan
     * itu BELUM ada di sini dan wajib ditambahkan saat modul tersebut dikerjakan.
     *
     * @return array<int, string>
     */
    public function deletionBlockers(Branch $branch): array
    {
        $blockers = [];

        // Menghapus cabang akan membuat admin/kasir menggantung tanpa cabang,
        // dan mereka kehilangan akses ke seluruh data operasional.
        $jumlahUser = $branch->users()->count();

        if ($jumlahUser > 0) {
            $blockers[] = "Masih ada {$jumlahUser} user yang ditugaskan ke cabang ini. Pindahkan atau nonaktifkan user tersebut lebih dulu.";
        }

        return $blockers;
    }
}
