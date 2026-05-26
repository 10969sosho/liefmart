# Checklist Fitur: Warehouse

Manajemen stok gudang, transfer barang, dan barang rusak.

**File View**: `resources/views/warehouse/index.blade.php`, `stock/list.blade.php`, `stock/damaged.blade.php`

## 1. Item Transfers (Pindah Gudang)
- [ ] **List Transfer**: Tampil riwayat transfer antar gudang/lokasi.
- [ ] **Buat Transfer**: Form input transfer barang.
  - [ ] **Validasi Stok**: Pastikan tidak bisa transfer lebih dari stok tersedia.
  - [ ] **Update Stok**: Pastikan stok gudang asal berkurang dan tujuan bertambah.

## 2. Stock List (Daftar Stok)
- [ ] **Tabel Stok**: Menampilkan stok per produk per gudang.
- [ ] **Filter**: Filter berdasarkan Gudang, Kategori, atau Produk.
- [ ] **History Kartu Stok**: Klik produk untuk melihat riwayat keluar masuk.

## 3. Barang Rusak (Damaged Goods)
- [ ] **Input Barang Rusak**: Catat barang rusak/expired.
- [ ] **Pengurangan Stok**: Pastikan stok aktif berkurang saat dicatat sebagai rusak.
- [ ] **Keterangan**: Input alasan kerusakan.
