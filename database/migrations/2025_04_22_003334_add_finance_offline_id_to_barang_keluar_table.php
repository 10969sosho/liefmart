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
        Schema::table('barang_keluar', function (Blueprint $table) {
            $table->unsignedBigInteger('finance_offline_id')->nullable()->after('warehouse_stock_id');
            $table->foreign('finance_offline_id')->references('id')->on('finance_offlines')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('barang_keluar', function (Blueprint $table) {
            $table->dropForeign(['finance_offline_id']);
            $table->dropColumn('finance_offline_id');
        });
    }
};
