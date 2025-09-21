<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('adjustment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('platform')->nullable(); // shopee, blibli, tiktok, tokopedia, etc
            $table->decimal('old_value', 15, 2)->nullable();
            $table->decimal('new_value', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('platform');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('adjustment_histories');
    }
}; 