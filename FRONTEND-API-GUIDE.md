# Panduan Integrasi Frontend — Warehouse Management API

Dokumen ini adalah report dari backend yang sudah dibangun, berisi instruksi teknis untuk tim frontend.

---

## 1. Ringkasan Backend

Backend **Warehouse Management System (WMS)** berbasis **Laravel 12 + Laravel Passport (OAuth Bearer token)** dengan RBAC **spatie/laravel-permission**.

### Fitur yang sudah dibangun
| Modul | Keterangan |
|---|---|
| Auth | login, register, logout, refresh token, `GET /me` |
| RBAC | role & permission, peran bawaan: `super-admin`, `admin`, `operator` |
| Master data | Gudang, Kategori, Satuan, Barang, Supplier, Customer, Lokasi Rak, Batch Barang |
| Transaksi | Barang Masuk, Barang Keluar, Mutasi Stok, Stok Opname (semua dengan detail item + alur persetujuan) |
| Kartu stok | riwayat mutasi stok per barang/gudang |
| Operasional | Absensi, Shift, Jadwal Petugas |
| Pendukung | Notifikasi, Aktivitas Log, History Harga |
| File | upload dokumen/gambar (`/api/upload`) |
| Laporan | stok, barang masuk/keluar, mutasi, opname, absensi |
| Ekspor | Excel (barang, barang masuk/keluar) & PDF surat jalan |
| Lainnya | Redis cache, rate limiting, Swagger docs (`{APP_URL}/api/documentation`) |

Dokumentasi interaktif lengkap dengan skema request/response: **`{APP_URL}/api/documentation`**.

---

## 2. Konvensi Response

