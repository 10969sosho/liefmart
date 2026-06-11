# Deployment Guide — Liefmart Monorepo

## Branch Strategy

```
develop          ← tempat coding & testing (bebas eksperimen)
   ↓ merge
main             ← untuk client / production (hanya yang sudah stable)
```

| Branch | Digunakan Oleh | Keterangan |
|--------|---------------|------------|
| `develop` | Developer (kamu) | Ngoding, testing, eksperimen |
| `main` | Client (toko) | Hanya yang sudah di-merge dari develop |

---

## Project Structure

```
Liefmart/
├── apps/
│   ├── livemart/          ← App untuk Liefmart (LAMOURAD, LIEFMART)
│   │   ├── app/
│   │   ├── config/
│   │   ├── database/
│   │   ├── public/        ← Document root (point web server ke sini)
│   │   ├── resources/
│   │   ├── routes/
│   │   ├── .env
│   │   ├── artisan
│   │   └── composer.json
│   │
│   └── hgn/               ← App untuk HGN (Shopee, TikTok, dll)
│       ├── app/
│       ├── config/
│       ├── database/
│       ├── public/        ← Document root
│       ├── resources/
│       ├── routes/
│       ├── .env
│       ├── artisan
│       └── composer.json
│
├── shared/                ← Kode bersama (berlaku untuk kedua app)
│   └── src/
│       ├── Helpers/
│       ├── Models/
│       └── Queries/
│
├── DEPLOYMENT.md          ← File ini
└── README.md
```

**Penting:**
- Masing-masing app punya **database sendiri** (beda koneksi)
- Masing-masing app punya **`.env` sendiri**
- Web server diarahkan ke `apps/livemart/public/` atau `apps/hgn/public/`, **bukan** ke root

---

## 1. Development Workflow (Kamu — Developer)

### 1.1 Kerja di Develop

```bash
# Pastikan di branch develop
git checkout develop

# Coding, testing, dll...
# Kalau sudah selesai, commit
git add .
git commit -m "feat: deskripsi perubahan"
git push origin develop
```

### 1.2 Merge ke Main (Rilis ke Client)

Gunakan **merge biasa** (bukan reset — karena sekarang main dan develop sudah sama strukturnya):

```bash
# Pindah ke main
git checkout main

# Merge develop ke main
git merge develop

# Push ke GitHub
git push origin main

# Kembali ke develop
git checkout develop
```

> **Catatan:** Karena main di-reset ke posisi develop, merge selanjutnya akan lancar tanpa konflik struktur.

---

## 2. Setup Client — Livemart (Pertama Kali)

Dilakukan **1x saja** di komputer client.

### 2.1 Prasyarat

- PHP 8.1+
- Composer
- Web server (XAMPP / Laragon / Apache / Nginx)
- MySQL / MariaDB
- Git

### 2.2 Clone & Setup

```bash
# Clone repo
git clone https://github.com/10969sosho/liefmart.git
cd liefmart

# Ambil branch main (branch untuk client)
git checkout main

# Masuk ke folder app livemart
cd apps/livemart

# Copy environment
cp .env.example .env
```

### 2.3 Edit File .env

Sesuaikan dengan database client:

```dotenv
APP_NAME=Liefmart
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=liefmart_db           # nama database
DB_USERNAME=root                  # user mysql
DB_PASSWORD=password123           # password mysql
```

### 2.4 Install Dependency

```bash
composer install --ignore-platform-reqs --no-interaction
php artisan key:generate
php artisan storage:link
```

### 2.5 Setup Database

Buat database MySQL baru (misal: `liefmart_db`), lalu import dari file SQL backup, atau jalankan:

```bash
# Jika pakai migration & seeder
php artisan migrate --seed

# Atau import dari file SQL
# php artisan db:import --sql=namafile.sql
```

### 2.6 Setup Web Server

#### Apache / XAMPP
Arahkan `DocumentRoot` ke:

```
C:/path/to/Liefmart/apps/livemart/public
```

#### Nginx
```nginx
server {
    listen 80;
    server_name livemart.local;
    root /path/to/Liefmart/apps/livemart/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Laragon (Windows — Paling Gampang)
1. Buat symlink: `Liefmart/apps/livemart` → `C:/laragon/www/livemart`
2. Atau set Document Root di Laragon ke folder `public`

### 2.7 Selesai

Buka `http://localhost:8000` (atau sesuai konfigurasi) di browser.

