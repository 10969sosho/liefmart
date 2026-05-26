-- =====================================================
-- Index untuk Optimasi Query Order Belum Terbayar TikTok
-- =====================================================
-- File ini berisi index SQL yang direkomendasikan untuk
-- meningkatkan performa query unpaid orders di TikTok financial
-- 
-- Eksekusi file ini di database untuk meningkatkan performa
-- =====================================================

-- =====================================================
-- 1. Index untuk Orders Table
-- =====================================================

-- Index untuk platform_id dan tanggal (untuk filter dan sorting)
-- Sangat penting untuk query: WHERE platform_id = 3 ORDER BY tanggal DESC
CREATE INDEX IF NOT EXISTS idx_orders_platform_tanggal 
ON orders(platform_id, tanggal DESC);

-- Index untuk order_number (jika ada filter order_number)
CREATE INDEX IF NOT EXISTS idx_orders_order_number 
ON orders(order_number);

-- Index untuk tanggal saja (untuk sorting)
CREATE INDEX IF NOT EXISTS idx_orders_tanggal 
ON orders(tanggal DESC);

-- =====================================================
-- 2. Index untuk Order Items Table
-- =====================================================

-- Index untuk order_id (untuk JOIN dengan orders)
-- Sangat penting untuk LEFT JOIN order_items ON orders.id = order_items.order_id
CREATE INDEX IF NOT EXISTS idx_order_items_order_id 
ON order_items(order_id);

-- Composite index untuk order_id dan calculation fields
-- Membantu query SUM(price_after_discount * quantity)
CREATE INDEX IF NOT EXISTS idx_order_items_order_calc 
ON order_items(order_id, price_after_discount, quantity);

-- =====================================================
-- 3. Index untuk Tiktok Financial Transactions
-- =====================================================

-- Index untuk order_id (untuk whereDoesntHave)
-- Sangat penting untuk: NOT EXISTS (SELECT 1 FROM tiktok_financial_transactions WHERE order_id = orders.id)
CREATE INDEX IF NOT EXISTS idx_tiktok_financial_order_id 
ON tiktok_financial_transactions(order_id);

-- Composite index jika ada filter lain
CREATE INDEX IF NOT EXISTS idx_tiktok_financial_order_platform 
ON tiktok_financial_transactions(order_id, created_at);

-- =====================================================
-- 4. Index untuk Retur Penjualan
-- =====================================================

-- Index untuk order_item_id (untuk join dengan order_items)
-- Sangat penting untuk subquery retur_penjualan_details
CREATE INDEX IF NOT EXISTS idx_retur_details_order_item 
ON retur_penjualan_details(order_item_id);

-- Index untuk retur_penjualan_id (untuk join dengan retur_penjualans)
CREATE INDEX IF NOT EXISTS idx_retur_details_retur_id 
ON retur_penjualan_details(retur_penjualan_id);

-- Index untuk status (untuk filter status IN ('draft', 'selesai'))
CREATE INDEX IF NOT EXISTS idx_retur_penjualan_status 
ON retur_penjualans(status);

-- Composite index untuk join yang lebih efisien
CREATE INDEX IF NOT EXISTS idx_retur_details_status 
ON retur_penjualan_details(retur_penjualan_id, order_item_id);

-- =====================================================
-- 5. Verifikasi Index
-- =====================================================

-- Query untuk melihat index yang sudah dibuat
-- SELECT 
--     TABLE_NAME,
--     INDEX_NAME,
--     COLUMN_NAME,
--     SEQ_IN_INDEX,
--     INDEX_TYPE
-- FROM 
--     INFORMATION_SCHEMA.STATISTICS
-- WHERE 
--     TABLE_SCHEMA = DATABASE()
--     AND INDEX_NAME LIKE 'idx_%'
-- ORDER BY 
--     TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- =====================================================
-- Catatan:
-- =====================================================
-- 1. Index akan meningkatkan performa SELECT query
-- 2. Index akan sedikit memperlambat INSERT/UPDATE/DELETE
-- 3. Monitor ukuran database setelah membuat index
-- 4. Jika ada masalah, gunakan DROP INDEX untuk menghapus
-- =====================================================

