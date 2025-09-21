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
        // Add biaya columns 7-12 to Blibli financial transactions
        Schema::table('blibli_financial_transactions', function (Blueprint $table) {
            $table->decimal('nominal_diskon7', 15, 2)->nullable()->after('nominal_diskon6');
            $table->decimal('nominal_diskon8', 15, 2)->nullable()->after('nominal_diskon7');
            $table->decimal('nominal_diskon9', 15, 2)->nullable()->after('nominal_diskon8');
            $table->decimal('nominal_diskon10', 15, 2)->nullable()->after('nominal_diskon9');
            $table->decimal('nominal_diskon11', 15, 2)->nullable()->after('nominal_diskon10');
            $table->decimal('nominal_diskon12', 15, 2)->nullable()->after('nominal_diskon11');
            
            // Add percentage columns for the new biaya columns
            $table->decimal('persentase_diskon7', 8, 2)->nullable()->after('persentase_diskon6');
            $table->decimal('persentase_diskon8', 8, 2)->nullable()->after('persentase_diskon7');
            $table->decimal('persentase_diskon9', 8, 2)->nullable()->after('persentase_diskon8');
            $table->decimal('persentase_diskon10', 8, 2)->nullable()->after('persentase_diskon9');
            $table->decimal('persentase_diskon11', 8, 2)->nullable()->after('persentase_diskon10');
            $table->decimal('persentase_diskon12', 8, 2)->nullable()->after('persentase_diskon11');
        });

        // Add biaya columns 7-12 to Shopee financial transactions
        Schema::table('shopee_financial_transactions', function (Blueprint $table) {
            $table->decimal('nominal_diskon7', 15, 2)->nullable()->after('nominal_diskon6');
            $table->decimal('nominal_diskon8', 15, 2)->nullable()->after('nominal_diskon7');
            $table->decimal('nominal_diskon9', 15, 2)->nullable()->after('nominal_diskon8');
            $table->decimal('nominal_diskon10', 15, 2)->nullable()->after('nominal_diskon9');
            $table->decimal('nominal_diskon11', 15, 2)->nullable()->after('nominal_diskon10');
            $table->decimal('nominal_diskon12', 15, 2)->nullable()->after('nominal_diskon11');
            
            // Add percentage columns for the new biaya columns
            $table->decimal('persentase_diskon7', 8, 2)->nullable()->after('persentase_diskon6');
            $table->decimal('persentase_diskon8', 8, 2)->nullable()->after('persentase_diskon7');
            $table->decimal('persentase_diskon9', 8, 2)->nullable()->after('persentase_diskon8');
            $table->decimal('persentase_diskon10', 8, 2)->nullable()->after('persentase_diskon9');
            $table->decimal('persentase_diskon11', 8, 2)->nullable()->after('persentase_diskon10');
            $table->decimal('persentase_diskon12', 8, 2)->nullable()->after('persentase_diskon11');
        });

        // Add biaya columns 7-12 to TikTok financial transactions
        Schema::table('tiktok_financial_transactions', function (Blueprint $table) {
            $table->decimal('nominal_diskon7', 15, 2)->default(0)->after('nominal_diskon6')->comment('BIAYA 7');
            $table->decimal('nominal_diskon8', 15, 2)->default(0)->after('nominal_diskon7')->comment('BIAYA 8');
            $table->decimal('nominal_diskon9', 15, 2)->default(0)->after('nominal_diskon8')->comment('BIAYA 9');
            $table->decimal('nominal_diskon10', 15, 2)->default(0)->after('nominal_diskon9')->comment('BIAYA 10');
            $table->decimal('nominal_diskon11', 15, 2)->default(0)->after('nominal_diskon10')->comment('BIAYA 11');
            $table->decimal('nominal_diskon12', 15, 2)->default(0)->after('nominal_diskon11')->comment('BIAYA 12');
            
            // Add percentage columns for the new biaya columns
            $table->decimal('persentase_diskon7', 8, 4)->default(0)->after('persentase_diskon6');
            $table->decimal('persentase_diskon8', 8, 4)->default(0)->after('persentase_diskon7');
            $table->decimal('persentase_diskon9', 8, 4)->default(0)->after('persentase_diskon8');
            $table->decimal('persentase_diskon10', 8, 4)->default(0)->after('persentase_diskon9');
            $table->decimal('persentase_diskon11', 8, 4)->default(0)->after('persentase_diskon10');
            $table->decimal('persentase_diskon12', 8, 4)->default(0)->after('persentase_diskon11');
        });

        // Add biaya columns 7-12 to Tokopedia financial transactions
        Schema::table('tokopedia_financial_transactions', function (Blueprint $table) {
            $table->decimal('nominal_diskon7', 12, 2)->default(0)->after('nominal_diskon6');
            $table->decimal('nominal_diskon8', 12, 2)->default(0)->after('nominal_diskon7');
            $table->decimal('nominal_diskon9', 12, 2)->default(0)->after('nominal_diskon8');
            $table->decimal('nominal_diskon10', 12, 2)->default(0)->after('nominal_diskon9');
            $table->decimal('nominal_diskon11', 12, 2)->default(0)->after('nominal_diskon10');
            $table->decimal('nominal_diskon12', 12, 2)->default(0)->after('nominal_diskon11');
            
            // Add percentage columns for the new biaya columns
            $table->decimal('persentase_diskon7', 5, 2)->default(0)->after('persentase_diskon6');
            $table->decimal('persentase_diskon8', 5, 2)->default(0)->after('persentase_diskon7');
            $table->decimal('persentase_diskon9', 5, 2)->default(0)->after('persentase_diskon8');
            $table->decimal('persentase_diskon10', 5, 2)->default(0)->after('persentase_diskon9');
            $table->decimal('persentase_diskon11', 5, 2)->default(0)->after('persentase_diskon10');
            $table->decimal('persentase_diskon12', 5, 2)->default(0)->after('persentase_diskon11');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove biaya columns 7-12 from Blibli financial transactions
        Schema::table('blibli_financial_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'nominal_diskon7', 'nominal_diskon8', 'nominal_diskon9', 
                'nominal_diskon10', 'nominal_diskon11', 'nominal_diskon12',
                'persentase_diskon7', 'persentase_diskon8', 'persentase_diskon9',
                'persentase_diskon10', 'persentase_diskon11', 'persentase_diskon12'
            ]);
        });

        // Remove biaya columns 7-12 from Shopee financial transactions
        Schema::table('shopee_financial_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'nominal_diskon7', 'nominal_diskon8', 'nominal_diskon9', 
                'nominal_diskon10', 'nominal_diskon11', 'nominal_diskon12',
                'persentase_diskon7', 'persentase_diskon8', 'persentase_diskon9',
                'persentase_diskon10', 'persentase_diskon11', 'persentase_diskon12'
            ]);
        });

        // Remove biaya columns 7-12 from TikTok financial transactions
        Schema::table('tiktok_financial_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'nominal_diskon7', 'nominal_diskon8', 'nominal_diskon9', 
                'nominal_diskon10', 'nominal_diskon11', 'nominal_diskon12',
                'persentase_diskon7', 'persentase_diskon8', 'persentase_diskon9',
                'persentase_diskon10', 'persentase_diskon11', 'persentase_diskon12'
            ]);
        });

        // Remove biaya columns 7-12 from Tokopedia financial transactions
        Schema::table('tokopedia_financial_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'nominal_diskon7', 'nominal_diskon8', 'nominal_diskon9', 
                'nominal_diskon10', 'nominal_diskon11', 'nominal_diskon12',
                'persentase_diskon7', 'persentase_diskon8', 'persentase_diskon9',
                'persentase_diskon10', 'persentase_diskon11', 'persentase_diskon12'
            ]);
        });
    }
}; 