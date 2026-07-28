<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
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
use Illuminate\Http\Request;

#[OA\Tag(name: 'Laporan')]
class LaporanController extends Controller
{
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
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stok report data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
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
        $barang = $query->paginate($perPage);
        $barangIds = $barang->pluck('id')->toArray();
        $saldoMap = $this->stokService->hitungSaldoStokBatch($barangIds);

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

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $barang->total(),
                'per_page' => $barang->perPage(),
                'current_page' => $barang->currentPage(),
                'last_page' => $barang->lastPage(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/laporan/barang-masuk',
        summary: 'Report barang masuk',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang masuk report data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function barangMasuk(Request $request)
    {
        $query = BarangMasuk::with([
            'gudang' => fn($q) => $q->withTrashed(),
            'supplier' => fn($q) => $q->withTrashed(),
            'createdBy' => fn($q) => $q->withTrashed(),
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

        return response()->json([
            'data' => BarangMasukResource::collection($query->paginate($perPage)),
        ]);
    }

    #[OA\Get(
        path: '/api/laporan/barang-keluar',
        summary: 'Report barang keluar',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang keluar report data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function barangKeluar(Request $request)
    {
        $query = BarangKeluar::with([
            'gudang' => fn($q) => $q->withTrashed(),
            'customer' => fn($q) => $q->withTrashed(),
            'createdBy' => fn($q) => $q->withTrashed(),
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

        return response()->json([
            'data' => BarangKeluarResource::collection($query->paginate($perPage)),
        ]);
    }

    #[OA\Get(
        path: '/api/laporan/mutasi-stok',
        summary: 'Report mutasi stok',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'gudang_asal_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Mutasi stok report data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function mutasiStok(Request $request)
    {
        $query = MutasiStok::with([
            'barang',
            'gudangAsal' => fn($q) => $q->withTrashed(),
            'gudangTujuan' => fn($q) => $q->withTrashed(),
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

        return response()->json([
            'data' => MutasiStokResource::collection($query->paginate($perPage)),
        ]);
    }

    #[OA\Get(
        path: '/api/laporan/stok-opname',
        summary: 'Report stok opname',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stok opname report data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function stokOpname(Request $request)
    {
        $query = StokOpname::with([
            'gudang' => fn($q) => $q->withTrashed(),
            'createdBy' => fn($q) => $q->withTrashed(),
            'details.barang',
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

        return response()->json([
            'data' => StokOpnameResource::collection($query->paginate($perPage)),
        ]);
    }

    #[OA\Get(
        path: '/api/laporan/absensi',
        summary: 'Report absensi',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Absensi report data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function absensi(Request $request)
    {
        $query = Absensi::with([
            'user' => fn($q) => $q->withTrashed(),
            'gudang' => fn($q) => $q->withTrashed(),
            'shift',
        ]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
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

        return response()->json([
            'data' => AbsensiResource::collection($query->paginate($perPage)),
        ]);
    }
}
