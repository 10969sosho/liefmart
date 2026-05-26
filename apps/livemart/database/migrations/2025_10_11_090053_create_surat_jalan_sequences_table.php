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
        Schema::create('surat_jalan_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('year_month', 4); // Format: 2508 (Agustus 2025)
            $table->integer('tax_id'); // Tax ID untuk menentukan suffix
            $table->integer('main_category_id'); // Main category ID (HPNSDA, HGNSDA)
            $table->integer('counter')->default(0); // Counter untuk nomor urut
            $table->timestamp('last_updated'); // Timestamp terakhir update
            $table->timestamps();
            
            // Index untuk performa query
            $table->index(['year_month', 'tax_id', 'main_category_id']);
            $table->index('last_updated');
            
            // Unique constraint untuk mencegah duplikasi
            $table->unique(['year_month', 'tax_id', 'main_category_id'], 'unique_sj_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_jalan_sequences');
    }
};