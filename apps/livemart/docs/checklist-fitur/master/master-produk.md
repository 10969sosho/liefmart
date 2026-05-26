# Checklist Fitur: Master Produk

Modul manajemen data induk produk dengan sistem hierarki kategori yang kompleks.

**Files Views**:
- `resources/views/master/products/` (index, create, edit, show)

---

## 1. Index Page (List Produk)
**Route**: `products.index`
- [ ] **UI Components**:
  - [ ] **Search & Filter Panel**:
    - [ ] **Search**: Input text (Nama/SKU).
    - [ ] **Filter Kategori**: Dropdown.
    - [ ] **Filter Brand**: Dropdown.
    - [ ] **Filter Status**: Dropdown (Aktif/Non-aktif).
  - [ ] **Table Columns**:
    - [ ] Checkbox (untuk bulk action, jika ada).
    - [ ] Thumbnail (Foto Produk).
    - [ ] Nama Produk & SKU.
    - [ ] Kategori (Breadcrumb: Brand > Cat > Type > Size > Var).
    - [ ] Harga Jual & HPP.
    - [ ] Stok (Total akumulasi).
    - [ ] Status (Badge Aktif/Non-aktif).
    - [ ] Action (Detail, Edit, Delete).
  - [ ] **Pagination**: Bottom right.
- [ ] **Functional Tests**:
  - [ ] **Search by SKU**: Ketik kode unik -> Muncul 1 produk tepat.
  - [ ] **Filter Combination**: Filter Brand "A" + Status "Aktif" -> Hasil sesuai.
  - [ ] **Delete**: Hapus produk -> Validasi relasi (tidak bisa hapus jika sudah ada transaksi).

## 2. Create Page (Tambah Produk)
**Route**: `products.create`
- [ ] **UI Components**:
  - [ ] **Section 1: Informasi Dasar**:
    - [ ] **Nama Produk**: Input Text (Mandatory).
    - [ ] **Kode Produk / SKU**: Input Text (Auto-generate button available?).
    - [ ] **Deskripsi**: Textarea / WYSIWYG Editor.
    - [ ] **Status**: Toggle Switch / Select.
  - [ ] **Section 2: Klasifikasi (Cascading)**:
    - [ ] **Brand**: TomSelect + Button "Add New".
    - [ ] **Sub Brand**: TomSelect (Filtered by Brand) + Button "Add New".
    - [ ] **Category**: TomSelect + Button "Add New".
    - [ ] **Type**: TomSelect (Filtered by Category) + Button "Add New".
    - [ ] **Size**: TomSelect (Filtered by Type) + Button "Add New".
    - [ ] **Variant**: TomSelect (Filtered by Size) + Button "Add New".
  - [ ] **Section 3: Harga**:
    - [ ] **Harga Modal (HPP)**: Input Number (Currency format).
    - [ ] **Harga Jual**: Input Number.
    - [ ] **Margin**: Display Only (Auto-calc: `(Jual - HPP) / Jual %`).
  - [ ] **Section 4: Media**:
    - [ ] **Foto Utama**: Upload Image.
    - [ ] **Galeri**: Multi-upload (optional).
- [ ] **Functional Tests**:
  - [ ] **Cascading Dropdown**: Pilih Brand -> Sub Brand loading/reset. Pilih Category -> Type loading/reset.
  - [ ] **Modal "Add New"**: Klik "+" di samping Brand -> Muncul Modal Tambah Brand -> Simpan -> Dropdown Brand otomatis terpilih brand baru.
  - [ ] **Margin Calculation**: Isi HPP 10.000, Jual 20.000 -> Margin text muncul "50%".
  - [ ] **Validation**: Submit kosong -> Error messages muncul di field terkait.

## 3. Edit Page (Edit Produk)
**Route**: `products.edit`
- [ ] **UI Components**:
  - [ ] Form sama dengan Create, tapi value sudah terisi.
  - [ ] **Foto Preview**: Foto lama muncul.
- [ ] **Functional Tests**:
  - [ ] **Partial Update**: Ganti Harga Jual saja -> Simpan -> Data lain tidak berubah.
  - [ ] **Change Hierarchy**: Ubah Brand -> Sub Brand harus dipilih ulang (reset).

## 4. Show Page (Detail Produk)
**Route**: `products.show`
- [ ] **UI Components**:
  - [ ] **Header**: Nama Produk, Badge Status.
  - [ ] **Info Grid**: SKU, Brand, Kategori lengkap, Harga.
  - [ ] **Stock Info**: Sisa stok per gudang (jika multi-gudang).
  - [ ] **Image Preview**: Klik gambar -> Zoom/Lightbox.
  - [ ] **History**: (Optional) Log perubahan harga / stok.
- [ ] **Functional Tests**:
  - [ ] **Back Button**: Kembali ke index dengan filter sebelumnya (maintain state).
