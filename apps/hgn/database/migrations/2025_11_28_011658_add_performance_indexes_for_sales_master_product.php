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
     * Add performance indexes for Sales by Master Product analytics
     * Based on performance test results analysis
     */
    public function up()
    {
        // Index on orders.tanggal (CRITICAL - for date filtering)
        if (!$this->indexExists('orders', 'idx_orders_tanggal')) {
            DB::statement('CREATE INDEX idx_orders_tanggal ON orders(tanggal)');
        }
        
        // Composite indexes for financial transactions (for SUM and EXISTS queries)
        // Shopee
        if (!$this->indexExists('shopee_financial_transactions', 'idx_shopee_ft_no_order_saldo')) {
            DB::statement('CREATE INDEX idx_shopee_ft_no_order_saldo ON shopee_financial_transactions(no_order, saldo_masuk)');
        }
        
        // TikTok
        if (!$this->indexExists('tiktok_financial_transactions', 'idx_tiktok_ft_no_order_saldo')) {
            DB::statement('CREATE INDEX idx_tiktok_ft_no_order_saldo ON tiktok_financial_transactions(no_order, saldo_masuk)');
        }
        
        // Tokopedia
        if (!$this->indexExists('tokopedia_financial_transactions', 'idx_tokopedia_ft_no_order_saldo')) {
            DB::statement('CREATE INDEX idx_tokopedia_ft_no_order_saldo ON tokopedia_financial_transactions(no_order, saldo_masuk)');
        }
        
        // Blibli
        if (!$this->indexExists('blibli_financial_transactions', 'idx_blibli_ft_no_order_saldo')) {
            DB::statement('CREATE INDEX idx_blibli_ft_no_order_saldo ON blibli_financial_transactions(no_order, saldo_masuk)');
        }
        
        // Indexes for invoice query (with ORDER BY)
        // Shopee
        if (!$this->indexExists('shopee_financial_transactions', 'idx_shopee_ft_invoice')) {
            DB::statement('CREATE INDEX idx_shopee_ft_invoice ON shopee_financial_transactions(no_order, saldo_masuk, tanggal_masuk_pembayaran, no_invoice)');
        }
        
        // TikTok
        if (!$this->indexExists('tiktok_financial_transactions', 'idx_tiktok_ft_invoice')) {
            DB::statement('CREATE INDEX idx_tiktok_ft_invoice ON tiktok_financial_transactions(no_order, saldo_masuk, tanggal_masuk_pembayaran, no_invoice)');
        }
        
        // Tokopedia
        if (!$this->indexExists('tokopedia_financial_transactions', 'idx_tokopedia_ft_invoice')) {
            DB::statement('CREATE INDEX idx_tokopedia_ft_invoice ON tokopedia_financial_transactions(no_order, saldo_masuk, tanggal_masuk_pembayaran, no_invoice)');
        }
        
        // Blibli
        if (!$this->indexExists('blibli_financial_transactions', 'idx_blibli_ft_invoice')) {
            DB::statement('CREATE INDEX idx_blibli_ft_invoice ON blibli_financial_transactions(no_order, saldo_masuk, tanggal_masuk_pembayaran, no_invoice)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Drop indexes
        if ($this->indexExists('orders', 'idx_orders_tanggal')) {
            DB::statement('DROP INDEX idx_orders_tanggal ON orders');
        }
        
        if ($this->indexExists('shopee_financial_transactions', 'idx_shopee_ft_no_order_saldo')) {
            DB::statement('DROP INDEX idx_shopee_ft_no_order_saldo ON shopee_financial_transactions');
        }
        
        if ($this->indexExists('tiktok_financial_transactions', 'idx_tiktok_ft_no_order_saldo')) {
            DB::statement('DROP INDEX idx_tiktok_ft_no_order_saldo ON tiktok_financial_transactions');
        }
        
        if ($this->indexExists('tokopedia_financial_transactions', 'idx_tokopedia_ft_no_order_saldo')) {
            DB::statement('DROP INDEX idx_tokopedia_ft_no_order_saldo ON tokopedia_financial_transactions');
        }
        
        if ($this->indexExists('blibli_financial_transactions', 'idx_blibli_ft_no_order_saldo')) {
            DB::statement('DROP INDEX idx_blibli_ft_no_order_saldo ON blibli_financial_transactions');
        }
        
        if ($this->indexExists('shopee_financial_transactions', 'idx_shopee_ft_invoice')) {
            DB::statement('DROP INDEX idx_shopee_ft_invoice ON shopee_financial_transactions');
        }
        
        if ($this->indexExists('tiktok_financial_transactions', 'idx_tiktok_ft_invoice')) {
            DB::statement('DROP INDEX idx_tiktok_ft_invoice ON tiktok_financial_transactions');
        }
        
        if ($this->indexExists('tokopedia_financial_transactions', 'idx_tokopedia_ft_invoice')) {
            DB::statement('DROP INDEX idx_tokopedia_ft_invoice ON tokopedia_financial_transactions');
        }
        
        if ($this->indexExists('blibli_financial_transactions', 'idx_blibli_ft_invoice')) {
            DB::statement('DROP INDEX idx_blibli_ft_invoice ON blibli_financial_transactions');
        }
    }
    
    /**
     * Check if index exists
     */
    private function indexExists($table, $indexName)
    {
        $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($result) > 0;
    }
};
