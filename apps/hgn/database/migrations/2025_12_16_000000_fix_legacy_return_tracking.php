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
        // Update warehouse stock records from the legacy return (ID: 1, dated 2025-07-09)
        // These records were created before return tracking was implemented
        
        $returPenjualan = DB::table('retur_penjualans')->where('id', 1)->first();
        
        if ($returPenjualan) {
            // Update warehouse stock records (IDs 336-359) that were created from this return
            DB::table('warehouse_stock')
                ->whereIn('id', range(336, 359))
                ->where('source_type', 'penerimaan')
                ->whereNull('source_date')
                ->update([
                    'source_type' => 'retur_penjualan',
                    'source_id' => 1,
                    'source_date' => $returPenjualan->tanggal_retur,
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert the warehouse stock records back to their original state
        DB::table('warehouse_stock')
            ->whereIn('id', range(336, 359))
            ->where('source_type', 'retur_penjualan')
            ->where('source_id', 1)
            ->update([
                'source_type' => 'penerimaan',
                'source_id' => null,
                'source_date' => null,
                'updated_at' => now()
            ]);
    }
}; 