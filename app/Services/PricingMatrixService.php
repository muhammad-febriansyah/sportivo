<?php

namespace App\Services;

use App\Enums\DayType;
use App\Models\Field;
use Illuminate\Support\Carbon;

/**
 * Menyusun preview matriks harga mingguan (7 hari × jam operasional) agar admin
 * melihat hasil akhir resolusi harga, bukan sekadar daftar rule mentah.
 *
 * Jam operasional yang belum ter-cover rule ditandai agar bisa diwarnai merah —
 * lihat docs/03-user-stories.md US-09.
 */
class PricingMatrixService
{
    public function __construct(private readonly PricingService $pricing) {}

    /**
     * @return array{
     *     hours: array<int, string>,
     *     days: array<int, array{day_type: string, label: string, is_weekend: bool}>,
     *     cells: array<string, array<string, int|null>>,
     *     gaps: int
     * }
     */
    public function build(Field $field): array
    {
        $field->loadMissing(['pricingRules', 'branch']);

        $hours = $this->operatingHours($field);
        $days = [];
        $cells = [];
        $gaps = 0;

        // Minggu acuan: Senin–Minggu. Tanggalnya tidak penting, hanya nama harinya.
        $senin = Carbon::parse('2026-07-13');

        for ($i = 0; $i < 7; $i++) {
            $tanggal = $senin->copy()->addDays($i);
            $dayType = DayType::specificFor($tanggal);

            $days[] = [
                'day_type' => $dayType->value,
                'label' => $dayType->label(),
                'is_weekend' => $tanggal->isWeekend(),
            ];

            $harga = $this->pricing->resolveMany($field, $tanggal, $hours);
            $cells[$dayType->value] = $harga;
            $gaps += count(array_filter($harga, fn (?int $h): bool => $h === null));
        }

        return [
            'hours' => $hours,
            'days' => $days,
            'cells' => $cells,
            'gaps' => $gaps,
        ];
    }

    /**
     * Jam operasional cabang, dibulatkan per jam.
     *
     * Weekday dan weekend bisa berbeda, jadi diambil rentang terluas agar tidak
     * ada jam yang luput dari preview.
     *
     * @return array<int, string>
     */
    private function operatingHours(Field $field): array
    {
        $jam = $field->branch->operating_hours;

        $buka = min(
            $jam['weekday']['open'] ?? '08:00',
            $jam['weekend']['open'] ?? '08:00',
        );
        $tutup = max(
            $jam['weekday']['close'] ?? '23:00',
            $jam['weekend']['close'] ?? '23:00',
        );

        $mulai = (int) substr($buka, 0, 2);
        $selesai = (int) substr($tutup, 0, 2);

        $hasil = [];

        // Slot terakhir dimulai satu jam sebelum tutup — slot berdurasi 1 jam.
        for ($h = $mulai; $h < $selesai; $h++) {
            $hasil[] = sprintf('%02d:00', $h);
        }

        return $hasil;
    }
}
