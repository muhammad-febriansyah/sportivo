<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;

trait BranchValidationRules
{
    /**
     * Aturan yang sama untuk tambah maupun ubah cabang.
     * Kolom `code` diatur terpisah karena aturan unique-nya berbeda.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function branchRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'phone' => ['required', 'string', 'max:20'],

            'province_id' => ['nullable', 'integer', 'exists:indonesia_provinces,id'],
            'city_id' => ['nullable', 'integer', 'exists:indonesia_cities,id'],
            'district_id' => ['nullable', 'integer', 'exists:indonesia_districts,id'],

            'operating_hours' => ['required', 'array'],
            'operating_hours.weekday.open' => ['required', 'date_format:H:i'],
            'operating_hours.weekday.close' => ['required', 'date_format:H:i', 'after:operating_hours.weekday.open'],
            'operating_hours.weekend.open' => ['required', 'date_format:H:i'],
            'operating_hours.weekend.close' => ['required', 'date_format:H:i', 'after:operating_hours.weekend.open'],

            'photo' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function branchAttributes(): array
    {
        return [
            'name' => 'nama cabang',
            'code' => 'kode cabang',
            'address' => 'alamat',
            'phone' => 'nomor telepon',
            'province_id' => 'provinsi',
            'city_id' => 'kota/kabupaten',
            'district_id' => 'kecamatan',
            'operating_hours.weekday.open' => 'jam buka weekday',
            'operating_hours.weekday.close' => 'jam tutup weekday',
            'operating_hours.weekend.open' => 'jam buka weekend',
            'operating_hours.weekend.close' => 'jam tutup weekend',
            'photo' => 'foto',
        ];
    }

    /**
     * Wilayah harus konsisten: kota berada di provinsi terpilih, kecamatan berada
     * di kota terpilih.
     *
     * Pemeriksaan ini perlu lookup tambahan karena kita menyimpan `id` sesuai
     * docs/02-erd.md, sementara laravolt merelasikan wilayah lewat `code`.
     */
    protected function validateRegionCascade(Validator $validator): void
    {
        $provinceId = $this->input('province_id');
        $cityId = $this->input('city_id');
        $districtId = $this->input('district_id');

        if ($cityId && $provinceId) {
            $city = City::find($cityId);
            $province = Province::find($provinceId);

            if ($city && $province && $city->province_code !== $province->code) {
                $validator->errors()->add('city_id', 'Kota/kabupaten tidak berada di provinsi yang dipilih.');
            }
        }

        if ($districtId && $cityId) {
            $district = District::find($districtId);
            $city = City::find($cityId);

            if ($district && $city && $district->city_code !== $city->code) {
                $validator->errors()->add('district_id', 'Kecamatan tidak berada di kota/kabupaten yang dipilih.');
            }
        }

        // Hierarki tidak boleh bolong: kecamatan tanpa kota, atau kota tanpa provinsi.
        if ($cityId && ! $provinceId) {
            $validator->errors()->add('province_id', 'Provinsi wajib dipilih bila kota/kabupaten diisi.');
        }

        if ($districtId && ! $cityId) {
            $validator->errors()->add('city_id', 'Kota/kabupaten wajib dipilih bila kecamatan diisi.');
        }
    }
}
