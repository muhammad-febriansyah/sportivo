<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->comment('Prefix internal, contoh JKT01');
            $table->text('address');
            // Menyimpan kolom `id` milik tabel laravolt (sesuai docs/02-erd.md).
            //
            // PERHATIAN: laravolt merelasikan wilayah lewat `code` (kode BPS), bukan `id`,
            // dan seeder-nya melakukan truncate + insert ulang (lihat
            // vendor/laravolt/indonesia/src/Seeds/DatabaseSeeder.php). Artinya `id` dapat
            // bergeser bila `php artisan laravolt:indonesia:seed` dijalankan ulang pada
            // database yang sudah berisi cabang — dan cabang akan menunjuk wilayah yang
            // salah tanpa error. JANGAN jalankan seeder itu ulang di database berisi data.
            $table->unsignedBigInteger('province_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->string('phone');
            $table->json('operating_hours')->comment('{"weekday":{"open":"08:00","close":"23:00"},"weekend":{...}}');
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
