<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '628'.fake()->numerify('##########'),
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Owner tidak terikat cabang — mengakses seluruh cabang.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'branch_id' => null,
        ])->afterCreating(fn (User $user) => $user->assignRole(UserRole::Owner->value));
    }

    public function admin(?Branch $branch = null): static
    {
        return $this->forBranch($branch)
            ->afterCreating(fn (User $user) => $user->assignRole(UserRole::Admin->value));
    }

    public function kasir(?Branch $branch = null): static
    {
        return $this->forBranch($branch)
            ->afterCreating(fn (User $user) => $user->assignRole(UserRole::Kasir->value));
    }

    public function forBranch(?Branch $branch = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'branch_id' => $branch?->id ?? Branch::factory(),
        ]);
    }

    /**
     * User nonaktif tidak bisa login, tapi data historisnya tetap tampil.
     * Lihat docs/01-prd.md Modul 1.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static {}
}
