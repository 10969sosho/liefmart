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
        Schema::create('mapping_barang_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('platform_product_id');
            $table->unsignedBigInteger('product_id');
            $table->float('quantity');
            $table->string('action'); // add, update, delete
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('keterangan')->nullable();
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
        Schema::dropIfExists('mapping_barang_histories');
    }
};
