<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\User;

/**
 * Semua role internal menangani booking; pembatasannya per cabang.
 * Kasir memang bertugas input booking walk-in dan verifikasi kedatangan
 * (docs/00-project-goals.md bagian Target Pengguna).
 */
class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(UserRole::values());
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->viewAny($user) && $this->sameBranch($user, $booking->branch_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking);
    }

    /**
     * Pembatalan berdampak ke uang (DP hangus/refund), jadi bukan untuk kasir.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return ($user->isOwner() || $user->hasRole(UserRole::Admin->value))
            && $this->sameBranch($user, $booking->branch_id);
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
