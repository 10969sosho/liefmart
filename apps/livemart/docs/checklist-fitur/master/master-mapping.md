# Checklist Fitur: Mapping Barang (Marketplace <-> Internal)

Menghubungkan produk dari marketplace (Shopee Lamourad, Shopee Liefmarket, Tiktok Lamourad, Tiktok Liefmarket) dengan Master Produk internal untuk sinkronisasi stok dan akuntansi.

**Files Views**:
- `resources/views/master/mapping/` (index, create, edit, show, check)

---

## 1. Index Page (List Mapping)
**Route**: `mapping.index`
- [ ] **UI Components**:
  - [ ] **Filter Panel**:
    - [ ] **Platform**: Dropdown (Shopee Lamourad, Shopee Liefmarket, Tiktok Lamourad, Tiktok Liefmarket).
    - [ ] **Status**: Mapped / Unmapped.
    - [ ] **Search**: Input text (Nama barang platform / SKU).
  - [ ] **Table Columns**:
    - [ ] Platform (Icon/Name).
    - [ ] Nama Barang Platform (External).
    - [ ] SKU Platform.
    - [ ] Nama Master Produk (Internal) -> Link ke detail produk.
    - [ ] Konversi (e.g., 1 pcs, 1 lusin).
    - [ ] Action (Edit Mapping, Unlink).
- [ ] **Functional Tests**:
  - [ ] **Filter Unmapped**: Pilih Status "Unmapped" -> Muncul barang platform yang belum punya pasangan.
  - [ ] **Search**: Cari nama barang Shopee -> Hasil muncul.

## 2. Create/Edit Mapping
**Route**: `mapping.create` / `mapping.edit`
- [ ] **UI Components**:
  - [ ] **Info Barang Platform**: Read-only (Nama, SKU, Harga).
  - [ ] **Pilih Master Produk**: Searchable Dropdown (Select2/TomSelect) cari by Nama/SKU Internal.
  - [ ] **Faktor Konversi**: Input Number (Default 1).
    - *Contoh: Jual "Paket Hemat" (1 item di Shopee) = Keluar "2 Pcs" (2 item di Gudang).*
- [ ] **Functional Tests**:
  - [ ] **Search Product**: Ketik "Kopi" di dropdown -> Muncul list produk internal mengandung kata "Kopi".
  - [ ] **Save**: Simpan -> Redirect ke Index -> Status berubah jadi Mapped.
  - [ ] **Validation**: Kosongkan master produk -> Error.

## 3. Check / Validation
**Route**: `mapping.check` (if available)
- [ ] **Functional Tests**:
  - [ ] **Duplicate Mapping**: Coba mapping barang platform yang sudah ter-mapping -> Warning/Error atau update existing?
  - [ ] **Bulk Action**: (Jika ada fitur auto-map based on SKU match) Coba jalankan -> Verifikasi hasil.

## 4. Unlink (Hapus Mapping)
- [ ] **Functional Tests**:
  - [ ] **Unlink**: Klik tombol putus hubungan -> Data mapping hilang, barang platform jadi "Unmapped", master produk aman.
