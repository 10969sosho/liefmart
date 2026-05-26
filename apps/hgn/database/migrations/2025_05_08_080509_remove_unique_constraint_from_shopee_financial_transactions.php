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
        Schema::table('shopee_financial_transactions', function (Blueprint $table) {
            // Drop existing unique constraint on no_order
            $table->dropUnique(['no_order']);
            
            // Add a composite unique index on no_order and no_invoice
            // This allows multiple transactions per order, but ensures each invoice is unique within an order
            $table->unique(['no_order', 'no_invoice']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopee_financial_transactions', function (Blueprint $table) {
            // Drop the composite unique index
            $table->dropUnique(['no_order', 'no_invoice']);
            
            // Restore the original unique constraint on no_order
            $table->unique('no_order');
        });
    }
};
