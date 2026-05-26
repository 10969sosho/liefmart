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
        Schema::create('arus_kas_tiktok_imports', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_pembayaran');
            $table->text('deskripsi')->nullable();
            $table->string('no_pesanan')->index();
            $table->date('tanggal_pesanan')->nullable();
            $table->decimal('pembayaran', 15, 2);
            $table->decimal('saldo_akhir', 15, 2);
            $table->foreignId('platform_id')->constrained('platforms');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arus_kas_tiktok_imports');
    }
};
