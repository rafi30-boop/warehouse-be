<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBatchBarangRequest;
use App\Http\Requests\UpdateBatchBarangRequest;
use App\Models\BatchBarang;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Batch Barang')]
class BatchBarangController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:batch-barang-list|batch-barang-create|batch-barang-edit|batch-barang-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:batch-barang-create', ['only' => ['store']]);
        $this->middleware('permission:batch-barang-edit', ['only' => ['update']]);
        $this->middleware('permission:batch-barang-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/batch-barang',
        summary: 'List all batch barang',
        tags: ['Batch Barang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'barang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Search by batch_number', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'expired', in: 'query', required: false, description: 'Filter batch belum expired (1) atau sudah expired (2)', schema: new OA\Schema(type: 'integer', enum: [1, 2])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of batch barang', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar batch barang berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BatchBarang')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = BatchBarang::with('barang');

        if ($request->filled('barang_id')) {
            $query->where('barang_id', $request->barang_id);
        }

        if ($request->filled('q')) {
            $query->where('batch_number', 'like', "%{$request->q}%");
        }

        if ($request->filled('expired')) {
            if ((int) $request->expired === 1) {
                $query->where(fn ($q) => $q->whereNull('expired_date')->orWhereDate('expired_date', '>=', now()->toDateString()));
            } else {
                $query->whereNotNull('expired_date')->whereDate('expired_date', '<', now()->toDateString());
            }
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar batch barang berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/batch-barang',
        summary: 'Create batch barang',
        tags: ['Batch Barang'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBatchBarangRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Batch barang created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Batch barang berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BatchBarang'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreBatchBarangRequest $request)
    {
        $batch = BatchBarang::create($request->validated());

        return $this->success($batch->load('barang'), 'Batch barang berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/batch-barang/{batch_barang}',
        summary: 'Get batch barang by ID',
        tags: ['Batch Barang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'batch_barang', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Batch barang detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail batch barang berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BatchBarang'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(BatchBarang $batchBarang)
    {
        return $this->success($batchBarang->load('barang'), 'Detail batch barang berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/batch-barang/{batch_barang}',
        summary: 'Update batch barang',
        tags: ['Batch Barang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'batch_barang', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBatchBarangRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Batch barang updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Batch barang berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BatchBarang'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateBatchBarangRequest $request, BatchBarang $batchBarang)
    {
        $batchBarang->update($request->validated());

        return $this->success($batchBarang->load('barang'), 'Batch barang berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/batch-barang/{batch_barang}',
        summary: 'Delete batch barang',
        tags: ['Batch Barang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'batch_barang', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Batch barang deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Batch barang berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(BatchBarang $batchBarang)
    {
        $batchBarang->delete();

        return $this->success(null, 'Batch barang berhasil dihapus');
    }
}
