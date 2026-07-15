<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Manajemen user internal — dibatasi owner lewat UserPolicy.
    Route::resource('users', UserController::class)->only([
        'index', 'create', 'store', 'edit', 'update',
    ]);

    // Master cabang — dibatasi owner lewat BranchPolicy.
    Route::resource('branches', BranchController::class)->only([
        'index', 'create', 'store', 'edit', 'update', 'destroy',
    ]);

    // Dropdown wilayah bertingkat untuk form cabang.
    Route::get('regions/cities', [RegionController::class, 'cities'])->name('regions.cities');
    Route::get('regions/districts', [RegionController::class, 'districts'])->name('regions.districts');
});

require __DIR__.'/settings.php';
