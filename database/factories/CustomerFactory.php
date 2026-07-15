<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '628'.fake()->unique()->numerify('##########'),
            'email' => fake()->optional()->safeEmail(),
            'is_member' => false,
            'member_until' => null,
        ];
    }

    public function member(?Carbon $until = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_member' => true,
            'member_until' => $until ?? Carbon::today()->addYear(),
        ]);
    }

    /**
     * Member yang masa berlakunya sudah lewat — harus kembali ke harga umum.
     */
    public function expiredMember(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_member' => true,
            'member_until' => Carbon::yesterday(),
        ]);
    }
}
