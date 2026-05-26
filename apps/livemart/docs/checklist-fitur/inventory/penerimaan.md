# Checklist Fitur: Penerimaan Barang

Modul untuk mencatat penerimaan barang masuk dari supplier/vendor.

**File View**: `resources/views/penerimaan/index.blade.php`, `create.blade.php`, `edit.blade.php`

## 1. Index Penerimaan (`index`)
- [x] **Filter Data**:
  - Kode Penerimaan
  - Nomor PO
  - Status
  - Kategori Pajak
  - Range Tanggal
- [x] **Export Excel**:
  - Ringkasan: Data header penerimaan.
  - Detail: Data sampai level item barang.
- [x] **Tabel Data**:
  - Tanggal
  - Kode Penerimaan
  - No. PO
  - Vendor/Supplier
  - Nilai DPP, PPN, Total
  - Status
  - Aksi

## 2. Input Penerimaan (`create`)
- [x] **Form Header**:
  - Kode Penerimaan (Auto/Manual)
  - Kategori (Inventory/Non-Inventory)
  - Tax Category (Non-PKP, PKP, dll)
  - Nomor PO
  - Metode Pembayaran
  - Tanggal Jatuh Tempo (Auto-set based on terms).
- [x] **Input Item Barang**:
  - Tabel input dinamis (tambah/hapus baris).
  - Pilih Produk (Searchable).
  - Input Qty, Harga Satuan.
- [x] **Sistem Diskon Bertingkat (Validasi Perhitungan)**:
  - [ ] **Check Diskon 0 (Tanpa Diskon)**: Pastikan perhitungan benar saat semua kolom diskon kosong/nol.
  - [ ] **Check 1 Level Diskon**: Input hanya pada level 1 (Persen atau Nominal).
  - [ ] **Check 2 Level Diskon**: Input pada level 1 & 2.
  - [ ] **Check 3 Level Diskon**: Input pada level 1, 2, & 3.
  - [ ] **Check 4 Level Diskon**: Input pada level 1, 2, 3, & 4.
  - [ ] **Check 5 Level Diskon**: Input pada semua level 1-5 (Full Level).
  - [ ] **Check Campuran Persen & Nominal**: Tes kombinasi (misal: Lvl 1 %, Lvl 2 Rp, Lvl 3 %).
  - [ ] **Check Mutual Exclusion**: Pastikan dalam satu level tidak bisa isi % dan Rp sekaligus (salah satu harus disable/reset).
- [x] **Kalkulasi Pajak**:
  - DPP (Dasar Pengenaan Pajak).
  - PPN (11% jika Tax Category sesuai).
  - Grand Total.

## 3. Edit Penerimaan (`edit`)
- [x] **Load Data**: Pre-fill form dengan data existing.
- [x] **Dynamic Loading**: Load produk dan kategori pajak via API/Backend logic untuk kompatibilitas TomSelect.
- [x] **JavaScript Shims**: Penanganan khusus untuk inisialisasi ulang TomSelect pada mode edit.
