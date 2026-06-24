# ARCHITECTURE.md

## Pola Arsitektur

### Frontend
- **Blade Templates** — Semua UI menggunakan Laravel Blade (tanpa SPA/frontend framework)
- **Filament Admin Panel** — Digunakan untuk resource management (CRUD) master data
- **Views** — Blade views untuk halaman bisnis (sales, warehouse, finance, analytics)
- **Layout** — Sidebar navigation dengan menu sesuai role & permission

### Backend (Laravel MVC)
```
Request → Middleware → Controller → Service/Query → Model → Database
                                      ↓
                                  Blade View / Export
```

### Layer Arsitektur

#### 1. Middleware Layer
Berurutan:
1. `Authenticate` — Cek login
2. `CheckMainCategory` — Cek session main category
3. `CheckRole` / `CheckPermission` — Otorisasi
4. `PreventBackHistory` — Mencegah cache halaman setelah logout
5. `UnderConstruction` — Maintenance mode per halaman
6. `IncreaseUploadLimits` — Untuk upload file besar

#### 2. Controller Layer
Controller dikelompokkan berdasarkan modul:
- `Http/Controllers/Master/*` — CRUD master data
- `Http/Controllers/Sales*` — Penjualan (online & offline)
- `Http/Controllers/Finance/*` — Keuangan per platform
- `Http/Controllers/Analytics/*` — Laporan & analitik
- `Http/Controllers/Admin/*` — Manajemen sistem
- `Http/Controllers/Auth/*` — Authentication

Controller bersifat tipis — hanya routing & validasi dasar.

#### 3. Service Layer
- `Services/Analytics/*` — Orchestrator untuk analytics queries
- `Services/OrderTaxSplitter` — Logic pemisahan pajak order
- `Services/ReturFinanceService` — Logic retur finance

Service bersifat orchestrator, tidak mengandung kalkulasi PHP berat. Kalkulasi dilakukan di SQL/Query layer.

#### 4. Query Layer
- `Queries/Analytics/*` — SQL queries mentah untuk laporan
- Menggunakan raw SQL (`DB::select()`) untuk performa
- Query dikelompokkan per domain: Sales, Finance, GrossProfit, Offline, Product, Stock

#### 5. Model Layer
- Eloquent models dengan global scopes (MainCategory filtering)
- Relationships didefinisikan di model
- Accessors & mutators untuk formatting

#### 6. Export Layer
- `Exports/*` — Export ke Excel menggunakan Maatwebsite/Laravel Excel
- Blade views untuk export PDF (DomPDF)

### Folder Structure
```
app/
├── Console/Commands/       # Artisan commands
├── Exceptions/              # Error handler
├── Exports/                 # Excel exports
├── Filament/Resources/      # Filament CRUD resources
├── Helpers/                 # Helper functions
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # System management
│   │   ├── Analytics/      # Reports & analytics
│   │   ├── Auth/           # Authentication
│   │   ├── Finance/        # Financial modules
│   │   └── Master/         # Master data CRUD
│   └── Middleware/          # Request middleware
├── Imports/                 # Excel imports
├── Models/                  # Eloquent models
├── Providers/               # Service providers
├── Queries/Analytics/       # Raw SQL queries
└── Services/                # Business logic services
```

### Database
- MySQL database
- Migrations for schema management
- Seeders untuk data awal
- Global scope `main_category_id` untuk multi-category filtering

### Authentication
- Laravel UI (Blade-based auth)
- Sanctum untuk API token
- Session-based untuk web

### Authorization
- **Role-based** — Superadmin, Admin, dan roles kustom
- **Permission-based** — Granular permissions per module/action
- Middleware `CheckRole` dan `CheckPermission` untuk proteksi route

### Multi-Category (Main Category)
- Disimpan di session (`main_category_id`)
- Semua model utama memiliki global scope `MainCategoryHelper`
- User harus memilih kategori sebelum mengakses aplikasi
