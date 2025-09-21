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
        Schema::table('arus_kas_tiktok_imports', function (Blueprint $table) {
            $table->integer('excel_row_number')->nullable()->after('raw_saldo_akhir')->comment('Original row number from Excel file for sorting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arus_kas_tiktok_imports', function (Blueprint $table) {
            $table->dropColumn('excel_row_number');
        });
    }
};