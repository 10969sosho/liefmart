<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WipeItemData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wipe:item-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe all item related data (Products, Stock, Mapping, Penerimaan, etc.) but keep Master Data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('This will DELETE ALL product, stock, and mapping data. Are you sure?')) {
            return;
        }

        $this->info('Wiping Item Data...');

        // Disable Foreign Key Checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // List of tables to truncate
        // Based on user request: product, warehouse_stock, mapping, barang platform, penerimaan
        // And "all yang berkaitan tentang item" -> sales, returns, etc.
        $tables = [
            'warehouse_stock',
            'mapping_barangs',
            'platform_products',
            'penerimaan_detail',
            'penerimaan',
            'penerimaan_activities',
            'products',
            // Also clearing transaction tables to ensure clean slate
            'order_items',
            'orders',
            'offline_sale_items',
            'offline_sales',
            'retur_penjualan_details',
            'retur_penjualans',
            'retur_pembelian_details',
            'retur_pembelians',
            'retur_offline_sale_details',
            'retur_offline_sales',
            'barang_keluar',
            'finance_offlines', // Linked to Offline Sales
            'import_temps', // Temp imports
        ];

        foreach ($tables as $table) {
            try {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::table($table)->truncate();
                    $this->info("Truncated: {$table}");
                } else {
                    $this->warn("Table not found: {$table}");
                }
            } catch (\Exception $e) {
                $this->error("Error truncating {$table}: " . $e->getMessage());
            }
        }

        // Re-enable Foreign Key Checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Item Data Wipe Completed!');
    }
}
