# Checklist Fitur: Offline Analytics

Kumpulan laporan analitik untuk penjualan offline (Non-Marketplace/Toko).

**Group Menu**: Offline Analytics

## 1. Monthly Sales Summary
**Route**: `analytics.offline.monthly-sales-summary`
**File**: `analytics/offline_monthly_sales_summary.blade.php`
- [ ] **Filter**: Tahun.
- [ ] **Summary Cards**:
  - Total Tahun Ini (Omset).
  - Total Qty Tahun Ini.
  - Rata-rata Bulanan.
- [ ] **Grafik**: Bar Chart per bulan.
- [ ] **Tabel Bulanan**:
  - Bulan.
  - Jumlah Transaksi.
  - Qty Terjual.
  - Total Penjualan (Rp).

## 2. Sales by Customer
**Route**: `analytics.offline.sales-by-customer`
**File**: `analytics/offline_sales_by_customer.blade.php`
- [ ] **Filter**: Date Range, Limit (Top N).
- [ ] **Analisis**: Siapa pelanggan terbesar (Pareto Analysis).
- [ ] **Tabel**:
  - Rank.
  - Nama Customer.
  - Frekuensi Belanja (Order Count).
  - Total Belanja (Total Value).
  - Kontribusi (%).

## 3. Sales Detail Report
**Route**: `analytics.offline.sales-detail-report`
**File**: `analytics/offline_sales_detail_report.blade.php`
- [ ] **Filter**: Date Range, Customer, Product.
- [ ] **Tabel Detail**:
  - No Invoice/Nota.
  - Tanggal.
  - Customer.
  - Item Produk.
  - Qty.
  - Harga Satuan.
  - Diskon.
  - Total.
- [ ] **Fitur**: Export Excel.

## 4. Sales by Product
**Route**: `analytics.offline.sales-by-product`
**File**: `analytics/offline_sales_by_product.blade.php`
- [ ] **Filter**: Date Range, Category, Brand.
- [ ] **Analisis**: Produk terlaris di toko offline.
- [ ] **Tabel**:
  - Rank.
  - SKU / Nama Produk.
  - Qty Terjual.
  - Total Nilai Penjualan.

## 5. Gross Profit (Offline)
**Route**: `analytics.offline.gross-profit`
**File**: `analytics/gross_profit_offline.blade.php`
- [ ] **Filter**: Date Range.
- [ ] **Perhitungan**:
  - `Gross Profit = Total Penjualan - HPP`.
  - `Margin % = (Gross Profit / Total Penjualan) * 100`.
- [ ] **Tabel**:
  - Produk.
  - Qty.
  - Sales.
  - HPP.
  - Profit.
  - Margin %.
