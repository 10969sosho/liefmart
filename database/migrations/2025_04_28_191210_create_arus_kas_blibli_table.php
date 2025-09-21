<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('arus_kas_blibli', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tanggal_pembayaran');
            $table->string('deskripsi');
            $table->string('no_pesanan')->nullable();
            $table->dateTime('tanggal_pesanan')->nullable();
            $table->decimal('pembayaran', 15, 2);
            $table->decimal('saldo_akhir', 15, 2);
            $table->timestamps();
            
            // Indices for better query performance
            $table->index('tanggal_pembayaran');
            $table->index('no_pesanan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('arus_kas_blibli');
    }
}; 