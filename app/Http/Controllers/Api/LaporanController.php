<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AbsensiResource;
use App\Http\Resources\BarangKeluarResource;
use App\Http\Resources\BarangMasukResource;
use App\Http\Resources\MutasiStokResource;
use App\Http\Resources\StokOpnameResource;
use App\Models\Absensi;
use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\MutasiStok;
use App\Models\StokOpname;
use App\Services\StokService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Laporan')]
class LaporanController extends Controller
{
    use ApiResponse;

    public function __construct(private StokService $stokService)
    {
        $this->middleware('permission:laporan-stok', ['only' => ['stok']]);
        $this->middleware('permission:laporan-barang-masuk', ['only' => ['barangMasuk']]);
        $this->middleware('permission:laporan-barang-keluar', ['only' => ['barangKeluar']]);
        $this->middleware('permission:laporan-mutasi-stok', ['only' => ['mutasiStok']]);
        $this->middleware('permission:laporan-stok-opname', ['only' => ['stokOpname']]);
        $this->middleware('permission:laporan-absensi', ['only' => ['absensi']]);
    }

    #[OA\Get(
        path: '/api/laporan/stok',
        summary: 'Report stok barang',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'kategori_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['aktif', 'nonaktif'])),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Search by nama or sku')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stok report data', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Laporan stok berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'barang_id', type: 'integer', example: 1),
                    new OA\Property(property: 'sku', type: 'string', example: 'BRG001'),
                    new OA\Property(property: 'nama', type: 'string', example: 'Semen 50kg'),
                    new OA\Property(property: 'kategori', type: 'string', nullable: true),
                    new OA\Property(property: 'satuan', type: 'string', nullable: true),
                    new OA\Property(property: 'stok_total', type: 'number', format: 'float', example: 120),
                    new OA\Property(property: 'min_stok', type: 'number', format: 'float'),
                    new OA\Property(property: 'max_stok', type: 'number', format: 'float'),
                    new OA\Property(property: 'status', type: 'string', enum: ['aktif', 'nonaktif']),
                ])),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function stok(Request $request)
    {
        $query = Barang::with(['kategori', 'satuan']);

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        $perPage = min(100, (int) $request->per_page ?: 50);
        $cacheKey = 'laporan_stok:'.md5(json_encode([
            $request->filled('kategori_id') ? (int) $request->kategori_id : null,
            $request->filled('status') ? $request->status : null,
            $request->filled('search') ? $request->search : null,
            (int) $request->get('page', 1),
            $perPage,
        ]));

        $result = Cache::remember($cacheKey, 60, function () use ($query, $perPage) {
            $barang = $query->paginate($perPage);
            $saldoMap = $this->stokService->hitungSaldoStokBatch($barang->pluck('id')->toArray());

            $data = $barang->map(function ($b) use ($saldoMap) {
                return [
                    'barang_id' => $b->id,
                    'sku' => $b->sku,
                    'nama' => $b->nama,
                    'kategori' => $b->kategori->nama ?? null,
                    'satuan' => $b->satuan->singkatan ?? null,
                    'stok_total' => $saldoMap[$b->id] ?? 0,
                    'min_stok' => $b->min_stok,
                    'max_stok' => $b->max_stok,
                    'status' => $b->status,
                ];
            });

            return [
                'data' => $data,
                'meta' => [
                    'current_page' => $barang->currentPage(),
                    'last_page' => $barang->lastPage(),
                    'per_page' => $barang->perPage(),
                    'total' => $barang->total(),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan stok berhasil dimuat',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ], 200);
    }

    #[OA\Get(
        path: '/api/laporan/barang-masuk',
        summary: 'Report barang masuk',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', description: 'Start date (Y-m-d)')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', description: 'End date (Y-m-d)')),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'supplier_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang masuk report data', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Laporan barang masuk berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BarangMasuk')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function barangMasuk(Request $request)
    {
        $query = BarangMasuk::with([
            'gudang' => fn ($q) => $q->withTrashed(),
            'supplier' => fn ($q) => $q->withTrashed(),
            'createdBy' => fn ($q) => $q->withTrashed(),
            'details.barang',
            'details.lokasiRak',
        ]);

        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('tanggal', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('tanggal', '<=', $request->to);
        }

        $query->orderBy('tanggal', 'desc');

        $perPage = min(100, (int) $request->per_page ?: 50);
        $paginated = $query->paginate($perPage);

        return $this->paginated($paginated, BarangMasukResource::collection($paginated->items()), 'Laporan barang masuk berhasil dimuat');
    }

    #[OA\Get(
        path: '/api/laporan/barang-keluar',
        summary: 'Report barang keluar',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', description: 'Start date (Y-m-d)')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', description: 'End date (Y-m-d)')),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'customer_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected', 'delivered', 'partial'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang keluar report data', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Laporan barang keluar berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BarangKeluar')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function barangKeluar(Request $request)
    {
        $query = BarangKeluar::with([
            'gudang' => fn ($q) => $q->withTrashed(),
            'customer' => fn ($q) => $q->withTrashed(),
            'createdBy' => fn ($q) => $q->withTrashed(),
            'details.barang',
            'details.lokasiRak',
        ]);

        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('tanggal', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('tanggal', '<=', $request->to);
        }

        $query->orderBy('tanggal', 'desc');

        $perPage = min(100, (int) $request->per_page ?: 50);
        $paginated = $query->paginate($perPage);

        return $this->paginated($paginated, BarangKeluarResource::collection($paginated->items()), 'Laporan barang keluar berhasil dimuat');
    }

    #[OA\Get(
        path: '/api/laporan/mutasi-stok',
        summary: 'Report mutasi stok',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', description: 'Start date (Y-m-d)')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', description: 'End date (Y-m-d)')),
            new OA\Parameter(name: 'barang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gudang_asal_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gudang_tujuan_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected', 'completed'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Mutasi stok report data', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Laporan mutasi stok berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/MutasiStok')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function mutasiStok(Request $request)
    {
        $query = MutasiStok::with([
            'barang',
            'gudangAsal' => fn ($q) => $q->withTrashed(),
            'gudangTujuan' => fn ($q) => $q->withTrashed(),
            'lokasiRakAsal',
            'lokasiRakTujuan',
            'createdBy',
        ]);

        if ($request->filled('barang_id')) {
            $query->where('barang_id', $request->barang_id);
        }

        if ($request->filled('gudang_asal_id')) {
            $query->where('gudang_asal_id', $request->gudang_asal_id);
        }

        if ($request->filled('gudang_tujuan_id')) {
            $query->where('gudang_tujuan_id', $request->gudang_tujuan_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('tanggal', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('tanggal', '<=', $request->to);
        }

        $query->orderBy('tanggal', 'desc');

        $perPage = min(100, (int) $request->per_page ?: 50);
        $paginated = $query->paginate($perPage);

        return $this->paginated($paginated, MutasiStokResource::collection($paginated->items()), 'Laporan mutasi stok berhasil dimuat');
    }

    #[OA\Get(
        path: '/api/laporan/stok-opname',
        summary: 'Report stok opname',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', description: 'Start date (Y-m-d)')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', description: 'End date (Y-m-d)')),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'in_progress', 'completed', 'cancelled'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stok opname report data', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Laporan stok opname berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StokOpname')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function stokOpname(Request $request)
    {
        $query = StokOpname::with([
            'gudang' => fn ($q) => $q->withTrashed(),
            'createdBy' => fn ($q) => $q->withTrashed(),
            'details.barang.kategori',
            'details.barang.satuan',
            'details.lokasiRak',
        ]);

        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('tanggal', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('tanggal', '<=', $request->to);
        }

        $query->orderBy('tanggal', 'desc');

        $perPage = min(100, (int) $request->per_page ?: 50);
        $paginated = $query->paginate($perPage);

        return $this->paginated($paginated, StokOpnameResource::collection($paginated->items()), 'Laporan stok opname berhasil dimuat');
    }

    #[OA\Get(
        path: '/api/laporan/absensi',
        summary: 'Report absensi',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', description: 'Start date (Y-m-d)')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', description: 'End date (Y-m-d)')),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'shift_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['hadir', 'izin', 'sakit', 'alpha', 'cuti', 'terlambat'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Absensi report data', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Laporan absensi berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Absensi')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function absensi(Request $request)
    {
        $query = Absensi::with([
            'user' => fn ($q) => $q->withTrashed(),
            'petugas',
            'gudang' => fn ($q) => $q->withTrashed(),
            'shift',
        ]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('petugas_id')) {
            $query->where('petugas_id', $request->petugas_id);
        }

        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('tanggal', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('tanggal', '<=', $request->to);
        }

        $query->orderBy('tanggal', 'desc');

        $perPage = min(100, (int) $request->per_page ?: 50);
        $paginated = $query->paginate($perPage);

        return $this->paginated($paginated, AbsensiResource::collection($paginated->items()), 'Laporan absensi berhasil dimuat');
    }
}
