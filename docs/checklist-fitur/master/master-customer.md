# Checklist Fitur: Master Customer

Manajemen data pelanggan.

**Files Views**:
- `resources/views/master/customers/` (index, create, edit, show)

---

## 1. Index Page (List Customer)
**Route**: `customers.index`
- [ ] **UI Components**:
  - [ ] **Search Bar**: Input text (Nama/No HP).
  - [ ] **Table Columns**:
    - [ ] No
    - [ ] Nama Customer
    - [ ] No. Telepon / WA
    - [ ] Alamat
    - [ ] Tipe / Tier (Umum/Member/Reseller)
    - [ ] Action
- [ ] **Functional Tests**:
  - [ ] **Search**: Cari "08123" -> Muncul customer dengan no hp tsb.

## 2. Create Page (Tambah Customer)
**Route**: `customers.create`
- [ ] **UI Components**:
  - [ ] **Form Fields**:
    - [ ] **Nama Lengkap** (Mandatory).
    - [ ] **No. Telepon** (Mandatory, Numeric only).
    - [ ] **Alamat** (Textarea).
    - [ ] **Tipe Customer**: Dropdown (Umum/Member/dll).
- [ ] **Input Validation**:
  - [ ] **Unique Phone**: Input no HP yang sudah terdaftar -> Error.
  - [ ] **Format Phone**: Input huruf di no HP -> Error/Blocked.

## 3. Edit Page (Edit Customer)
**Route**: `customers.edit`
- [ ] **Functional Tests**:
  - [ ] **Update Info**: Ubah alamat -> Simpan -> Data terupdate.

## 4. Show Page (Detail Customer)
**Route**: `customers.show`
- [ ] **UI Components**:
  - [ ] **Profile**: Data diri lengkap.
  - [ ] **Riwayat Belanja**: List transaksi penjualan customer ini (Table/Link).
  - [ ] **Total Belanja**: Statistik (Optional).
