<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!$this->indexExists('shopee_financial_transactions', 'idx_shopee_ft_order_id')) {
            DB::statement('CREATE INDEX idx_shopee_ft_order_id ON shopee_financial_transactions(order_id)');
        }

        if (!$this->indexExists('shopee2_financial_transactions', 'idx_shopee2_ft_order_id')) {
            DB::statement('CREATE INDEX idx_shopee2_ft_order_id ON shopee2_financial_transactions(order_id)');
        }

        if (!$this->indexExists('tiktok_financial_transactions', 'idx_tiktok_ft_order_id')) {
            DB::statement('CREATE INDEX idx_tiktok_ft_order_id ON tiktok_financial_transactions(order_id)');
        }

        if (!$this->indexExists('tiktok2_financial_transactions', 'idx_tiktok2_ft_order_id')) {
            DB::statement('CREATE INDEX idx_tiktok2_ft_order_id ON tiktok2_financial_transactions(order_id)');
        }

        if (!$this->indexExists('tokopedia_financial_transactions', 'idx_tokopedia_ft_order_id')) {
            DB::statement('CREATE INDEX idx_tokopedia_ft_order_id ON tokopedia_financial_transactions(order_id)');
        }

        if (!$this->indexExists('blibli_financial_transactions', 'idx_blibli_ft_order_id')) {
            DB::statement('CREATE INDEX idx_blibli_ft_order_id ON blibli_financial_transactions(order_id)');
        }

        if (!$this->indexExists('lazada_financial_transactions', 'idx_lazada_ft_order_id')) {
            DB::statement('CREATE INDEX idx_lazada_ft_order_id ON lazada_financial_transactions(order_id)');
        }

        if (!$this->indexExists('order_items', 'idx_order_items_order_qty')) {
            DB::statement('CREATE INDEX idx_order_items_order_qty ON order_items(order_id, quantity)');
        }

        if (!$this->indexExists('order_items', 'idx_order_items_ws_id')) {
            DB::statement('CREATE INDEX idx_order_items_ws_id ON order_items(warehouse_stock_id)');
        }
    }

    public function down()
    {
        if ($this->indexExists('shopee_financial_transactions', 'idx_shopee_ft_order_id')) {
            DB::statement('DROP INDEX idx_shopee_ft_order_id ON shopee_financial_transactions');
        }

        if ($this->indexExists('shopee2_financial_transactions', 'idx_shopee2_ft_order_id')) {
            DB::statement('DROP INDEX idx_shopee2_ft_order_id ON shopee2_financial_transactions');
        }

        if ($this->indexExists('tiktok_financial_transactions', 'idx_tiktok_ft_order_id')) {
            DB::statement('DROP INDEX idx_tiktok_ft_order_id ON tiktok_financial_transactions');
        }

        if ($this->indexExists('tiktok2_financial_transactions', 'idx_tiktok2_ft_order_id')) {
            DB::statement('DROP INDEX idx_tiktok2_ft_order_id ON tiktok2_financial_transactions');
        }

        if ($this->indexExists('tokopedia_financial_transactions', 'idx_tokopedia_ft_order_id')) {
            DB::statement('DROP INDEX idx_tokopedia_ft_order_id ON tokopedia_financial_transactions');
        }

        if ($this->indexExists('blibli_financial_transactions', 'idx_blibli_ft_order_id')) {
            DB::statement('DROP INDEX idx_blibli_ft_order_id ON blibli_financial_transactions');
        }

        if ($this->indexExists('lazada_financial_transactions', 'idx_lazada_ft_order_id')) {
            DB::statement('DROP INDEX idx_lazada_ft_order_id ON lazada_financial_transactions');
        }

        if ($this->indexExists('order_items', 'idx_order_items_order_qty')) {
            DB::statement('DROP INDEX idx_order_items_order_qty ON order_items');
        }

        if ($this->indexExists('order_items', 'idx_order_items_ws_id')) {
            DB::statement('DROP INDEX idx_order_items_ws_id ON order_items');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($result) > 0;
    }
};
