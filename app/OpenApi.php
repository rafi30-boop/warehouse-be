<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Warehouse Management API',
    description: 'API for warehouse management system including inventory, stock movements, attendance, and reporting.',
)]
#[OA\Server(
    url: '{host}',
    description: 'API Server',
    variables: [
        new OA\ServerVariable(
            serverVariable: 'host',
            default: 'http://localhost:8000',
        ),
    ],
)]
#[OA\Tag(name: 'Auth', description: 'Authentication endpoints (login, register, logout)')]
#[OA\Tag(name: 'Gudang', description: 'Warehouse CRUD')]
#[OA\Tag(name: 'Barang', description: 'Item/Product CRUD & export')]
#[OA\Tag(name: 'Kategori', description: 'Category CRUD')]
#[OA\Tag(name: 'Supplier', description: 'Supplier CRUD')]
#[OA\Tag(name: 'Customer', description: 'Customer CRUD')]
#[OA\Tag(name: 'Barang Masuk', description: 'Inbound goods CRUD, export & print surat jalan')]
#[OA\Tag(name: 'Barang Keluar', description: 'Outbound goods CRUD, export & print surat jalan')]
#[OA\Tag(name: 'Mutasi Stok', description: 'Stock transfer CRUD')]
#[OA\Tag(name: 'Stok Opname', description: 'Stock opname CRUD')]
#[OA\Tag(name: 'Absensi', description: 'Attendance CRUD')]
#[OA\Tag(name: 'Shift', description: 'Shift CRUD')]
#[OA\Tag(name: 'User', description: 'User management CRUD')]
#[OA\Tag(name: 'Role', description: 'Role & permission management CRUD')]
#[OA\Tag(name: 'Laporan', description: 'Reports (stock, inbound, outbound, mutation, opname, attendance)')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Passport',
    description: 'Enter Bearer token obtained from POST /api/login'
)]
class OpenApi
{
}

// ──────────────────────────────────────────────────────────
// Schemas
// ──────────────────────────────────────────────────────────

#[OA\Schema(
    schema: 'SuccessResponse',
    description: 'Standard success envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Operation successful'),
        new OA\Property(property: 'data', description: 'The response payload'),
    ],
)]
class SuccessResponse {}

#[OA\Schema(
    schema: 'ErrorResponse',
    description: 'Standard error envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
        new OA\Property(property: 'errors', type: 'object', nullable: true),
    ],
)]
class ErrorResponse {}

#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 5),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'total', type: 'integer', example: 75),
    ],
)]
class PaginationMeta {}

#[OA\Schema(
    schema: 'Gudang',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'kode', type: 'string', example: 'GDG001'),
        new OA\Property(property: 'nama', type: 'string', example: 'Gudang Utama'),
        new OA\Property(property: 'alamat', type: 'string', nullable: true),
        new OA\Property(property: 'pic', type: 'string', nullable: true),
        new OA\Property(property: 'telepon', type: 'string', nullable: true),
        new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['aktif', 'nonaktif']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class GudangSchema {}

