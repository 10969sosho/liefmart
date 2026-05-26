<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barang_keluar', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang_keluar')->unique();

            // Diubah menjadi nullable (online sale, opsional)
            $table->foreignId('order_item_id')
                ->nullable()
                ->constrained('order_items')
                ->onDelete('restrict');

            $table->foreignId('offline_sale_item_id')
                ->nullable()
                ->constrained('offline_sale_items')
                ->onDelete('restrict');

            $table->foreignId('warehouse_stock_id')
                ->constrained('warehouse_stock')
                ->onDelete('restrict');

            $table->decimal('qty', 8, 2);
            $table->date('tanggal_keluar');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_keluar');
    }
};
