<?php

namespace Database\Factories;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Field;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $field = Field::factory();
        $customer = Customer::factory();

        return [
            'code' => Booking::generateCode(Carbon::today()),
            'field_id' => $field,
            'branch_id' => fn (array $attributes): int => Field::find($attributes['field_id'])->branch_id,
            'customer_id' => $customer,

            'booking_date' => Carbon::today()->addDay()->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
            'duration_hours' => 1,

            'branch_name' => 'Cabang Uji',
            'field_name' => 'Lapangan Uji',
            'customer_name' => fake()->name(),
            'customer_phone' => '628'.fake()->numerify('##########'),
            'price_per_hour' => 150_000,
            'is_member_price' => false,

            'subtotal_field' => 150_000,
            'subtotal_addons' => 0,
            'total' => 150_000,
            'dp_amount' => 75_000,
            'paid_amount' => 0,

            'status' => BookingStatus::Pending,
            'source' => BookingSource::Walkin,
        ];
    }

    public function forField(Field $field): static
    {
        return $this->state(fn (array $attributes): array => [
            'field_id' => $field->id,
            'branch_id' => $field->branch_id,
            'field_name' => $field->name,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes): array => [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
        ]);
    }

    public function on(Carbon|string $date, string $startTime = '19:00', int $durationHours = 1): static
    {
        $mulai = (int) substr($startTime, 0, 2);

        return $this->state(fn (array $attributes): array => [
            'booking_date' => $date instanceof Carbon ? $date->toDateString() : $date,
            'start_time' => sprintf('%02d:00:00', $mulai),
            'end_time' => sprintf('%02d:00:00', $mulai + $durationHours),
            'duration_hours' => $durationHours,
        ]);
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => BookingSource::Online,
            'expired_at' => now()->addMinutes(15),
        ]);
    }

    /**
     * Booking online yang batas bayarnya sudah lewat — sasaran command expire.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => BookingSource::Online,
            'status' => BookingStatus::Pending,
            'expired_at' => now()->subMinute(),
        ]);
    }
}