#[OA\Schema(
    schema: 'Barang',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'sku', type: 'string', example: 'BRG001'),
        new OA\Property(property: 'barcode', type: 'string', nullable: true),
        new OA\Property(property: 'nama', type: 'string', example: 'Semen 50kg'),
        new OA\Property(property: 'kategori_id', type: 'integer'),
        new OA\Property(property: 'satuan_id', type: 'integer'),
        new OA\Property(property: 'min_stok', type: 'number', format: 'float'),
        new OA\Property(property: 'max_stok', type: 'number', format: 'float'),
        new OA\Property(property: 'berat', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'foto', type: 'string', nullable: true),
        new OA\Property(property: 'harga_beli', type: 'number', format: 'float'),
        new OA\Property(property: 'harga_jual', type: 'number', format: 'float'),
        new OA\Property(property: 'deskripsi', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['aktif', 'nonaktif']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class BarangSchema {}

#[OA\Schema(
    schema: 'Kategori',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
        new OA\Property(property: 'nama', type: 'string', example: 'Material Bangunan'),
        new OA\Property(property: 'deskripsi', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class KategoriSchema {}

#[OA\Schema(
    schema: 'Supplier',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'kode', type: 'string', example: 'SPL001'),
        new OA\Property(property: 'tipe', type: 'string', enum: ['perusahaan', 'pribadi']),
        new OA\Property(property: 'nama', type: 'string', example: 'PT Supplier Makmur'),
        new OA\Property(property: 'kontak', type: 'string', nullable: true),
        new OA\Property(property: 'telepon', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'alamat', type: 'string', nullable: true),
        new OA\Property(property: 'npwp', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class SupplierSchema {}

#[OA\Schema(
    schema: 'Customer',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'kode', type: 'string', example: 'CST001'),
        new OA\Property(property: 'tipe', type: 'string', enum: ['perusahaan', 'pribadi']),
        new OA\Property(property: 'nama', type: 'string', example: 'PT Customer Sejahtera'),
        new OA\Property(property: 'kontak', type: 'string', nullable: true),
        new OA\Property(property: 'telepon', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'alamat', type: 'string', nullable: true),
        new OA\Property(property: 'npwp', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class CustomerSchema {}

#[OA\Schema(
    schema: 'BarangMasuk',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'no_referensi', type: 'string', example: 'BM001'),
        new OA\Property(property: 'nomor_surat_jalan', type: 'string', nullable: true),
        new OA\Property(property: 'gudang_id', type: 'integer'),
        new OA\Property(property: 'supplier_id', type: 'integer'),
        new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected', 'partial']),
        new OA\Property(property: 'created_by', type: 'integer', nullable: true),
        new OA\Property(property: 'approved_by', type: 'integer', nullable: true),
        new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'dokumen', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class BarangMasukSchema {}

#[OA\Schema(
    schema: 'BarangKeluar',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'no_referensi', type: 'string', example: 'BK001'),
        new OA\Property(property: 'nomor_surat_jalan', type: 'string', nullable: true),
        new OA\Property(property: 'gudang_id', type: 'integer'),
        new OA\Property(property: 'customer_id', type: 'integer'),
        new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected', 'delivered', 'partial']),
        new OA\Property(property: 'created_by', type: 'integer', nullable: true),
        new OA\Property(property: 'approved_by', type: 'integer', nullable: true),
        new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'delivered_by', type: 'integer', nullable: true),
        new OA\Property(property: 'delivered_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'dokumen', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class BarangKeluarSchema {}

#[OA\Schema(
    schema: 'MutasiStok',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'no_referensi', type: 'string', example: 'MS001'),
        new OA\Property(property: 'barang_id', type: 'integer'),
        new OA\Property(property: 'gudang_asal_id', type: 'integer'),
        new OA\Property(property: 'gudang_tujuan_id', type: 'integer'),
        new OA\Property(property: 'lokasi_rak_asal_id', type: 'integer', nullable: true),
        new OA\Property(property: 'lokasi_rak_tujuan_id', type: 'integer', nullable: true),
        new OA\Property(property: 'qty', type: 'number', format: 'float'),
        new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected', 'completed']),
        new OA\Property(property: 'created_by', type: 'integer', nullable: true),
        new OA\Property(property: 'approved_by', type: 'integer', nullable: true),
        new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class MutasiStokSchema {}

#[OA\Schema(
    schema: 'StokOpname',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'no_referensi', type: 'string', example: 'SO001'),
        new OA\Property(property: 'gudang_id', type: 'integer'),
        new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'in_progress', 'completed', 'cancelled']),
        new OA\Property(property: 'created_by', type: 'integer', nullable: true),
        new OA\Property(property: 'approved_by', type: 'integer', nullable: true),
        new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class StokOpnameSchema {}

#[OA\Schema(
    schema: 'Absensi',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'gudang_id', type: 'integer'),
        new OA\Property(property: 'shift_id', type: 'integer'),
        new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
        new OA\Property(property: 'jam_masuk', type: 'string', nullable: true),
        new OA\Property(property: 'jam_pulang', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['hadir', 'izin', 'sakit', 'alpha', 'cuti', 'terlambat']),
        new OA\Property(property: 'lokasi_checkin', type: 'string', nullable: true),
        new OA\Property(property: 'lokasi_checkout', type: 'string', nullable: true),
        new OA\Property(property: 'radius_validasi', type: 'integer', nullable: true),
        new OA\Property(property: 'foto_masuk', type: 'string', nullable: true),
        new OA\Property(property: 'foto_pulang', type: 'string', nullable: true),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'approved_by', type: 'integer', nullable: true),
        new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class AbsensiSchema {}

#[OA\Schema(
    schema: 'Shift',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nama', type: 'string', example: 'Shift Pagi'),
        new OA\Property(property: 'jam_masuk', type: 'string', example: '07:00'),
        new OA\Property(property: 'jam_pulang', type: 'string', example: '15:00'),
        new OA\Property(property: 'toleransi_masuk', type: 'integer', nullable: true),
        new OA\Property(property: 'toleransi_pulang', type: 'integer', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['aktif', 'nonaktif']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class ShiftSchema {}

#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Admin'),
        new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
        new OA\Property(property: 'gudang_id', type: 'integer', nullable: true),
        new OA\Property(property: 'no_pegawai', type: 'string', nullable: true),
        new OA\Property(property: 'telepon', type: 'string', nullable: true),
        new OA\Property(property: 'foto', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'last_login_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class UserSchema {}

#[OA\Schema(
    schema: 'Role',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'admin'),
        new OA\Property(property: 'guard_name', type: 'string', example: 'web'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class RoleSchema {}

#[OA\Schema(
    schema: 'LoginRequest',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
    ],
)]
class LoginRequestSchema {}

