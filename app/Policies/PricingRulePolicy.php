<?php

namespace App\Policies;

use App\Models\Field;
use App\Models\PricingRule;
use App\Models\User;

/**
 * Harga mengikuti izin lapangannya: siapa yang boleh mengelola lapangan,
 * boleh mengatur harganya — termasuk pembatasan cabang.
 *
 * Lihat FieldPolicy dan docs/03-user-stories.md US-09.
 */
class PricingRulePolicy
{
    public function __construct(private readonly FieldPolicy $fields) {}

    public function viewAny(User $user, Field $field): bool
    {
        return $this->fields->view($user, $field);
    }

    public function create(User $user, Field $field): bool
    {
        return $this->fields->update($user, $field);
    }

    public function update(User $user, PricingRule $pricingRule): bool
    {
        return $this->fields->update($user, $pricingRule->field);
    }

    public function delete(User $user, PricingRule $pricingRule): bool
    {
        return $this->fields->update($user, $pricingRule->field);
    }
}
