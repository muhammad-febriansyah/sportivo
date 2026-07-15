<?php

use App\Enums\FieldStatus;
use App\Enums\SurfaceType;
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
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('Contoh: Lapangan A');
            $table->enum('surface_type', array_column(SurfaceType::cases(), 'value'));
            $table->string('size')->nullable()->comment('Contoh: 25x15 m');
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->enum('status', array_column(FieldStatus::cases(), 'value'))
                ->default(FieldStatus::Active->value);
            $table->timestamps();
            $table->softDeletes();

            // Grid booking selalu memfilter lapangan aktif per cabang.
            $table->index(['branch_id', 'status']);
        });

        // Tidak ada kolom harga di sini — harga selalu dari pricing_rules (Modul 4).
        // Lihat docs/02-erd.md dan docs/01-prd.md Modul 3.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
