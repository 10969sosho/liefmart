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
        Schema::create('retur_pembelian_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_pembelian_id')->constrained('retur_pembelians')->onDelete('cascade');
            $table->foreignId('penerimaan_detail_id')->constrained('penerimaan_detail');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('qty', 10, 2);
            $table->foreignId('satuan_id')->constrained('satuans');
            $table->text('alasan')->nullable();
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
        Schema::dropIfExists('retur_pembelian_details');
    }
}; 