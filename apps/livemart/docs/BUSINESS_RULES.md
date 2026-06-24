# BUSINESS_RULES.md

## Aturan Bisnis Utama

### 1. Main Category (Multi-Bisnis)
- Setiap user harus memilih **Main Category** (KOPI / SKINCARE) saat login
- Semua data di-scope berdasarkan main category yang dipilih
- Global scope otomatis diterapkan di model (Product, Order, Penerimaan, OfflineSale, dll)
- User bisa logout dan login ulang untuk ganti kategori

### 2. Penerimaan Barang (Goods Receipt)
- Penerimaan mencatat barang masuk dari supplier
- Status: `draft` → `selesai`
- Saat `selesai`, stok masuk ke **Unlocated** (belum dipindah ke gudang)
- Mendukung batch processing (create header → store details batch → finalize)
- Setiap penerimaan memiliki kode unik (`kode_penerimaan`)
- Barang yang diterima perlu dipindahkan ke gudang via fitur Warehouse Transfer

### 3. Warehouse & Stock
- **Unlocated Stock** — Stok yang sudah diterima tapi belum dipindahkan ke gudang
- **Warehouse Stock** — Stok di gudang penyimpanan
- Pemindahan dari Unlocated ke Warehouse dilakukan manual
- Stock tracking per product, tax category, dan warehouse
- Stok bisa memiliki status **damaged** (rusak)
- Stok keluar saat pesanan diproses (Barang Keluar)
- Stok return masuk kembali ke warehouse

### 4. Penjualan Offline
- Input manual oleh user
- Generate **Surat Jalan** (SJ) number otomatis
- Setelah disimpan, stok otomatis berkurang
- Mendukung diskon bertingkat (5 level discount)
- Bisa dicetak invoice dan surat jalan
- Status pembayaran: belum dibayar / sudah dibayar
- Penghapusan hanya oleh superadmin

### 5. Penjualan Online (Platform)
- Data diimport dari Excel (Shopee, TikTok)
- Setiap platform memiliki format Excel berbeda
- Import melalui tahap: upload → preview → process
- Platform products harus di-mapping ke internal products
- **Mapping Barang** menghubungkan platform_product_id → product_id
- Order online bisa memiliki multiple tax IDs (di-split oleh OrderTaxSplitter)

### 6. Mapping Barang
- Mapping bersifat **versioned** — memiliki riwayat perubahan
- Satu platform product bisa memiliki beberapa versi mapping
- Mapping yang sudah dipakai di sales tidak bisa dihapus, hanya di-nonaktifkan
- Change reason wajib diisi saat mengubah mapping

### 7. Finance
#### Finance Offline
- Mencatat pembayaran customer untuk penjualan offline
- Status: unpaid, paid, refund
- Mendukung partial payment
- Invoice number di-generate otomatis berdasarkan format:
  `{counter}/{yearMonth}/{suffix}/{taxCode}`
- Counter di-reset setiap bulan
- Ada batas cetak (print limit)

#### Finance Online (Platform)
- Rekonsiliasi pembayaran dari platform
- Per platform: Shopee, Shopee2, TikTok, TikTok2
- Mencatat biaya-biaya platform (admin fee, shipping, dll)
- Bisa di-lock/unlock untuk mencegah perubahan
- History tracking untuk setiap perubahan

### 8. Perpajakan
- Mendukung PKP dan NON-PKP
- Tax Categories: KOPI-PKP, KOPI-NONPKP, SKINCARE-PKP, SKINCARE-NONPKP (dan offline variants)
- Tax mapping digunakan untuk menentukan nomor invoice yang tepat
- Format invoice: `{counter}/{tahunBulan}/{suffix}/{kodePajak}`

### 9. Retur
#### Retur Pembelian
- Mengembalikan barang ke supplier
- Mencatat detail barang yang diretur

#### Retur Penjualan
- Mengembalikan barang dari customer
- Retur penjualan online: barang masuk kembali ke warehouse stock
- Retur penjualan offline: mengurangi piutang customer
- Mendukung kondisi barang diretur (kondisi: baik/rusak)

#### Retur Offline
- Untuk penjualan offline yang diretur
- Invoice retur dicetak terpisah

### 10. Roles & Permissions
- **Superadmin** — Akses tak terbatas, bisa menghapus data
- **Admin** — Bisa edit/manage data
- Permission granular per module: view, create, edit, delete
- Permission di-check via middleware `CheckPermission`
- Jika tidak punya akses, tampilkan 403 Forbidden

### 11. Akses Data
- User sales tidak bisa melihat laporan finance (kecuali diberi permission)
- Superadmin bisa melihat semua data
- User hanya bisa mengakses main category yang dipilih

### 12. Constraints Penting
- Order tidak bisa dihapus setelah diproses (kecuali superadmin)
- Invoice number tidak boleh duplikat
- Stock tidak boleh negatif
- Mapping barang yang sudah dipakai tidak bisa dihapus
- Hanya superadmin yang bisa akses menu admin management
