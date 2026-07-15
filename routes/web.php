<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Manajemen user internal — dibatasi owner lewat UserPolicy.
    Route::resource('users', UserController::class)->only([
        'index', 'create', 'store', 'edit', 'update',
    ]);
});

require __DIR__.'/settings.php';
