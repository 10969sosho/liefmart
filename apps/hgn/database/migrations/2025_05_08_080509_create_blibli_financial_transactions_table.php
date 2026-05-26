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
        Schema::create('blibli_financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_order');
            $table->string('hari_order');
            $table->string('no_order');
            $table->string('no_invoice')->nullable();
            $table->decimal('nominal_harga', 15, 2);
            
            // Diskon columns
            $table->decimal('nominal_diskon1', 15, 2)->nullable();
            $table->decimal('nominal_diskon2', 15, 2)->nullable();
            $table->decimal('nominal_diskon3', 15, 2)->nullable();
            $table->decimal('nominal_diskon4', 15, 2)->nullable();
            $table->decimal('nominal_diskon5', 15, 2)->nullable();
            $table->decimal('nominal_diskon6', 15, 2)->nullable();
            
            // For adjustments
            $table->decimal('adjustment', 15, 2)->nullable()->default(0);
            $table->string('adjustment_description')->nullable();
            
            // Calculated fields
            $table->decimal('nominal_fix', 15, 2);
            $table->decimal('saldo_masuk', 15, 2);
            $table->decimal('outstanding', 15, 2)->default(0);
            
            // Percentage fields
            $table->decimal('persentase_diskon1', 8, 2)->nullable();
            $table->decimal('persentase_diskon2', 8, 2)->nullable();
            $table->decimal('persentase_diskon3', 8, 2)->nullable();
            $table->decimal('persentase_diskon4', 8, 2)->nullable();
            $table->decimal('persentase_diskon5', 8, 2)->nullable();
            $table->decimal('persentase_diskon6', 8, 2)->nullable();
            $table->decimal('total_persentase', 8, 2)->nullable();
            $table->decimal('percentage_paid', 8, 2)->nullable();
            $table->decimal('percentage_outstanding', 8, 2)->nullable();
            
            // Date fields
            $table->date('tanggal_masuk_pembayaran')->nullable();
            $table->string('hari_masuk_pembayaran')->nullable();
            
            // Foreign keys
            $table->unsignedBigInteger('order_id')->nullable();
            
            // Locking fields
            $table->boolean('is_locked')->default(false);
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            
            $table->timestamps();
            
            // Add unique index on order number (like Shopee)
            $table->unique('no_order');
            
            // Add foreign key constraint
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('locked_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blibli_financial_transactions');
    }
}; 