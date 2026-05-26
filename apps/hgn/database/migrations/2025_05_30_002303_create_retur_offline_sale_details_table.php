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
        Schema::create('retur_offline_sale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_offline_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('offline_sale_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('qty', 10, 2);
            $table->enum('kondisi', ['BAGUS', 'RUSAK', 'HILANG'])->default('BAGUS');
            $table->string('alasan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_offline_sale_details');
    }
}; 