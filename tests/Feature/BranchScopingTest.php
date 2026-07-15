<?php

use App\Models\Branch;
use App\Models\User;

/**
 * Scope `visibleTo` adalah fondasi pembatasan data per cabang yang dipakai
 * seluruh modul berikutnya. Lihat docs/05-tech-conventions.md bagian Authorization.
 */
test('owner melihat data seluruh cabang', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();

    $owner = User::factory()->owner()->create();
    User::factory()->admin($cabangA)->create();
    User::factory()->kasir($cabangB)->create();

    $terlihat = User::query()->visibleTo($owner)->pluck('id');

    expect($terlihat)->toHaveCount(3);
});

test('admin hanya melihat data cabangnya sendiri', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();

    $adminA = User::factory()->admin($cabangA)->create();
    $kasirA = User::factory()->kasir($cabangA)->create();
    $adminB = User::factory()->admin($cabangB)->create();

    $terlihat = User::query()->visibleTo($adminA)->pluck('id');

    expect($terlihat)->toContain($adminA->id)
        ->toContain($kasirA->id)
        ->not->toContain($adminB->id);
});

test('kasir tidak melihat data cabang lain', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();

    $kasirA = User::factory()->kasir($cabangA)->create();
    $kasirB = User::factory()->kasir($cabangB)->create();

    $terlihat = User::query()->visibleTo($kasirA)->pluck('id');

    expect($terlihat)->not->toContain($kasirB->id);
});

/**
 * Tanpa penjagaan eksplisit, `where branch_id = null` justru cocok dengan
 * baris ber-branch_id NULL (yaitu para owner) — itu kebocoran data.
 */
test('non-owner tanpa cabang tidak melihat data apa pun', function () {
    $cabang = Branch::factory()->create();

    User::factory()->owner()->create();
    User::factory()->admin($cabang)->create();

    $adminTanpaCabang = User::factory()->create(['branch_id' => null]);
    $adminTanpaCabang->assignRole('admin');

    $terlihat = User::query()->visibleTo($adminTanpaCabang)->pluck('id');

    expect($terlihat)->toBeEmpty();
});
