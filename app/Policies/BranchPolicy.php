<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

/**
 * Master cabang hanya untuk owner. Ruang lingkup admin cabang terbatas pada
 * operasional (lapangan, harga, blocking slot) — lihat docs/00-project-goals.md
 * bagian Target Pengguna dan docs/01-prd.md Modul 2.
 */
class BranchPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Branch $branch): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Branch $branch): bool
    {
        return $user->isOwner();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Branch $branch): bool
    {
        return $user->isOwner();
    }
}
