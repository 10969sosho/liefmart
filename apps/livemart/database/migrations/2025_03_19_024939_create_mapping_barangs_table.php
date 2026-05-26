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
        Schema::create('mapping_barangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_product_id')->constrained();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mapping_barangs');
    }
};
