# Checklist Fitur: Analytic Finance

Modul analitik keuangan per platform marketplace (Shopee, Tiktok).

**Routes**:
- `analytics.finance.shopee`
- `analytics.finance.tiktok`

## 1. Fitur Umum (Semua Platform)
Setiap halaman analitik platform memiliki struktur serupa:

### Filter & Navigasi
- [ ] **Filter Tanggal**:
  - Input `start_date` dan `end_date`.
  - Default: Awal bulan ini s/d Hari ini.
- [ ] **Tombol Filter**: Submit form filter.
- [ ] **Tombol Reset**: Reset filter ke default.
- [ ] **Tombol Export Excel**: Download laporan dalam format Excel sesuai filter.

### Summary Cards (Ringkasan)
- [ ] **Total Pesanan**: Jumlah transaksi (order count).
- [ ] **Total Pendapatan (Revenue)**: Total nilai penjualan (Gross).
- [ ] **Biaya Admin/Layanan**: Total potongan marketplace.
- [ ] **Ongkir Dibayar**: Total ongkos kirim yang dibayar seller.
- [ ] **Total Bersih (Net)**: Pendapatan bersih (Revenue - Biaya - Ongkir).

### Grafik & Visualisasi
- [ ] **Grafik Tren Harian**:
  - Sumbu X: Tanggal.
  - Sumbu Y: Nilai (Rp).
  - Tooltip: Detail per tanggal.

## 2. Tabel Transaksi Per Platform
Setiap platform memiliki kolom spesifik dalam tabel detail transaksi.

### Shopee (`analytics/finance/shopee.blade.php`)
- [ ] **Kolom Tabel**:
  - No. Pesanan
  - Tanggal Pesanan
  - Status Pesanan
  - Total Harga Produk
  - Total Diskon
  - Biaya Admin
  - Biaya Layanan
  - Ongkos Kirim
  - Total Bersih
- [ ] **Perhitungan**: Validasi `Total Bersih = Harga Produk - Diskon - Admin - Layanan - Ongkir`.

### Tiktok (`analytics/finance/tiktok.blade.php`)
- [ ] **Kolom Tabel**:
  - Order ID
  - Order Status
  - SKU ID
  - Product Name
  - Quantity
  - Unit Price
  - Total Amount
  - Platform Commission
  - Affiliate Commission
  - Transaction Fee
  - Settlement Amount
- [ ] **Perhitungan**: Cek komisi platform dan affiliate.

## 3. Validasi Data
- [ ] **Empty State**: Tampilan jika tidak ada data pada rentang tanggal yang dipilih.
- [ ] **Pagination**: Navigasi halaman jika data banyak.
- [ ] **Sorting**: Urutan data default (biasanya tanggal terbaru).
