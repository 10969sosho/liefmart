# Checklist Fitur: Master Bank Accounts

Manajemen rekening bank untuk pembayaran dan invoice.

**Files Views**:
- `resources/views/master/bank_accounts/` (index, create, edit)

---

## 1. Index Page (List Akun Bank)
**Route**: `bank-accounts.index`
- [ ] **UI Components**:
  - [ ] **Table Columns**:
    - [ ] Logo Bank (Optional)
    - [ ] Nama Bank
    - [ ] No. Rekening
    - [ ] Atas Nama
    - [ ] Status (Aktif/Non-aktif)
    - [ ] Default (Badge "Default" jika akun utama)
    - [ ] Action
- [ ] **Functional Tests**:
  - [ ] **Toggle Status**: Switch On/Off -> Status berubah tanpa reload (AJAX) atau reload page.

## 2. Create Page (Tambah Akun)
**Route**: `bank-accounts.create`
- [ ] **UI Components**:
  - [ ] **Form Fields**:
    - [ ] **Nama Bank**: Input Text (e.g. BCA, Mandiri).
    - [ ] **No. Rekening**: Input Text/Number.
    - [ ] **Atas Nama**: Input Text.
    - [ ] **Set as Default**: Checkbox.
- [ ] **Validation**:
  - [ ] **Mandatory**: Semua field wajib diisi.

## 3. Edit Page (Edit Akun)
**Route**: `bank-accounts.edit`
- [ ] **Functional Tests**:
  - [ ] **Change Default**: Centang "Set as Default" -> Simpan -> Akun ini jadi default, akun lain otomatis un-default (logic check).

## 4. Delete Action
**Route**: `bank-accounts.destroy`
- [ ] **Functional Tests**:
  - [ ] **Confirm Delete**: Hapus -> Konfirmasi.
