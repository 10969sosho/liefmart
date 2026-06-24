# AGENTS.md — SOP untuk AI Agent

Ini adalah **SOP kerja** untuk AI Agent. Bukan tempat menyimpan informasi project.

## Cara Bekerja

### 1. Urutan Membaca Dokumentasi
Sebelum mengerjakan task apapun, baca dokumentasi dalam urutan ini:

1. **AGENTS.md** (file ini) — Pahami SOP
2. **PROJECT_CONTEXT.md** — Pahami project secara umum
3. **ARCHITECTURE.md** — Pahami pola coding
4. **BUSINESS_RULES.md** — Pahami aturan bisnis
5. **CODING_STANDARDS.md** — Pahami standar coding
6. **DATABASE.md** — Pahami struktur database
7. **API_REFERENCE.md** — Pahami endpoint

Setelah membaca, jika task membutuhkan file spesifik, baca file terkait.

### 2. Prinsip Kerja
- **Single Source of Truth** — Dokumentasi adalah acuan utama. Jika ada konflik antara dokumentasi dan kode, laporkan dan minta klarifikasi.
- **Konsisten** — Jangan buat pola baru. Ikuti pola yang sudah ada di codebase.
- **Efisien** — Pahami dulu, baru eksekusi. Jangan coba-coba.

### 3. Batasan Perubahan
| Area | Aturan |
|---|---|
| Arsitektur | Jangan ubah pola arsitektur tanpa diskusi |
| Database | Jangan hapus kolom yang sudah ada |
| Routes | Jangan ubah URL structure tanpa koordinasi |
| Middleware | Jangan lewati middleware keamanan |
| Business Rules | Jangan ubah aturan bisnis tanpa konfirmasi |
| Views | Ikuti struktur Blade yang sudah ada |

### 4. Workflow Analisa
Saat diberi task:
1. **Baca dokumen terkait** — Cari di dokumentasi modul mana yang terlibat
2. **Baca kode yang ada** — Pahami implementasi saat ini
3. **Identifikasi dampak** — Cek file mana saja yang perlu diubah
4. **Eksekusi** — Ikuti coding standards yang sudah ditetapkan
5. **Verifikasi** — Pastikan perubahan konsisten dengan dokumentasi

### 5. Komunikasi
- Jika ada yang tidak jelas, tanya
- Jika menemukan inkonsistensi, laporkan
- Jika perubahan berdampak luas, informasikan

### 6. Environment
- **PHP 8.1+** — Laravel 10
- **MySQL** — Database
- **Filament v3** — Admin Panel
- **Blade** — Templating engine
