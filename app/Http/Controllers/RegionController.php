<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;

/**
 * Endpoint wilayah untuk dropdown bertingkat.
 *
 * Data wilayah terlalu besar untuk dikirim sekaligus sebagai props
 * (514 kota, 7.285 kecamatan), jadi dimuat sesuai pilihan induknya.
 *
 * Kami menyimpan `id` (docs/02-erd.md) sementara laravolt merelasikan lewat
 * `code`, sehingga tiap query anak perlu menerjemahkan id induk ke code dulu.
 */
class RegionController extends Controller
{
    public function cities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'province_id' => ['required', 'integer', 'exists:indonesia_provinces,id'],
        ]);

        $province = Province::select(['id', 'code'])->findOrFail($validated['province_id']);

        $cities = City::query()
            ->select(['id', 'name'])
            ->where('province_code', $province->code)
            ->orderBy('name')
            ->get()
            ->map(fn (City $city): array => ['value' => (int) $city->id, 'label' => $city->name]);

        return response()->json($cities);
    }

    public function districts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:indonesia_cities,id'],
        ]);

        $city = City::select(['id', 'code'])->findOrFail($validated['city_id']);

        $districts = District::query()
            ->select(['id', 'name'])
            ->where('city_code', $city->code)
            ->orderBy('name')
            ->get()
            ->map(fn (District $district): array => ['value' => (int) $district->id, 'label' => $district->name]);

        return response()->json($districts);
    }
}
