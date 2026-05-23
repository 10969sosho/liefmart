## Rencana Refactoring: Mengganti "Liefmart" menjadi "Liefmarket"

### 📋 Hasil Analisis
Ditemukan **41 file** dengan kemunculan "Liefmart" yang perlu diubah menjadi "Liefmarket". Perubahan mencakup:

### 🎯 Kategori File yang Perlu Diubah:

#### 1. **Views (Blade Templates)** - 15 file
- `resources/views/sales/online.blade.php` - Judul dan alt text
- `resources/views/analytics/finance/*.blade.php` - Judul halaman
- `resources/views/finance/*/preview.blade.php` - Judul halaman
- `resources/views/sales/*/platform.blade.php` - Judul dan header
- `resources/views/components/sidebar.blade.php` - Menu navigation
- `resources/views/dashboard.blade.php` - Dashboard title
- `resources/views/financial/*.blade.php` - Financial pages

#### 2. **Controllers** - 12 file
- `PembayaranTiktok2Controller.php` - Platform queries dan labels
- `PembayaranShopee2Controller.php` - Platform queries dan labels
- `ArusKasTiktok2Controller.php` - Page titles
- `ArusKasShopee2Controller.php` - Page titles
- `Shopee2Controller.php` - Platform identification
- `Tiktok2Controller.php` - Platform identification
- `DashboardController.php` - Dashboard data
- `SalesController.php` - Sales platform handling

#### 3. **Database & Configuration** - 6 file
- `database/seeders/PlatformSeeder.php` - Platform names
- `database/sql/update_platform_names.sql` - SQL updates
- `BACKUP_1.sql` - Database backup
- `UPDATE 12.2.26 JAM 17.58.sql` - Migration data

#### 4. **Exports & Models** - 4 file
- `app/Exports/UnpaidOrdersExport.php` - Platform lists
- Various export files with platform references

#### 5. **Documentation** - 4 file
- `docs/checklist-fitur/*.md` - Feature documentation

### 🔧 Strategi Perubahan:

#### Fase 1: Views & Templates
- Ganti semua `alt="Shopee Liefmart"` menjadi `alt="Shopee Liefmarket"`
- Update judul halaman: `TikTok Liefmart` → `TikTok Liefmarket`
- Update header dan navigation menu

#### Fase 2: Controllers Logic
- Update platform queries: `whereRaw('LOWER(name) = ?', ['tiktok liefmart'])`
- Ganti platform labels: `$platformLabel = 'Tiktok Liefmarket'`
- Update error messages dan validation

#### Fase 3: Database & Seeds
- Update PlatformSeeder dengan nama baru
- Update SQL migration files
- Pastikan consistency di database

#### Fase 4: Testing & Validation
- Jalankan unit tests
- Test semua routes yang terdampak
- Validasi database queries
- Cek browser console untuk errors

### ⚠️ Perhatian Khusus:
- **Case Sensitivity**: Queries SQL menggunakan LOWER(), pastikan perubahan case-insensitive
- **Platform ID**: Beberapa controller menggunakan hardcoded ID (6 untuk Shopee, 7 untuk TikTok)
- **URL Routes**: Pastikan tidak ada route yang bergantung pada nama platform
- **JavaScript**: Cek jika ada JavaScript yang menggunakan nama platform

### 🧪 Testing Plan:
1. Test dashboard dan navigation
2. Test semua platform pages (Shopee2, TikTok2)
3. Test financial reports dan analytics
4. Test export functionality
5. Test database queries

### 📊 Estimasi:
- **Total File**: 41 file
- **Jenis Perubahan**: Text replacement dan query updates
- **Risk Level**: Medium (karena melibatkan database queries)

Rencana ini akan memastikan perubahan dilakukan secara sistematis tanpa merusak functionality yang ada.