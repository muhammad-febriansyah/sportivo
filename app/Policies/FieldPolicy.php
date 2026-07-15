<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Field;
use App\Models\User;

/**
 * Owner mengelola lapangan seluruh cabang; admin hanya cabangnya sendiri.
 * Kasir tidak mengelola master data — ruang lingkupnya booking dan pembayaran
 * (lihat docs/00-project-goals.md bagian Target Pengguna).
 *
 * Lihat docs/01-prd.md Modul 3 dan docs/03-user-stories.md US-08.
 */
class FieldPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->hasRole(UserRole::Admin->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Field $field): bool
    {
        return $this->viewAny($user) && $this->sameBranch($user, $field);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Field $field): bool
    {
        return $this->viewAny($user) && $this->sameBranch($user, $field);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Field $field): bool
    {
        return $this->viewAny($user) && $this->sameBranch($user, $field);
    }

    /**
     * Owner tidak terikat cabang. Admin tanpa branch_id tidak boleh apa pun —
     * tanpa penjagaan ini perbandingan null == null akan lolos.
     */
    private function sameBranch(User $user, Field $field): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return $user->branch_id !== null && $user->branch_id === $field->branch_id;
    }
}
