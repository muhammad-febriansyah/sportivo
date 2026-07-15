<?php

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Contoh: SPV-260715-A3F9');

            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('field_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            // Terisi bila booking hasil generate recurring (Modul 9).
            $table->unsignedBigInteger('recurring_booking_id')->nullable();

            $table->date('booking_date')->comment('Tanggal main, wall-clock WIB');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('duration_hours');

            // Kolom snapshot: data historis tidak boleh berubah saat master data
            // diedit. Lihat docs/02-erd.md tabel bookings.
            $table->string('branch_name');
            $table->string('field_name');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->bigInteger('price_per_hour')->comment('Snapshot dari pricing engine');
            $table->boolean('is_member_price')->default(false);

            $table->bigInteger('subtotal_field');
            $table->bigInteger('subtotal_addons')->default(0);
            $table->bigInteger('total');
            $table->bigInteger('dp_amount')->default(0);
            $table->bigInteger('paid_amount')->default(0)
                ->comment('Akumulasi pembayaran sukses (denormalisasi)');

            $table->enum('status', array_column(BookingStatus::cases(), 'value'))
                ->default(BookingStatus::Pending->value);
            $table->enum('source', array_column(BookingSource::cases(), 'value'));

            $table->timestamp('expired_at')->nullable()
                ->comment('Batas bayar booking online pending');
            $table->json('rescheduled_from')->nullable()
                ->comment('{date, start_time, end_time} jadwal lama');
            $table->unsignedTinyInteger('reschedule_count')->default(0);
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Null bila booking online oleh customer');

            $table->timestamps();

            // Transaksi TIDAK memakai soft delete — pembatalan memakai status
            // agar riwayat keuangan tetap utuh (docs/05-tech-conventions.md).

            // Pemeriksaan bentrok slot — index terpenting di tabel ini.
            $table->index(['field_id', 'booking_date', 'start_time']);
            // Grid harian per cabang.
            $table->index(['branch_id', 'booking_date']);
            // Job auto-expire booking pending.
            $table->index(['status', 'expired_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
