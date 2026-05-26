# Checklist Fitur: Finance Online (Contoh: Shopee)

Modul ini menangani rekonsiliasi dan pencatatan keuangan dari platform marketplace (Shopee, Tiktok).

**File View Utama**: `resources/views/financial/shopee/index.blade.php`
**File View Manual**: `resources/views/financial/shopee/manual.blade.php`

## 1. Dashboard & Ringkasan
- [x] **Card Ringkasan Utama**:
  - Total Transaksi (Count)
  - Total Nominal Fix (Sum)
  - Total Saldo Masuk (Sum)
  - Total Outstanding (Sum)
  - Order Belum Bayar (Count)
  - Nominal Belum Bayar (Sum)
- [x] **Alert Missing Orders**: Mendeteksi order yang ada di sistem penjualan tetapi belum ada data pembayaran di finance.
  - Flagging otomatis untuk order > 3 minggu (Badge "Telat X hari").
  - Tombol aksi "Input Pembayaran" manual.

## 2. Manajemen Data Transaksi
- [x] **Import Excel**: Fitur untuk upload laporan keuangan dari marketplace.
  - [ ] **Validasi Format File**: Tolak jika bukan Excel/CSV yang valid.
  - [ ] **Notifikasi Baris Invalid**: Cek apakah sistem memberitahu baris mana yang gagal diproses (Format tanggal salah, nilai minus, dll).
  - [ ] **Notifikasi Order Tidak Ditemukan**: Alert jika ada pembayaran untuk No. Order yang belum ada di Sales.
  - [ ] **Rekap Hasil Import**: Tampilkan berapa sukses, berapa gagal/skipped.
- [x] **Input Manual**: Form untuk input data keuangan secara manual.
- [x] **Export Excel**: Download laporan keuangan sistem.
- [x] **Filter Data**: Modal filter (berdasarkan parameter URL di tombol reset).

## 3. Tabel Detail Transaksi
- [x] **Kolom Data**:
  - Tanggal Order
  - No. Order
  - No. Invoice
  - Status
  - Harga (Nilai Penjualan)
  - Voucher (Potongan)
  - Komisi Marketplace
  - Biaya Admin
  - Biaya Layanan
  - Biaya Tambahan (Biaya 5 s/d 12)
  - Adjustment
  - Nominal Fix (Net Revenue)

## 4. Logika Bisnis Penting
- [x] **Rekonsiliasi Order**: Sistem mencocokkan data keuangan dengan data order penjualan yang sudah masuk.
- [x] **Penanganan Biaya Marketplace**: Menangkap berbagai komponen biaya (Admin, Layanan, Komisi, dll) secara terpisah untuk analisis profitabilitas.
- [x] **Status Pembayaran**: Pelacakan status pembayaran per order (Lunas/Belum).

## Catatan Platform Lain
Struktur serupa diharapkan ada pada:
- **Tiktok**: `financial/tiktok/index.blade.php`
