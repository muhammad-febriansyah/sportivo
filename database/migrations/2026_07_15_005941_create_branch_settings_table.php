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
        Schema::create('branch_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('dp_percentage')->default(50)->comment('Persen DP minimal');
            $table->unsignedTinyInteger('reschedule_limit_days')->default(1)->comment('Batas reschedule H-n');
            $table->unsignedTinyInteger('cancel_refund_limit_days')->default(2)->comment('Cancel >= H-n maka DP refund');
            $table->unsignedTinyInteger('max_reschedule')->default(1);
            $table->unsignedSmallInteger('online_hold_minutes')->default(15)->comment('Hold slot booking online pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_settings');
    }
};
