# Checklist Fitur: Penjualan Online

Modul pencatatan transaksi penjualan online (Marketplace: Shopee, Tokopedia, Tiktok, Lazada, dll).

**File View**: 
- List: `resources/views/sales/list.blade.php`
- Input Manual: `resources/views/sales/online-input.blade.php`
- Import Preview: `resources/views/sales/shopee/preview-import.blade.php` (dan platform lain)

## 1. Testing Per Platform
Pastikan alur penjualan berjalan lancar untuk setiap platform.
- [ ] **Shopee**: Test input manual & import file Shopee.
- [ ] **Tokopedia**: Test input manual & import file Tokopedia.
- [ ] **TikTok Shop**: Test input manual & import file TikTok.
- [ ] **Lazada**: Test input manual & import file Lazada.
- [ ] **Manual / WA**: Test input manual order dari WhatsApp.

## 2. UI Check: Input Manual (`online-input`)
Halaman `sales/online-input` (pilih platform -> create).

### A. Header Form
- [ ] **Breadcrumb**: Navigasi kembali ke menu utama berfungsi.
- [ ] **Judul Halaman**: Menampilkan nama platform yang sedang diinput (Dynamic).
- [ ] **Alerts**:
  - [ ] Alert Error (Session).
  - [ ] Alert Success (Session).
  - [ ] Alert Duplicate Order (AJAX).

### B. Form Data Utama
- [ ] **Tanggal Order**: Input Date. Wajib.
- [ ] **Hari**: Dropdown (Senin-Minggu). Wajib.
  - [ ] **Auto-Fill**: Ganti tanggal -> Hari otomatis terpilih.
- [ ] **Status Hari**: Input Text. Wajib.
  - [ ] Placeholder: "Contoh: Weekday,Weekend".
  - [ ] Info text: "Bisa multiple values...".
- [ ] **Nomor Order**: Input Text. Wajib.
  - [ ] **Tombol Cek**: Button "Cek" dengan icon search.
  - [ ] **AJAX Check**: Ketik nomor order -> Klik Cek -> Muncul warning jika duplikat.
  - [ ] **Real-time Check**: Input/Blur -> Auto check duplikat (delayed).
- [ ] **Nomor Resi**: Input Text. Optional.

### C. Item Penjualan (Tabel)
- [ ] **Tombol Tambah Item**: Button Primary (+ Tambah Item).
- [ ] **Tabel Header**: Produk, Variasi, Qty, Harga Satuan, Subtotal, Aksi.
- [ ] **Row Item**:
  - [ ] **Produk**: Dropdown Select (Searchable/TomSelect).
    - [ ] Menampilkan Nama + Varian.
    - [ ] Data Attribute: Stock, Variant Name.
  - [ ] **Variasi**: Readonly Input (Terisi otomatis saat produk dipilih).
  - [ ] **Qty**: Input Number (Min 1). Default 1.
  - [ ] **Harga Satuan**: Input Number (Step 0.001).
  - [ ] **Subtotal**: Readonly Text (Qty * Harga).
  - [ ] **Tombol Hapus**: Button Danger (Icon Trash).
- [ ] **Footer**:
  - [ ] **Grand Total**: Penjumlahan semua subtotal item.

### D. Action Buttons
- [ ] **Tombol Kembali**: Link ke Sales List.
- [ ] **Tombol Simpan**: Submit form.

## 3. UI Check: Sales List (`sales/list`)
Halaman daftar transaksi penjualan online.

### A. Header & Filter
- [ ] **Tombol Tambah**: Link ke `sales.choose-type`.
- [ ] **Tombol Filter**: Toggle visibility form filter.
- [ ] **Form Filter** (Hidden by default):
  - [ ] **Tanggal Mulai**: Input Date.
  - [ ] **Tanggal Akhir**: Input Date.
  - [ ] **Platform**: Dropdown Select.
  - [ ] **Nomor Order**: Input Text.
  - [ ] **Tombol Cari**: Submit filter.
  - [ ] **Tombol Reset**: Clear filter.

### B. Tabel Data (Rowspan Logic)
- [ ] **Kolom No**: Auto number.
- [ ] **Kolom Tanggal**: Format d-m-Y. (Rowspan per Order)
- [ ] **Kolom Hari**: Nama Hari. (Rowspan per Order)
- [ ] **Kolom Status Hari**: Text. (Rowspan per Order)
- [ ] **Kolom Platform**: Badge Warna (Shopee=Warning, Tokped=Success, Tiktok=Dark, dll). (Rowspan per Order)
- [ ] **Kolom No Order**: String. (Rowspan per Order)
- [ ] **Kolom Nama Barang**:
  - [ ] Nama Produk.
  - [ ] Varian (Small text).
- [ ] **Kolom Varian**: Badge Info / "-".
- [ ] **Kolom Qty**: Angka.
- [ ] **Kolom Harga**: Format Ribuan.
- [ ] **Kolom Total Item**: Qty * Harga.
- [ ] **Kolom Total Invoice**: Sum Total Item per Order. (Rowspan per Order)
- [ ] **Kolom No Resi**: String / "-". (Rowspan per Order)
- [ ] **Kolom Aksi**: (Rowspan per Order)
  - [ ] Dropdown Menu.
  - [ ] Detail: Icon Mata.
  - [ ] Cetak: Icon Print.
  - [ ] Hapus: Icon Trash (Superadmin only).

## 4. Import Penjualan (Shopee/Marketplace)
Fitur upload massal transaksi dari laporan marketplace.

### A. Upload & Validasi Awal
- [ ] **Upload File Format Salah**: Upload file PDF/Image. -> Harapan: Error validasi file type.
- [ ] **Upload File Kosong/Header Salah**: Upload excel random. -> Harapan: Error validasi format.

### B. Preview & Notifikasi (`preview-import`)
Halaman preview sebelum simpan.

- [ ] **Check Notifikasi Stock Kosong/Kurang**:
  - Skenario: Import file berisi order untuk produk yang stoknya tidak cukup.
  - Harapan: Alert merah `ERROR: Stok Tidak Mencukupi!`. Detail produk ditampilkan.
  
- [ ] **Check Notifikasi Data Invalid**:
  - Skenario: Format tanggal salah atau harga negatif.
  - Harapan: Alert `Data Tidak Valid` dengan detail baris/kolom.
  
- [ ] **Check Notifikasi Mapping Error**:
  - Skenario: Produk di file import belum di-mapping ke Master Produk.
  - Harapan: Alert `Mapping Error` / `Produk Tidak Ditemukan`.
  
- [ ] **Check Notifikasi Order Duplikat**:
  - Skenario: No. Order sudah ada di DB.
  - Harapan: Baris ditandai (merah/kuning) atau ditolak.

### C. Proses Simpan
- [ ] **Simpan Valid**: Data masuk DB, stok berkurang.
- [ ] **Error Handling**: Jika ada 1 error, apakah semua batal (Atomic) atau partial save? (Cek requirement).
