# Checklist Fitur: Finance Offline

Modul ini menangani pencatatan dan pemantauan keuangan untuk penjualan offline (non-marketplace).

## 1. Index Barang Penjualan (`finance.offline.index`)
**File View**: `resources/views/finance/offline/index.blade.php`

### Fitur Pencarian & Filter
- [x] **Filter Tanggal**: Range Tanggal Mulai s/d Tanggal Akhir.
- [x] **Filter No. PO**: Input text untuk mencari nomor PO.
- [x] **Filter No. Surat Jalan (SJ)**: Input text untuk mencari nomor SJ.
- [x] **Filter Customer**: Input text untuk mencari nama customer.
- [x] **Filter Kategori Pajak (Tax ID)**: Dropdown (Semua, PKP, Non-PKP).
- [x] **Filter Nama Produk**: Input text.
- [x] **Filter Status Invoice**: Dropdown (Semua, Sudah Ada Invoice, Belum Ada Invoice).
- [x] **Tombol Reset**: Mengembalikan filter ke default.

### Tabel Data Barang Penjualan
- [x] **Kolom Data**:
  - No. PO
  - Customer
  - Tanggal Penjualan
  - No. SJ
  - Tax ID (Badge: PKP=Primary, Non-PKP=Info)
  - Kategori Produk
  - Nama Produk (Kode Produk)
  - Qty
  - Sub Total (Rp)
  - Status Invoice (Link ke Invoice jika ada)
  - Aksi
- [x] **Logika Perhitungan Subtotal**:
  - Base Price * Total Qty
  - Diskon Persen (Level 1-5) bertingkat.
  - Diskon Nominal (Level 1-5).
  - Pembulatan 2 desimal (`NumberFormatter::roundToTwoDecimals`).

## 2. List Invoice Offline (`finance.offline.invoices`)
**File View**: `resources/views/finance/offline/list_invoices.blade.php`

### Dashboard Ringkasan (Summary Cards)
- [x] **Total Invoice**: Jumlah invoice dalam periode filter.
- [x] **Grand Total**: Total nilai tagihan.
- [x] **Total Terbayar**: Total pembayaran diterima.
- [x] **Sisa Tagihan**: Total outstanding.
- [x] **Counter Status**:
  - Lunas (Hijau)
  - Sebagian (Info)
  - Belum Bayar (Kuning)
  - Retur Full (Abu-abu)
  - Tidak Balance (Kuning Gelap)
- [x] **Total Retur**: Nilai total retur.

### Fitur Pencarian & Filter
- [x] **Filter Tanggal**: Range Tanggal.
- [x] **Filter No. Invoice**: Input text.
- [x] **Filter No. SJ**: Input text.
- [x] **Filter Customer**: Input text.
- [x] **Filter Status Pembayaran**: Dropdown (Lunas, Belum Lunas, Retur Full, Tidak Balance).
- [x] **Filter Tax ID**: Dropdown (PKP, Non-PKP).
- [x] **Quick Dates**: Tombol cepat (7 Hari, 30 Hari, 90 Hari, Bulan Ini).

### Tabel Invoice
- [x] **Kolom Data**:
  - No. Invoice
  - Tanggal
  - No. SJ
  - Tax ID
  - Kategori
  - Customer
  - DPP (Rp)
  - Retur (Rp)
  - Net (Rp)
  - PPN (Rp)
  - Total (Rp)
  - Dibayar (Rp)
  - Sisa Tagihan (Rp)
  - Status (Badge)
  - Aksi
- [x] **Logika Partial Refund**:
  - Jika status `partial_refund`, perhitungan DPP dilakukan reverse dari Grand Total (jika PKP).
  - Rumus Reverse PKP: `DPP = Grand Total / 1.11`.

### Fitur Export
- [x] **Export Excel**: Button export dengan parameter filter yang aktif.
