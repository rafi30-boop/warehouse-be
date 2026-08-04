# Warehouse Management API

Backend API untuk sistem manajemen gudang (warehouse management system) berbasis **Laravel 12** + **Laravel Passport** (OAuth Bearer token) dengan RBAC via **spatie/laravel-permission**.

Dokumentasi interaktif API tersedia di Swagger UI: `{APP_URL}/api/documentation`.

## Fitur

- Autentikasi & otorisasi: login, register, logout, refresh token, RBAC (role & permission)
- Manajemen master: Gudang, Kategori, Satuan, Barang, Supplier, Customer, Lokasi Rak
- Transaksi stok: Barang Masuk, Barang Keluar, Mutasi Stok, Stok Opname — lengkap dengan **detail item** dan **alur persetujuan** (pending → approved/rejected → delivered)
- Kartu stok & riwayat per barang
- Absensi & shift, jadwal petugas
- Notifikasi, aktivitas log, batch/kedaluwarsa barang, history harga
- Upload file (dokumen/gambar) via `/api/upload`
- Ekspor Excel & cetak PDF surat jalan
- Laporan: stok, barang masuk/keluar, mutasi, opname, absensi
- Response terstandar dengan envelope `{ success, message, data }` + pagination meta

## Persyaratan

- PHP 8.2+
- Composer 2
- MySQL 8+ / MariaDB

## Instalasi

```bash
# 1. Install dependencies
composer install

# 2. Setup environment
cp .env.example .env
php artisan key:generate
# edit .env: atur DB_*, APP_URL, FRONTEND_URL

# 3. Migrasi & seeder (termasuk role & permission + akun default)
php artisan migrate --seed

# 4. Passport: buat kunci OAuth + personal access client (wajib agar token login berfungsi)
php artisan passport:install --no-interaction

# 5. Storage link untuk file upload
php artisan storage:link

# 6. Jalankan server
php artisan serve
```

> `passport:install` akan membuat client `Laravel Personal Access Client` yang dibutuhkan oleh `createToken()`. Jika sudah pernah dijalankan dan login tetap gagal, jalankan `php artisan passport:client --personal --no-interaction` lalu restart server.

## Akun Default

Seeder (`DatabaseSeeder`) membuat akun berikut:

| Role        | Email                 | Password   |
|-------------|-----------------------|------------|
| super-admin | admin@example.com     | password   |
| operator    | operator@example.com  | password   |

## Struktur Response

Semua endpoint (kecuali file download) mengembalikan envelope standar:

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": { ... }
}
```

Error (422/401/403/404):

```json
{
  "success": false,
  "message": "Email atau password salah",
  "errors": { "email": ["..."] }
}
```

List (paginated) menyertakan `meta`:

```json
{
  "success": true,
  "message": "Daftar barang berhasil dimuat",
  "data": [ ... ],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 75 }
}
```

## Autentikasi

- `POST /api/login` — body `{ email, password }` → `data.token`
- Semua request selain login/register memakai header `Authorization: Bearer <token>`
- `POST /api/refresh` — menerbitkan token baru dan mencabut token lama (token rotation)
- `POST /api/logout` — mencabut token aktif

Rate limit: login/register 5 request/menit/IP, API 100 request/menit.

## Transaksi & Alur Status

**Barang Masuk** (`barang-masuk`): `pending` → `approve` → `approved` | `reject` → `rejected`. Stok masuk dihitung dari dokumen berstatus `approved`.

**Barang Keluar** (`barang-keluar`): `pending` → `approve` → `approved` → `deliver` → `delivered` (stok keluar dihitung saat `delivered`) | `partial`. Cek ketersediaan stok otomatis saat buat dokumen (`store`) dan saat `deliver`; stok minus ditolak 422.

**Stok Opname** (`stok-opname`): `draft` → `start` → `in_progress` → `complete` → `completed` (menghitung ulang `stok_sistem` & `selisih`) | `cancel` → `cancelled`.

**Mutasi Stok** (`mutasi-stok`): stok keluar dari gudang asal & masuk ke gudang tujuan hanya saat status `completed`.

Setiap dokumen transaksi menerima `details` (array item). Contoh barang keluar:

```json
{
  "no_referensi": "BK001",
  "gudang_id": 1,
  "customer_id": 1,
  "tanggal": "2026-08-04",
  "details": [
    { "barang_id": 1, "lokasi_rak_id": 1, "qty": 5, "harga_satuan": 15000 }
  ]
}
```

## Daftar Endpoint Utama

- Auth: `POST /api/login`, `POST /api/register`, `GET /api/me`, `POST /api/logout`, `POST /api/refresh`
- Upload: `POST /api/upload` (multipart `file`, max 10MB)
- Master: `gudang`, `kategori`, `satuan`, `barang`, `supplier`, `customer`, `lokasi-rak`, `batch-barang`
- Transaksi: `barang-masuk` (+`/{id}/approve`, `/{id}/reject`, `/export/excel`, `/{id}/print-surat-jalan`), `barang-keluar` (+`/{id}/approve`, `/{id}/reject`, `/{id}/deliver`, `/{id}/partial`), `mutasi-stok`, `stok-opname` (+`/{id}/start`, `/{id}/complete`, `/{id}/cancel`)
- Kartu stok: `GET /api/kartu-stok` (filter), `GET /api/kartu-stok/riwayat?barang_id=..&gudang_id=..&from=..&to=..`
- Lainnya: `absensi`, `shift`, `jadwal-petugas`, `notifikasi` (+`/{id}/read`, `/read-all`), `aktivitas-log`, `history-harga`
- Manajemen: `user`, `role`
- Laporan: `/api/laporan/stok|barang-masuk|barang-keluar|mutasi-stok|stok-opname|absensi`

Lihat daftar lengkap & skema di Swagger: `{APP_URL}/api/documentation`.

## Permission (RBAC)

Pola: `{entity}-list|create|edit|delete` plus permission khusus:

- `barang-masuk-approve`, `barang-keluar-approve`, `barang-keluar-deliver`
- `stok-opname-start|complete|cancel`
- `barang-export`, `barang-masuk-export|print`, `barang-keluar-export|print`
- `laporan-*`, `upload`, `kartu-stok-list`, `aktivitas-log-*`, `notifikasi-*`

Peran bawaan: `super-admin` (semua permission), `admin` (semua), `operator` (terbatas). Pengguna baru dari `register` otomatis mendapat role `operator`.

## Menjalankan Pengujian

```bash
php artisan test          # seluruh test suite
vendor\bin\pint --dirty   # format kode (Windows)
```

Test memakai SQLite in-memory, tidak menyentuh database utama.

## Regenerasi Dokumentasi Swagger

```bash
php artisan l5-swagger:generate
```
