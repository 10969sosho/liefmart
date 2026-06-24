# CODING_STANDARDS.md

## Bahasa
- Kode (class, method, variable) menggunakan **English**
- Comment bebas, preferensi English
- User-facing text (view, validation message) menggunakan **Bahasa Indonesia**

## Naming Convention
| Elemen | Aturan | Contoh |
|---|---|---|
| Class (PSR-4) | PascalCase | `PenerimaanController` |
| Method | camelCase | `getProducts()` |
| Variable | camelCase | `$mainCategoryId` |
| Database Table | snake_case (plural) | `order_items`, `mapping_barangs` |
| Database Column | snake_case | `main_category_id`, `total_amount` |
| Route | kebab-case | `/penerimaan/get-products` |
| Route Name | snake_case | `penerimaan.get-products` |
| Config | snake_case | `invoice.offline_mapping` |
| Blade View | kebab-case | `sales/offline/create.blade.php` |

## Struktur Kode

### Controller
- Tipis — hanya handle request, validasi, dan return response
- Panggil Service atau langsung Model/Query
- Jangan ada logic bisnis di controller
- Gunakan form request untuk validasi kompleks

### Service
- Orchestrator — koordinasi antar komponen
- Hindari kalkulasi PHP; serahkan ke SQL/Query layer jika memungkinkan
- Return array/collection, bukan response

### Query Layer
- Gunakan raw SQL (`DB::select()`, `DB::selectOne()`) untuk laporan kompleks
- SQL ditulis langsung (bukan query builder) untuk optimasi performa
- Setiap query class memiliki method `build($filters)` yang return SQL string

### Model
- Gunakan `$fillable` (bukan `$guarded`) untuk mass assignment protection
- Definisikan `$casts` untuk type casting
- Gunakan global scope untuk filtering wajib (MainCategory)
- Relasi didefinisikan dengan tipe return eksplisit (`BelongsTo`, `HasMany`, dll)

## Aturan Penulisan

### Umum
- **Gunakan `===`** untuk perbandingan, bukan `==`
- **Gunakan type hints** di parameter dan return type method
- **Gunakan strict types** (`declare(strict_types=1)`) di file baru
- Hindari `@php` tag inline di Blade — pindahkan logic ke controller/service
- Jangan gunakan `env()` di production — gunakan `config()`

### Database & Eloquent
- Selalu gunakan **migrations** untuk perubahan schema
- Gunakan **seeders** untuk data master awal
- Hindari N+1 queries — gunakan `with()` untuk eager loading
- Global scope untuk MainCategory filtering sudah otomatis

### Middleware
- Middleware berurutan: auth → main.category → role/permission → lainnya
- Permission middleware menerima parameter permission name
- Role middleware menerima parameter role name(s)

### Routes
- Web routes di `routes/web.php`
- API routes di `routes/api.php`
- Route model binding digunakan untuk parameter model
- Gunakan `name()` untuk named routes

### View (Blade)
- Gunakan layout utama `layouts/app.blade.php`
- Section `@yield('content')` untuk konten utama
- Include component via `@include('components.*')`
- Format uang menggunakan helper `NumberFormatter`

### Export
- Excel: extends `Maatwebsite\Excel\Concerns\*`
- PDF: Blade view + DomPDF

### Error Handling
- 403: `abort(403)` untuk unauthorized access
- 404: Resource not found
- 500: Unexpected errors — log wajib diisi
- Logging untuk unauthorized access attempt

### Security
- Sanctum untuk API auth
- CSRF protection aktif untuk web routes
- SQL injection dicegah via parameter binding di raw SQL
- XSS dicegah via Blade auto-escaping

## Kepatuhan
- Ikuti PSR-4 untuk autoloading
- Ikuti PSR-12 untuk coding style
- PHPStan level dasar untuk type checking
