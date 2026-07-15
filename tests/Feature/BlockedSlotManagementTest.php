<?php

use App\Enums\BookingStatus;
use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Field;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Blocking slot — docs/01-prd.md Modul 12 dan docs/03-user-stories.md US-10.
 */
beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-07-14 08:00:00'));
    $this->tanggal = '2026-07-15';

    $this->cabangA = Branch::factory()->create();
    $this->cabangB = Branch::factory()->create();
    $this->lapanganA = Field::factory()->forBranch($this->cabangA)->create();
    $this->lapanganB = Field::factory()->forBranch($this->cabangB)->create();
});

afterEach(fn () => Carbon::setTestNow());

function dataBlokir(array $ubah = []): array
{
    return array_merge([
        'branch_id' => test()->cabangA->id,
        'field_id' => test()->lapanganA->id,
        'block_date' => test()->tanggal,
        'start_time' => '08:00',
        'end_time' => '12:00',
        'reason' => 'Maintenance rumput',
    ], $ubah);
}

test('tamu diarahkan ke halaman login', function () {
    $this->get(route('blocked-slots.index'))->assertRedirect(route('login'));
});

test('admin bisa membuka daftar blokir', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    $this->actingAs($admin)->get(route('blocked-slots.index'))->assertOk();
});

/**
 * Kasir tidak menutup lapangan.
 */
test('kasir ditolak mengakses blocking slot', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();

    $this->actingAs($kasir)->get(route('blocked-slots.index'))->assertForbidden();
});

test('kasir ditolak membuat blokir', function () {
    $kasir = User::factory()->kasir($this->cabangA)->create();

    $this->actingAs($kasir)
        ->post(route('blocked-slots.store'), dataBlokir())
        ->assertForbidden();

    expect(BlockedSlot::count())->toBe(0);
});

test('admin bisa memblokir lapangan cabangnya', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    $this->actingAs($admin)
        ->post(route('blocked-slots.store'), dataBlokir())
        ->assertRedirect(route('blocked-slots.index'));

    expect(BlockedSlot::count())->toBe(1)
        ->and(BlockedSlot::first()->created_by)->toBe($admin->id);
});

test('admin bisa memblokir seluruh lapangan cabangnya', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    $this->actingAs($admin)
        ->post(route('blocked-slots.store'), dataBlokir(['field_id' => null]))
        ->assertRedirect(route('blocked-slots.index'));

    expect(BlockedSlot::first()->field_id)->toBeNull();
});

/**
 * Menembus lewat payload.
 */
test('admin tidak bisa memblokir cabang lain', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    $this->actingAs($admin)
        ->post(route('blocked-slots.store'), dataBlokir([
            'branch_id' => $this->cabangB->id,
            'field_id' => $this->lapanganB->id,
        ]))
        ->assertSessionHasErrors('branch_id');

    expect(BlockedSlot::count())->toBe(0);
});

test('admin tidak bisa memblokir lapangan cabang lain di cabangnya sendiri', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    // branch_id benar, tapi field_id milik cabang lain.
    $this->actingAs($admin)
        ->post(route('blocked-slots.store'), dataBlokir([
            'field_id' => $this->lapanganB->id,
        ]))
        ->assertSessionHasErrors('field_id');
});

test('jam selesai harus setelah jam mulai', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    $this->actingAs($admin)
        ->post(route('blocked-slots.store'), dataBlokir([
            'start_time' => '12:00',
            'end_time' => '08:00',
        ]))
        ->assertSessionHasErrors('end_time');
});

test('blokir tanggal lampau ditolak', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    $this->actingAs($admin)
        ->post(route('blocked-slots.store'), dataBlokir([
            'block_date' => '2026-07-01',
        ]))
        ->assertSessionHasErrors('block_date');
});

/**
 * Aturan inti US-10: slot dengan booking aktif tidak boleh diblokir, dan
 * daftar bentroknya harus disebut agar admin tahu apa yang harus dibereskan.
 */
