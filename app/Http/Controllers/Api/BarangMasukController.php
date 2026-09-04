<?php

namespace App\Http\Controllers\Api;

use App\Exports\BarangMasukExport;
use App\Exports\SuratJalanExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBarangMasukRequest;
use App\Http\Requests\UpdateBarangMasukRequest;
use App\Models\BarangMasuk;
use App\Policies\BasePolicy;
use App\Services\NotifikasiService;
use App\Services\StokService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Barang Masuk')]
class BarangMasukController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:barang-masuk-list|barang-masuk-create|barang-masuk-edit|barang-masuk-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:barang-masuk-create', ['only' => ['store']]);
        $this->middleware('permission:barang-masuk-edit', ['only' => ['update']]);
        $this->middleware('permission:barang-masuk-delete', ['only' => ['destroy']]);
        $this->middleware('permission:barang-masuk-export', ['only' => ['exportExcel']]);
        $this->middleware('permission:barang-masuk-print', ['only' => ['printSuratJalan']]);
        $this->middleware('permission:barang-masuk-approve', ['only' => ['approve', 'reject']]);
    }

    #[OA\Get(
        path: '/api/barang-masuk/export/excel',
        summary: 'Export barang masuk to Excel',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Excel file download (application/vnd.openxmlformats-officedocument.spreadsheetml.sheet)'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function exportExcel()
    {
        return Excel::download(
            new BarangMasukExport,
            'barang-masuk.xlsx'
        );
    }

    #[OA\Get(
        path: '/api/barang-masuk/{barang_masuk}/print-surat-jalan',
        summary: 'Print surat jalan for barang masuk',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_masuk', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF surat jalan (application/pdf)'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function printSuratJalan(BarangMasuk $barangMasuk)
    {
        return SuratJalanExport::forBarangMasuk($barangMasuk)->stream();
    }

    #[OA\Post(
        path: '/api/barang-masuk/{barang_masuk}/approve',
        summary: 'Approve barang masuk',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_masuk', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang masuk approved', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang masuk berhasil disetujui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BarangMasuk'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid state transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function approve(BarangMasuk $barangMasuk)
    {
        if ($barangMasuk->status !== 'pending') {
            return $this->error('Hanya dokumen berstatus pending yang dapat disetujui', 422);
        }

        BasePolicy::denyIfSelfApprove(request()->user(), $barangMasuk);

        $stokService = app(StokService::class);

        $saldoAwal = [];
        foreach ($barangMasuk->details as $detail) {
            $saldoAwal[(int) $detail->barang_id] = $stokService->hitungSaldoStok($detail->barang_id, $barangMasuk->gudang_id);
        }

        DB::transaction(function () use ($barangMasuk, $stokService, $saldoAwal) {
            $barangMasuk->update([
                'status' => 'approved',
                'approved_by' => request()->user()->id,
                'approved_at' => now(),
            ]);

            $stokService->catatBarangMasuk($barangMasuk, $saldoAwal, request()->user()->id);
        });

        app(NotifikasiService::class)->send(
            $barangMasuk->created_by,
            'Barang masuk disetujui',
            "Barang masuk {$barangMasuk->no_referensi} telah disetujui.",
            'success',
            'medium',
            "/barang-masuk/{$barangMasuk->id}"
        );

        return $this->success($barangMasuk->load(['gudang', 'supplier', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']), 'Barang masuk berhasil disetujui');
    }

    #[OA\Post(
        path: '/api/barang-masuk/{barang_masuk}/reject',
        summary: 'Reject barang masuk',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_masuk', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'keterangan', type: 'string', nullable: true, description: 'Alasan penolakan'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Barang masuk rejected', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang masuk berhasil ditolak'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BarangMasuk'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid state transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function reject(Request $request, BarangMasuk $barangMasuk)
    {
        BasePolicy::denyIfSelfApprove(request()->user(), $barangMasuk);
        if ($barangMasuk->status !== 'pending') {
            return $this->error('Hanya dokumen berstatus pending yang dapat ditolak', 422);
        }

        $barangMasuk->update([
            'status' => 'rejected',
            'keterangan' => $request->filled('keterangan') ? $request->keterangan : $barangMasuk->keterangan,
            'approved_by' => request()->user()->id,
            'approved_at' => now(),
        ]);

        app(NotifikasiService::class)->send(
            $barangMasuk->created_by,
            'Barang masuk ditolak',
            "Barang masuk {$barangMasuk->no_referensi} telah ditolak.",
            'error',
            'high',
            "/barang-masuk/{$barangMasuk->id}"
        );

        return $this->success($barangMasuk->load(['gudang', 'supplier', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']), 'Barang masuk berhasil ditolak');
    }

    #[OA\Get(
        path: '/api/barang-masuk',
        summary: 'List all barang masuk',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'supplier_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of barang masuk', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar barang masuk berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BarangMasuk')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = BarangMasuk::with(['gudang', 'supplier', 'createdBy', 'details.barang', 'details.lokasiRak']);

        // Auto-scope: non super-admin/admin hanya melihat gudang sendiri
        $user = $request->user();
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            if ($request->filled('gudang_id')) {
                $query->where('gudang_id', $request->gudang_id);
            }
        } elseif ($user->gudang_id) {
            $query->where('gudang_id', $user->gudang_id);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar barang masuk berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/barang-masuk',
        summary: 'Create barang masuk',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBarangMasukRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Barang masuk created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang masuk berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BarangMasuk'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreBarangMasukRequest $request)
    {
        $data = $request->validated();
        BasePolicy::denyCrossGudangWrite($request->user(), $data['gudang_id'] ?? null);
        $details = $data['details'] ?? [];
        unset($data['details']);

        $barangMasuk = DB::transaction(function () use ($data, $details, $request) {
            $data['created_by'] = $request->user()->id;
            $barangMasuk = BarangMasuk::create($data);

            foreach ($details as $detail) {
                $barangMasuk->details()->create($this->prepareDetail($detail));
            }

            return $barangMasuk;
        });

        app(NotifikasiService::class)->sendToApprovers(
            'barang-masuk-approve',
            'Dokumen barang masuk baru',
            "Barang masuk {$barangMasuk->no_referensi} menunggu persetujuan Anda.",
            'info',
            $request->user()->id,
            "/barang-masuk/{$barangMasuk->id}"
        );

        return $this->success($barangMasuk->load(['gudang', 'supplier', 'createdBy', 'details.barang', 'details.lokasiRak']), 'Barang masuk berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/barang-masuk/{barang_masuk}',
        summary: 'Get barang masuk by ID',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_masuk', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang masuk detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail barang masuk berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BarangMasuk'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(BarangMasuk $barangMasuk)
    {
        return $this->success($barangMasuk->load(['gudang', 'supplier', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']), 'Detail barang masuk berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/barang-masuk/{barang_masuk}',
        summary: 'Update barang masuk',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_masuk', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBarangMasukRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Barang masuk updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang masuk berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BarangMasuk'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateBarangMasukRequest $request, BarangMasuk $barangMasuk)
    {
        $data = $request->validated();
        BasePolicy::denyCrossGudangWrite($request->user(), $data['gudang_id'] ?? $barangMasuk->gudang_id);
        $details = $data['details'] ?? null;
        unset($data['details']);

        DB::transaction(function () use ($data, $details, $barangMasuk) {
            $barangMasuk->update($data);

            if ($details !== null) {
                $barangMasuk->details()->delete();
                foreach ($details as $detail) {
                    $barangMasuk->details()->create($this->prepareDetail($detail));
                }
            }
        });

        return $this->success($barangMasuk->load(['gudang', 'supplier', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']), 'Barang masuk berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/barang-masuk/{barang_masuk}',
        summary: 'Delete barang masuk',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_masuk', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang masuk deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang masuk berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(BarangMasuk $barangMasuk)
    {
        if ($barangMasuk->status === 'approved') {
            return $this->error('Dokumen yang sudah disetujui tidak dapat dihapus', 422);
        }

        $barangMasuk->delete();

        return $this->success(null, 'Barang masuk berhasil dihapus');
    }

    private function prepareDetail(array $detail): array
    {
        $hargaSatuan = (float) ($detail['harga_satuan'] ?? 0);
        $diskon = (float) ($detail['diskon'] ?? 0);
        $pajak = (float) ($detail['pajak'] ?? 0);
        $qty = (float) $detail['qty'];

        return [
            'barang_id' => $detail['barang_id'],
            'lokasi_rak_id' => $detail['lokasi_rak_id'] ?? null,
            'qty' => $qty,
            'harga_satuan' => $hargaSatuan,
            'diskon' => $diskon,
            'pajak' => $pajak,
            'subtotal' => round(($qty * $hargaSatuan) - $diskon + $pajak, 2),
        ];
    }
}
