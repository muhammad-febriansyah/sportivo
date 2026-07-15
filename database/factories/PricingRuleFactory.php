<?php

namespace Database\Factories;

use App\Enums\DayType;
use App\Models\Field;
use App\Models\PricingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingRule>
 */
class PricingRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'field_id' => Field::factory(),
            'day_type' => DayType::Weekday,
            'start_time' => '08:00:00',
            'end_time' => '23:00:00',
            'price' => 150_000,
            'member_price' => null,
        ];
    }

    public function forField(Field $field): static
    {
        return $this->state(fn (array $attributes): array => [
            'field_id' => $field->id,
        ]);
    }

    public function dayType(DayType $dayType): static
    {
        return $this->state(fn (array $attributes): array => [
            'day_type' => $dayType,
        ]);
    }

    public function between(string $start, string $end): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    public function price(int $price, ?int $memberPrice = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'price' => $price,
            'member_price' => $memberPrice,
        ]);
    }
}
