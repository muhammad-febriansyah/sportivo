<?php

namespace Database\Factories;

use App\Models\Addon;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Addon>
 */
class AddonFactory extends Factory
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
            'name' => fake()->randomElement(['Rompi (10 pcs)', 'Bola', 'Sepatu', 'Air Mineral']),
            'price' => fake()->randomElement([10_000, 25_000, 50_000]),
            'stock' => null,
            'is_active' => true,
        ];
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes): array => [
            'branch_id' => $branch->id,
        ]);
    }

    public function price(int $price): static
    {
        return $this->state(fn (array $attributes): array => [
            'price' => $price,
        ]);
    }

    public function stock(?int $stock): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock' => $stock,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
