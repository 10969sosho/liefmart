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
        // Add adjustment_description to shopee_financial_transactions
        Schema::table('shopee_financial_transactions', function (Blueprint $table) {
            $table->text('adjustment_description')->nullable()->after('adjustment');
        });

        // Add adjustment_description to tiktok_financial_transactions
        Schema::table('tiktok_financial_transactions', function (Blueprint $table) {
            $table->text('adjustment_description')->nullable()->after('adjustment');
        });

        // Add adjustment_description to tiktok2_financial_transactions
        Schema::table('tiktok2_financial_transactions', function (Blueprint $table) {
            $table->text('adjustment_description')->nullable()->after('adjustment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove adjustment_description from shopee_financial_transactions
        Schema::table('shopee_financial_transactions', function (Blueprint $table) {
            $table->dropColumn('adjustment_description');
        });

        // Remove adjustment_description from tiktok_financial_transactions
        Schema::table('tiktok_financial_transactions', function (Blueprint $table) {
            $table->dropColumn('adjustment_description');
        });

        // Remove adjustment_description from tiktok2_financial_transactions
        Schema::table('tiktok2_financial_transactions', function (Blueprint $table) {
            $table->dropColumn('adjustment_description');
        });
    }
};
