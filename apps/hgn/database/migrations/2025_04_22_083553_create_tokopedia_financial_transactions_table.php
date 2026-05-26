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
        Schema::create('tokopedia_financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_order');
            $table->string('hari_order');
            $table->string('no_order');
            $table->string('no_invoice');
            $table->decimal('nominal_harga', 12, 2);
            $table->decimal('nominal_diskon1', 12, 2)->default(0);
            $table->decimal('nominal_diskon2', 12, 2)->default(0);
            $table->decimal('nominal_diskon3', 12, 2)->default(0);
            $table->decimal('nominal_diskon4', 12, 2)->default(0);
            $table->decimal('nominal_diskon5', 12, 2)->default(0);
            $table->decimal('nominal_diskon6', 12, 2)->default(0);
            $table->decimal('adjustment', 12, 2)->default(0);
            $table->decimal('nominal_fix', 12, 2);
            $table->decimal('saldo_masuk', 12, 2);
            $table->date('tanggal_masuk_pembayaran');
            $table->string('hari_masuk_pembayaran');
            $table->decimal('outstanding', 12, 2);
            $table->decimal('persentase_diskon1', 5, 2)->default(0);
            $table->decimal('persentase_diskon2', 5, 2)->default(0);
            $table->decimal('persentase_diskon3', 5, 2)->default(0);
            $table->decimal('persentase_diskon4', 5, 2)->default(0);
            $table->decimal('persentase_diskon5', 5, 2)->default(0);
            $table->decimal('persentase_diskon6', 5, 2)->default(0);
            $table->decimal('total_persentase', 5, 2)->default(0);
            $table->decimal('percentage_paid', 5, 2)->default(0);
            $table->decimal('percentage_outstanding', 5, 2)->default(0);
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tokopedia_financial_transactions');
    }
}; 