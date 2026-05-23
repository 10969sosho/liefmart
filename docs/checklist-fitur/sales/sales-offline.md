# Checklist Fitur: Sales Offline

Modul pencatatan transaksi penjualan langsung di toko (Offline Store).

**File View**: 
- List: `resources/views/sales/offline/list.blade.php`
- Create: `resources/views/sales/offline/create.blade.php`
- Print: `resources/views/sales/print/order.blade.php` (Shared/Specific?) -> Controller uses `sales.print.order` or `sales.offline.print.sj`?
  - Controller: `printOrder($id)` uses `sales.print.order`.
  - Controller: `offlineSalesList` uses `sales.offline.list`.
  - Route list shows `sales.offline.print.sj`. Need to check if this view exists or uses a shared one.

## 1. UI Check: List Page (`sales/offline/list`)
Periksa ketersediaan dan fungsi setiap elemen UI di halaman list.

### A. Filter Section
- [ ] **Date Start**: Input type date. Default kosong/terisi?
- [ ] **Date End**: Input type date.
- [ ] **Nomor Surat Jalan**: Input text (Search).
- [ ] **Nomor PO**: Input text (Search).
- [ ] **Tombol Filter**: Submit form, refresh data sesuai filter.
- [ ] **Tombol Reset**: Clear semua filter, reload all data.

### B. Summary Cards (Atas)
- [ ] **Total Penjualan**: Angka count transaksi.
- [ ] **Total Value**: Format Currency (Rp).
- [ ] **Total Volume**: Satuan pcs.
- [ ] **Status Breakdown**: Count per status (Paid, Partial, Pending, Retur, Cancelled).

### C. Data Table
- [ ] **Kolom No**: Auto number (pagination aware).
- [ ] **Kolom Tanggal**: Format d/m/Y.
- [ ] **Kolom No. Surat Jalan**: String.
- [ ] **Kolom No. PO**: String / '-' jika null.
- [ ] **Kolom Pelanggan**: Nama customer.
- [ ] **Kolom Total**: 
  - [ ] Logic: Jika Retur Full -> 0.
  - [ ] Logic: Jika Tax > 0 -> Total Amount (DPP+PPN).
  - [ ] Logic: Jika Tax = 0 -> Subtotal.
- [ ] **Kolom Status**: Badge warna (Success=Lunas, Warning=Pending, Danger=Batal/Retur).
- [ ] **Tombol Aksi**:
  - [ ] **View/Detail**: Icon Mata (`sales.offline.show`).
  - [ ] **Print SJ**: Icon Truck (`sales.offline.print.sj`).

## 2. UI Check: Create Page (`sales/offline/create`)
Halaman input transaksi baru.

### A. Header & Form Utama
- [ ] **Tombol Kembali**: Redirect ke list.
- [ ] **Alert Error/Info**: Muncul jika ada session flash.
- [ ] **Tanggal Penjualan**: Default hari ini. Wajib.
- [ ] **Nomor PO**: Optional.
- [ ] **Pelanggan**: Dropdown Searchable (TomSelect). Wajib.
- [ ] **Status Pembayaran**: Dropdown (Belum Dibayar, Sudah Dibayar, Dibatalkan).
- [ ] **Payment Details** (Muncul jika Status = Sudah Dibayar):
  - [ ] **Tanggal Bayar**: Date picker.
  - [ ] **Metode Bayar**: Dropdown (Cash, Transfer, Check, Credit Card).
- [ ] **Catatan**: Textarea.
- [ ] **Kategori Utama**: Readonly (Auto-filled dari session login).

### B. Item Table (Dinamis)
- [ ] **Tombol Tambah Item**: Menambah baris baru di tabel.
- [ ] **Row Item**:
  - [ ] **Produk**: Dropdown select. Menampilkan Nama Produk.
  - [ ] **Kuantitas**: Input number (min 0.001).
  - [ ] **Harga**: Input number.
  - [ ] **Diskon**:
    - [ ] Input Nominal (Rp).
    - [ ] Input Persen (%).
    - [ ] Tombol Tambah Diskon (Multiple discount layers?).
  - [ ] **Subtotal**: Readonly (Auto-calc).
  - [ ] **Tombol Hapus**: Icon Trash (Merah).
- [ ] **Footer Table**:
  - [ ] **Subtotal Global**: Sum of item subtotals.
  - [ ] **Grand Total**: Final amount.

### C. Action Buttons
- [ ] **Tombol Batal**: Back to previous.
- [ ] **Tombol Simpan**: Submit form.

## 3. Functional Testing & Logic

### A. Validasi Input
- [ ] **Required Fields**: Coba submit kosong. Harap error message di field terkait.
- [ ] **Stock Validation**:
  - [ ] Pilih produk.
  - [ ] Input Qty > Stok Gudang.
  - [ ] Submit.
  - [ ] Harapan: Error "Stok tidak tersedia" atau blokir di frontend.
- [ ] **Negative Values**: Input harga/qty negatif. Harap ditolak.

### B. Kalkulasi Harga
- [ ] **Subtotal Item**: `Qty * Harga`.
- [ ] **Diskon Item**:
  - [ ] Test Diskon Nominal: `(Qty * Harga) - Diskon`.
  - [ ] Test Diskon Persen: `(Qty * Harga) * (100% - Diskon%)`.
  - [ ] Test Kombinasi (Jika support): Bertingkat atau Akumulasi? (Cek View Logic).
- [ ] **Grand Total**: Sum semua subtotal.

### C. Business Logic (Controller)
- [ ] **Stock Deduction (FIFO)**:
  - [ ] Pastikan stok berkurang dari `warehouse_stocks`.
  - [ ] Logic FIFO: Stok lama (tanggal penerimaan awal) terambil duluan.
  - [ ] Logic Tax Priority: `orderBy('tax_id', 'asc')` (HGN/Tax=3 dulu, baru LM/Tax=4).
- [ ] **Surat Jalan Generation**:
  - [ ] Auto-generate format `SJ/OFF/[Category]/[Date]/[Increment]`.
  - [ ] Test generate beda kategori pajak (jika logic split SJ aktif).

## 4. Error Handling
- [ ] **Stok Habis saat Submit**:
  - [ ] Buka 2 tab create.
  - [ ] Tab 1 ambil semua stok. Submit.
  - [ ] Tab 2 coba submit barang sama.
  - [ ] Harapan: Error graceful, tidak crash.
- [ ] **Database Transaction**:
  - [ ] Simulasikan error di tengah save (misal matikan koneksi atau modif code die()).
  - [ ] Pastikan tidak ada data "setengah matang" (Order masuk tapi stok tidak potong).

## 5. Large Data Handling (Performance)
- [ ] **Banyak Item**:
  - [ ] Input 50+ item dalam 1 transaksi.
  - [ ] Cek performa kalkulasi JS di frontend.
  - [ ] Cek proses simpan ke DB (timeout?).
