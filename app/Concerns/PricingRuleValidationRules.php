<?php

namespace App\Concerns;

use App\Enums\DayType;
use App\Models\PricingRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait PricingRuleValidationRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function pricingRuleRules(): array
    {
        return [
            'day_type' => ['required', Rule::enum(DayType::class)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            // Rupiah selalu integer tanpa desimal.
            'price' => ['required', 'integer', 'min:0'],
            'member_price' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function pricingRuleAttributes(): array
    {
        return [
            'day_type' => 'tipe hari',
            'start_time' => 'jam mulai',
            'end_time' => 'jam selesai',
            'price' => 'harga',
            'member_price' => 'harga member',
        ];
    }

    /**
     * Rentang waktu tidak boleh tumpang tindih untuk lapangan + tipe hari yang sama.
     *
     * Tanpa aturan ini dua rule bisa menampung jam yang sama dan harga menjadi
     * ambigu — pelanggan bisa ditagih berbeda tergantung urutan baris di database.
     * Lihat docs/01-prd.md Modul 4.
     */
    protected function validateNoOverlap(Validator $validator, int $fieldId, ?int $ignoreRuleId = null): void
    {
        $start = $this->input('start_time');
        $end = $this->input('end_time');
        $dayType = $this->input('day_type');

        if (! $start || ! $end || ! $dayType) {
            return;
        }

        $bentrok = PricingRule::query()
            ->where('field_id', $fieldId)
            ->where('day_type', $dayType)
            ->when($ignoreRuleId, fn ($query, int $id) => $query->whereKeyNot($id))
            // Dua rentang bertabrakan bila yang satu mulai sebelum yang lain selesai,
            // DAN selesai setelah yang lain mulai.
            ->where('start_time', '<', PricingRule::normalizeTime($end))
            ->where('end_time', '>', PricingRule::normalizeTime($start))
            ->first();

        if ($bentrok !== null) {
            $validator->errors()->add('start_time', sprintf(
                'Rentang jam bertabrakan dengan aturan %s–%s yang sudah ada untuk tipe hari yang sama.',
                substr($bentrok->start_time, 0, 5),
                substr($bentrok->end_time, 0, 5),
            ));
        }
    }
}
