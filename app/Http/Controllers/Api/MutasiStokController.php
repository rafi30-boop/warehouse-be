<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMutasiStokRequest;
use App\Http\Requests\UpdateMutasiStokRequest;
use App\Models\MutasiStok;
use App\Policies\BasePolicy;
use App\Services\NotifikasiService;
use App\Services\StokService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Mutasi Stok')]
class MutasiStokController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:mutasi-stok-list|mutasi-stok-create|mutasi-stok-edit|mutasi-stok-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:mutasi-stok-create', ['only' => ['store']]);
        $this->middleware('permission:mutasi-stok-edit', ['only' => ['update']]);
        $this->middleware('permission:mutasi-stok-delete', ['only' => ['destroy']]);
        $this->middleware('permission:mutasi-stok-approve', ['only' => ['approve', 'reject', 'complete']]);
    }

    #[OA\Post(
        path: '/api/mutasi-stok/{mutasi_stok}/approve',
        summary: 'Approve mutasi stok',
        tags: ['Mutasi Stok'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'mutasi_stok', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Mutasi stok approved', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Mutasi stok berhasil disetujui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/MutasiStok'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid state transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function approve(MutasiStok $mutasiStok)
    {
        if ($mutasiStok->status !== 'pending') {
            return $this->error('Hanya dokumen berstatus pending yang dapat disetujui', 422);
        }

        BasePolicy::denyIfSelfApprove(request()->user(), $mutasiStok);

        $mutasiStok->update([
            'status' => 'approved',
            'approved_by' => request()->user()->id,
            'approved_at' => now(),
        ]);

        app(NotifikasiService::class)->send(
            $mutasiStok->created_by,
            'Mutasi stok disetujui',
            "Mutasi stok {$mutasiStok->no_referensi} telah disetujui.",
            'success',
            'medium',
            "/mutasi-stok/{$mutasiStok->id}"
        );

        return $this->success($this->loadRelations($mutasiStok), 'Mutasi stok berhasil disetujui');
    }

    #[OA\Post(
        path: '/api/mutasi-stok/{mutasi_stok}/reject',
        summary: 'Reject mutasi stok',
        tags: ['Mutasi Stok'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'mutasi_stok', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'keterangan', type: 'string', nullable: true, description: 'Alasan penolakan'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Mutasi stok rejected', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Mutasi stok berhasil ditolak'),
                new OA\Property(property: 'data', ref: '#/components/schemas/MutasiStok'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid state transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function reject(Request $request, MutasiStok $mutasiStok)
    {
        BasePolicy::denyIfSelfApprove(request()->user(), $mutasiStok);
        if ($mutasiStok->status !== 'pending') {
            return $this->error('Hanya dokumen berstatus pending yang dapat ditolak', 422);
        }

        $mutasiStok->update([
            'status' => 'rejected',
            'keterangan' => $request->filled('keterangan') ? $request->keterangan : $mutasiStok->keterangan,
            'approved_by' => request()->user()->id,
            'approved_at' => now(),
        ]);

        app(NotifikasiService::class)->send(
            $mutasiStok->created_by,
            'Mutasi stok ditolak',
            "Mutasi stok {$mutasiStok->no_referensi} telah ditolak.",
            'error',
            'high',
            "/mutasi-stok/{$mutasiStok->id}"
        );

        return $this->success($this->loadRelations($mutasiStok), 'Mutasi stok berhasil ditolak');
    }

    #[OA\Post(
        path: '/api/mutasi-stok/{mutasi_stok}/complete',
        summary: 'Complete mutasi stok (approved to completed, memindahkan stok)',
        tags: ['Mutasi Stok'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'mutasi_stok', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Mutasi stok completed', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Mutasi stok berhasil diselesaikan'),
                new OA\Property(property: 'data', ref: '#/components/schemas/MutasiStok'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid state transition', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function complete(MutasiStok $mutasiStok)
    {
        if ($mutasiStok->status !== 'approved') {
            return $this->error('Hanya dokumen berstatus approved yang dapat diselesaikan', 422);
        }

        BasePolicy::denyIfSelfApprove(request()->user(), $mutasiStok);

        $stokService = app(StokService::class);
        $errors = $stokService->validasiStokDetail([
            ['barang_id' => $mutasiStok->barang_id, 'qty' => $mutasiStok->qty],
        ], $mutasiStok->gudang_asal_id);

        if (! empty($errors)) {
            return $this->error('Stok tidak mencukupi untuk mutasi', 422, $errors);
        }

        DB::transaction(function () use ($mutasiStok) {
            $mutasiStok->update([
                'status' => 'completed',
                'approved_by' => request()->user()->id,
                'approved_at' => now(),
            ]);

            app(StokService::class)->catatMutasiStok($mutasiStok, request()->user()->id);
        });

        app(NotifikasiService::class)->send(
            $mutasiStok->created_by,
            'Mutasi stok selesai',
            "Mutasi stok {$mutasiStok->no_referensi} telah diselesaikan.",
            'success',
            'medium',
            "/mutasi-stok/{$mutasiStok->id}"
        );

        return $this->success($this->loadRelations($mutasiStok), 'Mutasi stok berhasil diselesaikan');
    }

    #[OA\Get(
        path: '/api/mutasi-stok',
        summary: 'List all mutasi stok',
        tags: ['Mutasi Stok'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'barang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', description: 'Filter where gudang is either asal OR tujuan (used by FE dropdown)')),
            new OA\Parameter(name: 'gudang_asal_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gudang_tujuan_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Search no_referensi, barang nama/sku, rute')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected', 'completed'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of mutasi stok', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar mutasi stok berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/MutasiStok')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = MutasiStok::with(['barang', 'gudangAsal', 'gudangTujuan', 'lokasiRakAsal', 'lokasiRakTujuan', 'createdBy']);

        if ($request->filled('barang_id')) {
            $query->where('barang_id', $request->barang_id);
        }

        // Auto-scope: non super-admin/admin hanya melihat gudang sendiri
        $user = $request->user();
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            if ($request->filled('gudang_id')) {
                $query->where(function ($q) use ($request) {
                    $q->where('gudang_asal_id', $request->gudang_id)
                      ->orWhere('gudang_tujuan_id', $request->gudang_id);
                });
            }
        } elseif ($user->gudang_id) {
            $query->where(function ($q) use ($user) {
                $q->where('gudang_asal_id', $user->gudang_id)
                  ->orWhere('gudang_tujuan_id', $user->gudang_id);
            });
        }

        if ($request->filled('gudang_asal_id')) {
            $query->where('gudang_asal_id', $request->gudang_asal_id);
        }

        if ($request->filled('gudang_tujuan_id')) {
            $query->where('gudang_tujuan_id', $request->gudang_tujuan_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('no_referensi', 'like', "%{$s}%")
                  ->orWhereHas('barang', function ($qb) use ($s) {
                      $qb->where('nama', 'like', "%{$s}%")
                         ->orWhere('sku', 'like', "%{$s}%");
                  })
                  ->orWhereHas('gudangAsal', function ($qb) use ($s) {
                      $qb->where('nama', 'like', "%{$s}%");
                  })
                  ->orWhereHas('gudangTujuan', function ($qb) use ($s) {
                      $qb->where('nama', 'like', "%{$s}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar mutasi stok berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/mutasi-stok',
        summary: 'Create mutasi stok',
        tags: ['Mutasi Stok'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreMutasiStokRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Mutasi stok created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Mutasi stok berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/MutasiStok'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreMutasiStokRequest $request)
    {
        $data = $request->validated();
        BasePolicy::denyCrossGudangWrite($request->user(), $data['gudang_asal_id'] ?? null);
        $data['created_by'] = $request->user()->id;

        return $this->success(MutasiStok::create($data), 'Mutasi stok berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/mutasi-stok/{mutasi_stok}',
        summary: 'Get mutasi stok by ID',
        tags: ['Mutasi Stok'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'mutasi_stok', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Mutasi stok detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail mutasi stok berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/MutasiStok'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(MutasiStok $mutasiStok)
    {
        return $this->success($this->loadRelations($mutasiStok), 'Detail mutasi stok berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/mutasi-stok/{mutasi_stok}',
        summary: 'Update mutasi stok',
        tags: ['Mutasi Stok'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'mutasi_stok', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreMutasiStokRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Mutasi stok updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Mutasi stok berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/MutasiStok'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateMutasiStokRequest $request, MutasiStok $mutasiStok)
    {
        $data = $request->validated();
        BasePolicy::denyCrossGudangWrite($request->user(), $data['gudang_asal_id'] ?? $mutasiStok->gudang_asal_id);
        $menjadiCompleted = ($data['status'] ?? null) === 'completed' && $mutasiStok->status !== 'completed';

        DB::transaction(function () use ($data, $mutasiStok) {
            $mutasiStok->update($data);
        });

        if ($menjadiCompleted) {
            app(StokService::class)->catatMutasiStok($mutasiStok->fresh(), $request->user()->id);
        }

        return $this->success($this->loadRelations($mutasiStok), 'Mutasi stok berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/mutasi-stok/{mutasi_stok}',
        summary: 'Delete mutasi stok',
        tags: ['Mutasi Stok'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'mutasi_stok', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Mutasi stok deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Mutasi stok berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(MutasiStok $mutasiStok)
    {
        if ($mutasiStok->status === 'completed') {
            return $this->error('Dokumen yang sudah selesai tidak dapat dihapus', 422);
        }

        $mutasiStok->delete();

        return $this->success(null, 'Mutasi stok berhasil dihapus');
    }

    private function loadRelations(MutasiStok $mutasiStok): MutasiStok
    {
        return $mutasiStok->load(['barang', 'gudangAsal', 'gudangTujuan', 'lokasiRakAsal', 'lokasiRakTujuan', 'createdBy', 'approvedBy']);
    }
}
