# PROJECT_CONTEXT.md

## Nama Project
**Liefmart** — Sistem Manajemen Retail (Inventory, Sales, Finance, Analytics)

## Tujuan Project
Aplikasi web untuk mengelola operasional bisnis retail yang mencakup:
- Manajemen inventaris & gudang
- Penjualan offline (langsung) dan online (Shopee, TikTok)
- Keuangan & pembayaran multi-platform
- Mapping produk platform ke produk internal
- Analitik & laporan penjualan
- Manajemen roles, permissions, dan users

## Tech Stack
| Komponen | Teknologi |
|---|---|
| Backend | Laravel 10 (PHP 8.1+) |
| Admin Panel | Filament v3 |
| Database | MySQL |
| Frontend | Blade Templates, CSS |
| Export | Laravel Excel (Maatwebsite), DomPDF |
| Auth | Laravel UI + Sanctum |
| Import CSV | league/csv |

## Struktur Project (Monorepo)
```
Liefmart/
├── apps/
│   └── livemart/          # Aplikasi utama Laravel
│       ├── app/
│       │   ├── Console/Commands/     # Artisan commands (import, fix, sync)
│       │   ├── Exports/              # Export ke Excel/PDF
│       │   ├── Filament/             # Filament admin panel resources
│       │   ├── Helpers/              # Helper functions
│       │   ├── Http/
│       │   │   ├── Controllers/      # Controllers per modul
│       │   │   └── Middleware/       # Middleware (auth, permission, role)
│       │   ├── Imports/              # Import dari Excel
│       │   ├── Models/               # Eloquent models
│       │   ├── Providers/            # Service providers
│       │   ├── Queries/              # Query layer (SQL mentah)
│       │   └── Services/             # Service layer (business logic)
│       ├── config/                   # Laravel config files
│       ├── database/
│       │   ├── migrations/           # Database migrations
│       │   └── seeders/              # Database seeders
│       ├── resources/views/          # Blade templates
│       └── routes/                   # Web & API routes
└── shared/                           # Shared code antar apps
    └── src/
        ├── Helpers/                  # Shared helpers
        ├── Models/                   # Shared models
        └── Queries/                  # Shared query builders
```

## Modul Utama
1. **Dashboard** — Ringkasan data & statistik
2. **Inventory**
   - Goods Receipt (Penerimaan Barang)
   - Warehouse & Stock Management
3. **Sales**
   - Penjualan Offline (langsung)
   - Penjualan Online (Shopee, TikTok — import via Excel)
4. **Finance**
   - Finance Offline (invoice, pembayaran)
   - Finance Online (Shopee, TikTok — rekonsiliasi pembayaran)
5. **Analytics**
   - Sales Analytics (laporan penjualan)
   - Finance Analytics (arus kas, biaya)
   - Gross Profit Analytics
   - Stock Analytics
   - Product Analytics (best seller)
6. **Master Data**
   - Brands, Categories, Products
   - Platform Products
   - Mapping Barang (platform → internal)
   - Customers, Bank Accounts
7. **Admin Management**
   - Roles & Permissions
   - User Management

## User Role
- **Superadmin** — Akses penuh, termasuk hapus data & manajemen user
- **Admin** — Akses edit/manage data
- Role lain dapat dikustomisasi via sistem permission terintegrasi

## Flow Bisnis Utama
1. **Penerimaan Barang** → Barang masuk ke gudang (unlocated) → Dipindahkan ke gudang penyimpanan
2. **Penjualan Online** → Import Excel dari platform → Mapping barang → Proses pesanan → Barang keluar
3. **Penjualan Offline** → Input manual → Cetak surat jalan → Cetak invoice
4. **Finance** → Rekonsiliasi pembayaran dari platform → Generate invoice → Pelacakan piutang
5. **Retur** → Retur pembelian (ke supplier) → Retur penjualan (dari customer)

## Multi-Category (Main Category)
Aplikasi mendukung multiple main categories (KOPI, SKINCARE) yang dipilih via session. Semua data di-scope berdasarkan main category yang aktif.
