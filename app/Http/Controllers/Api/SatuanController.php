<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSatuanRequest;
use App\Http\Requests\UpdateSatuanRequest;
use App\Models\Satuan;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Satuan')]
class SatuanController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:satuan-list|satuan-create|satuan-edit|satuan-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:satuan-create', ['only' => ['store']]);
        $this->middleware('permission:satuan-edit', ['only' => ['update']]);
        $this->middleware('permission:satuan-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/satuan',
        summary: 'List all satuan',
        tags: ['Satuan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Search by nama or singkatan', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of satuan', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar satuan berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Satuan')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = Satuan::query();

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->q}%")
                    ->orWhere('singkatan', 'like', "%{$request->q}%");
            });
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar satuan berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/satuan',
        summary: 'Create satuan',
        tags: ['Satuan'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreSatuanRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Satuan created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Satuan berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Satuan'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreSatuanRequest $request)
    {
        $satuan = Satuan::create($request->validated());

        return $this->success($satuan, 'Satuan berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/satuan/{satuan}',
        summary: 'Get satuan by ID',
        tags: ['Satuan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'satuan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Satuan detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail satuan berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Satuan'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Satuan $satuan)
    {
        return $this->success($satuan, 'Detail satuan berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/satuan/{satuan}',
        summary: 'Update satuan',
        tags: ['Satuan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'satuan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreSatuanRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Satuan updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Satuan berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Satuan'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateSatuanRequest $request, Satuan $satuan)
    {
        $satuan->update($request->validated());

        return $this->success($satuan, 'Satuan berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/satuan/{satuan}',
        summary: 'Delete satuan',
        tags: ['Satuan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'satuan', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Satuan deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Satuan berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Satuan $satuan)
    {
        $satuan->delete();

        return $this->success(null, 'Satuan berhasil dihapus');
    }
}
