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
        Schema::create('offline_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offline_sale_id')->constrained('offline_sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_stock_id')->constrained('warehouse_stock');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_amount_1', 15, 2)->default(0);
            $table->decimal('discount_percent_1', 5, 2)->default(0);
            $table->decimal('discount_amount_2', 15, 2)->default(0);
            $table->decimal('discount_percent_2', 5, 2)->default(0);
            $table->decimal('discount_amount_3', 15, 2)->default(0);
            $table->decimal('discount_percent_3', 5, 2)->default(0);
            $table->decimal('discount_amount_4', 15, 2)->default(0);
            $table->decimal('discount_percent_4', 5, 2)->default(0);
            $table->decimal('discount_amount_5', 15, 2)->default(0);
            $table->decimal('discount_percent_5', 5, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_sale_items');
    }
}; 