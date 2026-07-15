<?php

use App\Http\Controllers\AddonController;
use App\Http\Controllers\BlockedSlotController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\PricingRuleController;
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

    // Master lapangan — owner semua cabang, admin hanya cabangnya (FieldPolicy).
    Route::resource('fields', FieldController::class)->only([
        'index', 'create', 'store', 'edit', 'update', 'destroy',
    ]);

    // Pengaturan harga per lapangan — izinnya mengikuti lapangan (PricingRulePolicy).
    Route::get('fields/{field}/pricing', [PricingRuleController::class, 'index'])->name('fields.pricing.index');
    Route::post('fields/{field}/pricing', [PricingRuleController::class, 'store'])->name('fields.pricing.store');
    Route::put('pricing/{pricing_rule}', [PricingRuleController::class, 'update'])->name('pricing.update');
    Route::delete('pricing/{pricing_rule}', [PricingRuleController::class, 'destroy'])->name('pricing.destroy');

    // Grid ketersediaan — tampilan inti (Modul 5). Didaftarkan sebelum
    // bookings/{booking} agar "grid" tidak tertangkap sebagai id booking.
    Route::get('bookings/grid', [BookingController::class, 'grid'])->name('bookings.grid');

    Route::resource('bookings', BookingController::class)->only([
        'index', 'create', 'store', 'show',
    ]);
    Route::post('bookings/{booking}/check-in', [BookingController::class, 'checkIn'])->name('bookings.check-in');

    // Reschedule & pembatalan — kebijakannya per cabang (BookingRuleService).
    Route::get('bookings/{booking}/reschedule', [BookingController::class, 'editReschedule'])->name('bookings.reschedule.edit');
    Route::put('bookings/{booking}/reschedule', [BookingController::class, 'reschedule'])->name('bookings.reschedule');
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

    // Master add-on — admin cabang & owner (AddonPolicy).
    Route::resource('addons', AddonController::class)->only([
        'index', 'store', 'update', 'destroy',
    ]);

    // Blocking slot — admin cabang & owner (BlockedSlotPolicy).
    Route::resource('blocked-slots', BlockedSlotController::class)->only([
        'index', 'store', 'destroy',
    ]);

    // Pelanggan — tidak terikat cabang; kasir perlu membuatnya saat walk-in.
    Route::get('customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::post('customers/quick', [CustomerController::class, 'quickStore'])->name('customers.quick-store');
    Route::resource('customers', CustomerController::class)->only([
        'index', 'create', 'store', 'show', 'edit', 'update',
    ]);

    // Dropdown wilayah bertingkat untuk form cabang.
    Route::get('regions/cities', [RegionController::class, 'cities'])->name('regions.cities');
    Route::get('regions/districts', [RegionController::class, 'districts'])->name('regions.districts');
});

require __DIR__.'/settings.php';
