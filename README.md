# Sportivo

Sistem manajemen booking lapangan mini soccer multi-cabang untuk operator venue di Indonesia. Menangani jadwal, pricing dinamis, pembayaran, pelanggan tetap, dan laporan pendapatan.

## Stack

- **Backend** — Laravel 13, PHP 8.4, MySQL
- **Frontend** — React 19, Inertia.js v3, TypeScript, Tailwind CSS v4, shadcn/ui
- **Auth** — Laravel Fortify + spatie/laravel-permission
- **Testing** — Pest v4

## Setup

Butuh PHP 8.4, MySQL, Node 20+, dan [pnpm](https://pnpm.io).

> Pakai **pnpm**, bukan npm. Mencampur keduanya membuat `node_modules` punya salinan paket ganda dan merusak resolusi tipe TypeScript.

```bash
composer install
pnpm install

cp .env.example .env
php artisan key:generate
```

Buat database, lalu sesuaikan `.env`:

```
DB_CONNECTION=mysql
DB_DATABASE=sportivo
DB_USERNAME=root
DB_PASSWORD=
```

> MySQL wajib — bukan SQLite. Proteksi anti double-booking bergantung pada `lockForUpdate()`, dan itu no-op di SQLite sehingga test race condition lolos secara palsu.

```bash
php artisan migrate
php artisan laravolt:indonesia:seed   # data provinsi/kota/kecamatan
```

## Menjalankan

```bash
composer run dev   # server + queue + vite sekaligus
```

Atau terpisah:

```bash
php artisan serve
pnpm dev
```

## Test

```bash
php artisan test --compact
```

Test memakai database terpisah `sportivo_test` (lihat `phpunit.xml`). Buat dulu:

```sql
CREATE DATABASE sportivo_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Kualitas kode

```bash
vendor/bin/pint      # format PHP
pnpm lint            # eslint
pnpm types:check     # tsc
pnpm format          # prettier
```

## Konvensi

- Nominal Rupiah: `BIGINT`, integer, tanpa desimal.
- Timestamp disimpan UTC, ditampilkan WIB. Kolom `booking_date`/`start_time` adalah wall-clock WIB dan tidak dikonversi.
- Format uang/tanggal lewat helper di `resources/js/lib/format.ts` — dilarang format inline.
- Light mode only.
- Routing frontend pakai Wayfinder (`@/actions`, `@/routes`), jangan hardcode URL.
