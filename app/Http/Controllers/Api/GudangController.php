<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGudangRequest;
use App\Http\Requests\UpdateGudangRequest;
use App\Models\Gudang;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Gudang')]
class GudangController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:gudang-list|gudang-create|gudang-edit|gudang-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:gudang-create', ['only' => ['store']]);
        $this->middleware('permission:gudang-edit', ['only' => ['update']]);
        $this->middleware('permission:gudang-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/gudang',
        summary: 'List all gudang',
        tags: ['Gudang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Search by nama or kode')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of gudang', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar gudang berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Gudang')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = Gudang::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', "%{$s}%")
                    ->orWhere('kode', 'like', "%{$s}%");
            });
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar gudang berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/gudang',
        summary: 'Create gudang',
        tags: ['Gudang'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreGudangRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Gudang created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Gudang berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Gudang'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreGudangRequest $request)
    {
        return $this->success(Gudang::create($request->validated()), 'Gudang berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/gudang/{gudang}',
        summary: 'Get gudang by ID',
        tags: ['Gudang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'gudang', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Gudang detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail gudang berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Gudang'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Gudang $gudang)
    {
        return $this->success($gudang->load('lokasiRak'), 'Detail gudang berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/gudang/{gudang}',
        summary: 'Update gudang',
        tags: ['Gudang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'gudang', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreGudangRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Gudang updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Gudang berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Gudang'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateGudangRequest $request, Gudang $gudang)
    {
        $gudang->update($request->validated());

        return $this->success($gudang->load('lokasiRak'), 'Gudang berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/gudang/{gudang}',
        summary: 'Delete gudang',
        tags: ['Gudang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'gudang', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Gudang deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Gudang berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Gudang $gudang)
    {
        try {
            $gudang->forceDelete();
        } catch (\Illuminate\Database\QueryException) {
            return $this->error('Gudang tidak dapat dihapus karena masih digunakan pada data lain', 409);
        }

        return $this->success(null, 'Gudang berhasil dihapus');
    }
}
