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
        Schema::create('lazada_financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_order')->nullable();
            $table->string('hari_order')->nullable();
            $table->string('no_order');
            $table->string('no_invoice')->nullable();
            $table->decimal('nominal_harga', 15, 2)->default(0);
            
            // Diskon columns - Lazada specific
            $table->decimal('nominal_diskon1', 15, 2)->default(0)->comment('BIAYA PROSES FIX');
            $table->decimal('nominal_diskon2', 15, 2)->default(0)->comment('GRATIS ONGKIR');
            $table->decimal('nominal_diskon3', 15, 2)->default(0)->comment('BIAYA ADMIN');
            $table->decimal('nominal_diskon4', 15, 2)->default(0)->comment('BIAYA TRANSAKSI');
            $table->decimal('nominal_diskon5', 15, 2)->default(0)->comment('DISKON 5');
            $table->decimal('nominal_diskon6', 15, 2)->default(0)->comment('DISKON 6');
            $table->decimal('nominal_diskon7', 15, 2)->default(0)->comment('DISKON 7');
            $table->decimal('nominal_diskon8', 15, 2)->default(0)->comment('DISKON 8');
            $table->decimal('nominal_diskon9', 15, 2)->default(0)->comment('DISKON 9');
            $table->decimal('nominal_diskon10', 15, 2)->default(0)->comment('DISKON 10');
            $table->decimal('nominal_diskon11', 15, 2)->default(0)->comment('DISKON 11');
            $table->decimal('nominal_diskon12', 15, 2)->default(0)->comment('DISKON 12');
            
            // For adjustments
            $table->decimal('adjustment', 15, 2)->default(0);
            $table->string('adjustment_description')->nullable();
            
            // Calculated fields
            $table->decimal('nominal_fix', 15, 2)->default(0);
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('saldo_masuk', 15, 2)->default(0);
            $table->date('tanggal_masuk_pembayaran')->nullable();
            $table->string('hari_masuk_pembayaran')->nullable();
            $table->decimal('outstanding', 15, 2)->default(0);
            
            // Percentage fields
            $table->decimal('persentase_diskon1', 8, 4)->default(0);
            $table->decimal('persentase_diskon2', 8, 4)->default(0);
            $table->decimal('persentase_diskon3', 8, 4)->default(0);
            $table->decimal('persentase_diskon4', 8, 4)->default(0);
            $table->decimal('persentase_diskon5', 8, 4)->default(0);
            $table->decimal('persentase_diskon6', 8, 4)->default(0);
            $table->decimal('persentase_diskon7', 8, 4)->default(0);
            $table->decimal('persentase_diskon8', 8, 4)->default(0);
            $table->decimal('persentase_diskon9', 8, 4)->default(0);
            $table->decimal('persentase_diskon10', 8, 4)->default(0);
            $table->decimal('persentase_diskon11', 8, 4)->default(0);
            $table->decimal('persentase_diskon12', 8, 4)->default(0);
            $table->decimal('total_persentase', 8, 4)->default(0);
            $table->decimal('percentage_paid', 8, 4)->default(0);
            $table->decimal('percentage_outstanding', 8, 4)->default(0);
            
            // Foreign keys
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            
            // Locking mechanism
            $table->boolean('is_locked')->default(false);
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lazada_financial_transactions');
    }
};