#[OA\Schema(
    schema: 'RegisterRequest',
    required: ['name', 'email', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'New User'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password'),
    ],
)]
class RegisterRequestSchema {}

#[OA\Schema(
    schema: 'StoreGudangRequest',
    required: ['kode', 'nama'],
    properties: [
        new OA\Property(property: 'kode', type: 'string', example: 'GDG001'),
        new OA\Property(property: 'nama', type: 'string', example: 'Gudang Utama'),
        new OA\Property(property: 'alamat', type: 'string', nullable: true),
        new OA\Property(property: 'pic', type: 'string', nullable: true),
        new OA\Property(property: 'telepon', type: 'string', nullable: true),
        new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['aktif', 'nonaktif'], default: 'aktif'),
    ],
)]
class StoreGudangRequestSchema {}

#[OA\Schema(
    schema: 'StoreBarangRequest',
    required: ['sku', 'nama', 'kategori_id', 'satuan_id'],
    properties: [
        new OA\Property(property: 'sku', type: 'string', example: 'BRG001'),
        new OA\Property(property: 'barcode', type: 'string', nullable: true),
        new OA\Property(property: 'nama', type: 'string', example: 'Semen 50kg'),
        new OA\Property(property: 'kategori_id', type: 'integer'),
        new OA\Property(property: 'satuan_id', type: 'integer'),
        new OA\Property(property: 'min_stok', type: 'number', format: 'float'),
        new OA\Property(property: 'max_stok', type: 'number', format: 'float'),
        new OA\Property(property: 'berat', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'foto', type: 'string', nullable: true),
        new OA\Property(property: 'harga_beli', type: 'number', format: 'float'),
        new OA\Property(property: 'harga_jual', type: 'number', format: 'float'),
        new OA\Property(property: 'deskripsi', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['aktif', 'nonaktif'], default: 'aktif'),
    ],
)]
class StoreBarangRequestSchema {}

#[OA\Schema(
    schema: 'StoreKategoriRequest',
    required: ['nama'],
    properties: [
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
        new OA\Property(property: 'nama', type: 'string', example: 'Material Bangunan'),
        new OA\Property(property: 'deskripsi', type: 'string', nullable: true),
    ],
)]
class StoreKategoriRequestSchema {}

#[OA\Schema(
    schema: 'StoreSupplierRequest',
    required: ['kode', 'tipe', 'nama'],
    properties: [
        new OA\Property(property: 'kode', type: 'string', example: 'SPL001'),
        new OA\Property(property: 'tipe', type: 'string', enum: ['perusahaan', 'pribadi']),
        new OA\Property(property: 'nama', type: 'string', example: 'PT Supplier Makmur'),
        new OA\Property(property: 'kontak', type: 'string', nullable: true),
        new OA\Property(property: 'telepon', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'alamat', type: 'string', nullable: true),
        new OA\Property(property: 'npwp', type: 'string', nullable: true),
    ],
)]
class StoreSupplierRequestSchema {}

#[OA\Schema(
    schema: 'StoreCustomerRequest',
    required: ['kode', 'tipe', 'nama'],
    properties: [
        new OA\Property(property: 'kode', type: 'string', example: 'CST001'),
        new OA\Property(property: 'tipe', type: 'string', enum: ['perusahaan', 'pribadi']),
        new OA\Property(property: 'nama', type: 'string', example: 'PT Customer Sejahtera'),
        new OA\Property(property: 'kontak', type: 'string', nullable: true),
        new OA\Property(property: 'telepon', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'alamat', type: 'string', nullable: true),
        new OA\Property(property: 'npwp', type: 'string', nullable: true),
    ],
)]
class StoreCustomerRequestSchema {}

#[OA\Schema(
    schema: 'StoreBarangMasukRequest',
    required: ['no_referensi', 'gudang_id', 'supplier_id', 'tanggal'],
    properties: [
        new OA\Property(property: 'no_referensi', type: 'string', example: 'BM001'),
        new OA\Property(property: 'nomor_surat_jalan', type: 'string', nullable: true),
        new OA\Property(property: 'gudang_id', type: 'integer'),
        new OA\Property(property: 'supplier_id', type: 'integer'),
        new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected', 'partial'], default: 'pending'),
        new OA\Property(property: 'dokumen', type: 'string', nullable: true),
    ],
)]
class StoreBarangMasukRequestSchema {}

