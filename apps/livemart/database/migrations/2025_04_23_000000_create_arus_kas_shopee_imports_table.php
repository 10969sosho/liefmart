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
        Schema::create('arus_kas_shopee_imports', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_pemasukan');
            $table->string('tipe_transaksi')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('no_pesanan')->nullable();
            $table->string('jenis_transaksi')->nullable();
            $table->decimal('pemasukan', 15, 2);
            $table->string('status')->nullable();
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
        Schema::dropIfExists('arus_kas_shopee_imports');
    }
}; 