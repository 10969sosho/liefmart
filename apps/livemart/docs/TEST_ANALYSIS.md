# TEST ANALYSIS & POTENTIAL ISSUES

> Dibuat: 2026-06-20
> Update FINAL: Complete System Audit — All Features Tested

## Ringkasan Test Coverage Keseluruhan (FINAL)

| Test Type | Files | Tests |
|---|---|---|
| Database/Unit | 8 | 118 |
| Validation | 6 | 100 |
| Feature | 24 | 500 |
| Browser (Dusk) | 10 | 78 |
| **Total** | **48** | **796** |

## Coverage Detail (FINAL)

| Modul | Database | Validation | Feature | Browser | Status |
|---|---|---|---|---|---|
| Login & Auth | ✅ User | ✅ 11 | ✅ 14 | ✅ 9 | ✅ COVERED |
| Dashboard/Home | — | — | ✅ 6 | ✅ 1 | ✅ COVERED |
| Penerimaan | ✅ 16 | ✅ 18 | ✅ 21 | ✅ 10 | ✅ COVERED |
| Warehouse Transfer | — | ✅ 16 | ✅ 25 | ✅ 5 | ✅ COVERED |
| Stock Analytics | ✅ 15 | — | ✅ 22 | ✅ 7 | ✅ COVERED |
| Sales Online | ✅ 20 | ✅ 15 | ✅ 25 | ✅ 5 | ✅ COVERED |
| Sales Offline | *(incl)* | ✅ 7 | ✅ 22 | ✅ 5 | ✅ COVERED |
| Import Flows (Shopee/TikTok) | — | — | ✅ 22 | — | ✅ COVERED |
| Mapping Barang | *(Sales)* | — | ✅ 18 | ✅ 1 | ✅ COVERED |
| Finance Offline | ✅ 20 | ✅ 14 | ✅ 30 | ✅ 8 | ✅ COVERED |
| Finance Online | *(incl)* | *(incl)* | ✅ 18 | *(incl)* | ✅ COVERED |
| Cash Flow (Arus Kas) | — | — | ✅ 12 | — | ✅ COVERED |
| Unpaid Orders | — | — | ✅ 5 | — | ✅ COVERED |
| Master Data (hierarchy) | ✅ 25 | ✅ 20 | ✅ 40 | ✅ 8 | ✅ COVERED |
| Admin Management | — | — | ✅ 18 | ✅ 2 | ✅ COVERED |
| Retur System | ✅ 25 | — | ✅ 70 | ✅ 6 | ✅ COVERED |
| Sales Analytics | — | — | ✅ 35 | — | ✅ COVERED |
| Product Analytics | — | — | ✅ 28 | — | ✅ COVERED |
| Finance Analytics | — | — | ✅ 22 | — | ✅ COVERED |
| Offline Analytics | — | — | ✅ 15 | — | ✅ COVERED |
| Bank Accounts | — | — | ✅ 3 | ✅ 1 | ✅ COVERED |
| Profile | — | — | ✅ 2 | ✅ 1 | ✅ COVERED |
| Other (DB Restore, etc) | — | — | ✅ 14 | ✅ 1 | ✅ COVERED |

---

## FINANCE-SPECIFIC POTENTIAL ISSUES

### 21. Outstanding Floating Point Precision
**Lokasi**: `PembayaranShopeeController@index()` — `ABS(outstanding) > 0.01`

**Deskripsi**: Filter outstanding menggunakan toleransi `ABS(outstanding) <= 0.01` untuk "lunas". Floating point arithmetic bisa menghasilkan nilai seperti `0.0000001` yang seharusnya dianggap lunas.

**Dampak**: False positive "belum lunas" untuk transaksi yang sebenarnya sudah lunas.

**Saran**: Gunakan pembulatan (`ROUND(outstanding, 2)`) atau bandingkan dengan 0 secara eksak.

---

### 22. Cross-Table Invoice Sequence Dependency
**Lokasi**: `InvoiceSequence@getNextInvoiceNumber()` — query 5 tables

**Deskripsi**: Invoice sequence membaca semua nomor invoice dari 5 tabel berbeda (`shopee_financial_transactions`, `tiktok_financial_transactions`, `shopee2_*`, `tiktok2_*`, `finance_offlines`) untuk mencegah duplikat. Jika salah satu tabel error, sequence gagal.

**Dampak**: Gagal generate nomor invoice jika ada masalah di satu tabel.

**Saran**: 
- Tambahkan error handling per tabel (seperti yang sudah ada untuk Shopee2)
- Atau gunakan sequence number yang terpusat

---

