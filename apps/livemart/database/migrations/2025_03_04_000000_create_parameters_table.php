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
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('param_name'); // Nama parameter
            $table->string('param_value'); // Nilai parameter
            $table->string('param_group')->nullable(); // Grup parameter
            $table->text('description')->nullable(); // Deskripsi parameter
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Index
            $table->index(['param_name', 'param_group']);
            $table->unique(['param_name', 'param_group']);
        });
        
        // Insert default parameters untuk invoice format
        DB::table('parameters')->insert([
            [
                'param_name' => 'invoice_format_suffix',
                'param_value' => 'AMP',
                'param_group' => 'invoice_format',
                'description' => 'Format suffix untuk nomor invoice',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'param_name' => 'invoice_format_year_month',
                'param_value' => '2601',
                'param_group' => 'invoice_format',
                'description' => 'Format tahun-bulan untuk nomor invoice (YYMM)',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'param_name' => 'invoice_format_counter_length',
                'param_value' => '4',
                'param_group' => 'invoice_format',
                'description' => 'Panjang digit untuk counter nomor invoice',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'param_name' => 'pkp_tax_code',
                'param_value' => '01',
                'param_group' => 'invoice_format',
                'description' => 'Kode pajak untuk PKP',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'param_name' => 'non_pkp_tax_code',
                'param_value' => '02',
                'param_group' => 'invoice_format',
                'description' => 'Kode pajak untuk NON PKP',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parameters');
    }
};