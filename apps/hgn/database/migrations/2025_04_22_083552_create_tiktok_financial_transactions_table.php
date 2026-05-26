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
        Schema::create('tiktok_financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_order')->nullable();
            $table->string('hari_order')->nullable();
            $table->string('no_order');
            $table->string('no_invoice')->nullable();
            $table->decimal('nominal_harga', 15, 2)->default(0);
            $table->decimal('nominal_diskon1', 15, 2)->default(0)->comment('BIAYA ADMIN');
            $table->decimal('nominal_diskon2', 15, 2)->default(0)->comment('AFFILIATE COMMISSION');
            $table->decimal('nominal_diskon3', 15, 2)->default(0)->comment('SELLER SHIPPING FEE + SFP SERVICE FEE');
            $table->decimal('nominal_diskon4', 15, 2)->default(0)->comment('VOUCHER XTRA SERVICE FEE');
            $table->decimal('nominal_diskon5', 15, 2)->default(0)->comment('CASHBACK FEE');
            $table->decimal('nominal_diskon6', 15, 2)->default(0)->comment('DISKON 6');
            $table->decimal('adjustment', 15, 2)->default(0);
            $table->decimal('nominal_fix', 15, 2)->default(0);
            $table->decimal('saldo_masuk', 15, 2)->default(0);
            $table->date('tanggal_masuk_pembayaran')->nullable();
            $table->string('hari_masuk_pembayaran')->nullable();
            $table->decimal('outstanding', 15, 2)->default(0);
            $table->decimal('persentase_diskon1', 8, 4)->default(0);
            $table->decimal('persentase_diskon2', 8, 4)->default(0);
            $table->decimal('persentase_diskon3', 8, 4)->default(0);
            $table->decimal('persentase_diskon4', 8, 4)->default(0);
            $table->decimal('persentase_diskon5', 8, 4)->default(0);
            $table->decimal('persentase_diskon6', 8, 4)->default(0);
            $table->decimal('total_persentase', 8, 4)->default(0);
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_financial_transactions');
    }
}; 