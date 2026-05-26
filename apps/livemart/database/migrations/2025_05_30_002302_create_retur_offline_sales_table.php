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
        Schema::create('retur_offline_sales', function (Blueprint $table) {
            $table->id();
            $table->string('kode_retur');
            $table->foreignId('offline_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->date('tanggal_retur');
            $table->text('catatan')->nullable();
            $table->enum('status', ['draft', 'selesai', 'dibatalkan'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_offline_sales');
    }
}; 