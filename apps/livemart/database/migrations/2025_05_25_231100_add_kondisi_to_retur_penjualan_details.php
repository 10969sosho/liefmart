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
        Schema::table('retur_penjualan_details', function (Blueprint $table) {
            $table->string('kondisi')->default('BAGUS')->after('qty')->comment('BAGUS, RUSAK, HILANG');
            $table->dropColumn('is_rusak');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('retur_penjualan_details', function (Blueprint $table) {
            $table->boolean('is_rusak')->default(false)->after('qty');
            $table->dropColumn('kondisi');
        });
    }
};
