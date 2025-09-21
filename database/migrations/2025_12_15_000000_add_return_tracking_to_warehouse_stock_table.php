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
        Schema::table('warehouse_stock', function (Blueprint $table) {
            // Add fields to track return information
            $table->string('source_type')->default('penerimaan')->after('catatan')
                ->comment('Source of the stock: penerimaan, retur_penjualan, retur_offline');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type')
                ->comment('ID of the source record (penerimaan_id, retur_penjualan_id, etc.)');
            $table->date('source_date')->nullable()->after('source_id')
                ->comment('Date when the stock was received/returned');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('warehouse_stock', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_id', 'source_date']);
        });
    }
}; 