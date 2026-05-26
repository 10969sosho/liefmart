<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehouseStockTable extends Migration
{
    public function up()
    {
        Schema::create('warehouse_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('lokasi_id');
            $table->unsignedBigInteger('penerimaan_detail_id');
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->decimal('qty', 10, 2);
            $table->date('expired_date')->nullable();
            $table->enum('status_ed', ['aman', 'hampir_kadaluarsa', 'kadaluarsa'])->default('aman');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('lokasi_id')->references('id')->on('lokasi');
            $table->foreign('penerimaan_detail_id')->references('id')->on('penerimaan_detail');
            $table->foreign('tax_id')->references('id')->on('tax_categories');
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouse_stock');
    }
}
