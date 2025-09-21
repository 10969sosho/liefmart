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
        Schema::table('shopee_financial_transactions', function (Blueprint $table) {
            $table->decimal('percentage_paid', 8, 2)->nullable()->after('total_persentase');
            $table->decimal('percentage_outstanding', 8, 2)->nullable()->after('percentage_paid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopee_financial_transactions', function (Blueprint $table) {
            $table->dropColumn('percentage_paid');
            $table->dropColumn('percentage_outstanding');
        });
    }
};
