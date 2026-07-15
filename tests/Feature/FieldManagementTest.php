<?php

use App\Enums\FieldStatus;
use App\Models\Branch;
use App\Models\Field;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Owner mengelola lapangan seluruh cabang; admin hanya cabangnya sendiri.
 * Lihat docs/01-prd.md Modul 3 dan docs/03-user-stories.md US-08.
 */
function dataLapangan(array $ubah = []): array
{
    return array_merge([
        'name' => 'Lapangan A',
        'surface_type' => 'sintetis',
        'size' => '25x15 m',
        'description' => 'Lapangan indoor',
        'status' => 'active',
    ], $ubah);
}

test('tamu diarahkan ke halaman login', function () {
    $this->get(route('fields.index'))->assertRedirect(route('login'));
});

test('owner bisa membuka daftar lapangan', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->get(route('fields.index'))->assertOk();
});

test('admin bisa membuka daftar lapangan', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('fields.index'))->assertOk();
});

test('kasir ditolak mengakses master lapangan', function () {
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($kasir)->get(route('fields.index'))->assertForbidden();
});

/**
 * US-15: user cabang A tidak boleh melihat data cabang B.
 */
test('admin hanya melihat lapangan cabangnya di daftar', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();

    $admin = User::factory()->admin($cabangA)->create();
    $punyaA = Field::factory()->forBranch($cabangA)->create(['name' => 'Lapangan Cabang A']);
    $punyaB = Field::factory()->forBranch($cabangB)->create(['name' => 'Lapangan Cabang B']);

    $this->actingAs($admin)
        ->get(route('fields.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('fields.data', 1)
            ->where('fields.data.0.id', $punyaA->id)
        );
});

test('owner melihat lapangan seluruh cabang', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();

    $owner = User::factory()->owner()->create();
    Field::factory()->forBranch($cabangA)->create();
    Field::factory()->forBranch($cabangB)->create();

    $this->actingAs($owner)
        ->get(route('fields.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('fields.data', 2));
});

test('admin ditolak mengedit lapangan cabang lain', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();

    $admin = User::factory()->admin($cabangA)->create();
    $lapanganB = Field::factory()->forBranch($cabangB)->create();

    $this->actingAs($admin)
        ->get(route('fields.edit', $lapanganB))
        ->assertForbidden();
});

test('admin ditolak memperbarui lapangan cabang lain', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();

    $admin = User::factory()->admin($cabangA)->create();
    $lapanganB = Field::factory()->forBranch($cabangB)->create(['name' => 'Asli']);

    $this->actingAs($admin)
        ->put(route('fields.update', $lapanganB), dataLapangan([
            'branch_id' => $cabangB->id,
            'name' => 'Diretas',
        ]))
        ->assertForbidden();

    expect($lapanganB->fresh()->name)->toBe('Asli');
});

test('admin ditolak menghapus lapangan cabang lain', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();

    $admin = User::factory()->admin($cabangA)->create();
    $lapanganB = Field::factory()->forBranch($cabangB)->create();

    $this->actingAs($admin)
        ->delete(route('fields.destroy', $lapanganB))
        ->assertForbidden();

    expect(Field::find($lapanganB->id))->not->toBeNull();
});

/**
 * Menembus lewat payload: admin mengirim branch_id cabang lain saat create.
 */
test('admin tidak bisa membuat lapangan di cabang lain lewat payload', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();

    $admin = User::factory()->admin($cabangA)->create();

    $this->actingAs($admin)->post(route('fields.store'), dataLapangan([
        'branch_id' => $cabangB->id,
        'name' => 'Selundupan',
    ]))->assertRedirect(route('fields.index'));

    $lapangan = Field::where('name', 'Selundupan')->first();

    // branch_id dipaksa ke cabang admin, bukan cabang yang dikirim.
    expect($lapangan->branch_id)->toBe($cabangA->id)
        ->and($lapangan->branch_id)->not->toBe($cabangB->id);
});

test('admin bisa membuat lapangan di cabangnya sendiri', function () {
    $cabang = Branch::factory()->create();
    $admin = User::factory()->admin($cabang)->create();

    $this->actingAs($admin)->post(route('fields.store'), dataLapangan([
        'branch_id' => $cabang->id,
    ]))->assertRedirect(route('fields.index'));

    expect(Field::where('name', 'Lapangan A')->first()->branch_id)->toBe($cabang->id);
});

