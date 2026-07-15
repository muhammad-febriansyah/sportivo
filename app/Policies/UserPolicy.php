<?php

namespace App\Policies;

use App\Models\User;

/**
 * Manajemen user hanya untuk owner.
 * Lihat docs/01-prd.md Modul 1 dan docs/03-user-stories.md US-15.
 */
class UserPolicy
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
    public function view(User $user, User $model): bool
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
    public function update(User $user, User $model): bool
    {
        return $user->isOwner();
    }

    /**
     * Owner tidak boleh menonaktifkan dirinya sendiri — mencegah venue
     * kehilangan satu-satunya akses owner.
     */
    public function deactivate(User $user, User $model): bool
    {
        return $user->isOwner() && $user->isNot($model);
    }
}
