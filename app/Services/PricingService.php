<?php

namespace App\Services;

use App\Enums\DayType;
use App\Exceptions\PriceNotConfiguredException;
use App\Models\Field;
use App\Models\PricingRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Mesin harga. SEMUA harga booking berasal dari sini — kasir tidak pernah
 * mengetik harga. Lihat docs/01-prd.md Modul 4.
 */
class PricingService
{
    /**
     * Harga per jam (Rupiah) untuk sebuah slot.
     *
     * Urutan resolusi: rule hari spesifik lebih dulu, baru weekday/weekend.
     * Tidak ada rule yang menampung jam tersebut = exception, bukan harga 0 —
     * slot tanpa harga tidak boleh dibooking.
     *
     * @throws PriceNotConfiguredException
     */
    public function resolve(Field $field, Carbon $date, string $startTime, bool $isMember = false): int
    {
        $rule = $this->resolveRule($field, $date, $startTime);

        if ($rule === null) {
            throw PriceNotConfiguredException::for($field, $date, $startTime);
        }

        return $rule->priceFor($isMember);
    }

    /**
     * Sama seperti resolve(), tapi mengembalikan null alih-alih exception.
     * Dipakai grid yang perlu menandai slot "harga belum diatur".
     */
    public function resolveOrNull(Field $field, Carbon $date, string $startTime, bool $isMember = false): ?int
    {
        $rule = $this->resolveRule($field, $date, $startTime);

        return $rule?->priceFor($isMember);
    }

    /**
     * Rule yang berlaku untuk slot tersebut, atau null bila tidak ada.
     */
    public function resolveRule(Field $field, Carbon $date, string $startTime): ?PricingRule
    {
        $rules = $this->rulesFor($field);

        return $this->pickRule($rules, $date, $startTime);
    }

    /**
     * Resolusi harga untuk banyak slot sekaligus tanpa query berulang.
     *
     * Grid availability wajib memakai ini — dilarang query per slot
     * (docs/05-tech-conventions.md bagian Performa).
     *
     * @param  array<int, string>  $startTimes
     * @return array<string, int|null> jam => harga (null bila belum diatur)
     */
    public function resolveMany(Field $field, Carbon $date, array $startTimes, bool $isMember = false): array
    {
        $rules = $this->rulesFor($field);
        $hasil = [];

        foreach ($startTimes as $jam) {
            $rule = $this->pickRule($rules, $date, $jam);
            $hasil[$jam] = $rule?->priceFor($isMember);
        }

        return $hasil;
    }

    /**
     * Aturan harga milik lapangan. Memakai relasi yang sudah di-eager-load bila
     * tersedia, agar pemanggilan dari grid tidak memicu query tambahan.
     *
     * @return Collection<int, PricingRule>
     */
    private function rulesFor(Field $field): Collection
    {
        if ($field->relationLoaded('pricingRules')) {
            return $field->pricingRules;
        }

        return $field->pricingRules()->get();
    }

    /**
     * Hari spesifik menang atas weekday/weekend.
     *
     * @param  Collection<int, PricingRule>  $rules
     */
    private function pickRule(Collection $rules, Carbon $date, string $startTime): ?PricingRule
    {
        $spesifik = DayType::specificFor($date);
        $umum = DayType::generalFor($date);

        $cocok = $rules->filter(fn (PricingRule $rule): bool => $rule->covers($startTime));

        return $cocok->firstWhere('day_type', $spesifik)
            ?? $cocok->firstWhere('day_type', $umum);
    }
}