Semua endpoint (kecuali download file) memakai envelope standar:

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": { ... }
}
```

**Error** (401/403/404/422):
```json
{
  "success": false,
  "message": "Email atau password salah",
  "errors": { "email": ["..."] }
}
```

**List (paginated)** menyertakan `meta`:
```json
{
  "success": true,
  "message": "Daftar barang berhasil dimuat",
  "data": [ ... ],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 75 }
}
```

> Instruksi frontend: selalu baca `response.data` (bukan `response.data.data`), dan tampilkan `message` untuk notifikasi sukses/gagal. Gunakan `meta` untuk pagination.

---

## 3. Autentikasi

```
POST /api/login     body: { email, password }        → data: { token, user }
POST /api/register  body: { name, email, password, password_confirmation } → data: { token, user }
GET  /api/me        header Authorization: Bearer <token>  → data: user + roles.permissions + gudang
POST /api/logout    (revoke token)
POST /api/refresh   (terbitkan token baru, token lama dicabut) → data: { token }
```

- Semua request selain login/register wajib `Authorization: Bearer <token>`.
- Token adalah **Passport access token** (format string, bukan JWT segment). Simpan di `localStorage`/`sessionStorage`.
- Rate limit: login/register **5 req/menit/IP**, API umum **100 req/menit**.

> Instruksi frontend: gunakan axios interceptor untuk: (1) otomatis tambah header `Authorization`; (2) bila response 401, coba `POST /api/refresh`, lalu ulangi request; bila refresh gagal → redirect ke halaman login. Render halaman sesuai permission dari `GET /api/me` (`roles[].permissions[]`).

---

## 4. Daftar Endpoint

Format: `METHOD /path` — keterangan.

### 4.1 Master Data (CRUD lengkap: index, store, show, update, destroy)

| Endpoint | Keterangan |
|---|---|
| `GET|POST /api/gudang` `GET/PUT/DELETE /api/gudang/{id}` | Gudang |
| `GET|POST /api/kategori` `.../kategori/{id}` | Kategori barang |
| `GET|POST /api/satuan` `.../satuan/{id}` | Satuan |
| `GET|POST /api/barang` `.../barang/{id}` | Barang |
| `GET|POST /api/supplier` `.../supplier/{id}` | Supplier |
| `GET|POST /api/customer` `.../customer/{id}` | Customer |
| `GET|POST /api/lokasi-rak` `.../lokasi-rak/{id}` | Lokasi rak |
| `GET|POST /api/batch-barang` `.../batch-barang/{id}` | Batch/kedaluwarsa |
| `GET|POST /api/history-harga` `.../history-harga/{id}` | History harga (tanpa update) |

Filter list: query `?search=`, `?per_page=`, dan filter khusus tiap modul (mis. `barang?kategori_id=&gudang_id=`, `lokasi-rak?gudang_id=`) — detail skema di Swagger.

**Struktur data penting (response)**
- Barang: `{ id, sku, barcode, nama, kategori_id, satuan_id, min_stok, max_stok, berat, foto, harga_beli, harga_jual, deskripsi, status, kategori, satuan }`
- User: `{ id, name, email, gudang_id, no_pegawai, telepon, foto, is_active, last_login_at, gudang, roles }`

### 4.2 Barang Masuk (`/api/barang-masuk`)

| Endpoint | Keterangan |
|---|---|
| `GET /api/barang-masuk` | list (filter: `?status=`, `?gudang_id=`, `?supplier_id=`, `?from=`, `?to=`) |
| `POST /api/barang-masuk` | buat dokumen + detail |
| `GET /api/barang-masuk/{id}` | detail (termasuk `details`, `gudang`, `supplier`, `createdBy`, `approvedBy`) |
| `PUT /api/barang-masuk/{id}` | update (hanya saat status `pending`) |
| `DELETE /api/barang-masuk/{id}` | hapus (hanya `pending`) |
| `POST /api/barang-masuk/{id}/approve` | setujui → **stok masuk** dihitung |
| `POST /api/barang-masuk/{id}/reject` | tolak |
| `GET /api/barang-masuk/export/excel` | download `.xlsx` |
| `GET /api/barang-masuk/{id}/print-surat-jalan` | download PDF surat jalan |

**Body create/update:**
```json
{
  "no_referensi": "BM001",
  "nomor_surat_jalan": "SJ-001",
  "gudang_id": 1,
  "supplier_id": 1,
  "tanggal": "2026-08-13",
  "keterangan": "Pembelian rutin",
  "dokumen": "url/hasil upload",
  "details": [
    { "barang_id": 1, "lokasi_rak_id": 1, "qty": 10, "harga_satuan": 15000, "expired_at": "2027-01-01" }
  ]
}
```

**Alur status:** `pending` → `approved` (via approve) | `rejected` (via reject)

### 4.3 Barang Keluar (`/api/barang-keluar`)

| Endpoint | Keterangan |
|---|---|
| `GET /api/barang-keluar` | list |
| `POST /api/barang-keluar` | buat dokumen + detail (otomatis cek ketersediaan stok; stok minus → 422) |
| `GET /api/barang-keluar/{id}` | detail |
| `PUT /api/barang-keluar/{id}` | update (hanya `pending`) |
| `DELETE /api/barang-keluar/{id}` | hapus (hanya `pending`) |
| `POST /api/barang-keluar/{id}/approve` | setujui |
| `POST /api/barang-keluar/{id}/reject` | tolak |
| `POST /api/barang-keluar/{id}/deliver` | kirim → **stok keluar dihitung** saat ini |
| `POST /api/barang-keluar/{id}/partial` | pengiriman sebagian |
| `GET /api/barang-keluar/export/excel` | download `.xlsx` |
| `GET /api/barang-keluar/{id}/print-surat-jalan` | download PDF surat jalan |

Body sama pola dengan barang masuk, bedanya `customer_id` menggantikan `supplier_id`.

**Alur status:** `pending` → `approved` → `delivered` | `partial` ; tolak → `rejected`

### 4.4 Mutasi Stok (`/api/mutasi-stok`)

| Endpoint | Keterangan |
|---|---|
| `GET|POST /api/mutasi-stok` `GET/PUT/DELETE /api/mutasi-stok/{id}` | CRUD |
| `POST /api/mutasi-stok/{id}/approve` | setujui |
| `POST /api/mutasi-stok/{id}/reject` | tolak |
| `POST /api/mutasi-stok/{id}/complete` | selesaikan → **stok pindah: keluar dari gudang asal, masuk ke gudang tujuan** |

**Body create:**
```json
{
  "no_referensi": "MS001",
  "gudang_asal_id": 1,
  "gudang_tujuan_id": 2,
  "tanggal": "2026-08-13",
  "keterangan": "Transfer gudang",
  "details": [ { "barang_id": 1, "qty": 5 } ]
}
```

**Alur status:** `pending` → `approved` → `completed` | `rejected`

### 4.5 Stok Opname (`/api/stok-opname`)

| Endpoint | Keterangan |
|---|---|
| `GET|POST /api/stok-opname` `GET/PUT/DELETE /api/stok-opname/{id}` | CRUD |
| `POST /api/stok-opname/{id}/start` | mulai opname (draft → in_progress) |
| `POST /api/stok-opname/{id}/complete` | selesai → backend hitung ulang `stok_sistem` & `selisih` per item |
| `POST /api/stok-opname/{id}/cancel` | batalkan |

**Alur status:** `draft` → `in_progress` → `completed` | `cancelled`

### 4.6 Kartu Stok (`/api/kartu-stok`)

| Endpoint | Keterangan |
|---|---|
| `GET /api/kartu-stok` | list kartu stok (filter: `barang_id`, `gudang_id`, `lokasi_rak_id`, `tipe` = `in|out|mutasi_in|mutasi_out|opname`) |
| `GET /api/kartu-stok/{id}` | detail |
| `GET /api/kartu-stok/riwayat?barang_id=&gudang_id=&from=&to=` | **riwayat transaksi per barang** (wajib `barang_id`) |

### 4.7 Operasional

| Endpoint | Keterangan |
|---|---|
| `GET|POST /api/absensi` `.../absensi/{id}` | Absensi (filter `?user_id=&from=&to=`) |
| `GET|POST /api/shift` `.../shift/{id}` | Shift |
| `GET|POST /api/jadwal-petugas` `.../jadwal-petugas/{id}` | Jadwal petugas |

### 4.8 Manajemen & Pendukung

| Endpoint | Keterangan |
|---|---|
| `GET|POST /api/user` `GET/PUT/DELETE /api/user/{id}` | Manajemen user (hanya super-admin/admin) |
| `GET|POST /api/role` `GET/PUT/DELETE /api/role/{id}` | Manajemen role & permission |
| `GET /api/notifikasi` `GET /api/notifikasi/{id}` `DELETE /api/notifikasi/{id}` | Notifikasi (hanya milik user sendiri) |
| `POST /api/notifikasi/{id}/read` | tandai dibaca |
| `POST /api/notifikasi/read-all` | tandai semua dibaca |
| `GET /api/aktivitas-log` `GET /api/aktivitas-log/{id}` `DELETE /api/aktivitas-log/{id}` | Log aktivitas (filter `?user_id=`, `?modul=`, `?from=&to=`) |

### 4.9 Upload File

```
POST /api/upload   (multipart/form-data, field: file, max 10MB)
Allowed types: jpg, jpeg, png, pdf, doc, docx, xls, xlsx, csv
```
Response:
```json
{ "success": true, "data": { "url": "http://.../storage/uploads/xxx.pdf", "path": "uploads/xxx.pdf", "name": "xxx.pdf" } }
```
Simpan `url` (untuk ditampilkan) ke field dokumen/foto pada entity terkait.

### 4.10 Laporan (`/api/laporan/*`)

| Endpoint | Keterangan |
|---|---|
| `GET /api/laporan/stok` | rekap stok (filter `?gudang_id=`, `?kategori_id=`, `?search=`) |
| `GET /api/laporan/barang-masuk` | filter `?gudang_id=`, `?supplier_id=`, `?from=&to=` |
| `GET /api/laporan/barang-keluar` | filter `?gudang_id=`, `?customer_id=`, `?from=&to=` |
| `GET /api/laporan/mutasi-stok` | filter `?gudang_id=`, `?from=&to=` |
| `GET /api/laporan/stok-opname` | filter `?gudang_id=`, `?status=`, `?from=&to=` |
| `GET /api/laporan/absensi` | filter `?user_id=`, `?from=&to=` |

Semua laporan punya parameter `?format=excel` → download `.xlsx` (jika ingin versi file).

---

## 5. Download File (Excel & PDF)

Endpoint export/print mengembalikan **binary stream** (bukan envelope JSON):

- Excel: `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, file `*.xlsx`
- Surat jalan: `Content-Type: application/pdf`

> Instruksi frontend: panggil dengan `responseType: 'blob'` (axios), lalu buat URL `URL.createObjectURL` dan trigger download/print. Sertakan header `Authorization` sama seperti request lain.

---

## 6. Hak Akses untuk UI (RBAC)

Pola permission: `{entity}-list|create|edit|delete`. Gunakan data `GET /api/me` → `roles[].permissions[]` untuk memutuskan menu/tombol yang ditampilkan.

| Area | Permission |
|---|---|
| Barang Masuk | `barang-masuk-list/create/edit/delete`, `barang-masuk-approve`, `barang-masuk-export`, `barang-masuk-print` |
| Barang Keluar | `barang-keluar-list/create/edit/delete`, `barang-keluar-approve`, `barang-keluar-deliver`, `barang-keluar-export`, `barang-keluar-print` |
| Stok Opname | `stok-opname-*` + `stok-opname-start/complete/cancel` |
| Barang | `barang-*` + `barang-export` |
| Laporan | `laporan-*` |
| Kartu stok | `kartu-stok-list` |
| Upload | `upload` |
| Lainnya | `aktivitas-log-*`, `notifikasi-*` |

Peran bawaan: **super-admin** & **admin** = semua permission; **operator** = terbatas (biasanya create/list sendiri, tidak approve). User dari `register` otomatis menjadi `operator`.

> Instruksi frontend: jangan hardcode role; cek list permission dari API. Tombol approve/deliver/complete hanya tampil bila user punya permission terkait (dan status dokumen valid).

---

## 7. Instruksi Implementasi Frontend (Checklist)

1. **HTTP client**: axios, baseURL `{APP_URL}/api`, timeout ±30 detik.
2. **Interceptor request**: tambah `Authorization: Bearer <token>` jika ada.
3. **Interceptor response**:
   - `success === false` → tampilkan `message` (+ detail `errors` untuk form validation 422).
   - 401 → coba `POST /api/refresh` sekali, lalu retry; gagal → logout paksa ke halaman login.
4. **State auth**: simpan token; pada boot aplikasi panggil `GET /api/me` untuk user + permission; guard route per permission.
5. **Pagination**: semua list pakai `?page=&per_page=` (maks 100); render `meta`.
6. **Detail transaksi**: saat edit hanya boleh saat status `pending`; setelah approve, form disabled.
7. **Tombol aksi status**:
   - Barang masuk: Approve / Reject (saat `pending`)
   - Barang keluar: Approve / Reject (`pending`), Deliver / Partial (`approved`)
   - Mutasi: Approve / Reject (`pending`), Complete (`approved`)
   - Opname: Start (`draft`), Complete (`in_progress`), Cancel (`draft`/`in_progress`)
8. **Form transaksi** selalu kirim `details` sebagai array of object; pilih `barang_id` via dropdown dari `/api/barang`, `gudang_id` dari `/api/gudang`, dst.
9. **Upload**: kirim FormData field `file`, tampilkan preview dari `data.url`.
10. **Tanggal**: kirim format `YYYY-MM-DD` (kolom `tanggal`).
11. **Download**: gunakan `responseType: 'blob'`; perhatikan nama file dari `Content-Disposition` bila tersedia.

---

## 8. Akun Test

| Role | Email | Password |
|---|---|---|
| super-admin | admin@example.com | password |
| operator | operator@example.com | password |

## 9. Konfigurasi Lingkungan

- `APP_URL` — base URL backend (untuk Swagger & file upload)
- `FRONTEND_URL` — origin frontend (untuk CORS)
- Swagger UI: `{APP_URL}/api/documentation`
