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
            $table->string('raw_tanggal_pembayaran')->nullable()->after('tanggal_pembayaran');
            $table->string('raw_tanggal_pesanan')->nullable()->after('tanggal_pesanan');
            $table->string('raw_pembayaran')->nullable()->after('pembayaran');
            $table->string('raw_saldo_akhir')->nullable()->after('saldo_akhir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arus_kas_tiktok_imports', function (Blueprint $table) {
            $table->dropColumn([
                'raw_tanggal_pembayaran',
                'raw_tanggal_pesanan',
                'raw_pembayaran',
                'raw_saldo_akhir'
            ]);
        });
    }
};
