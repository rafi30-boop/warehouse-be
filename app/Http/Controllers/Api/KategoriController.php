<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKategoriRequest;
use App\Http\Requests\UpdateKategoriRequest;
use App\Models\Kategori;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Kategori')]
class KategoriController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:kategori-list|kategori-create|kategori-edit|kategori-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:kategori-create', ['only' => ['store']]);
        $this->middleware('permission:kategori-edit', ['only' => ['update']]);
        $this->middleware('permission:kategori-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/kategori',
        summary: 'List all kategori',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Search by nama')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of kategori', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar kategori berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Kategori')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = Kategori::with('parent');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('nama', 'like', "%{$s}%");
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar kategori berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/kategori',
        summary: 'Create kategori',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreKategoriRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Kategori created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Kategori berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Kategori'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreKategoriRequest $request)
    {
        return $this->success(Kategori::create($request->validated()), 'Kategori berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/kategori/{kategori}',
        summary: 'Get kategori by ID',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'kategori', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Kategori detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail kategori berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Kategori'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Kategori $kategori)
    {
        return $this->success($kategori->load(['parent', 'children']), 'Detail kategori berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/kategori/{kategori}',
        summary: 'Update kategori',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'kategori', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreKategoriRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Kategori updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Kategori berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Kategori'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateKategoriRequest $request, Kategori $kategori)
    {
        $kategori->update($request->validated());

        return $this->success($kategori->load(['parent', 'children']), 'Kategori berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/kategori/{kategori}',
        summary: 'Delete kategori',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'kategori', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Kategori deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Kategori berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Kategori $kategori)
    {
        $kategori->delete();

        return $this->success(null, 'Kategori berhasil dihapus');
    }
}
