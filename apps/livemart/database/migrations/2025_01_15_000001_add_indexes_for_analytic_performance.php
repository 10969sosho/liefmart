<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index untuk orders table
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!$this->indexExists('orders', 'idx_orders_tanggal_platform')) {
                    $table->index(['tanggal', 'platform_id'], 'idx_orders_tanggal_platform');
                }
                if (!$this->indexExists('orders', 'idx_orders_order_number')) {
                    $table->index('order_number', 'idx_orders_order_number');
                }
            });
        }

        // Index untuk order_items table
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (!$this->indexExists('order_items', 'idx_order_items_order_id')) {
                    $table->index('order_id', 'idx_order_items_order_id');
                }
            });
        }

        // Index untuk barang_keluar table
        if (Schema::hasTable('barang_keluar')) {
            Schema::table('barang_keluar', function (Blueprint $table) {
                if (!$this->indexExists('barang_keluar', 'idx_barang_keluar_order_item_id')) {
                    $table->index('order_item_id', 'idx_barang_keluar_order_item_id');
                }
                if (!$this->indexExists('barang_keluar', 'idx_barang_keluar_warehouse_stock_id')) {
                    $table->index('warehouse_stock_id', 'idx_barang_keluar_warehouse_stock_id');
                }
            });
        }

        // Index untuk warehouse_stock table
        if (Schema::hasTable('warehouse_stock')) {
            Schema::table('warehouse_stock', function (Blueprint $table) {
                if (!$this->indexExists('warehouse_stock', 'idx_warehouse_stock_product_id')) {
                    $table->index('product_id', 'idx_warehouse_stock_product_id');
                }
                if (!$this->indexExists('warehouse_stock', 'idx_warehouse_stock_penerimaan_detail_id')) {
                    $table->index('penerimaan_detail_id', 'idx_warehouse_stock_penerimaan_detail_id');
                }
            });
        }

        // Index untuk products table
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!$this->indexExists('products', 'idx_products_brand_id')) {
                    $table->index('brand_id', 'idx_products_brand_id');
                }
                if (!$this->indexExists('products', 'idx_products_sub_brand_id')) {
                    $table->index('sub_brand_id', 'idx_products_sub_brand_id');
                }
                if (!$this->indexExists('products', 'idx_products_product_category_id')) {
                    $table->index('product_category_id', 'idx_products_product_category_id');
                }
                if (!$this->indexExists('products', 'idx_products_product_type_id')) {
                    $table->index('product_type_id', 'idx_products_product_type_id');
                }
                if (!$this->indexExists('products', 'idx_products_product_size_id')) {
                    $table->index('product_size_id', 'idx_products_product_size_id');
                }
                if (!$this->indexExists('products', 'idx_products_product_variant_id')) {
                    $table->index('product_variant_id', 'idx_products_product_variant_id');
                }
                if (!$this->indexExists('products', 'idx_products_sku')) {
                    $table->index('sku', 'idx_products_sku');
                }
            });
        }

        // Index untuk financial transactions tables
        $this->addFinancialTransactionIndexes('shopee_financial_transactions');
        $this->addFinancialTransactionIndexes('tiktok_financial_transactions');

        // Index untuk penerimaan table
        if (Schema::hasTable('penerimaan')) {
            Schema::table('penerimaan', function (Blueprint $table) {
                if (!$this->indexExists('penerimaan', 'idx_penerimaan_status')) {
                    $table->index('status', 'idx_penerimaan_status');
                }
            });
        }

        // Index untuk penerimaan_detail table
        if (Schema::hasTable('penerimaan_detail')) {
            Schema::table('penerimaan_detail', function (Blueprint $table) {
                if (!$this->indexExists('penerimaan_detail', 'idx_penerimaan_detail_penerimaan_id')) {
                    $table->index('penerimaan_id', 'idx_penerimaan_detail_penerimaan_id');
                }
                if (!$this->indexExists('penerimaan_detail', 'idx_penerimaan_detail_product_id')) {
                    $table->index('product_id', 'idx_penerimaan_detail_product_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if ($this->indexExists('orders', 'idx_orders_tanggal_platform')) {
                    $table->dropIndex('idx_orders_tanggal_platform');
                }
                if ($this->indexExists('orders', 'idx_orders_order_number')) {
                    $table->dropIndex('idx_orders_order_number');
                }
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if ($this->indexExists('order_items', 'idx_order_items_order_id')) {
                    $table->dropIndex('idx_order_items_order_id');
                }
            });
        }

        if (Schema::hasTable('barang_keluar')) {
            Schema::table('barang_keluar', function (Blueprint $table) {
                if ($this->indexExists('barang_keluar', 'idx_barang_keluar_order_item_id')) {
                    $table->dropIndex('idx_barang_keluar_order_item_id');
                }
                if ($this->indexExists('barang_keluar', 'idx_barang_keluar_warehouse_stock_id')) {
                    $table->dropIndex('idx_barang_keluar_warehouse_stock_id');
                }
            });
        }

        if (Schema::hasTable('warehouse_stock')) {
            Schema::table('warehouse_stock', function (Blueprint $table) {
                if ($this->indexExists('warehouse_stock', 'idx_warehouse_stock_product_id')) {
                    $table->dropIndex('idx_warehouse_stock_product_id');
                }
                if ($this->indexExists('warehouse_stock', 'idx_warehouse_stock_penerimaan_detail_id')) {
                    $table->dropIndex('idx_warehouse_stock_penerimaan_detail_id');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if ($this->indexExists('products', 'idx_products_brand_id')) {
                    $table->dropIndex('idx_products_brand_id');
                }
                if ($this->indexExists('products', 'idx_products_sub_brand_id')) {
                    $table->dropIndex('idx_products_sub_brand_id');
                }
                if ($this->indexExists('products', 'idx_products_product_category_id')) {
                    $table->dropIndex('idx_products_product_category_id');
                }
                if ($this->indexExists('products', 'idx_products_product_type_id')) {
                    $table->dropIndex('idx_products_product_type_id');
                }
                if ($this->indexExists('products', 'idx_products_product_size_id')) {
                    $table->dropIndex('idx_products_product_size_id');
                }
                if ($this->indexExists('products', 'idx_products_product_variant_id')) {
                    $table->dropIndex('idx_products_product_variant_id');
                }
                if ($this->indexExists('products', 'idx_products_sku')) {
                    $table->dropIndex('idx_products_sku');
                }
            });
        }

        $this->removeFinancialTransactionIndexes('shopee_financial_transactions');
        $this->removeFinancialTransactionIndexes('tiktok_financial_transactions');

        if (Schema::hasTable('penerimaan')) {
            Schema::table('penerimaan', function (Blueprint $table) {
                if ($this->indexExists('penerimaan', 'idx_penerimaan_status')) {
                    $table->dropIndex('idx_penerimaan_status');
                }
            });
        }

        if (Schema::hasTable('penerimaan_detail')) {
            Schema::table('penerimaan_detail', function (Blueprint $table) {
                if ($this->indexExists('penerimaan_detail', 'idx_penerimaan_detail_penerimaan_id')) {
                    $table->dropIndex('idx_penerimaan_detail_penerimaan_id');
                }
                if ($this->indexExists('penerimaan_detail', 'idx_penerimaan_detail_product_id')) {
                    $table->dropIndex('idx_penerimaan_detail_product_id');
                }
            });
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        
        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$database, $table, $indexName]
        );
        
        return $result[0]->count > 0;
    }

    /**
     * Add indexes for financial transaction table
     */
    private function addFinancialTransactionIndexes(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            $indexName = 'idx_' . str_replace('_financial_transactions', '_ft_order_saldo', $tableName);
            if (!$this->indexExists($tableName, $indexName)) {
                $table->index(['no_order', 'saldo_masuk'], $indexName);
            }
        });
    }

    /**
     * Remove indexes for financial transaction table
     */
    private function removeFinancialTransactionIndexes(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            $indexName = 'idx_' . str_replace('_financial_transactions', '_ft_order_saldo', $tableName);
            if ($this->indexExists($tableName, $indexName)) {
                $table->dropIndex($indexName);
            }
        });
    }
};

