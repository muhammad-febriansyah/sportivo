<?php

use App\Models\Branch;
use App\Models\User;

/**
 * Hanya owner yang boleh mengakses manajemen user.
 * Lihat docs/01-prd.md Modul 1 dan docs/03-user-stories.md US-15.
 */
test('tamu diarahkan ke halaman login', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('owner bisa membuka daftar user', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->get(route('users.index'))->assertOk();
});

test('admin ditolak mengakses manajemen user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('users.index'))->assertForbidden();
});

test('kasir ditolak mengakses manajemen user', function () {
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($kasir)->get(route('users.index'))->assertForbidden();
});

test('admin ditolak membuat user baru', function () {
    $admin = User::factory()->admin()->create();
    $cabang = Branch::factory()->create();

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Kasir Baru',
        'email' => 'kasir.baru@sportivo.test',
        'role' => 'kasir',
        'branch_id' => $cabang->id,
        'password' => 'KataSandi#2026',
        'password_confirmation' => 'KataSandi#2026',
    ])->assertForbidden();

    expect(User::where('email', 'kasir.baru@sportivo.test')->exists())->toBeFalse();
});

test('owner bisa membuat kasir dengan cabang', function () {
    $owner = User::factory()->owner()->create();
    $cabang = Branch::factory()->create();

    $this->actingAs($owner)->post(route('users.store'), [
        'name' => 'Kasir Baru',
        'email' => 'kasir.baru@sportivo.test',
        'phone' => '628123456789',
        'role' => 'kasir',
        'branch_id' => $cabang->id,
        'password' => 'KataSandi#2026',
        'password_confirmation' => 'KataSandi#2026',
    ])->assertRedirect(route('users.index'));

    $kasir = User::where('email', 'kasir.baru@sportivo.test')->first();

    expect($kasir)->not->toBeNull()
        ->and($kasir->branch_id)->toBe($cabang->id)
        ->and($kasir->hasRole('kasir'))->toBeTrue()
        ->and($kasir->is_active)->toBeTrue();
});

test('admin dan kasir wajib punya cabang', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->post(route('users.store'), [
        'name' => 'Admin Tanpa Cabang',
        'email' => 'admin.tanpa@sportivo.test',
        'role' => 'admin',
        'branch_id' => null,
        'password' => 'KataSandi#2026',
        'password_confirmation' => 'KataSandi#2026',
    ])->assertSessionHasErrors('branch_id');

    expect(User::where('email', 'admin.tanpa@sportivo.test')->exists())->toBeFalse();
});

test('cabang diabaikan saat membuat owner', function () {
    $owner = User::factory()->owner()->create();
    $cabang = Branch::factory()->create();

    $this->actingAs($owner)->post(route('users.store'), [
        'name' => 'Owner Kedua',
        'email' => 'owner2@sportivo.test',
        'role' => 'owner',
        'branch_id' => $cabang->id,
        'password' => 'KataSandi#2026',
        'password_confirmation' => 'KataSandi#2026',
    ])->assertRedirect(route('users.index'));

    expect(User::where('email', 'owner2@sportivo.test')->first()->branch_id)->toBeNull();
});

test('owner tidak bisa menonaktifkan akunnya sendiri', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->put(route('users.update', $owner), [
        'name' => $owner->name,
        'email' => $owner->email,
        'role' => 'owner',
        'is_active' => false,
    ])->assertSessionHasErrors('is_active');

    expect($owner->fresh()->is_active)->toBeTrue();
});

test('owner bisa menonaktifkan user lain', function () {
    $owner = User::factory()->owner()->create();
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($owner)->put(route('users.update', $kasir), [
        'name' => $kasir->name,
        'email' => $kasir->email,
        'role' => 'kasir',
        'branch_id' => $kasir->branch_id,
        'is_active' => false,
    ])->assertRedirect(route('users.index'));

    expect($kasir->fresh()->is_active)->toBeFalse();
});

test('kata sandi lama dipertahankan bila form dikosongkan', function () {
    $owner = User::factory()->owner()->create();
    $kasir = User::factory()->kasir()->create();
    $sandiLama = $kasir->password;

    $this->actingAs($owner)->put(route('users.update', $kasir), [
        'name' => 'Nama Diperbarui',
        'email' => $kasir->email,
        'role' => 'kasir',
        'branch_id' => $kasir->branch_id,
        'is_active' => true,
        'password' => '',
    ])->assertRedirect(route('users.index'));

    expect($kasir->fresh()->password)->toBe($sandiLama)
        ->and($kasir->fresh()->name)->toBe('Nama Diperbarui');
});

test('kata sandi berubah bila form diisi', function () {
    $owner = User::factory()->owner()->create();
    $kasir = User::factory()->kasir()->create();
    $sandiLama = $kasir->password;

    $this->actingAs($owner)->put(route('users.update', $kasir), [
        'name' => $kasir->name,
        'email' => $kasir->email,
        'role' => 'kasir',
        'branch_id' => $kasir->branch_id,
        'is_active' => true,
        'password' => 'SandiBaru#2026',
        'password_confirmation' => 'SandiBaru#2026',
    ])->assertRedirect(route('users.index'));

    expect($kasir->fresh()->password)->not->toBe($sandiLama);
});

test('email harus unik', function () {
    $owner = User::factory()->owner()->create();
    $adaDuluan = User::factory()->kasir()->create();

    $this->actingAs($owner)->post(route('users.store'), [
        'name' => 'Bentrok',
        'email' => $adaDuluan->email,
        'role' => 'owner',
        'password' => 'KataSandi#2026',
        'password_confirmation' => 'KataSandi#2026',
    ])->assertSessionHasErrors('email');
});

test('mengubah role menggantikan role lama, bukan menumpuk', function () {
    $owner = User::factory()->owner()->create();
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($owner)->put(route('users.update', $kasir), [
        'name' => $kasir->name,
        'email' => $kasir->email,
        'role' => 'admin',
        'branch_id' => $kasir->branch_id,
        'is_active' => true,
    ])->assertRedirect(route('users.index'));

    $kasir->refresh();

    expect($kasir->hasRole('admin'))->toBeTrue()
        ->and($kasir->hasRole('kasir'))->toBeFalse()
        ->and($kasir->roles)->toHaveCount(1);
});
