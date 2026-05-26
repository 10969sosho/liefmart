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
        Schema::table('offline_sale_items', function (Blueprint $table) {
            $table->json('discount_mapping')->nullable()->after('notes')
                ->comment('JSON mapping of discount ordering for display');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('offline_sale_items', function (Blueprint $table) {
            $table->dropColumn('discount_mapping');
        });
    }
};
