# Checklist Fitur: Master Kategori & Atribut Produk

Manajemen hierarki produk: Category -> Type -> Size -> Variant.

**Files Views**:
- `resources/views/master/product-categories/`
- `resources/views/master/product-types/`
- `resources/views/master/product-sizes/`
- `resources/views/master/product-variants/`

---

## 1. Master Category (Level 1)

### A. Index Page (List Category)
**Route**: `product-categories.index`
- [ ] **UI Components**:
  - [ ] **Title**: "Master Kategori"
  - [ ] **Table Columns**: No, Nama Kategori, Action.
- [ ] **Functional Tests**:
  - [ ] **Create**: Tambah kategori baru -> Muncul di list.
  - [ ] **Edit**: Rename kategori -> Terupdate.
  - [ ] **Delete**: Hapus kategori (pastikan tidak ada produk terkait).

### B. Create/Edit Page
**Route**: `product-categories.create` / `edit`
- [ ] **Form Fields**:
  - [ ] **Nama Kategori** (Mandatory, Unique).
- [ ] **Validation**:
  - [ ] **Duplicate Check**: Input nama yang sama -> Error.

---

## 2. Master Product Type (Level 2)

### A. Index Page (List Type)
**Route**: `product-types.index`
- [ ] **UI Components**:
  - [ ] **Table Columns**: No, Nama Tipe, Parent Category, Action.
  - [ ] **Filter**: Dropdown Filter by Category.
- [ ] **Functional Tests**:
  - [ ] **Filter Logic**: Pilih Kategori A -> Hanya muncul tipe milik A.

### B. Create/Edit Page
**Route**: `product-types.create` / `edit`
- [ ] **Form Fields**:
  - [ ] **Parent Category**: Dropdown (Select2/TomSelect).
  - [ ] **Nama Tipe**: Input Text.
- [ ] **Validation**:
  - [ ] **Category Required**: Wajib pilih kategori.

---

## 3. Master Product Size (Level 3)

### A. Index Page (List Size)
**Route**: `product-sizes.index`
- [ ] **UI Components**:
  - [ ] **Table Columns**: No, Nama Size, Parent Type, Parent Category, Action.
- [ ] **Functional Tests**:
  - [ ] **Cascading Filter**: Filter Category -> Filter Type menyesuaikan.

### B. Create/Edit Page
**Route**: `product-sizes.create` / `edit`
- [ ] **Form Fields**:
  - [ ] **Parent Category**: Dropdown.
  - [ ] **Parent Type**: Dropdown (Dependent on Category).
  - [ ] **Nama Size**: Input Text (e.g., S, M, L, XL, 30, 32).

---

## 4. Master Product Variant (Level 4)

### A. Index Page (List Variant)
**Route**: `product-variants.index`
- [ ] **UI Components**:
  - [ ] **Table Columns**: No, Nama Variant, Parent Size, Parent Type, Action.

### B. Create/Edit Page
**Route**: `product-variants.create` / `edit`
- [ ] **Form Fields**:
  - [ ] **Parent Category** -> **Type** -> **Size** (3 Level Dependency).
  - [ ] **Nama Variant**: Input Text (e.g., Merah, Biru, Original).
- [ ] **Functional Tests**:
  - [ ] **Chain Select**: Ubah Category -> Type reset. Ubah Type -> Size reset.
