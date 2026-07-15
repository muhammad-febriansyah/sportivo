<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Untuk model yang punya kolom `branch_id`.
 *
 * Owner mengakses seluruh cabang; admin & kasir hanya cabangnya sendiri.
 * Scope ini WAJIB dipanggil eksplisit di Service — jangan hanya menyembunyikan
 * data di UI. Lihat docs/05-tech-conventions.md bagian Authorization.
 */
trait ScopesToBranch
{
    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->isOwner()) {
            return;
        }

        // Admin/kasir tanpa branch_id tidak boleh melihat apa pun. Tanpa baris ini
        // `where branch_id = null` justru cocok dengan baris ber-branch_id NULL.
        if ($user->branch_id === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where($query->getModel()->getTable().'.branch_id', $user->branch_id);
    }
}
