<?php

use App\Enums\DayType;
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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained()->cascadeOnDelete();
            $table->enum('day_type', array_column(DayType::cases(), 'value'))
                ->comment('Hari spesifik (monday..sunday) menang atas weekday/weekend');
            $table->time('start_time')->comment('Inklusif');
            $table->time('end_time')->comment('Eksklusif');
            // Rupiah selalu BIGINT tanpa desimal — docs/05-tech-conventions.md.
            $table->bigInteger('price')->comment('Rupiah per jam, harga umum');
            $table->bigInteger('member_price')->nullable()->comment('Fallback ke price bila null');
            $table->timestamps();
            $table->softDeletes();

            // Resolusi harga selalu memfilter lapangan + tipe hari.
            $table->index(['field_id', 'day_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
