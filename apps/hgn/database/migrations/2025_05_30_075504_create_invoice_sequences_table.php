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
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('year_month', 4)->comment('Format: YYMM, e.g. 2503 for March 2025');
            $table->unsignedInteger('counter')->default(0)->comment('Last used counter for this year/month');
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            
            // Add a unique index on year_month to ensure we only have one record per month
            $table->unique('year_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_sequences');
    }
};