---

## 3. Setup Client — HGN (Pertama Kali)

Langkahnya sama dengan livemart, bedanya:

```bash
cd Liefmart/apps/hgn

# Copy & edit .env
cp .env.example .env
# isi DB_DATABASE, DB_USERNAME, DB_PASSWORD sesuai database HGN

composer install --ignore-platform-reqs --no-interaction
php artisan key:generate
php artisan storage:link
```

Web server diarahkan ke: `apps/hgn/public/`

---

## 4. Update Client — Setiap Ada Rilis Baru

### 4.1 Manual via Terminal

```bash
cd /path/to/Liefmart

# Ambil update terbaru dari branch main
git pull origin main

# Clear cache livemart
cd apps/livemart
php artisan optimize:clear

# (Opsional) Kalau ada perubahan shared
composer dump-autoload
```

### 4.2 File .bat untuk Client Windows (Double Click)

Buat file `update-livemart.bat` di **desktop client**:

```bat
@echo off
TITLE Update Liefmart
COLOR 0A

echo ===============================================
echo            UPDATE LIEFMART
echo ===============================================
echo.

cd /d C:\path\to\Liefmart

echo [1/4] Git Pull dari branch main...
git pull origin main
if %errorlevel% neq 0 (
    echo ERROR: Git pull gagal!
    pause
    exit /b
)
echo.

echo [2/4] Clear cache livemart...
cd apps\livemart
php artisan optimize:clear
echo.

echo [3/4] Clear cache hgn...
cd ..\hgn
php artisan optimize:clear
echo.

echo ===============================================
echo            UPDATE SELESAI!
echo ===============================================
echo.
pause
```

> **Catatan:** Sesuaikan path `C:\path\to\Liefmart` dengan lokasi sebenarnya di komputer client.

### 4.3 File .bat untuk HGN

```bat
@echo off
TITLE Update HGN
COLOR 0A

echo ===============================================
echo            UPDATE HGN
echo ===============================================
echo.

cd /d C:\path\to\Liefmart

echo [1/3] Git Pull dari branch main...
git pull origin main
if %errorlevel% neq 0 (
    echo ERROR: Git pull gagal!
    pause
    exit /b
)
echo.

echo [2/3] Clear cache...
cd apps\hgn
php artisan optimize:clear
echo.

echo ===============================================
echo            UPDATE SELESAI!
echo ===============================================
echo.
pause
```

---

## 5. Ringkasan Perintah Cepat

### Developer (Kamu)

| Tujuan | Perintah |
|--------|----------|
| Coding | `git checkout develop` |
| Commit | `git add . && git commit -m "pesan" && git push origin develop` |
| Rilis ke main | `git checkout main && git merge develop && git push origin main && git checkout develop` |

### Client Livemart

| Tujuan | Perintah |
|--------|----------|
| Setup awal | `git clone ... && cd liefmart && git checkout main && cd apps/livemart && cp .env.example .env && composer install && php artisan key:generate` |
| Update manual | `git pull origin main && cd apps/livemart && php artisan optimize:clear` |
| Update double-click | Jalankan `update-livemart.bat` |

### Client HGN

| Tujuan | Perintah |
|--------|----------|
| Setup awal | Sama seperti livemart, beda folder `apps/hgn/` dan `.env` berbeda |
| Update manual | `git pull origin main && cd apps/hgn && php artisan optimize:clear` |

---

## 6. Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `Target class does not exist` | `composer dump-autoload` lalu `php artisan optimize:clear` |
| `No application key` | `php artisan key:generate` |
| `Class "Shared\..." not found` | Pastikan `composer.json` sudah include PSR-4 `Shared\\` → `../../shared/src/` |
| Git pull gagal (conflict) | `git stash` dulu, lalu `git pull origin main`, lalu `git stash pop` |
| Error write permission storage | `chmod -R 777 storage/ bootstrap/cache/` (Linux/Mac) |
| White screen / 500 | Cek `storage/logs/laravel.log` untuk detail error |

---

> **Terakhir update:** 11 Juni 2026
> **Branch aktif:** `develop` untuk coding, `main` untuk client
