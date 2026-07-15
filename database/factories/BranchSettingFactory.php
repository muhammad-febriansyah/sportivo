<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchSetting>
 */
class BranchSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'dp_percentage' => 50,
            'reschedule_limit_days' => 1,
            'cancel_refund_limit_days' => 2,
            'max_reschedule' => 1,
            'online_hold_minutes' => 15,
        ];
    }
}
