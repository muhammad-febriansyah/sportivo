<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Master cabang hanya untuk owner. Lihat docs/01-prd.md Modul 2.
 *
 * Data wilayah dibuat minimal di sini — menyeed laravolt penuh (83 ribu desa)
 * di tiap test terlalu lambat.
 */
function buatWilayah(): array
{
    DB::table('indonesia_provinces')->insert([
        ['id' => 901, 'code' => '91', 'name' => 'PROVINSI UJI'],
        ['id' => 902, 'code' => '92', 'name' => 'PROVINSI LAIN'],
    ]);

    DB::table('indonesia_cities')->insert([
        ['id' => 9101, 'code' => '9101', 'province_code' => '91', 'name' => 'KOTA UJI'],
        ['id' => 9201, 'code' => '9201', 'province_code' => '92', 'name' => 'KOTA LAIN'],
    ]);

    DB::table('indonesia_districts')->insert([
        ['id' => 910101, 'code' => '9101010', 'city_code' => '9101', 'name' => 'KECAMATAN UJI'],
        ['id' => 920101, 'code' => '9201010', 'city_code' => '9201', 'name' => 'KECAMATAN LAIN'],
    ]);

    return [
        'provinsi' => 901,
        'provinsiLain' => 902,
        'kota' => 9101,
        'kotaLain' => 9201,
        'kecamatan' => 910101,
        'kecamatanLain' => 920101,
    ];
}

function dataCabang(array $ubah = []): array
{
    return array_merge([
        'name' => 'Sportivo Uji',
        'code' => 'uji01',
        'address' => 'Jl. Uji No. 1',
        'phone' => '0211234567',
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '23:00'],
            'weekend' => ['open' => '07:00', 'close' => '23:00'],
        ],
        'is_active' => true,
    ], $ubah);
}

test('tamu diarahkan ke halaman login', function () {
    $this->get(route('branches.index'))->assertRedirect(route('login'));
});

test('owner bisa membuka daftar cabang', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->get(route('branches.index'))->assertOk();
});

test('admin ditolak mengakses master cabang', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('branches.index'))->assertForbidden();
});

test('kasir ditolak mengakses master cabang', function () {
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($kasir)->get(route('branches.index'))->assertForbidden();
});

test('admin ditolak membuat cabang', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('branches.store'), dataCabang())
        ->assertForbidden();

    expect(Branch::where('code', 'UJI01')->exists())->toBeFalse();
});

test('owner bisa membuat cabang dan pengaturannya ikut terbuat', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->post(route('branches.store'), dataCabang())
        ->assertRedirect(route('branches.index'));

    $cabang = Branch::where('code', 'UJI01')->first();

    expect($cabang)->not->toBeNull()
        ->and($cabang->operating_hours['weekday']['open'])->toBe('08:00')
        ->and($cabang->setting)->not->toBeNull()
        ->and($cabang->setting->dp_percentage)->toBe(50)
        ->and($cabang->setting->online_hold_minutes)->toBe(15);
});

test('kode cabang disimpan huruf besar', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->post(route('branches.store'), dataCabang(['code' => 'jkt01']));

    expect(Branch::where('code', 'JKT01')->exists())->toBeTrue();
});

test('kode cabang harus unik', function () {
    $owner = User::factory()->owner()->create();
    Branch::factory()->create(['code' => 'JKT01']);

    $this->actingAs($owner)
        ->post(route('branches.store'), dataCabang(['code' => 'JKT01']))
        ->assertSessionHasErrors('code');
});

test('jam tutup harus setelah jam buka', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->post(route('branches.store'), dataCabang([
        'operating_hours' => [
            'weekday' => ['open' => '23:00', 'close' => '08:00'],
            'weekend' => ['open' => '08:00', 'close' => '23:00'],
        ],
    ]))->assertSessionHasErrors('operating_hours.weekday.close');
});

test('kota harus berada di provinsi yang dipilih', function () {
    $owner = User::factory()->owner()->create();
    $wilayah = buatWilayah();

    $this->actingAs($owner)->post(route('branches.store'), dataCabang([
        'province_id' => $wilayah['provinsi'],
        'city_id' => $wilayah['kotaLain'],
    ]))->assertSessionHasErrors('city_id');
});