### 23. Finance Offline Filter Performing Nested Queries
**Lokasi**: `FinanceOfflineController@index()` — multiple `whereHas()` chains

**Deskripsi**: Filter di finance offline index menggunakan `whereHas` chains hingga 4 level: `barangKeluarItems.offlineSaleItem.offlineSale.customerInfo`. Ini menghasilkan subquery yang sangat berat.

**Dampak**: Halaman finance offline lambat untuk database dengan banyak transaksi.

**Saran**: Gunakan `join()` atau tambahkan denormalized columns.

---

### 24. Print Limit & Reprint Approval Flow
**Lokasi**: `FinanceOffline` — `print_count`, `reprint_requested`, `reprint_approved`

**Deskripsi**: Flow reprint approval membutuhkan koordinasi antara user (request) dan superadmin (approve). Jika superadmin tidak merespons, invoice tidak bisa dicetak ulang.

**Dampak**: Bottleneck operasional.

**Saran**: 
- Timeout auto-approve setelah X jam
- Atau batasi print count, bukan require approval

---

### 25. No Validation on `adjustment` Direction
**Lokasi**: Shopee/Tiktok Financial Transaction `adjustment` field

**Deskripsi**: Adjustment bisa positif (menambah) atau negatif (mengurangi) tanpa validasi. Tidak ada constraint yang memastikan adjustment + reason konsisten.

**Dampak**: Adjustment positif yang salah bisa menambah nominal_fix secara tidak wajar.

**Saran**: Tambahkan enum atau validasi untuk tipe adjustment (credit/debit).

---

### 26. Shopee/Tiktok Transaction Lock Tidak Mencegah Semua Perubahan
**Lokasi**: `is_locked` field di shopee/tiktok financial transactions

**Deskripsi**: Lock hanya mencegah perubahan di UI. Tidak ada database-level protection atau soft delete prevention.

**Dampak**: Direct database update bisa bypass lock.

**Saran**: 
- Tambahkan `CAN UPDATE` trigger di database
- Atau gunakan model event `updating` untuk cek lock

---

### 27. Unpaid Orders Export Format
**Lokasi**: `UnpaidOrdersExport`

**Deskripsi**: Export unpaid orders menggabungkan data dari berbagai platform dengan format yang mungkin berbeda.

**Dampak**: Format Excel tidak konsisten antar platform.

**Saran**: Standarisasi kolom export.

---

## MASTER DATA-SPECIFIC POTENTIAL ISSUES

### 28. No Cascade Protection on Hierarchy Deletion
**Lokasi**: Brand/SubBrand/ProductCategory/ProductType/ProductSize controllers

**Deskripsi**: Tidak ada proteksi cascade saat menghapus master data parent. Contoh: menghapus Brand tidak mengecek apakah ada SubBrand/Product yang masih menggunakan brand tersebut.

**Dampak**: Data orphan (yatim piatu) — produk tanpa brand, sub_brand tanpa brand.

**Saran**: Gunakan foreign key cascade atau `ON DELETE RESTRICT`, atau tambahkan pengecekan di controller sebelum delete.

---

### 29. Hierarchy Cross-Category Inconsistency
**Lokasi**: `ProductController@store()` — validasi terpisah per field

**Deskripsi**: Validasi produk tidak memeriksa konsistensi antar level hierarki. Produk bisa memiliki `main_category_id = SKINCARE` tapi `brand_id` dari KOPI.

**Dampak**: Data tidak konsisten — produk SKINCARE dengan brand KOPI.

**Saran**: Tambahkan validasi `brand.main_category_id == product.main_category_id`.

---

### 30. Global Scope Hides Orphan Data
**Lokasi**: `Brand::booted()`, `TaxCategory::booted()` — global scope `mainCategory`

**Deskripsi**: Brand/TaxCategory tanpa `main_category_id` (null) disembunyikan oleh global scope saat session aktif.

**Dampak**: Admin tidak bisa melihat brand yang datanya corrupt.

**Saran**: Tambahkan `->orWhereNull('main_category_id')` seperti OfflineSale.

---

### 31. Product Initial Price Versioning Not Thread-Safe
**Lokasi**: `Product::booted()` — `static::saved()`, `ProductInitialPriceVersionController@store()`

**Deskripsi**: Race condition pada `max('version')` antara read dan write jika ada request concurrent.

**Dampak**: Duplicate version number atau version number loncat.

**Saran**: Gunakan `DB::transaction()` dengan `lockForUpdate()` seperti InvoiceSequence.

---

### 32. Customer Email "-" Workaround
**Lokasi**: `CustomerController@store()` — `$data['email'] = '-'`

**Deskripsi**: Email kosong diganti dengan "-" karena kolom mungkin memiliki unique constraint.

