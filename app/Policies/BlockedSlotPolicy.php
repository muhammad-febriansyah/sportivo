<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\BlockedSlot;
use App\Models\User;

/**
 * Blocking slot adalah wewenang admin cabang dan owner — kasir tidak menutup
 * lapangan (docs/00-project-goals.md bagian Target Pengguna, US-10).
 */
class BlockedSlotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->hasRole(UserRole::Admin->value);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, BlockedSlot $blockedSlot): bool
    {
        return $this->viewAny($user) && $this->sameBranch($user, $blockedSlot->branch_id);
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
