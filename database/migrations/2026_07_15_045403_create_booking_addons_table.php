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
        Schema::create('booking_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('addon_id')->constrained()->restrictOnDelete();
            // Snapshot: mengubah master add-on tidak boleh mengubah booking lama
            // (docs/02-erd.md tabel booking_addons).
            $table->string('addon_name');
            $table->bigInteger('addon_price');
            $table->unsignedSmallInteger('qty');
            $table->bigInteger('subtotal')->comment('addon_price x qty');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_addons');
    }
};
