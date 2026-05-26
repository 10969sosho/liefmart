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
        Schema::table('tiktok_financial_transactions', function (Blueprint $table) {
            $table->decimal('qty', 10, 2)->nullable()->after('nominal_fix');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tiktok_financial_transactions', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
};
