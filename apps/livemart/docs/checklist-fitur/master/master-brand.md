# Checklist Fitur: Master Brand & Sub-Brand

Manajemen merek produk dan sub-merek.

**Files Views**:
- `resources/views/master/brands/` (index, create, edit, show)
- `resources/views/master/subbrands/` (index, create, edit, show)

---

## 1. Master Brand

### A. Index Page (List Brand)
**Route**: `brands.index`
- [ ] **UI Components**:
  - [ ] **Title**: "Master Brand"
  - [ ] **Button**: "Tambah Brand" (redirects to create)
  - [ ] **Search Bar**: Input text untuk cari nama brand.
  - [ ] **Table Columns**:
    - [ ] No
    - [ ] Logo (Thumbnail image)
    - [ ] Nama Brand
    - [ ] Slug (optional)
    - [ ] Action (Button: Detail, Edit, Delete)
  - [ ] **Pagination**: Muncul jika data > 10/20.
- [ ] **Functional Tests**:
  - [ ] **Search**: Ketik nama brand yang ada -> Hasil muncul. Ketik random -> "Data tidak ditemukan".
  - [ ] **Pagination**: Klik page 2 -> Data berubah.
  - [ ] **Delete**: Klik tombol hapus -> Muncul SweetAlert konfirmasi -> Klik "Ya" -> Data terhapus & notifikasi sukses.
  - [ ] **Delete Constraint**: Coba hapus brand yang sudah dipakai di produk/sub-brand -> Muncul error/notifikasi gagal hapus.

### B. Create Page (Tambah Brand)
**Route**: `brands.create`
- [ ] **UI Components**:
  - [ ] **Title**: "Tambah Brand Baru"
  - [ ] **Form Fields**:
    - [ ] **Nama Brand** (Input Text, Mandatory)
    - [ ] **Logo** (Input File/Image, Optional/Mandatory)
  - [ ] **Buttons**: "Simpan", "Kembali"
- [ ] **Input Validation**:
  - [ ] **Empty Submit**: Klik Simpan kosong -> Muncul error "Nama Brand wajib diisi".
  - [ ] **Duplicate Name**: Input nama brand yang sudah ada -> Error "Nama brand sudah terdaftar".
  - [ ] **Invalid Image**: Upload file .txt/.pdf -> Error "Format file harus gambar (jpg, png, jpeg)".
  - [ ] **Max Size**: Upload gambar > 2MB -> Error.

### C. Edit Page (Edit Brand)
**Route**: `brands.edit`
- [ ] **UI Components**:
  - [ ] **Title**: "Edit Brand: [Nama Brand]"
  - [ ] **Pre-filled Data**: Nama brand lama sudah terisi.
  - [ ] **Current Logo**: Menampilkan logo saat ini (preview).
- [ ] **Functional Tests**:
  - [ ] **Update Name**: Ubah nama -> Simpan -> Redirect ke Index -> Nama berubah.
  - [ ] **Update Logo**: Upload logo baru -> Simpan -> Logo berubah di index/show.
  - [ ] **No Change**: Klik Simpan tanpa ubah apa-apa -> Sukses (tidak error).

### D. Show Page (Detail Brand)
**Route**: `brands.show`
- [ ] **UI Components**:
  - [ ] **Detail Info**: Nama Brand, Logo Besar.
  - [ ] **List Sub-Brand**: Menampilkan daftar sub-brand yang bernaung di bawah brand ini (jika ada).
  - [ ] **List Produk**: (Optional) Menampilkan produk dengan brand ini.
  - [ ] **Button**: "Edit", "Kembali".

---

## 2. Master Sub-Brand

### A. Index Page (List Sub-Brand)
**Route**: `subbrands.index`
- [ ] **UI Components**:
  - [ ] **Table Columns**:
    - [ ] Nama Sub-Brand
    - [ ] Parent Brand (Nama Brand Induk)
    - [ ] Action
- [ ] **Functional Tests**:
  - [ ] **Filter by Brand**: Dropdown filter parent brand -> List terfilter.

### B. Create Page (Tambah Sub-Brand)
**Route**: `subbrands.create`
- [ ] **UI Components**:
  - [ ] **Parent Brand**: Dropdown (Select2/TomSelect) pilih Brand Induk.
  - [ ] **Nama Sub-Brand**: Input Text.
- [ ] **Input Validation**:
  - [ ] **Parent Required**: Kosongkan parent -> Error.

### C. Edit Page (Edit Sub-Brand)
**Route**: `subbrands.edit`
- [ ] **Functional Tests**:
  - [ ] **Change Parent**: Ubah parent brand -> Simpan -> Data terupdate.
