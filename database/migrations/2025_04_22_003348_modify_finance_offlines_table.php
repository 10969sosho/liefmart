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
        Schema::table('finance_offlines', function (Blueprint $table) {
            // First drop the foreign key
            $table->dropForeign(['barang_keluar_id']);
            
            // Then drop the column
            $table->dropColumn('barang_keluar_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('finance_offlines', function (Blueprint $table) {
            $table->unsignedBigInteger('barang_keluar_id')->after('id');
            $table->foreign('barang_keluar_id')->references('id')->on('barang_keluar')->onDelete('cascade');
        });
    }
};
