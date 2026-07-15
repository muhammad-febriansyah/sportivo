<?php

namespace Database\Factories;

use App\Models\BlockedSlot;
use App\Models\Branch;
use App\Models\Field;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<BlockedSlot>
 */
class BlockedSlotFactory extends Factory
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
            'field_id' => null,
            'block_date' => Carbon::today()->addDay()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Maintenance rumput',
            'created_by' => User::factory(),
        ];
    }

    public function forField(Field $field): static
    {
        return $this->state(fn (array $attributes): array => [
            'branch_id' => $field->branch_id,
            'field_id' => $field->id,
        ]);
    }

    /**
     * Blokir seluruh lapangan di satu cabang.
     */
    public function wholeBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes): array => [
            'branch_id' => $branch->id,
            'field_id' => null,
        ]);
    }

    public function on(Carbon|string $date, string $start = '08:00:00', string $end = '12:00:00'): static
    {
        return $this->state(fn (array $attributes): array => [
            'block_date' => $date instanceof Carbon ? $date->toDateString() : $date,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }
}
