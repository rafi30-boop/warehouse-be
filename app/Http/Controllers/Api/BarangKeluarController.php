<?php

namespace App\Http\Controllers\Api;

use App\Exports\BarangKeluarExport;
use App\Exports\SuratJalanExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBarangKeluarRequest;
use App\Http\Requests\UpdateBarangKeluarRequest;
use App\Models\BarangKeluar;
use App\Policies\BasePolicy;
use App\Services\NotifikasiService;
use App\Services\StokService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Barang Keluar')]
class BarangKeluarController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:barang-keluar-list|barang-keluar-create|barang-keluar-edit|barang-keluar-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:barang-keluar-create', ['only' => ['store']]);
        $this->middleware('permission:barang-keluar-edit', ['only' => ['update']]);
        $this->middleware('permission:barang-keluar-delete', ['only' => ['destroy']]);
        $this->middleware('permission:barang-keluar-export', ['only' => ['exportExcel']]);
        $this->middleware('permission:barang-keluar-print', ['only' => ['printSuratJalan']]);
        $this->middleware('permission:barang-keluar-approve', ['only' => ['approve', 'reject']]);
        $this->middleware('permission:barang-keluar-deliver', ['only' => ['deliver', 'partial']]);
    }

    #[OA\Get(
        path: '/api/barang-keluar/export/excel',
        summary: 'Export barang keluar to Excel',
        tags: ['Barang Keluar'],
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
            new BarangKeluarExport,
            'barang-keluar.xlsx'
        );
    }

    #[OA\Get(
        path: '/api/barang-keluar/{barang_keluar}/print-surat-jalan',
        summary: 'Print surat jalan for barang keluar',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_keluar', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF surat jalan (application/pdf)'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function printSuratJalan(BarangKeluar $barangKeluar)
    {
        return SuratJalanExport::forBarangKeluar($barangKeluar)->stream();
    }

    #[OA\Post(
        path: '/api/barang-keluar/{barang_keluar}/approve',
        summary: 'Approve barang keluar',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_keluar', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang keluar approved', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang keluar berhasil disetujui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BarangKeluar'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid state transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function approve(BarangKeluar $barangKeluar)
    {
        if ($barangKeluar->status !== 'pending') {
            return $this->error('Hanya dokumen berstatus pending yang dapat disetujui', 422);
        }

        BasePolicy::denyIfSelfApprove(request()->user(), $barangKeluar);

        $barangKeluar->update([
            'status' => 'approved',
            'approved_by' => request()->user()->id,
            'approved_at' => now(),
        ]);

        app(NotifikasiService::class)->send(
            $barangKeluar->created_by,
            'Barang keluar disetujui',
            "Barang keluar {$barangKeluar->no_referensi} telah disetujui dan siap dikirim.",
            'success',
            'medium',
            "/barang-keluar/{$barangKeluar->id}"
        );

        return $this->success($this->loadRelations($barangKeluar), 'Barang keluar berhasil disetujui');
    }

    #[OA\Post(
        path: '/api/barang-keluar/{barang_keluar}/reject',
        summary: 'Reject barang keluar',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_keluar', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'keterangan', type: 'string', nullable: true, description: 'Alasan penolakan'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Barang keluar rejected', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang keluar berhasil ditolak'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BarangKeluar'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid state transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function reject(Request $request, BarangKeluar $barangKeluar)
    {
        BasePolicy::denyIfSelfApprove(request()->user(), $barangKeluar);
        if ($barangKeluar->status !== 'pending') {
            return $this->error('Hanya dokumen berstatus pending yang dapat ditolak', 422);
        }

        $barangKeluar->update([
            'status' => 'rejected',
            'keterangan' => $request->filled('keterangan') ? $request->keterangan : $barangKeluar->keterangan,
            'approved_by' => request()->user()->id,
            'approved_at' => now(),
        ]);

        app(NotifikasiService::class)->send(
            $barangKeluar->created_by,
            'Barang keluar ditolak',
            "Barang keluar {$barangKeluar->no_referensi} telah ditolak.",
            'error',
            'high',
            "/barang-keluar/{$barangKeluar->id}"
        );

        return $this->success($this->loadRelations($barangKeluar), 'Barang keluar berhasil ditolak');
    }

    #[OA\Post(
        path: '/api/barang-keluar/{barang_keluar}/deliver',
        summary: 'Mark barang keluar as delivered',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_keluar', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang keluar delivered', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang keluar berhasil dikirim'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BarangKeluar'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid state transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function deliver(BarangKeluar $barangKeluar)
    {
        if ($barangKeluar->status !== 'approved') {
            return $this->error('Hanya dokumen berstatus approved yang dapat dikirim', 422);
        }

        $stokService = app(StokService::class);
        $errors = $stokService->validasiStokDetail($barangKeluar->details->toArray(), $barangKeluar->gudang_id);

        if (! empty($errors)) {
            return $this->error('Stok tidak mencukupi untuk pengiriman', 422, $errors);
        }

        $saldoAwal = [];
        foreach ($barangKeluar->details as $detail) {
            $saldoAwal[(int) $detail->barang_id] = $stokService->hitungSaldoStok($detail->barang_id, $barangKeluar->gudang_id);
        }

        DB::transaction(function () use ($barangKeluar, $stokService, $saldoAwal) {
            $barangKeluar->update([
                'status' => 'delivered',
                'delivered_by' => request()->user()->id,
                'delivered_at' => now(),
            ]);

            $stokService->catatBarangKeluar($barangKeluar, $saldoAwal, request()->user()->id);
        });

        app(NotifikasiService::class)->send(
            $barangKeluar->created_by,
            'Barang keluar dikirim',
            "Barang keluar {$barangKeluar->no_referensi} telah dikirim.",
            'success',
            'medium',
            "/barang-keluar/{$barangKeluar->id}"
        );

        return $this->success($this->loadRelations($barangKeluar), 'Barang keluar berhasil dikirim');
    }

    #[OA\Post(
        path: '/api/barang-keluar/{barang_keluar}/partial',
        summary: 'Mark barang keluar as partial delivery',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_keluar', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang keluar partial', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang keluar ditandai pengiriman sebagian'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BarangKeluar'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid state transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function partial(BarangKeluar $barangKeluar)
    {
        if (! in_array($barangKeluar->status, ['approved', 'delivered'])) {
            return $this->error('Dokumen harus berstatus approved untuk pengiriman sebagian', 422);
        }

        $barangKeluar->update(['status' => 'partial']);

        return $this->success($this->loadRelations($barangKeluar), 'Barang keluar ditandai pengiriman sebagian');
    }

    #[OA\Get(
        path: '/api/barang-keluar',
        summary: 'List all barang keluar',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'customer_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected', 'delivered', 'partial'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of barang keluar', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar barang keluar berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BarangKeluar')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = BarangKeluar::with(['gudang', 'customer', 'createdBy', 'details.barang', 'details.lokasiRak']);

        // Auto-scope: non super-admin/admin hanya melihat gudang sendiri
        $user = $request->user();
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            if ($request->filled('gudang_id')) {
                $query->where('gudang_id', $request->gudang_id);
            }
        } elseif ($user->gudang_id) {
            $query->where('gudang_id', $user->gudang_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar barang keluar berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/barang-keluar',
        summary: 'Create barang keluar',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBarangKeluarRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Barang keluar created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang keluar berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BarangKeluar'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreBarangKeluarRequest $request)
    {
        $data = $request->validated();
        BasePolicy::denyCrossGudangWrite($request->user(), $data['gudang_id'] ?? null);
        $details = $data['details'] ?? [];
        unset($data['details']);

        $stokService = app(StokService::class);
        $stokErrors = $stokService->validasiStokDetail($details, $data['gudang_id'] ?? null);

        if (! empty($stokErrors)) {
            return $this->error('Stok tidak mencukupi', 422, $stokErrors);
        }

        $barangKeluar = DB::transaction(function () use ($data, $details, $request) {
            $data['created_by'] = $request->user()->id;
            $barangKeluar = BarangKeluar::create($data);

            foreach ($details as $detail) {
                $barangKeluar->details()->create($this->prepareDetail($detail));
            }

            return $barangKeluar;
        });

        app(NotifikasiService::class)->sendToApprovers(
            'barang-keluar-approve',
            'Dokumen barang keluar baru',
            "Barang keluar {$barangKeluar->no_referensi} menunggu persetujuan Anda.",
            'info',
            $request->user()->id,
            "/barang-keluar/{$barangKeluar->id}"
        );

        return $this->success($this->loadRelations($barangKeluar), 'Barang keluar berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/barang-keluar/{barang_keluar}',
        summary: 'Get barang keluar by ID',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_keluar', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang keluar detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail barang keluar berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BarangKeluar'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(BarangKeluar $barangKeluar)
    {
        return $this->success($this->loadRelations($barangKeluar), 'Detail barang keluar berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/barang-keluar/{barang_keluar}',
        summary: 'Update barang keluar',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_keluar', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBarangKeluarRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Barang keluar updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang keluar berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BarangKeluar'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateBarangKeluarRequest $request, BarangKeluar $barangKeluar)
    {
        $data = $request->validated();
        BasePolicy::denyCrossGudangWrite($request->user(), $data['gudang_id'] ?? $barangKeluar->gudang_id);
        $details = $data['details'] ?? null;
        unset($data['details']);

        DB::transaction(function () use ($data, $details, $barangKeluar) {
            $barangKeluar->update($data);

            if ($details !== null) {
                $barangKeluar->details()->delete();
                foreach ($details as $detail) {
                    $barangKeluar->details()->create($this->prepareDetail($detail));
                }
            }
        });

        return $this->success($this->loadRelations($barangKeluar), 'Barang keluar berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/barang-keluar/{barang_keluar}',
        summary: 'Delete barang keluar',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_keluar', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang keluar deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang keluar berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(BarangKeluar $barangKeluar)
    {
        if (in_array($barangKeluar->status, ['approved', 'delivered', 'partial'])) {
            return $this->error('Dokumen yang sudah diproses tidak dapat dihapus', 422);
        }

        $barangKeluar->delete();

        return $this->success(null, 'Barang keluar berhasil dihapus');
    }

    private function loadRelations(BarangKeluar $barangKeluar): BarangKeluar
    {
        return $barangKeluar->load(['gudang', 'customer', 'createdBy', 'approvedBy', 'deliveredBy', 'details.barang', 'details.lokasiRak']);
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
