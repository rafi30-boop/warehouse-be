<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStokOpnameRequest;
use App\Http\Requests\UpdateStokOpnameRequest;
use App\Models\StokOpname;
use App\Services\StokService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Stok Opname')]
class StokOpnameController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:stok-opname-list|stok-opname-create|stok-opname-edit|stok-opname-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:stok-opname-create', ['only' => ['store']]);
        $this->middleware('permission:stok-opname-edit', ['only' => ['update']]);
        $this->middleware('permission:stok-opname-delete', ['only' => ['destroy']]);
        $this->middleware('permission:stok-opname-start', ['only' => ['start']]);
        $this->middleware('permission:stok-opname-complete', ['only' => ['complete']]);
        $this->middleware('permission:stok-opname-cancel', ['only' => ['cancel']]);
    }

    #[OA\Post(
        path: '/api/stok-opname/{stok_opname}/start',
        summary: 'Start stok opname (draft to in_progress)',
        tags: ['Stok Opname'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'stok_opname', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stok opname started', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Stok opname dimulai'),
                new OA\Property(property: 'data', ref: '#/components/schemas/StokOpname'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid state transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function start(StokOpname $stokOpname)
    {
        if ($stokOpname->status !== 'draft') {
            return $this->error('Hanya dokumen berstatus draft yang dapat dimulai', 422);
        }

        $stokOpname->update(['status' => 'in_progress']);

        return $this->success($this->loadRelations($stokOpname), 'Stok opname dimulai');
    }

    #[OA\Post(
        path: '/api/stok-opname/{stok_opname}/complete',
        summary: 'Complete stok opname',
        tags: ['Stok Opname'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'stok_opname', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stok opname completed', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Stok opname selesai'),
                new OA\Property(property: 'data', ref: '#/components/schemas/StokOpname'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid state transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function complete(StokOpname $stokOpname)
    {
        if ($stokOpname->status !== 'in_progress') {
            return $this->error('Hanya dokumen berstatus in_progress yang dapat diselesaikan', 422);
        }

        $stokService = app(StokService::class);

        DB::transaction(function () use ($stokOpname, $stokService) {
            foreach ($stokOpname->details as $detail) {
                $stokSistem = $stokService->hitungSaldoStok($detail->barang_id, $stokOpname->gudang_id);
                $detail->update([
                    'stok_sistem' => $stokSistem,
                    'selisih' => (float) $detail->stok_fisik - (float) $stokSistem,
                ]);
            }

            $stokOpname->update([
                'status' => 'completed',
                'approved_by' => request()->user()->id,
                'approved_at' => now(),
            ]);

            $stokService->catatStokOpname($stokOpname, request()->user()->id);
        });

        return $this->success($this->loadRelations($stokOpname), 'Stok opname selesai');
    }

    #[OA\Post(
        path: '/api/stok-opname/{stok_opname}/cancel',
        summary: 'Cancel stok opname',
        tags: ['Stok Opname'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'stok_opname', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stok opname cancelled', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Stok opname dibatalkan'),
                new OA\Property(property: 'data', ref: '#/components/schemas/StokOpname'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid state transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function cancel(StokOpname $stokOpname)
    {
        if (! in_array($stokOpname->status, ['draft', 'in_progress'])) {
            return $this->error('Dokumen yang sudah selesai tidak dapat dibatalkan', 422);
        }

        $stokOpname->update(['status' => 'cancelled']);

        return $this->success($this->loadRelations($stokOpname), 'Stok opname dibatalkan');
    }

    #[OA\Get(
        path: '/api/stok-opname',
        summary: 'List all stok opname',
        tags: ['Stok Opname'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'in_progress', 'completed', 'cancelled'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of stok opname', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar stok opname berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StokOpname')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = StokOpname::with(['gudang', 'createdBy', 'details.barang', 'details.lokasiRak']);

        // Auto-scope: non super-admin/admin hanya melihat gudang sendiri
        $user = $request->user();
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            if ($request->filled('gudang_id')) {
                $query->where('gudang_id', $request->gudang_id);
            }
        } elseif ($user->gudang_id) {
            $query->where('gudang_id', $user->gudang_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar stok opname berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/stok-opname',
        summary: 'Create stok opname',
        tags: ['Stok Opname'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreStokOpnameRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Stok opname created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Stok opname berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/StokOpname'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreStokOpnameRequest $request)
    {
        $data = $request->validated();
        $details = $data['details'] ?? [];
        unset($data['details']);

        $stokOpname = DB::transaction(function () use ($data, $details, $request) {
            $data['created_by'] = $request->user()->id;
            $stokOpname = StokOpname::create($data);

            foreach ($details as $detail) {
                $stokOpname->details()->create($this->prepareDetail($detail, $stokOpname));
            }

            return $stokOpname;
        });

        return $this->success($this->loadRelations($stokOpname), 'Stok opname berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/stok-opname/{stok_opname}',
        summary: 'Get stok opname by ID',
        tags: ['Stok Opname'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'stok_opname', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stok opname detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail stok opname berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/StokOpname'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(StokOpname $stokOpname)
    {
        return $this->success($this->loadRelations($stokOpname), 'Detail stok opname berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/stok-opname/{stok_opname}',
        summary: 'Update stok opname',
        tags: ['Stok Opname'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'stok_opname', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreStokOpnameRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Stok opname updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Stok opname berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/StokOpname'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateStokOpnameRequest $request, StokOpname $stokOpname)
    {
        $data = $request->validated();
        $details = $data['details'] ?? null;
        unset($data['details']);

        DB::transaction(function () use ($data, $details, $stokOpname) {
            $stokOpname->update($data);

            if ($details !== null) {
                $stokOpname->details()->delete();
                foreach ($details as $detail) {
                    $stokOpname->details()->create($this->prepareDetail($detail, $stokOpname));
                }
            }
        });

        return $this->success($this->loadRelations($stokOpname), 'Stok opname berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/stok-opname/{stok_opname}',
        summary: 'Delete stok opname',
        tags: ['Stok Opname'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'stok_opname', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Stok opname deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Stok opname berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(StokOpname $stokOpname)
    {
        if ($stokOpname->status === 'completed') {
            return $this->error('Dokumen yang sudah selesai tidak dapat dihapus', 422);
        }

        $stokOpname->delete();

        return $this->success(null, 'Stok opname berhasil dihapus');
    }

    private function loadRelations(StokOpname $stokOpname): StokOpname
    {
        return $stokOpname->load(['gudang', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']);
    }

    private function prepareDetail(array $detail, StokOpname $stokOpname): array
    {
        $stokSistem = app(StokService::class)->hitungSaldoStok($detail['barang_id'], $stokOpname->gudang_id);
        $stokFisik = (float) $detail['stok_fisik'];

        return [
            'barang_id' => $detail['barang_id'],
            'lokasi_rak_id' => $detail['lokasi_rak_id'] ?? null,
            'stok_sistem' => $stokSistem,
            'stok_fisik' => $stokFisik,
            'selisih' => round($stokFisik - $stokSistem, 2),
            'keterangan' => $detail['keterangan'] ?? null,
        ];
    }
}