test('kecamatan harus berada di kota yang dipilih', function () {
    $owner = User::factory()->owner()->create();
    $wilayah = buatWilayah();

    $this->actingAs($owner)->post(route('branches.store'), dataCabang([
        'province_id' => $wilayah['provinsi'],
        'city_id' => $wilayah['kota'],
        'district_id' => $wilayah['kecamatanLain'],
    ]))->assertSessionHasErrors('district_id');
});

test('wilayah yang konsisten diterima', function () {
    $owner = User::factory()->owner()->create();
    $wilayah = buatWilayah();

    $this->actingAs($owner)->post(route('branches.store'), dataCabang([
        'province_id' => $wilayah['provinsi'],
        'city_id' => $wilayah['kota'],
        'district_id' => $wilayah['kecamatan'],
    ]))->assertRedirect(route('branches.index'));

    $cabang = Branch::where('code', 'UJI01')->first();

    expect($cabang->city_id)->toBe($wilayah['kota'])
        ->and($cabang->district_id)->toBe($wilayah['kecamatan']);
});

test('kota tanpa provinsi ditolak', function () {
    $owner = User::factory()->owner()->create();
    $wilayah = buatWilayah();

    $this->actingAs($owner)->post(route('branches.store'), dataCabang([
        'city_id' => $wilayah['kota'],
    ]))->assertSessionHasErrors('province_id');
});

test('cabang dengan user tidak bisa dihapus', function () {
    $owner = User::factory()->owner()->create();
    $cabang = Branch::factory()->create();
    User::factory()->kasir($cabang)->create();

    $this->actingAs($owner)->delete(route('branches.destroy', $cabang));

    expect($cabang->fresh())->not->toBeNull()
        ->and($cabang->fresh()->deleted_at)->toBeNull();
});

test('cabang tanpa user bisa dihapus dan hanya di-soft-delete', function () {
    $owner = User::factory()->owner()->create();
    $cabang = Branch::factory()->create();

    $this->actingAs($owner)
        ->delete(route('branches.destroy', $cabang))
        ->assertRedirect(route('branches.index'));

    expect(Branch::find($cabang->id))->toBeNull()
        ->and(Branch::withTrashed()->find($cabang->id))->not->toBeNull();
});

test('foto tersimpan saat cabang dibuat', function () {
    Storage::fake('public');
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->post(route('branches.store'), dataCabang([
        'photo' => UploadedFile::fake()->image('cabang.jpg'),
    ]))->assertRedirect(route('branches.index'));

    $cabang = Branch::where('code', 'UJI01')->first();

    expect($cabang->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($cabang->photo_path);
});

test('foto lama dihapus saat diganti', function () {
    Storage::fake('public');
    $owner = User::factory()->owner()->create();
    $cabang = Branch::factory()->create(['photo_path' => 'branches/lama.jpg']);
    Storage::disk('public')->put('branches/lama.jpg', 'isi');

    $this->actingAs($owner)->put(route('branches.update', $cabang), dataCabang([
        'code' => $cabang->code,
        'photo' => UploadedFile::fake()->image('baru.jpg'),
    ]))->assertRedirect(route('branches.index'));

    Storage::disk('public')->assertMissing('branches/lama.jpg');
    expect($cabang->fresh()->photo_path)->not->toBe('branches/lama.jpg');
});

test('endpoint kota hanya mengembalikan kota di provinsi tersebut', function () {
    $owner = User::factory()->owner()->create();
    $wilayah = buatWilayah();

    $this->actingAs($owner)
        ->getJson(route('regions.cities', ['province_id' => $wilayah['provinsi']]))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['value' => $wilayah['kota'], 'label' => 'KOTA UJI']);
});

test('endpoint kecamatan hanya mengembalikan kecamatan di kota tersebut', function () {
    $owner = User::factory()->owner()->create();
    $wilayah = buatWilayah();

    $this->actingAs($owner)
        ->getJson(route('regions.districts', ['city_id' => $wilayah['kota']]))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['value' => $wilayah['kecamatan'], 'label' => 'KECAMATAN UJI']);
});
