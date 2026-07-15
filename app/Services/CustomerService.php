<?php

namespace App\Services;

use App\Models\Customer;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CustomerService
{
    /**
     * @param  array{search?: string|null, member?: string|null, sort?: string|null, direction?: string|null}  $filters
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'name';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return Customer::query()
            ->select(['id', 'name', 'phone', 'email', 'is_member', 'member_until'])
            ->withCount('bookings')
            ->when(
                $filters['search'] ?? null,
                fn ($query, string $search) => $query->where(
                    fn ($q) => $q
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', '%'.PhoneNumber::normalize($search).'%')
                        ->orWhere('email', 'like', "%{$search}%")
                )
            )
            ->when(
                ($filters['member'] ?? null) === 'member',
                fn ($query) => $query->activeMembers()
            )
            ->orderBy(
                in_array($sort, ['name', 'phone', 'is_member'], true) ? $sort : 'name',
                $direction
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Pencarian cepat untuk form booking walk-in — kasir mengetik nomor WA.
     *
     * Nomor dinormalisasi dulu agar "0812…" menemukan pelanggan yang tersimpan
     * sebagai "62812…". Lihat docs/03-user-stories.md US-05.
     *
     * @return Collection<int, Customer>
     */
    public function search(string $keyword, int $limit = 10): Collection
    {
        $normalized = PhoneNumber::normalize($keyword);

        return Customer::query()
            ->select(['id', 'name', 'phone', 'is_member', 'member_until'])
            ->where(fn ($q) => $q
                ->where('name', 'like', "%{$keyword}%")
                ->when($normalized, fn ($q2, string $n) => $q2->orWhere('phone', 'like', "%{$n}%"))
            )
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }
}
