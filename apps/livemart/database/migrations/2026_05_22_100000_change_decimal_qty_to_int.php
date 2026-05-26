<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE warehouse_stock MODIFY COLUMN qty INT(10) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE warehouse_stock MODIFY COLUMN qty_damaged INT(10) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE penerimaan_detail MODIFY COLUMN qty INT(10) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE order_items MODIFY COLUMN quantity INT(10) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE retur_penjualan_details MODIFY COLUMN qty INT(10) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE retur_pembelian_details MODIFY COLUMN qty INT(10) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE retur_offline_sale_details MODIFY COLUMN qty INT(10) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE mapping_barangs MODIFY COLUMN quantity INT(10) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE barang_keluar MODIFY COLUMN qty INT(10) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE warehouse_stock MODIFY COLUMN qty DECIMAL(10,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE warehouse_stock MODIFY COLUMN qty_damaged DECIMAL(10,2) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE penerimaan_detail MODIFY COLUMN qty DECIMAL(10,2) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE order_items MODIFY COLUMN quantity DECIMAL(10,2) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE retur_penjualan_details MODIFY COLUMN qty DECIMAL(10,2) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE retur_pembelian_details MODIFY COLUMN qty DECIMAL(10,2) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE retur_offline_sale_details MODIFY COLUMN qty DECIMAL(10,2) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE mapping_barangs MODIFY COLUMN quantity DECIMAL(10,2) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE barang_keluar MODIFY COLUMN qty DECIMAL(8,2) NOT NULL DEFAULT 0');
    }
};
