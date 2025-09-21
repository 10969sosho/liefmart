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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_order');
            $table->string('hari_order');
            $table->string('no_order');
            $table->string('no_invoice');
            $table->decimal('harga_setelah_diskon', 15, 2);
            
            // 5 kolom admin lain yang bisa kosong
            $table->string('admin_lain_1')->nullable();
            $table->string('admin_lain_2')->nullable();
            $table->string('admin_lain_3')->nullable();
            $table->string('admin_lain_4')->nullable();
            $table->string('admin_lain_5')->nullable();
            
            // Kolom lainnya
            $table->decimal('adjustment', 15, 2)->nullable();
            $table->decimal('nominal_fix', 15, 2);
            $table->decimal('saldo_masuk', 15, 2);
            $table->date('tanggal_masuk_pembayaran')->nullable();
            $table->string('hari_masuk_pembayaran')->nullable();
            $table->decimal('outstanding', 15, 2)->default(0);
            
            // Kolom untuk persen masing-masing biaya admin
            $table->decimal('persen_admin_1', 8, 2)->nullable();
            $table->decimal('persen_admin_2', 8, 2)->nullable();
            $table->decimal('persen_admin_3', 8, 2)->nullable();
            $table->decimal('persen_admin_4', 8, 2)->nullable();
            $table->decimal('persen_admin_5', 8, 2)->nullable();
            
            // Foreign keys jika diperlukan
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};