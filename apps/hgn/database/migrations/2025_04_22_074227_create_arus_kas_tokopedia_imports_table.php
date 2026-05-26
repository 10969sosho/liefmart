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
        Schema::create('arus_kas_tokopedia_imports', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_masuk_pembayaran');
            $table->string('hari_masuk_pembayaran')->nullable();
            $table->string('mutation_type')->nullable();
            $table->text('description')->nullable();
            $table->decimal('nominal', 15, 2);
            $table->foreignId('platform_id')->constrained('platforms');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arus_kas_tokopedia_imports');
    }
};
