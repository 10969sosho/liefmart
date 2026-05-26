# Liefmart + HGN Monorepo

## Struktur

```
Liefmart/                         ← root workspace (monorepo)
├── README.md                     ← panduan ini
├── shared/                       ← LOGIC BERSAMA (bug fix cukup sekali)
│   ├── composer.json
│   └── src/
│       ├── Helpers/
│       │   ├── NumberFormatter.php
│       │   ├── MainCategoryHelper.php
│       │   ├── PathHelper.php
│       │   └── SecurePathHelper.php
│       ├── Models/
│       │   ├── Customer.php
│       │   ├── PaymentMethod.php
│       │   └── BankAccount.php
│       └── Queries/
│           └── Analytics/
│               └── Core/
│                   ├── FilterBuilder.php
│                   └── JoinBuilder.php
├── apps/
│   ├── livemart/                  ← App LIEFMART (Laravel 9)
│   │   ├── .env
│   │   ├── composer.json
│   │   ├── app/
│   │   ├── config/
│   │   ├── database/
│   │   ├── resources/
│   │   ├── routes/
│   │   ├── public/
│   │   └── vendor/
│   └── hgn/                       ← App HGN (Laravel 9)
│       ├── .env
│       ├── composer.json
│       ├── app/
│       ├── config/
│       ├── database/
│       ├── resources/
│       ├── routes/
│       ├── public/
│       └── vendor/
```

---

## Cara Revisi

### 1. Revisi file di `shared/` (berlaku untuk kedua app)

Contoh: memperbaiki bug di `NumberFormatter.php`

```bash
# Edit file
nano shared/src/Helpers/NumberFormatter.php

# Clear cache kedua app
cd apps/livemart && php artisan optimize:clear
cd ../hgn && php artisan optimize:clear

# Test
cd apps/livemart && php artisan serve --port=8000
# Terminal lain
cd apps/hgn && php artisan serve --port=8001
```

### 2. Revisi file app-specific

```bash
# Liefmart
cd apps/livemart
nano app/Http/Controllers/...
php artisan optimize:clear
php artisan serve --port=8000

# HGN
cd apps/hgn
nano app/Http/Controllers/...
php artisan optimize:clear
php artisan serve --port=8001
```

### 3. Setelah revisi selesai dan tested

```bash
cd /path/to/Liefmart
git add .
git commit -m "fix: perbaiki bug <deskripsi>"
git push origin main
```

---

## Cara Setup Git & Push Pertama Kali

```bash
cd /path/to/Liefmart

git init
git add .
git commit -m "Initial monorepo structure"

# Tambahkan remote (ganti URL dengan repo GitHub Anda)
git remote add origin https://github.com/<username>/liefmart-monorepo.git

# Push
git branch -M main
git push -u origin main
```

---

## Cara Pull di Komputer Kantor

### Setup awal (sekali saja)

```bash
git clone https://github.com/<username>/liefmart-monorepo.git
cd Liefmart

# Setup Liefmart
cd apps/livemart
cp .env.example .env
nano .env                              # isi DB_HOST, DB_NAME, DB_USER, DB_PASS
php artisan key:generate
composer install --ignore-platform-reqs --no-interaction

# Setup HGN
cd ../hgn
cp .env.example .env
nano .env                              # isi DB_HOST, DB_NAME, DB_USER, DB_PASS
php artisan key:generate
composer install --ignore-platform-reqs --no-interaction
```

### Pull update (setiap hari)

```bash
cd /path/to/Liefmart
git pull origin main

# Clear cache kedua app (jika ada perubahan shared)
cd apps/livemart && php artisan optimize:clear && cd ../..
cd apps/hgn && php artisan optimize:clear && cd ../..
```

### **Bonus: `.bat` 1x klik untuk komputer kantor**

Buat file `update.bat` di desktop:

```bat
@echo off
cd /d C:\path\to\Liefmart
echo ==================================
echo    UPDATE LIEFMART & HGN
echo ==================================
echo.
echo [1/3] Git Pull...
git pull origin main
echo.
echo [2/3] Clear Liefmart cache...
cd apps\livemart
php artisan optimize:clear
cd ..\..
echo.
echo [3/3] Clear HGN cache...
cd apps\hgn
php artisan optimize:clear
cd ..\..
echo.
echo ==================================
echo    SELESAI!
echo ==================================
pause
```

---

## Cara Deploy

### Liefmart (production)

```bash
cd apps/livemart

# Pastikan .env production sudah benar
# APP_ENV=production
# APP_DEBUG=false

composer install --no-dev --optimize-autoloader --ignore-platform-reqs

php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### HGN (production)

```bash
cd apps/hgn

# Pastikan .env production sudah benar
composer install --no-dev --optimize-autoloader --ignore-platform-reqs

php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> **Penting**: `apps/livemart/public/` dan `apps/hgn/public/` adalah document root masing-masing app. Point web server (Apache/Nginx) ke folder `public/` app yang bersangkutan, bukan ke root monorepo.

---

## Catatan Penting

| Hal | Keterangan |
|-----|-----------|
| `shared/` | Hanya file **benar-benar identik** di kedua app. Revisi di sini berlaku untuk Liefmart & HGN. |
| `apps/livemart` | App Liefmart — punya platform: LAMOURAD, LIEFMART |
| `apps/hgn` | App HGN — punya platform: Shopee, TikTok, Tokopedia, Blibli, Lazada, dll |
| Database | Kedua app connect ke database **berbeda** |
| `composer.json` autoload | PSR-4 `Shared\\` → `../../shared/src/` — jangan dihapus |
| Custom 500 page | Ada di `resources/views/errors/500.blade.php` — jangan dihapus |
