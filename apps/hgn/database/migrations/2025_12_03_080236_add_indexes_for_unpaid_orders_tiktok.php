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
     * Add indexes to optimize unpaid orders query for TikTok financial
     * These indexes will significantly improve query performance for:
     * - Filtering orders by platform_id and sorting by tanggal
     * - JOIN with order_items
     * - Checking tiktok_financial_transactions (whereDoesntHave)
     * - Filtering fully returned orders
     */
    public function up()
    {
        // =====================================================
        // 1. Index untuk Orders Table
        // =====================================================
        
        // Index untuk platform_id dan tanggal (untuk filter dan sorting)
        // Sangat penting untuk query: WHERE platform_id = 3 ORDER BY tanggal DESC
        if (!$this->indexExists('orders', 'idx_orders_platform_tanggal')) {
            DB::statement('CREATE INDEX idx_orders_platform_tanggal ON orders(platform_id, tanggal DESC)');
        }
        
        // Index untuk order_number (jika ada filter order_number)
        if (!$this->indexExists('orders', 'idx_orders_order_number')) {
            DB::statement('CREATE INDEX idx_orders_order_number ON orders(order_number)');
        }
        
        // Index untuk tanggal saja (untuk sorting)
        if (!$this->indexExists('orders', 'idx_orders_tanggal')) {
            DB::statement('CREATE INDEX idx_orders_tanggal ON orders(tanggal DESC)');
        }
        
        // =====================================================
        // 2. Index untuk Order Items Table
        // =====================================================
        
        // Index untuk order_id (untuk JOIN dengan orders)
        // Sangat penting untuk LEFT JOIN order_items ON orders.id = order_items.order_id
        if (!$this->indexExists('order_items', 'idx_order_items_order_id')) {
            DB::statement('CREATE INDEX idx_order_items_order_id ON order_items(order_id)');
        }
        
        // Composite index untuk order_id dan calculation fields
        // Membantu query SUM(price_after_discount * quantity)
        if (!$this->indexExists('order_items', 'idx_order_items_order_calc')) {
            DB::statement('CREATE INDEX idx_order_items_order_calc ON order_items(order_id, price_after_discount, quantity)');
        }
        
        // =====================================================
        // 3. Index untuk Tiktok Financial Transactions
        // =====================================================
        
        // Index untuk order_id (untuk whereDoesntHave)
        // Sangat penting untuk: NOT EXISTS (SELECT 1 FROM tiktok_financial_transactions WHERE order_id = orders.id)
        if (!$this->indexExists('tiktok_financial_transactions', 'idx_tiktok_financial_order_id')) {
            DB::statement('CREATE INDEX idx_tiktok_financial_order_id ON tiktok_financial_transactions(order_id)');
        }
        
        // Composite index jika ada filter lain
        if (!$this->indexExists('tiktok_financial_transactions', 'idx_tiktok_financial_order_platform')) {
            DB::statement('CREATE INDEX idx_tiktok_financial_order_platform ON tiktok_financial_transactions(order_id, created_at)');
        }
        
        // Index untuk no_order (untuk filter outstanding status - sangat penting!)
        if (!$this->indexExists('tiktok_financial_transactions', 'idx_tiktok_financial_no_order')) {
            DB::statement('CREATE INDEX idx_tiktok_financial_no_order ON tiktok_financial_transactions(no_order)');
        }
        
        // Composite index untuk no_order dan outstanding (untuk filter outstanding status)
        if (!$this->indexExists('tiktok_financial_transactions', 'idx_tiktok_financial_no_order_outstanding')) {
            DB::statement('CREATE INDEX idx_tiktok_financial_no_order_outstanding ON tiktok_financial_transactions(no_order, outstanding)');
        }
        
        // =====================================================
        // 4. Index untuk Retur Penjualan
        // =====================================================
        
        // Index untuk order_item_id (untuk join dengan order_items)
        // Sangat penting untuk subquery retur_penjualan_details
        if (!$this->indexExists('retur_penjualan_details', 'idx_retur_details_order_item')) {
            DB::statement('CREATE INDEX idx_retur_details_order_item ON retur_penjualan_details(order_item_id)');
        }
        
        // Index untuk retur_penjualan_id (untuk join dengan retur_penjualans)
        if (!$this->indexExists('retur_penjualan_details', 'idx_retur_details_retur_id')) {
            DB::statement('CREATE INDEX idx_retur_details_retur_id ON retur_penjualan_details(retur_penjualan_id)');
        }
        
        // Index untuk status (untuk filter status IN ('draft', 'selesai'))
        if (!$this->indexExists('retur_penjualans', 'idx_retur_penjualan_status')) {
            DB::statement('CREATE INDEX idx_retur_penjualan_status ON retur_penjualans(status)');
        }
        
        // Composite index untuk join yang lebih efisien
        if (!$this->indexExists('retur_penjualan_details', 'idx_retur_details_status')) {
            DB::statement('CREATE INDEX idx_retur_details_status ON retur_penjualan_details(retur_penjualan_id, order_item_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Drop indexes in reverse order
        DB::statement('DROP INDEX IF EXISTS idx_retur_details_status ON retur_penjualan_details');
        DB::statement('DROP INDEX IF EXISTS idx_retur_penjualan_status ON retur_penjualans');
        DB::statement('DROP INDEX IF EXISTS idx_retur_details_retur_id ON retur_penjualan_details');
        DB::statement('DROP INDEX IF EXISTS idx_retur_details_order_item ON retur_penjualan_details');
        DB::statement('DROP INDEX IF EXISTS idx_tiktok_financial_no_order_outstanding ON tiktok_financial_transactions');
        DB::statement('DROP INDEX IF EXISTS idx_tiktok_financial_no_order ON tiktok_financial_transactions');
        DB::statement('DROP INDEX IF EXISTS idx_tiktok_financial_order_platform ON tiktok_financial_transactions');
        DB::statement('DROP INDEX IF EXISTS idx_tiktok_financial_order_id ON tiktok_financial_transactions');
        DB::statement('DROP INDEX IF EXISTS idx_order_items_order_calc ON order_items');
        DB::statement('DROP INDEX IF EXISTS idx_order_items_order_id ON order_items');
        DB::statement('DROP INDEX IF EXISTS idx_orders_tanggal ON orders');
        DB::statement('DROP INDEX IF EXISTS idx_orders_order_number ON orders');
        DB::statement('DROP INDEX IF EXISTS idx_orders_platform_tanggal ON orders');
    }
    
    /**
     * Check if index exists
     */
    private function indexExists($table, $indexName)
    {
        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = DATABASE() 
             AND table_name = ? 
             AND index_name = ?",
            [$table, $indexName]
        );
        
        return $result[0]->count > 0;
    }
};
