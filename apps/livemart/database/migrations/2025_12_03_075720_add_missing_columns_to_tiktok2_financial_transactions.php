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
        Schema::table('tiktok2_financial_transactions', function (Blueprint $table) {
            // Add adjustment_description if it doesn't exist
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'adjustment_description')) {
                $table->text('adjustment_description')->nullable()->after('adjustment');
            }
            
            // Add nominal_diskon7-12 if they don't exist
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'nominal_diskon7')) {
                $table->decimal('nominal_diskon7', 15, 2)->default(0)->after('nominal_diskon6')->comment('BIAYA 7');
            }
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'nominal_diskon8')) {
                $table->decimal('nominal_diskon8', 15, 2)->default(0)->after('nominal_diskon7')->comment('BIAYA 8');
            }
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'nominal_diskon9')) {
                $table->decimal('nominal_diskon9', 15, 2)->default(0)->after('nominal_diskon8')->comment('BIAYA 9');
            }
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'nominal_diskon10')) {
                $table->decimal('nominal_diskon10', 15, 2)->default(0)->after('nominal_diskon9')->comment('BIAYA 10');
            }
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'nominal_diskon11')) {
                $table->decimal('nominal_diskon11', 15, 2)->default(0)->after('nominal_diskon10')->comment('BIAYA 11');
            }
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'nominal_diskon12')) {
                $table->decimal('nominal_diskon12', 15, 2)->default(0)->after('nominal_diskon11')->comment('BIAYA 12');
            }
            
            // Add persentase_diskon7-12 if they don't exist
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'persentase_diskon7')) {
                $table->decimal('persentase_diskon7', 8, 4)->default(0)->after('persentase_diskon6');
            }
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'persentase_diskon8')) {
                $table->decimal('persentase_diskon8', 8, 4)->default(0)->after('persentase_diskon7');
            }
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'persentase_diskon9')) {
                $table->decimal('persentase_diskon9', 8, 4)->default(0)->after('persentase_diskon8');
            }
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'persentase_diskon10')) {
                $table->decimal('persentase_diskon10', 8, 4)->default(0)->after('persentase_diskon9');
            }
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'persentase_diskon11')) {
                $table->decimal('persentase_diskon11', 8, 4)->default(0)->after('persentase_diskon10');
            }
            if (!Schema::hasColumn('tiktok2_financial_transactions', 'persentase_diskon12')) {
                $table->decimal('persentase_diskon12', 8, 4)->default(0)->after('persentase_diskon11');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tiktok2_financial_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('tiktok2_financial_transactions', 'adjustment_description')) {
                $table->dropColumn('adjustment_description');
            }
            if (Schema::hasColumn('tiktok2_financial_transactions', 'nominal_diskon7')) {
                $table->dropColumn([
                    'nominal_diskon7', 'nominal_diskon8', 'nominal_diskon9', 
                    'nominal_diskon10', 'nominal_diskon11', 'nominal_diskon12',
                    'persentase_diskon7', 'persentase_diskon8', 'persentase_diskon9',
                    'persentase_diskon10', 'persentase_diskon11', 'persentase_diskon12'
                ]);
            }
        });
    }
};
