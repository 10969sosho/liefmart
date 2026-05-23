# Checklist Fitur: Retur (Returns)

Manajemen pengembalian barang baik dari pembelian maupun penjualan.

**File View**: `resources/views/retur-pembelian/index.blade.php`, `retur-penjualan/index.blade.php`, `retur-offline/index.blade.php`

## 1. Retur Pembelian (Purchase Returns)
- [ ] **Input Retur**: Pilih PO/Penerimaan yang akan diretur.
- [ ] **Alasan Retur**: Input alasan (Rusak, Salah Kirim).
- [ ] **Update Stok**: Stok gudang harus berkurang.
- [ ] **Potong Hutang/Refund**: Update status pembayaran ke supplier.

## 2. Retur Penjualan Online
- [ ] **Input Retur**: Cari berdasarkan No Order Marketplace.
- [ ] **Kondisi Barang**: Layak jual kembali (masuk stok) atau Rusak (masuk gudang reject).
- [ ] **Update Finance**: Penyesuaian laporan keuangan jika ada refund.

## 3. Retur Penjualan Offline
- [ ] **Input Retur**: Cari berdasarkan No Invoice Offline.
- [ ] **Pengembalian Dana**: Cash/Transfer/Store Credit.
- [ ] **Restock**: Opsi kembalikan barang ke rak.
