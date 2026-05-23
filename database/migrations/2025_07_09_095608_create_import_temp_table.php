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
        Schema::create('import_temps', function (Blueprint $table) {
            $table->id();
            $table->string('import_type'); // 'tiktok', 'shopee', etc.
            $table->string('session_id');
            $table->json('data'); // Array of import data
            $table->json('issues')->nullable(); // Array of issues
            $table->json('summary'); // Summary data
            $table->timestamps();
            
            // Add index for faster queries
            $table->index(['import_type', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('import_temps');
    }
};
