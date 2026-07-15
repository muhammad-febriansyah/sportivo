<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Exceptions\AddonUnavailableException;
use App\Exceptions\PriceNotConfiguredException;
use App\Exceptions\SlotUnavailableException;
use App\Models\Addon;
use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Field;
use App\Services\PricingService;
use Illuminate\Support\Facades\DB;

/**
 * Pembuatan booking — titik paling kritikal di sistem ini.
 *
 * Seluruh perhitungan harga berasal dari PricingService; tidak ada harga yang
 * dikirim dari klien. Lihat docs/01-prd.md Modul 6 dan docs/03-user-stories.md US-16.
 */
class CreateBookingAction
{
    public function __construct(private readonly PricingService $pricing) {}

    /**
     * @throws SlotUnavailableException
     * @throws PriceNotConfiguredException
     */
    public function execute(CreateBookingData $data): Booking
    {
        $field = Field::with('branch.setting')->findOrFail($data->fieldId);
        $customer = Customer::findOrFail($data->customerId);

        $endTime = $data->endTime();

        return DB::transaction(function () use ($data, $field, $customer, $endTime): Booking {
            // Kunci baris booking yang bentrok SEBELUM memeriksa.
            //
            // lockForUpdate menahan transaksi lain yang menyentuh baris yang sama
            // sampai transaksi ini selesai. Tanpa itu dua request bersamaan bisa
            // sama-sama lolos pemeriksaan dan menghasilkan double booking.
            //
            // CATATAN: lockForUpdate adalah no-op di SQLite — karena itu aplikasi
            // dan test wajib memakai MySQL (lihat phpunit.xml dan README).
            $bentrok = Booking::query()
                ->conflictingWith($field->id, $data->date, $data->startTime, $endTime)
                ->lockForUpdate()
                ->exists();

            if ($bentrok) {
                throw SlotUnavailableException::make();
            }

            // Slot yang diblokir admin tidak boleh dibooking. Penandaan abu di
            // grid saja tidak cukup: form bisa disubmit langsung, dan blokir
            // bisa dibuat setelah halaman grid dimuat. Lihat docs/01-prd.md Modul 12.
            $blokir = BlockedSlot::query()
                ->where('branch_id', $field->branch_id)
                // field_id null = blokir seluruh lapangan di cabang tersebut.
                ->where(fn ($q) => $q->whereNull('field_id')->orWhere('field_id', $field->id))
                ->overlapping($data->date, $data->startTime, $endTime)
                ->first();

            if ($blokir !== null) {
                throw SlotUnavailableException::blocked($blokir->reason);
            }

            $isMember = $customer->isActiveMember($data->date);

            // Harga selalu dari mesin harga — kasir tidak pernah mengetik harga.
            // Slot tanpa harga melempar PriceNotConfiguredException, bukan 0.
            $hargaPerJam = $this->pricing->resolve(
                $field,
                $data->date,
                $data->startTime,
                $isMember,
            );

            $subtotalLapangan = $hargaPerJam * $data->durationHours;

            // Harga add-on juga diambil server dari master, tidak dari klien.
            $addons = $this->resolveAddons($data, $field->branch_id);
            $subtotalAddons = array_sum(array_column($addons, 'subtotal'));

            $total = $subtotalLapangan + $subtotalAddons;

            $dp = $this->resolveDpAmount($data, $field, $total);
            $holdMenit = $field->branch->setting?->online_hold_minutes ?? 15;

            $booking = Booking::create([
                'code' => $this->uniqueCode($data),
                'branch_id' => $field->branch_id,
                'field_id' => $field->id,
                'customer_id' => $customer->id,

                'booking_date' => $data->date->toDateString(),
                'start_time' => $data->startTime,
                'end_time' => $endTime,
                'duration_hours' => $data->durationHours,

                // Snapshot: booking historis tidak boleh berubah saat master
                // data diedit (docs/02-erd.md).
                'branch_name' => $field->branch->name,
                'field_name' => $field->name,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'price_per_hour' => $hargaPerJam,
                'is_member_price' => $isMember,

                'subtotal_field' => $subtotalLapangan,
                'subtotal_addons' => $subtotalAddons,
                'total' => $total,
                'dp_amount' => $dp,
                'paid_amount' => 0,

                'status' => BookingStatus::Pending,
                'source' => $data->source,
                // Hanya booking online yang punya batas waktu bayar.
                'expired_at' => $data->source->expiresWhenUnpaid()
                    ? now()->addMinutes($holdMenit)
                    : null,
                'created_by' => $data->createdBy,
            ]);

            if ($addons !== []) {
                $booking->addons()->createMany($addons);
            }

            return $booking;
        });
    }

    /**
     * Baris add-on beserta snapshot nama & harganya.
     *
     * Add-on wajib milik cabang yang sama dan berstatus aktif — tanpa itu,
     * mengirim addon_id cabang lain di payload akan menambah tagihan dengan
     * barang yang tidak ada di cabang tersebut.
     *
     * @return array<int, array{addon_id: int, addon_name: string, addon_price: int, qty: int, subtotal: int}>
     *
     * @throws AddonUnavailableException
     */
    private function resolveAddons(CreateBookingData $data, int $branchId): array
    {
        if ($data->addons === []) {
            return [];
        }

        $master = Addon::query()
            ->whereIn('id', array_keys($data->addons))
            ->where('branch_id', $branchId)
            ->active()
            ->get()
            ->keyBy('id');

        $hasil = [];

        foreach ($data->addons as $addonId => $qty) {
            $addon = $master->get($addonId);

            if ($addon === null) {
                throw AddonUnavailableException::notFound($addonId);
            }

            if ($qty < 1) {
                continue;
            }

            if (! $addon->hasStockFor($qty)) {
                throw AddonUnavailableException::outOfStock($addon->name, $addon->stock ?? 0);
            }

            $hasil[] = [
                'addon_id' => $addon->id,
                // Snapshot — perubahan master tidak mengubah tagihan booking ini.
                'addon_name' => $addon->name,
                'addon_price' => $addon->price,
                'qty' => $qty,
                'subtotal' => $addon->price * $qty,
            ];
        }

        return $hasil;
    }

    /**
     * DP mengikuti pengaturan cabang bila tidak ditentukan.
     */
    private function resolveDpAmount(CreateBookingData $data, Field $field, int $total): int
    {
        if ($data->payFull) {
            return $total;
        }

        if ($data->dpAmount !== null) {
            return min($data->dpAmount, $total);
        }

        $persen = $field->branch->setting?->dp_percentage ?? 50;

        return (int) round($total * $persen / 100);
    }

    /**
     * Kode booking acak; diulang bila bertabrakan dengan kode yang sudah ada.
     */
    private function uniqueCode(CreateBookingData $data): string
    {
        do {
            $code = Booking::generateCode($data->date);
        } while (Booking::where('code', $code)->exists());

        return $code;
    }
}