test('blokir ditolak bila ada booking aktif di rentang tersebut', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    Booking::factory()->forField($this->lapanganA)
        ->on($this->tanggal, '09:00')
        ->status(BookingStatus::Paid)
        ->create(['code' => 'SPV-260715-TEST', 'customer_name' => 'Budi']);

    $response = $this->actingAs($admin)
        ->post(route('blocked-slots.store'), dataBlokir())
        ->assertSessionHasErrors('start_time');

    expect(BlockedSlot::count())->toBe(0);

    $errors = session('errors')->get('start_time');
    expect($errors[0])->toContain('SPV-260715-TEST')
        ->and($errors[0])->toContain('Budi');
});

test('blokir diterima bila booking sudah dibatalkan', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    Booking::factory()->forField($this->lapanganA)
        ->on($this->tanggal, '09:00')
        ->status(BookingStatus::Cancelled)
        ->create();

    $this->actingAs($admin)
        ->post(route('blocked-slots.store'), dataBlokir())
        ->assertRedirect(route('blocked-slots.index'));

    expect(BlockedSlot::count())->toBe(1);
});

test('blokir diterima bila booking di luar rentang jam', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    Booking::factory()->forField($this->lapanganA)
        ->on($this->tanggal, '19:00')
        ->status(BookingStatus::Paid)
        ->create();

    $this->actingAs($admin)
        ->post(route('blocked-slots.store'), dataBlokir())
        ->assertRedirect(route('blocked-slots.index'));
});

test('blokir diterima bila booking di lapangan lain', function () {
    $admin = User::factory()->admin($this->cabangA)->create();
    $lapanganLain = Field::factory()->forBranch($this->cabangA)->create();

    Booking::factory()->forField($lapanganLain)
        ->on($this->tanggal, '09:00')
        ->status(BookingStatus::Paid)
        ->create();

    $this->actingAs($admin)
        ->post(route('blocked-slots.store'), dataBlokir())
        ->assertRedirect(route('blocked-slots.index'));
});

/**
 * Blokir se-cabang harus memeriksa booking di SEMUA lapangan cabang tersebut.
 */
test('blokir se-cabang ditolak bila ada booking di salah satu lapangan', function () {
    $admin = User::factory()->admin($this->cabangA)->create();
    $lapanganLain = Field::factory()->forBranch($this->cabangA)->create();

    Booking::factory()->forField($lapanganLain)
        ->on($this->tanggal, '09:00')
        ->status(BookingStatus::Paid)
        ->create();

    $this->actingAs($admin)
        ->post(route('blocked-slots.store'), dataBlokir(['field_id' => null]))
        ->assertSessionHasErrors('start_time');

    expect(BlockedSlot::count())->toBe(0);
});

test('admin hanya melihat blokir cabangnya', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    $punyaA = BlockedSlot::factory()->forField($this->lapanganA)
        ->on($this->tanggal)->create();
    BlockedSlot::factory()->forField($this->lapanganB)
        ->on($this->tanggal)->create();

    $this->actingAs($admin)
        ->get(route('blocked-slots.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('blocks.data', 1)
            ->where('blocks.data.0.id', $punyaA->id)
        );
});

test('blokir yang sudah lewat tidak ditampilkan', function () {
    $admin = User::factory()->admin($this->cabangA)->create();

    BlockedSlot::factory()->forField($this->lapanganA)
        ->on('2026-07-01')->create();
    BlockedSlot::factory()->forField($this->lapanganA)
        ->on($this->tanggal)->create();

    $this->actingAs($admin)
        ->get(route('blocked-slots.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('blocks.data', 1));
});

test('admin ditolak menghapus blokir cabang lain', function () {
    $admin = User::factory()->admin($this->cabangA)->create();
    $blokirB = BlockedSlot::factory()->forField($this->lapanganB)
        ->on($this->tanggal)->create();

    $this->actingAs($admin)
        ->delete(route('blocked-slots.destroy', $blokirB))
        ->assertForbidden();

    expect(BlockedSlot::find($blokirB->id))->not->toBeNull();
});

test('admin bisa menghapus blokir cabangnya', function () {
    $admin = User::factory()->admin($this->cabangA)->create();
    $blokir = BlockedSlot::factory()->forField($this->lapanganA)
        ->on($this->tanggal)->create();

    $this->actingAs($admin)
        ->delete(route('blocked-slots.destroy', $blokir))
        ->assertRedirect(route('blocked-slots.index'));

    expect(BlockedSlot::find($blokir->id))->toBeNull();
});