**Dampak**: Customer dengan email "-" muncul di pencarian email — data tidak akurat.

**Saran**: Buat kolom email nullable di database.

---

### 33. Product Export Format Not Explicitly Validated
**Lokasi**: `ProductController@export()` — hanya `abort(404)` untuk invalid

**Deskripsi**: Format export divalidasi hanya dengan `abort(404)` tanpa pesan error yang jelas.

**Dampak**: User mendapat 404 tanpa tahu format apa yang valid.

**Saran**: Gunakan `in:xlsx,csv,pdf` validation rule dengan pesan error.

---

## RETUR SYSTEM-SPECIFIC POTENTIAL ISSUES

### 34. Package Return Fractional Quantity Grey Area
**Lokasi**: `ReturPenjualanController@store()` — `$returnQty * $mappingQty`

**Deskripsi**: Untuk produk package (1 paket = 2A + 1B), return 1 pcs A saja = 0.5 paket. Sistem mendukung fractional quantity (`$newQuantity = round($orderItem->quantity - $returnQty)`), tapi secara fisik 0.5 paket tidak realistis.

**Dampak**: Order item quantity jadi desimal (9.5). Visual di UI mungkin membingungkan.

**Saran**: 
- Validasi bahwa return qty untuk package harus kelipatan dari mapping quantity
- Atau minimal 1 paket penuh

---

### 35. Return Before Finance Transaction — Fallback Logic
**Lokasi**: `ReturFinanceService@getCurrentFinanceTotal()` — return 0 jika tidak ada transaksi

**Deskripsi**: Saat retur terjadi sebelum finance transaction diimport, `originalOrderTotal = 0`. Fallback: `$currentItemsTotal + $refundAmount`. Tapi jika order items sudah dikurangi quantity-nya oleh retur, `$currentItemsTotal` juga 0.

**Dampak**: `$refundAmount >= $originalOrderTotal` → full refund → mencoba delete transaksi yang tidak ada.

**Saran**: Cek existence transaksi sebelum handle refund. Jika tidak ada, skip finance processing.

---

### 36. Retur Offline Reverse — Stock Double Restoration Risk
**Lokasi**: `ReturOfflineSaleController@reverseReturn()` — restore stock

**Deskripsi**: Saat reverse return, stock yang sudah diretur dikembalikan ke quantity awal. Tapi jika antara proses retur dan reverse ada transaksi lain yang mengurangi stock, reverse akan menambah stock berlebih.

**Dampak**: Stock tidak akurat — lebih besar dari seharusnya.

**Saran**: Reverse hanya diizinkan jika tidak ada perubahan stock (sale, retur lain) sejak retur diproses.

---

### 37. Duplicate Order Item ID in Details
**Lokasi**: `ReturPenjualanController@store()` — loop details

**Deskripsi**: Controller mengizinkan multiple entries dengan `order_item_id` yang sama dalam satu request (beda kondisi). Ini bisa menyebabkan logika mapping terduplikasi.

**Dampak**: Stock kembali double untuk product yang sama.

**Saran**: Group details by `order_item_id + product_id` sebelum diproses, atau reject duplicate entries.

---

### 38. HILANG Condition — Stock Not Restored But Finance Affected
**Lokasi**: `ReturPenjualanController@store()` — skip addBackToStock untuk HILANG

**Deskripsi**: Kondisi HILANG tidak mengembalikan stock ke warehouse (logis). Tapi finance tetap diproses (refund tetap dihitung untuk barang yang hilang). Ini berarti perusahaan mengembalikan uang untuk barang yang tidak kembali.

**Dampak**: Kerugian finansial — refund diberikan tapi stock tidak kembali.

**Saran**: 
- Bedakan perlakuan finance untuk HILANG vs BAGUS/RUSAK
- Atau hanya izinkan HILANG dengan approval khusus

---

### 39. Mapping Version for Return Calculation
**Lokasi**: `ReturPenjualanController@store()` — `MappingBarang::getMappingsForOrderCreatedAt()`

**Deskripsi**: Perhitungan return menggunakan `MappingBarang` yang aktif saat order dibuat. Jika mapping berubah antara order dan retur, return menggunakan mapping lama. Ini benar secara logika tapi bisa membingungkan user.

**Dampak**: User melihat mapping berbeda antara saat order dan retur.

**Saran**: Tampilkan mapping yang digunakan di halaman retur.

---

### 40. Retur Pembelian — Stock Deduction from Warehouse
**Lokasi**: `ReturPembelianController` — belum selesai membaca

