<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Hapus constraint unik pada kolom year_month
        Schema::table('invoice_sequences', function (Blueprint $table) {
            $table->dropUnique('invoice_sequences_year_month_unique');
        });
        
        Schema::table('invoice_sequences', function (Blueprint $table) {
            // Tambahkan kolom baru dengan length terbatas
            $table->string('category_type', 20)->nullable()->after('last_updated');
            $table->string('sales_type', 20)->nullable()->after('category_type');
            $table->string('tax_status', 20)->nullable()->after('sales_type');
        });
        
        // Buat indeks unik yang mencakup semua kolom yang dibutuhkan
        Schema::table('invoice_sequences', function (Blueprint $table) {
            $table->unique(['year_month', 'category_type', 'sales_type', 'tax_status'], 'invoice_seq_unique');
        });
        
        // Migrasi data yang sudah ada ke format baru
        DB::table('invoice_sequences')->get()->each(function ($sequence) {
            $updates = ['category_type' => 'SKINCARE', 'sales_type' => 'ONLINE', 'tax_status' => 'PKP'];
            DB::table('invoice_sequences')
                ->where('id', $sequence->id)
                ->update($updates);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_sequences', function (Blueprint $table) {
            // Hapus indeks unik yang baru
            $table->dropIndex('invoice_seq_unique');
            
            // Hapus kolom yang ditambahkan
            $table->dropColumn(['category_type', 'sales_type', 'tax_status']);
        });
        
        // Mengembalikan constraint unik pada kolom year_month
        Schema::table('invoice_sequences', function (Blueprint $table) {
            $table->unique('year_month');
        });
    }
};
