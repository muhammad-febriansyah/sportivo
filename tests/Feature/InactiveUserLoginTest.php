<?php

use App\Models\User;

/**
 * User nonaktif tidak bisa login, tapi barisnya tetap ada agar data historis
 * (booking, pembayaran) tetap konsisten. Lihat docs/01-prd.md Modul 1.
 */
test('user aktif bisa login', function () {
    $user = User::factory()->owner()->create([
        'email' => 'owner@sportivo.test',
    ]);

    $this->post(route('login.store'), [
        'email' => 'owner@sportivo.test',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('user nonaktif tidak bisa login', function () {
    User::factory()->owner()->inactive()->create([
        'email' => 'nonaktif@sportivo.test',
    ]);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'nonaktif@sportivo.test',
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('user nonaktif tetap ada di database', function () {
    $user = User::factory()->owner()->inactive()->create();

    expect(User::find($user->id))->not->toBeNull()
        ->and($user->fresh()->is_active)->toBeFalse();
});

test('password salah ditolak untuk user aktif', function () {
    User::factory()->owner()->create(['email' => 'owner@sportivo.test']);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'owner@sportivo.test',
            'password' => 'password-salah',
        ])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