**Deskripsi**: Retur pembelian harus mengurangi stock warehouse karena barang dikembalikan ke supplier. Jika tidak ada pengecekan stock cukup, retur bisa terjadi saat stock sudah terpakai.

**Dampak**: Stock negatif di warehouse.

**Saran**: Validasi qty retur <= current warehouse stock untuk produk terkait.

---

## ANALYTICS-SPECIFIC POTENTIAL ISSUES

### 41. withoutGlobalScope('mainCategory') in Analytics
**Lokasi**: Semua analytics controller — `Order::withoutGlobalScope('mainCategory')`

**Deskripsi**: Analytics query menggunakan `withoutGlobalScope` yang menyebabkan SEMUA data dari kategori manapun muncul. Data SKINCARE dan KOPI tercampur dalam satu laporan.

**Dampak**: Angka laporan tidak akurat untuk user yang hanya ingin melihat satu kategori.

**Saran**: 
- Tambahkan filter main_category_id jika session tersedia
- Atau tampilkan filter kategori di halaman analytics

---

### 42. Subquery Performance on Large Date Ranges
**Lokasi**: `SalesAnalyticsController@salesByPlatformReport()` — `selectRaw` with subqueries

**Deskripsi**: Menggunakan subquery `SELECT COALESCE(SUM(...), 0)` untuk setiap row di pagination. Untuk range tanggal lebar (1 tahun) dengan ribuan order, ini bisa sangat lambat.

**Dampak**: Halaman loading lama atau timeout.

**Saran**: 
- Gunakan materialized view atau caching
- Batasi range date default ke 1 bulan
- Tambahkan query timeout handling

---

### 43. Gross Profit HPP Calculation Complexity
**Lokasi**: `GrossProfitAnalyticsController@grossProfitOfflineReport()` — PHP calculation

**Deskripsi**: HPP dihitung di PHP dengan loop over items, bukan di SQL. Untuk ribuan sale items, memory usage bisa tinggi.

**Dampak**: Memory exhaustion untuk dataset besar.

**Saran**: 
- Pindahkan kalkulasi ke SQL query layer
- Atau gunakan chunk processing

---

### 44. Double Query Pattern in Finance Analytics
**Lokasi**: `FinanceAnalyticsController@shopeeAnalytics()` — `clone $query` pattern

**Deskripsi**: Query di-clone multiple kali untuk menghitung total (`$query->count()`, `$query->sum()`, `$query->paginate()`). Ini mengeksekusi 3 query hampir identik.

**Dampak**: 3x lebih banyak query ke database dari yang diperlukan.

**Saran**: 
- Gunakan selectRaw untuk totals sekaligus
- Cache summary dalam session untuk durasi pendek

---

### 45. AJAX Dependent Dropdowns Not Scoped by Main Category
**Lokasi**: `ProductAnalyticsController@getSubBrands()` — filter by brand_ids

**Deskripsi**: AJAX endpoint untuk sub_brand, product_category, dll. tidak memfilter berdasarkan main_category session. Brand dari kategori lain bisa muncul.

**Dampak**: Filter dropdown menampilkan data yang tidak relevan.

**Saran**: Tambahkan filter main_category_id di query.

---

### 46. Retur Not Subtracted from Sales Volume/Value
**Lokasi**: Sales analytics queries — tidak mengurangi order yang diretur

**Deskripsi**: Sales volume dan value tidak mengurangi order yang sudah diretur (full atau partial). Angka penjualan tetap termasuk barang yang diretur.

**Dampak**: Overstatement penjualan.

**Saran**: 
- Kurangi order items yang sudah diretur dari total
- Atau tampilkan net sales (gross - retur)

---

## PRIORITAS PERBAIKAN (Final — All Modules)

| Priority | Issue | Effort | Impact |
|---|---|---|---|
| 🔴 Tinggi | withoutGlobalScope data campur (41) | Kecil | High |
| 🔴 Tinggi | Retur not subtracted from sales (46) | Sedang | High |
| 🔴 Tinggi | Package fractional qty grey area (34) | Kecil | Medium |
| 🟡 Sedang | Gross profit memory usage (43) | Besar | High |
| 🟡 Sedang | Subquery performance (42) | Sedang | Medium |
| 🟡 Sedang | AJAX not scoped by category (45) | Kecil | Medium |
| 🟡 Sedang | Double query finance (44) | Kecil | Low |
| 🟡 Sedang | Return before finance fallback (35) | Kecil | High |
| 🟢 Rendah | Duplicate order_item_id in details (37) | Kecil | High |
| 🟢 Rendah | Reverse stock double restoration (36) | Sedang | High |
| 🟢 Rendah | HILANG refund tanpa stock (38) | Sedang | Medium |
