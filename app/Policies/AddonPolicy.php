<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Addon;
use App\Models\User;

/**
 * Master add-on adalah wewenang admin cabang dan owner — sama seperti master
 * lapangan. Kasir memakainya saat booking, tapi tidak mengubah harganya.
 *
 * Lihat docs/01-prd.md Modul 11.
 */
class AddonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->hasRole(UserRole::Admin->value);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Addon $addon): bool
    {
        return $this->viewAny($user) && $this->sameBranch($user, $addon->branch_id);
    }

    public function delete(User $user, Addon $addon): bool
    {
        return $this->update($user, $addon);
    }

    /**
     * Owner tidak terikat cabang. Non-owner tanpa branch_id tidak boleh apa pun —
     * tanpa penjagaan ini perbandingan null === null akan lolos.
     */
    private function sameBranch(User $user, int $branchId): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return $user->branch_id !== null && $user->branch_id === $branchId;
    }
}