#[OA\Schema(
    schema: 'StoreBarangKeluarRequest',
    required: ['no_referensi', 'gudang_id', 'customer_id', 'tanggal'],
    properties: [
        new OA\Property(property: 'no_referensi', type: 'string', example: 'BK001'),
        new OA\Property(property: 'nomor_surat_jalan', type: 'string', nullable: true),
        new OA\Property(property: 'gudang_id', type: 'integer'),
        new OA\Property(property: 'customer_id', type: 'integer'),
        new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected', 'delivered', 'partial'], default: 'pending'),
        new OA\Property(property: 'dokumen', type: 'string', nullable: true),
    ],
)]
class StoreBarangKeluarRequestSchema {}

#[OA\Schema(
    schema: 'StoreMutasiStokRequest',
    required: ['no_referensi', 'barang_id', 'gudang_asal_id', 'gudang_tujuan_id', 'qty', 'tanggal'],
    properties: [
        new OA\Property(property: 'no_referensi', type: 'string', example: 'MS001'),
        new OA\Property(property: 'barang_id', type: 'integer'),
        new OA\Property(property: 'gudang_asal_id', type: 'integer'),
        new OA\Property(property: 'gudang_tujuan_id', type: 'integer'),
        new OA\Property(property: 'lokasi_rak_asal_id', type: 'integer', nullable: true),
        new OA\Property(property: 'lokasi_rak_tujuan_id', type: 'integer', nullable: true),
        new OA\Property(property: 'qty', type: 'number', format: 'float'),
        new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected', 'completed'], default: 'pending'),
    ],
)]
class StoreMutasiStokRequestSchema {}

#[OA\Schema(
    schema: 'StoreStokOpnameRequest',
    required: ['no_referensi', 'gudang_id', 'tanggal'],
    properties: [
        new OA\Property(property: 'no_referensi', type: 'string', example: 'SO001'),
        new OA\Property(property: 'gudang_id', type: 'integer'),
        new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'in_progress', 'completed', 'cancelled'], default: 'draft'),
    ],
)]
class StoreStokOpnameRequestSchema {}

#[OA\Schema(
    schema: 'StoreAbsensiRequest',
    required: ['user_id', 'gudang_id', 'shift_id', 'tanggal', 'status'],
    properties: [
        new OA\Property(property: 'user_id', type: 'integer'),
        new OA\Property(property: 'gudang_id', type: 'integer'),
        new OA\Property(property: 'shift_id', type: 'integer'),
        new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
        new OA\Property(property: 'jam_masuk', type: 'string', nullable: true),
        new OA\Property(property: 'jam_pulang', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['hadir', 'izin', 'sakit', 'alpha', 'cuti', 'terlambat']),
        new OA\Property(property: 'lokasi_checkin', type: 'string', nullable: true),
        new OA\Property(property: 'lokasi_checkout', type: 'string', nullable: true),
        new OA\Property(property: 'radius_validasi', type: 'integer', nullable: true),
        new OA\Property(property: 'foto_masuk', type: 'string', nullable: true),
        new OA\Property(property: 'foto_pulang', type: 'string', nullable: true),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
    ],
)]
class StoreAbsensiRequestSchema {}

#[OA\Schema(
    schema: 'StoreShiftRequest',
    required: ['nama', 'jam_masuk', 'jam_pulang'],
    properties: [
        new OA\Property(property: 'nama', type: 'string', example: 'Shift Pagi'),
        new OA\Property(property: 'jam_masuk', type: 'string', example: '07:00'),
        new OA\Property(property: 'jam_pulang', type: 'string', example: '15:00'),
        new OA\Property(property: 'toleransi_masuk', type: 'integer', nullable: true),
        new OA\Property(property: 'toleransi_pulang', type: 'integer', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['aktif', 'nonaktif'], default: 'aktif'),
    ],
)]
class StoreShiftRequestSchema {}

#[OA\Schema(
    schema: 'StoreUserRequest',
    required: ['name', 'email', 'password'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Staff Gudang'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'staff@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
        new OA\Property(property: 'gudang_id', type: 'integer', nullable: true),
        new OA\Property(property: 'no_pegawai', type: 'string', nullable: true),
        new OA\Property(property: 'telepon', type: 'string', nullable: true),
        new OA\Property(property: 'foto', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', default: true),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
    ],
)]
class StoreUserRequestSchema {}

#[OA\Schema(
    schema: 'StoreRoleRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'manager'),
        new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
    ],
)]
class StoreRoleRequestSchema {}