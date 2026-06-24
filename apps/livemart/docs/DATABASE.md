# DATABASE.md

## Koneksi
- **Driver:** MySQL
- **Database:** `liefmart_db` (default, via .env)
- **Default Connection:** `mysql`

## Entity Relationship Summary

### Master Data
```
main_categories
    ├── id (PK)
    ├── name (KOPI, SKINCARE)
    └── is_active

brands
    ├── id (PK)
    ├── main_category_id (FK → main_categories)
    ├── name
    └── is_active

sub_brands
    ├── id (PK)
    ├── brand_id (FK → brands)
    └── name

product_categories
    ├── id (PK)
    ├── main_category_id (FK → main_categories)
    └── name

product_types
    ├── id (PK)
    ├── main_category_id (FK → main_categories)
    └── name

product_sizes
    ├── id (PK)
    ├── main_category_id (FK → main_categories)
    └── name

product_variants
    ├── id (PK)
    ├── main_category_id (FK → main_categories)
    └── name

products
    ├── id (PK)
    ├── main_category_id (FK → main_categories)
    ├── tax_category_id (FK → tax_categories)
    ├── brand_id (FK → brands)
    ├── sub_brand_id (FK → sub_brands)
    ├── product_category_id (FK → product_categories)
    ├── product_type_id (FK → product_types)
    ├── product_size_id (FK → product_sizes)
    ├── product_variant_id (FK → product_variants)
    ├── name, sku, barcode
    ├── initial_price, discount_percentage
    └── is_active

product_stocks
    ├── id (PK)
    ├── product_id (FK → products)
    ├── lokasi_id (FK → lokasi)
    └── qty

product_initial_price_versions
    ├── id (PK)
    ├── product_id (FK → products)
    ├── version (int)
    ├── initial_price, discount_percentage
    ├── is_active, valid_from, valid_until
    └── change_reason

tax_categories
    ├── id (PK)
    ├── main_category_id (FK → main_categories)
    ├── name (KOPI-PKP, KOPI-NONPKP, SKINCARE-PKP, SKINCARE-NONPKP)
    └── code (01=PKP, 02=NONPKP)

customers
    ├── id (PK)
    ├── name, email, phone, address
    └── main_category_id

payment_methods
    ├── id (PK)
    └── name

lokasi (locations)
    ├── id (PK)
    └── name

satuans (units)
    ├── id (PK)
    └── name

bank_accounts
    ├── id (PK)
    ├── platform (shopee, tiktok, etc.)
    ├── bank_name, account_number, account_name
    └── is_active
```

### Inventory
```
penerimaan (goods receipt header)
    ├── id (PK)
    ├── kode_penerimaan (unique)
    ├── main_category_id (FK)
    ├── tax_category_id (FK)
    ├── nomor_po, tanggal_penerimaan
    ├── metode_pembayaran, tanggal_jatuh_tempo
    ├── total_harga, status (draft/selesai)
    ├── catatan, lokasi_id
    └── timestamps

penerimaan_details
    ├── id (PK)
    ├── penerimaan_id (FK → penerimaan)
    ├── product_id (FK → products)
    ├── qty_diterima, harga_satuan, subtotal
    └── tax_category_id

penerimaan_activities
    ├── id (PK)
    ├── penerimaan_id (FK)
    ├── user_id (FK → users)
    ├── activity_type, description
    └── timestamps

warehouse_stock
    ├── id (PK)
    ├── product_id (FK → products)
    ├── penerimaan_detail_id (nullable)
    ├── lokasi_id (FK → lokasi)
    ├── tax_id (FK → tax_categories)
    ├── qty, qty_damaged
    ├── is_damaged (boolean)
    ├── return_from (nullable)
    └── timestamps

adjustment_histories
    ├── id (PK)
    ├── warehouse_stock_id (FK)
    ├── product_id (FK)
    ├── qty_before, qty_after
    └── reason
```

