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
        Schema::create('platform_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained();
            $table->string('platform_product_name');
            $table->string('variant')->nullable(); // Menambahkan kolom variant yang bisa kosong
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
        Schema::dropIfExists('platform_products');
    }
};