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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Nomor WA adalah identifier utama pelanggan, dinormalisasi ke 628xxx
            // sebelum disimpan — lihat docs/05-tech-conventions.md.
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->string('password')->nullable()->comment('Null bila hanya walk-in');
            $table->boolean('is_member')->default(false);
            $table->date('member_until')->nullable()
                ->comment('Member valid bila is_member && member_until >= hari ini');
            $table->text('notes')->nullable()->comment('Catatan internal kasir');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
