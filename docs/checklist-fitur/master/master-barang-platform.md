# Checklist Fitur: Master Barang Platform

Manajemen data mentah produk dari marketplace (biasanya hasil import).

**Files Views**:
- `resources/views/master/barang-platform/` (index, create, edit)

---

## 1. Index Page (List Barang Platform)
**Route**: `barang-platform.index`
- [ ] **UI Components**:
  - [ ] **Filter**: Platform (Shopee Lamourad, Shopee Liefmarket, Tiktok Lamourad, Tiktok Liefmarket).
  - [ ] **Search**: Nama / SKU / Item ID.
  - [ ] **Table Columns**:
    - [ ] Platform
    - [ ] Item ID (Unique Platform ID)
    - [ ] Nama Barang
    - [ ] SKU
    - [ ] Harga Tayang
    - [ ] Status Mapping (Sudah/Belum)
    - [ ] Action
- [ ] **Functional Tests**:
  - [ ] **Import Button**: (Jika ada) Tombol untuk upload Excel/CSV update data platform.

## 2. Create Page (Manual Input - Optional)
**Route**: `barang-platform.create`
*Biasanya data ini dari Import, tapi jika ada input manual:*
- [ ] **UI Components**:
  - [ ] **Platform**: Dropdown.
  - [ ] **Item ID**: Input Text (Wajib Unique).
  - [ ] **Nama Barang**: Input Text.
  - [ ] **SKU**: Input Text.
  - [ ] **Harga**: Input Number.

## 3. Edit Page
**Route**: `barang-platform.edit`
- [ ] **Functional Tests**:
  - [ ] **Update SKU**: Perbaiki SKU yang salah agar match dengan internal.
  - [ ] **Update Nama**: Sesuaikan nama agar mudah dicari.
- [ ] **Validation**:
  - [ ] **Item ID Immutable**: ID Platform sebaiknya tidak bisa diubah (primary key external).