### Sales
```
platforms
    ├── id (PK)
    └── name (Shopee, TikTok, etc.)

platform_products
    ├── id (PK)
    ├── platform_id (FK → platforms)
    ├── product_name, product_variant, sku
    └── platform_product_id (external ID)

mapping_barangs (versioned)
    ├── id (PK)
    ├── platform_product_id (FK → platform_products)
    ├── product_id (FK → products)
    ├── quantity
    ├── version (int)
    ├── is_active, valid_from, valid_until
    ├── parent_mapping_id (self FK, untuk versioning)
    └── change_reason

mapping_barang_histories
    ├── id (PK)
    ├── mapping_barang_id (FK)
    ├── action (create/update/deactivate)
    ├── old_data, new_data (JSON)
    └── user_id, changed_at

orders
    ├── id (PK)
    ├── platform_id (FK → platforms)
    ├── order_number
    ├── order_date, customer_name
    ├── total_amount, status
    ├── main_category_id (FK)
    └── status_hari

order_items
    ├── id (PK)
    ├── order_id (FK → orders)
    ├── platform_product_id (FK → platform_products)
    ├── product_name, variant, quantity
    ├── price, price_after_discount, total_price
    └── warehouse_stock_id (nullable FK)

offline_sales
    ├── id (PK)
    ├── surat_jalan_number (unique)
    ├── No_PO, sale_date
    ├── customer_name, customer_id (FK → customers)
    ├── status, payment_date, payment_method
    ├── subtotal, tax_amount, total_amount
    ├── notes, created_by (FK → users)
    └── main_category_id

offline_sale_items
    ├── id (PK)
    ├── offline_sale_id (FK → offline_sales)
    ├── product_id (FK → products)
    ├── warehouse_stock_id (FK)
    ├── quantity, unit_price
    ├── discount_amount_1~5, discount_percent_1~5
    ├── subtotal, notes
    └── discount_mapping (JSON)

barang_keluar (outgoing items)
    ├── id (PK)
    ├── order_item_id (FK → order_items)
    ├── warehouse_stock_id (FK → warehouse_stock)
    ├── offline_sale_item_id (nullable FK)
    ├── product_id (FK)
    ├── qty
    ├── tax_id (FK → tax_categories)
    └── finance_offline_id (nullable FK)
```

### Returns
```
retur_pembelians (purchase returns)
    ├── id (PK)
    ├── kode_retur, tanggal_retur
    ├── supplier_name, alasan
    ├── total_amount, status
    └── tipe_retur

retur_pembelian_details
    ├── id (PK)
    ├── retur_pembelian_id (FK)
    ├── product_id (FK)
    └── qty, harga_satuan, subtotal, alasan

retur_penjualans (sales returns)
    ├── id (PK)
    ├── kode_retur, tanggal_retur
    ├── customer_name, alasan
    ├── total_amount, status
    └── order_id (nullable FK)

retur_penjualan_details
    ├── id (PK)
    ├── retur_penjualan_id (FK)
    ├── product_id (FK)
    ├── qty, harga_satuan, subtotal
    └── kondisi (baik/rusak)

retur_offline_sales (offline returns)
    ├── id (PK)
    └── (similar structure for offline returns)

retur_offline_sale_details
    ├── id (PK)
    └── (detail items)
```

### Finance
```
financial_transactions (general ledger)
    ├── id (PK)
    ├── transaction_type, description
    ├── amount, transaction_date
    ├── reference_type, reference_id
    └── main_category_id

finance_offlines (offline invoices)
    ├── id (PK)
    ├── offline_sale_id (FK → offline_sales)
    ├── invoice_number (format: counter/YYMM/suffix/taxCode)
    ├── customer_name, total_amount, paid_amount
    ├── status (unpaid/paid/refund)
    ├── print_count, max_print
    ├── payment_date, payment_method
    ├── refund_status
    ├── created_by (FK → users)
    └── main_category_id

invoice_payments
    ├── id (PK)
    ├── finance_offline_id (FK)
    ├── amount, payment_date
    └── payment_method

invoice_sequences
    ├── id (PK)
    ├── year_month (YYMM)
    ├── category, sales_type, tax_status
    ├── counter (sequential number)
    └── global_counter

surat_jalan_sequences
    ├── id (PK)
    ├── year_month
    └── counter

shopee_financial_transactions / shopee2_financial_transactions
    ├── id (PK)
    ├── order_id (FK → orders, nullable)
    ├── transaction_date, description
    ├── amount, biaya_1~12
    ├── qty, status
    └── is_locked (boolean)

tiktok_financial_transactions / tiktok2_financial_transactions
    ├── id (PK)
    └── (same structure as shopee)

arus_kas_shopee_imports / arus_kas_shopee2_imports
    ├── id (PK)
    ├── raw data columns
    └── imported_at

arus_kas_tiktok_imports / arus_kas_tiktok2_imports
    └── (same pattern)
```

### Admin & Auth
```
users
    ├── id (PK)
    ├── name, username, email, password
    ├── role_id (FK → roles)
    ├── is_active (boolean)
    └── timestamps

roles
    ├── id (PK)
    ├── name (superadmin, admin, etc.)
    └── is_active

permissions
    ├── id (PK)
    ├── name (e.g. sales.view, warehouse.create)
    ├── group (sales, warehouse, finance, etc.)
    └── description

role_permission
    ├── role_id (FK)
    └── permission_id (FK)

personal_access_tokens (Sanctum)
    └── token management

password_resets
    └── password reset tokens
```

### Other
```
parameters
    ├── id (PK)
    ├── key, value
    └── description

import_temp
    └── temporary import data
```

## Key Indexes
- `orders.order_date` — untuk laporan penjualan
- `orders.main_category_id` — scope multi-bisnis
- `products.main_category_id` — scope multi-bisnis
- `finance_offlines.invoice_number` — unique invoice tracking
- `warehouse_stock.product_id` — stock lookup
- `mapping_barangs.platform_product_id + is_active` — mapping lookup
- Composite indexes untuk performance analytics queries
