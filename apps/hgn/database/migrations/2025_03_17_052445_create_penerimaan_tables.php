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
        Schema::create('penerimaan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_penerimaan')->unique();
            $table->foreignId('main_category_id')->constrained('main_categories')->onDelete('cascade');
            $table->foreignId('tax_category_id')->nullable()->constrained('tax_categories')->onDelete('set null');
            $table->foreignId('lokasi_id')->nullable()->constrained('lokasi')->onDelete('set null');
            $table->string('nomor_po');
            $table->date('tanggal_penerimaan');
            $table->enum('metode_pembayaran', ['Cash', 'Jatuh Tempo']);
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->enum('status', ['Unlocated', 'Located'])->default('Unlocated');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('penerimaan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerimaan_id')->constrained('penerimaan')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('qty', 10, 2);
            $table->foreignId('satuan_id')->constrained('satuans');
            $table->decimal('harga_hpp', 15, 2)->default(0);

            // Diskon bertingkat
            $table->decimal('diskon_persen_1', 5, 2)->default(0);
            $table->decimal('diskon_nominal_1', 15, 2)->default(0);
            $table->decimal('diskon_persen_2', 5, 2)->default(0);
            $table->decimal('diskon_nominal_2', 15, 2)->default(0);
            $table->decimal('diskon_persen_3', 5, 2)->default(0);
            $table->decimal('diskon_nominal_3', 15, 2)->default(0);
            $table->decimal('diskon_persen_4', 5, 2)->default(0);
            $table->decimal('diskon_nominal_4', 15, 2)->default(0);
            $table->decimal('diskon_persen_5', 5, 2)->default(0);
            $table->decimal('diskon_nominal_5', 15, 2)->default(0);

            $table->boolean('is_free')->default(false);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerimaan_detail');
        Schema::dropIfExists('penerimaan');
    }
};
