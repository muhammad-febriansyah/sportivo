<?php

use App\Enums\DayType;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Field;
use App\Models\PricingRule;
use Illuminate\Support\Facades\DB;

/**
 * US-16: dua request paralel ke slot yang sama → tepat 1 sukses.
 *
 * Direktori ini memakai DatabaseTruncation, bukan RefreshDatabase: transaksi
 * RefreshDatabase membuat data seed tidak terlihat oleh proses lain, sehingga
 * race condition tidak mungkin diuji di dalamnya.
 *
 * Test ini bergantung pada lockForUpdate — no-op di SQLite, jadi wajib MySQL.
 */
test('dua booking bersamaan ke slot sama hanya satu yang sukses', function () {
    $cabang = Branch::factory()->create();
    $lapangan = Field::factory()->forBranch($cabang)->create();
    $customer = Customer::factory()->create();

    PricingRule::factory()->forField($lapangan)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '23:00:00')
        ->price(150_000)
        ->create();

    $skrip = <<<PHP
    try {
        app(App\Actions\CreateBookingAction::class)->execute(new App\Actions\CreateBookingData(
            fieldId: {$lapangan->id},
            customerId: {$customer->id},
            date: Illuminate\Support\Carbon::parse('2026-07-15'),
            startTime: '19:00',
            durationHours: 1,
            source: App\Enums\BookingSource::Walkin,
        ));
        echo 'HASIL:SUKSES';
    } catch (Throwable \$e) {
        echo 'HASIL:GAGAL:' . get_class(\$e);
    }
    PHP;

    // Dua proses PHP terpisah agar benar-benar bersaing di level database.
    $proses = [];

    for ($i = 0; $i < 2; $i++) {
        $proses[] = popen(
            sprintf(
                'cd %s && APP_ENV=testing DB_CONNECTION=mysql DB_DATABASE=sportivo_test php artisan tinker --execute=%s 2>&1',
                escapeshellarg(base_path()),
                escapeshellarg($skrip),
            ),
            'r'
        );
    }

    $keluaran = [];

    foreach ($proses as $p) {
        $keluaran[] = stream_get_contents($p);
        pclose($p);
    }

    $sukses = count(array_filter($keluaran, fn (string $o): bool => str_contains($o, 'HASIL:SUKSES')));
    $gagal = count(array_filter($keluaran, fn (string $o): bool => str_contains($o, 'HASIL:GAGAL')));

    expect($sukses)->toBe(1, 'Tepat satu booking harus sukses. Keluaran: '.implode(' | ', $keluaran))
        ->and($gagal)->toBe(1, 'Satu booking harus ditolak. Keluaran: '.implode(' | ', $keluaran))
        ->and(Booking::count())->toBe(1);
})->skip(
    fn () => DB::connection()->getDriverName() !== 'mysql',
    'Butuh MySQL — lockForUpdate adalah no-op di SQLite.'
);
