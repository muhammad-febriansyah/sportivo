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
        Schema::create('blocked_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            // Null = seluruh lapangan di cabang tersebut (docs/02-erd.md).
            $table->foreignId('field_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('block_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason')->comment('Contoh: Maintenance rumput');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            // Grid harian memfilter blokir per cabang + tanggal.
            $table->index(['branch_id', 'block_date']);
            $table->index(['field_id', 'block_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_slots');
    }
};
