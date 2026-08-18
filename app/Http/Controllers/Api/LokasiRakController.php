<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLokasiRakRequest;
use App\Http\Requests\UpdateLokasiRakRequest;
use App\Models\LokasiRak;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Lokasi Rak')]
class LokasiRakController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:lokasi-rak-list|lokasi-rak-create|lokasi-rak-edit|lokasi-rak-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:lokasi-rak-create', ['only' => ['store']]);
        $this->middleware('permission:lokasi-rak-edit', ['only' => ['update']]);
        $this->middleware('permission:lokasi-rak-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/lokasi-rak',
        summary: 'List all lokasi rak',
        tags: ['Lokasi Rak'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['aktif', 'nonaktif', 'penuh'])),
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Search by kode_rak or zona', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of lokasi rak', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar lokasi rak berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LokasiRak')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = LokasiRak::with('gudang');

        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('kode_rak', 'like', "%{$request->q}%")
                    ->orWhere('zona', 'like', "%{$request->q}%");
            });
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar lokasi rak berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/lokasi-rak',
        summary: 'Create lokasi rak',
        tags: ['Lokasi Rak'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreLokasiRakRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Lokasi rak created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Lokasi rak berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/LokasiRak'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreLokasiRakRequest $request)
    {
        $lokasiRak = LokasiRak::create($request->validated());

        return $this->success($lokasiRak->load('gudang'), 'Lokasi rak berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/lokasi-rak/{lokasi_rak}',
        summary: 'Get lokasi rak by ID',
        tags: ['Lokasi Rak'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'lokasi_rak', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lokasi rak detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail lokasi rak berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/LokasiRak'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(LokasiRak $lokasiRak)
    {
        return $this->success($lokasiRak->load('gudang'), 'Detail lokasi rak berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/lokasi-rak/{lokasi_rak}',
        summary: 'Update lokasi rak',
        tags: ['Lokasi Rak'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'lokasi_rak', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreLokasiRakRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Lokasi rak updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Lokasi rak berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/LokasiRak'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateLokasiRakRequest $request, LokasiRak $lokasiRak)
    {
        $lokasiRak->update($request->validated());

        return $this->success($lokasiRak->load('gudang'), 'Lokasi rak berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/lokasi-rak/{lokasi_rak}',
        summary: 'Delete lokasi rak',
        tags: ['Lokasi Rak'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'lokasi_rak', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lokasi rak deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Lokasi rak berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(LokasiRak $lokasiRak)
    {
        try {
            $lokasiRak->forceDelete();
        } catch (\Illuminate\Database\QueryException) {
            return $this->error('Lokasi rak tidak dapat dihapus karena masih digunakan pada data lain', 409);
        }

        return $this->success(null, 'Lokasi rak berhasil dihapus');
    }
}
