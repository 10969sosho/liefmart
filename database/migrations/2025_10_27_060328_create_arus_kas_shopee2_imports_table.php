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
        Schema::create('arus_kas_shopee2_imports', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_pemasukan');
            $table->text('deskripsi')->nullable();
            $table->string('no_pesanan')->nullable();
            $table->decimal('pemasukan', 15, 2);
            $table->decimal('saldo_akhir', 15, 2);
            $table->foreignId('platform_id')->constrained('platforms');
            $table->integer('excel_row_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('arus_kas_shopee2_imports');
    }
};
