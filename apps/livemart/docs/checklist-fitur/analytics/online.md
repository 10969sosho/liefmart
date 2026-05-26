# Checklist Fitur: Online Analytics

Kumpulan laporan analitik untuk penjualan online (Marketplace).

**Group Menu**: Online Analytics

## 1. Sales Overview Reports

### Sales Report (By Platform)
**Route**: `analytics.sales-by-platform`
**File**: `analytics/sales_by_platform.blade.php`
- [ ] **Filter**: Tanggal (Start/End), Platform, Sort (Value/Volume Highest/Lowest, Date Newest/Oldest).
- [ ] **Summary Cards**:
  - Total Pesanan
  - Total Retur
  - Total Value (Rp) & Avg Order Value
  - Total Volume (Pcs) & Avg Volume Order
- [ ] **Tabel Ringkasan**:
  - Group by Platform.
  - Kolom: Platform, Jumlah Order, Total Value, Avg Value, Total Volume, Avg Volume.
- [ ] **Fitur**: Export Excel.

### Monthly Sales Summary
**Route**: `analytics.monthly-sales-summary`
**File**: `analytics/monthly_sales_summary.blade.php`
- [ ] **Filter**: Tahun (Dropdown), Platform.
- [ ] **Visualisasi**: Grafik Bar/Line per bulan.
- [ ] **Tabel Bulanan**:
  - Baris: Januari - Desember.
  - Kolom: Total Order, Total Qty, Total Omset.
  - Subtotal per tahun.

### Sales by Day of Week
**Route**: `analytics.sales-by-day-of-week`
**File**: `analytics/sales_by_day_of_week.blade.php`
- [ ] **Analisis**: Tren penjualan berdasarkan hari (Senin-Minggu).
- [ ] **Visualisasi**: Heatmap atau Bar chart hari tersibuk.
- [ ] **Tabel**:
  - Hari (Senin, Selasa, dst).
  - Rata-rata Order, Total Order, Persentase.

### Sales by Date
**Route**: `analytics.sales-by-date-number`
**File**: `analytics/sales_by_date_number.blade.php`
- [ ] **Analisis**: Tren penjualan berdasarkan tanggal (1-31).
- [ ] **Guna**: Identifikasi pola tanggal gajian atau promo bulanan.

### Sales by Status & Day
**Route**: `analytics.sales-by-status-day`
**File**: `analytics/sales_by_status_day.blade.php`
- [ ] **Analisis**: Distribusi status pesanan (Selesai, Batal, Retur) per hari.
- [ ] **Matriks**: Cross-tabulation Hari vs Status.

## 2. Detailed Transaction Reports

### Sales Detail Report
**Route**: `analytics.sales-detail-report`
**File**: `analytics/sales_detail_report.blade.php`
- [ ] **Filter**:
  - Tanggal Range.
  - Platform.
  - Status Pesanan.
  - Search (No Pesanan/Resi).
- [ ] **Tabel Detail**:
  - Tanggal, No Pesanan, Platform, SKU, Nama Produk, Qty, Harga, Total, Status.
- [ ] **Export**: Tombol Export Excel detail (baris per item).

### Analytics Penjualan Master Internal
**Route**: `analytics.internal-product-sales`
**File**: `analytics/internal_product_sales.blade.php`
- [ ] **Fokus**: Mapping penjualan marketplace ke SKU Master Internal.
- [ ] **Filter**: Brand, Category, Date.
- [ ] **Tabel**: SKU Internal, Nama Internal, Total Qty Terjual (Agregat dari semua platform), Total Revenue.

## 3. Product Performance & Profitability

### Produk Platform Terlaris
**Route**: `analytics.produk-platform-terlaris`
**File**: `analytics/produk_platform_terlaris.blade.php`
- [ ] **Filter**: Platform, Date Range, Limit (Top 10/20/50).
- [ ] **Tabel**:
  - Rank.
  - Nama Produk Platform.
  - Total Qty.
  - Total Value.
  - Kontribusi %.

### Produk Internal Terlaris
**Route**: `analytics.produk-internal-terlaris`
**File**: `analytics/produk_internal_terlaris.blade.php`
- [ ] **Filter**: Brand, Category, Date Range.
- [ ] **Tabel**:
  - Rank.
  - SKU Internal.
  - Nama Produk.
  - Total Qty (All Channels).
  - Total Value.

### Gross Profit by Master Internal
**Route**: `analytics.sales-by-master-product`
**File**: `analytics/sales_by_master_product_new.blade.php`
- [ ] **Analisis**: Profitabilitas per produk master.
- [ ] **Perhitungan**: `(Harga Jual Rata-rata - HPP) * Qty`.
- [ ] **Tabel**:
  - SKU, Produk.
  - Qty Terjual.
  - Total Sales (Omset).
  - Total HPP (Modal).
  - Gross Profit (Rp).
  - Margin (%).

### Gross Profit Master AVR
**Route**: `analytics.sales-by-master-product-special`
**File**: `analytics/sales_by_master_product_special.blade.php`
- [ ] **Fokus**: Laporan khusus (mungkin untuk kategori/brand tertentu atau logika HPP khusus).
- [ ] **Cek**: Bandingkan output dengan report standard.

### Gross Profit Platform
**Route**: `analytics.sales-by-platform-product`
**File**: `analytics/sales_by_platform_product.blade.php`
- [ ] **Fokus**: Profitabilitas per listing produk di platform.
- [ ] **Analisis**: Identifikasi produk yang rugi di platform tertentu (misal karena salah harga atau potongan tinggi).
