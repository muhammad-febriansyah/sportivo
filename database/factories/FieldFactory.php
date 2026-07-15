<?php

namespace Database\Factories;

use App\Enums\FieldStatus;
use App\Enums\SurfaceType;
use App\Models\Branch;
use App\Models\Field;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Field>
 */
class FieldFactory extends Factory
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
            'name' => 'Lapangan '.fake()->randomLetter(),
            'surface_type' => fake()->randomElement(SurfaceType::cases()),
            'size' => fake()->randomElement(['25x15 m', '30x20 m', '40x20 m']),
            'description' => fake()->optional()->sentence(),
            'status' => FieldStatus::Active,
        ];
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes): array => [
            'branch_id' => $branch->id,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FieldStatus::Maintenance,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FieldStatus::Inactive,
        ]);
    }
}
