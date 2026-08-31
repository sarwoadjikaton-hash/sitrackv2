# SiTrack — Sistem Persuratan & Tracking

SiTrack adalah aplikasi administrasi dan tracking persuratan berbasis **Laravel + Vue 3 + Inertia.js + TypeScript + Bootstrap 5 + PostgreSQL + Redis + Docker + Spatie Laravel Permission**.

## Stack

- Laravel 11
- PHP 8.3 FPM Alpine
- Vue 3 + TypeScript
- Inertia.js
- Bootstrap 5.3 + Bootstrap Icons
- PostgreSQL 16
- Redis 7 (cache, session, queue)
- Spatie Laravel Permission
- Nginx
- Docker Compose

## Fitur utama

- Dashboard monitoring persuratan
- Ketersediaan dan reservasi nomor surat
- Data surat berdasarkan workbook/jenis naskah
- Lajur tindak lanjut / TTD
- Lajur disposisi dan instruksi pimpinan
- Tracking publik dengan kode `TUS-YYYYMMDD-XXX`
- Scan QR untuk pembaruan status
- Relasi antar surat
- Cetak lembar disposisi dan pendamping
- Master unit, kategori, jenis nomor, dan pengguna
- Rekap master dan ekspor CSV
- Hak akses berbasis role/permission Spatie
- UI biru modern, responsif, animasi halus, dan dukungan `prefers-reduced-motion`

## Role bawaan

| Role | Fungsi |
|---|---|
| `super_admin` | Seluruh fitur + master data |
| `admin` | Operasional persuratan |
| `kasubbag` | Operasional dan monitoring sesuai permission |
| `sekjen` | Monitoring dan pemberian arahan disposisi |

> Akun demo disediakan oleh seeder untuk lingkungan development. Ganti seluruh password sebelum digunakan di lingkungan nyata.

## Menjalankan dengan Docker

### 1. Siapkan environment

```bash
cp .env.example .env
```

Untuk Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

### 2. Build dan jalankan

```bash
docker compose up -d --build
```

### 3. Migrasi + seed

```bash
docker compose exec app php artisan migrate --seed
```

### 4. Storage link

```bash
docker compose exec app php artisan storage:link --force
```

Buka:

- Aplikasi: http://localhost:8000
- Vite HMR: http://localhost:5173
- PostgreSQL: localhost:5432
- Redis: localhost:6379

### 5. Queue

Worker Redis sudah disediakan sebagai service `queue`.

```bash
docker compose logs -f queue
```

## Development tanpa Docker

Pastikan PHP 8.2+, Composer, Node.js 20+, PostgreSQL, dan Redis tersedia.

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run dev
```

Di terminal lain:

```bash
php artisan queue:work redis
```

## Validasi build frontend

```bash
npm run build
```

Perintah build menjalankan TypeScript checking (`vue-tsc`) sebelum Vite membuat asset production.

## Catatan keamanan

- Jangan commit `.env` atau kredensial production.
- Ganti password akun seeder sebelum deployment.
- Jangan menggunakan `APP_DEBUG=true` di production.
- Gunakan HTTPS di production.
- Permission Spatie diterapkan pada route yang membutuhkan akses khusus.
- File upload diproses melalui Laravel Storage, bukan akses file mentah dari endpoint aplikasi.
- Docker tidak lagi memberikan `777` ke seluruh project; hanya direktori runtime Laravel yang dibuat writable.
- Untuk production, gunakan secret manager / environment secret dan password PostgreSQL/Redis yang kuat.
