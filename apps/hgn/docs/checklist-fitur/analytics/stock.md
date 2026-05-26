# Checklist Fitur: Analisis Stok Barang

Modul analisis stok terkonsolidasi di gudang.
**File View**: `resources/views/warehouse/stock-analytics.blade.php`
**Route**: `warehouse.stock.analytics`

## 1. UI Check: Header & Summary
- [ ] **Judul Halaman**: "Analisis Stok Barang".
- [ ] **Tombol Export**:
  - [ ] **Export Terpilih**: Disabled default, Enabled saat checkbox dicentang.
  - [ ] **Export Semua**: Link download Excel seluruh data.
- [ ] **Summary Cards** (Pastikan angka sesuai DB):
  - [ ] **Total Produk**: Count distinct produk.
  - [ ] **Total Quantity**: Sum qty semua produk.
  - [ ] **Kadaluarsa**: Count produk dengan status expired.
  - [ ] **Barang Rusak**: Count produk rusak.
  - [ ] **Multi ED**: Count produk dengan >1 tanggal expired.
  - [ ] **Total Nilai Inventory**: Sum (Qty * HPP).

## 2. UI Check: Filter Form
- [ ] **Pencarian Dasar**:
  - [ ] **Search**: Input text (Nama Produk).
  - [ ] **SKU**: Input text.
  - [ ] **Status ED**: Dropdown (Kadaluarsa, <3 Bulan, <6 Bulan, <1 Tahun, >1 Tahun, Tanpa ED).
  - [ ] **Pajak**: Dropdown (Tanpa Pajak, PPN 11%, dll).
  - [ ] **Status Produk**: Dropdown (Free Item / Normal).
- [ ] **Advanced Filter** (Toggle Button):
  - [ ] **Brand**: Dropdown.
  - [ ] **Sub Brand**: Dropdown.
  - [ ] **Kategori**: Dropdown.
  - [ ] **Tipe**: Dropdown.
  - [ ] **Ukuran**: Dropdown.
  - [ ] **Varian**: Dropdown.
- [ ] **Tombol Aksi**:
  - [ ] **Terapkan Filter**: Submit form.
  - [ ] **Reset Filter**: Link reload page tanpa query params.

## 3. UI Check: Tabel Data
- [ ] **Header Tabel**: Checkbox All, No, SKU, Nama Produk, Total Qty, Status ED, Action.
- [ ] **Row Data**:
  - [ ] **Checkbox**: Select individual row.
  - [ ] **SKU**: Font monospace.
  - [ ] **Nama Produk**: Nama + Badge "Free Item" (jika ada).
  - [ ] **Total Qty**: Angka + Badge Lokasi (Jumlah lokasi / Nama lokasi).
  - [ ] **Status ED**: Badge (Kadaluarsa/Aman) + Tooltip detail ED.
  - [ ] **Action**: Tombol Detail / History.

## 4. Functional Test
- [ ] **Filter Function**: Coba kombinasi filter (misal: Brand X + Status ED Kadaluarsa).
- [ ] **Export Selected**: Pilih 3 produk -> Klik Export Terpilih -> Download Excel -> Cek isi Excel hanya 3 produk tsb.
- [ ] **Export All**: Klik Export Semua -> Cek isi Excel sesuai filter yang aktif.
- [ ] **Pagination**: Cek navigasi halaman (Next, Prev, Page Numbers).