test('owner bisa membuat lapangan di cabang mana pun', function () {
    $cabang = Branch::factory()->create();
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->post(route('fields.store'), dataLapangan([
        'branch_id' => $cabang->id,
    ]))->assertRedirect(route('fields.index'));

    expect(Field::where('name', 'Lapangan A')->first()->branch_id)->toBe($cabang->id);
});

test('tipe rumput di luar daftar ditolak', function () {
    $cabang = Branch::factory()->create();
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->post(route('fields.store'), dataLapangan([
        'branch_id' => $cabang->id,
        'surface_type' => 'beton',
    ]))->assertSessionHasErrors('surface_type');
});

test('status di luar daftar ditolak', function () {
    $cabang = Branch::factory()->create();
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->post(route('fields.store'), dataLapangan([
        'branch_id' => $cabang->id,
        'status' => 'rusak',
    ]))->assertSessionHasErrors('status');
});

test('lapangan hanya di-soft-delete', function () {
    $cabang = Branch::factory()->create();
    $owner = User::factory()->owner()->create();
    $lapangan = Field::factory()->forBranch($cabang)->create();

    $this->actingAs($owner)
        ->delete(route('fields.destroy', $lapangan))
        ->assertRedirect(route('fields.index'));

    expect(Field::find($lapangan->id))->toBeNull()
        ->and(Field::withTrashed()->find($lapangan->id))->not->toBeNull();
});

/**
 * US-08: status maintenance menyembunyikan lapangan dari grid publik.
 */
test('scope publiclyBookable hanya mengembalikan lapangan aktif', function () {
    $cabang = Branch::factory()->create();

    $aktif = Field::factory()->forBranch($cabang)->create();
    $maintenance = Field::factory()->forBranch($cabang)->maintenance()->create();
    $nonaktif = Field::factory()->forBranch($cabang)->inactive()->create();

    $terlihat = Field::query()->publiclyBookable()->pluck('id');

    expect($terlihat)->toContain($aktif->id)
        ->not->toContain($maintenance->id)
        ->not->toContain($nonaktif->id);
});

test('lapangan maintenance tetap tampil di daftar internal', function () {
    $cabang = Branch::factory()->create();
    $admin = User::factory()->admin($cabang)->create();
    Field::factory()->forBranch($cabang)->maintenance()->create();

    $this->actingAs($admin)
        ->get(route('fields.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('fields.data', 1));
});

test('filter status menyaring daftar', function () {
    $cabang = Branch::factory()->create();
    $owner = User::factory()->owner()->create();
    Field::factory()->forBranch($cabang)->create();
    $maintenance = Field::factory()->forBranch($cabang)->maintenance()->create();

    $this->actingAs($owner)
        ->get(route('fields.index', ['status' => FieldStatus::Maintenance->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('fields.data', 1)
            ->where('fields.data.0.id', $maintenance->id)
        );
});

test('admin hanya melihat cabangnya sendiri sebagai pilihan', function () {
    $cabangA = Branch::factory()->create();
    Branch::factory()->create();

    $admin = User::factory()->admin($cabangA)->create();

    $this->actingAs($admin)
        ->get(route('fields.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('branches', 1)
            ->where('branches.0.value', $cabangA->id)
            ->where('lockedBranchId', $cabangA->id)
        );
});

test('owner melihat semua cabang sebagai pilihan', function () {
    Branch::factory()->count(2)->create();
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->get(route('fields.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('branches', 2)
            ->where('lockedBranchId', null)
        );
});

test('foto tersimpan dan foto lama dihapus saat diganti', function () {
    Storage::fake('public');
    $cabang = Branch::factory()->create();
    $owner = User::factory()->owner()->create();
    $lapangan = Field::factory()->forBranch($cabang)->create(['photo_path' => 'fields/lama.jpg']);
    Storage::disk('public')->put('fields/lama.jpg', 'isi');

    $this->actingAs($owner)->put(route('fields.update', $lapangan), dataLapangan([
        'branch_id' => $cabang->id,
        'photo' => UploadedFile::fake()->image('baru.jpg'),
    ]))->assertRedirect(route('fields.index'));

    Storage::disk('public')->assertMissing('fields/lama.jpg');
    Storage::disk('public')->assertExists($lapangan->fresh()->photo_path);
});
