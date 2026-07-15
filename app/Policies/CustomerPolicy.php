<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;

/**
 * Pelanggan TIDAK terikat cabang — satu orang bisa main di cabang mana pun,
 * jadi tidak ada scoping cabang di sini.
 *
 * Kasir perlu membuat pelanggan saat input booking walk-in (US-05), tapi tidak
 * mengubah status member — itu keputusan owner/admin (docs/01-prd.md Modul 10).
 */
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(UserRole::values());
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Kasir wajib bisa membuat pelanggan inline saat booking walk-in.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->isOwner() || $user->hasRole(UserRole::Admin->value);
    }

    /**
     * Mengubah status member berdampak langsung ke harga, jadi dibatasi.
     */
    public function manageMembership(User $user, Customer $customer): bool
    {
        return $this->update($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->isOwner();
    }
}
