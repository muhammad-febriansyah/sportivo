<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Sportivo '.fake()->city(),
            'code' => Str::upper(fake()->unique()->bothify('???##')),
            'address' => fake()->address(),
            'phone' => '628'.fake()->numerify('##########'),
            'operating_hours' => [
                'weekday' => ['open' => '08:00', 'close' => '23:00'],
                'weekend' => ['open' => '08:00', 'close' => '23:00'],
            ],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
