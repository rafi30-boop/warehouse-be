<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Http\Requests\StoreKategoriRequest;
use App\Http\Requests\UpdateKategoriRequest;
use Illuminate\Http\Request;

#[OA\Tag(name: 'Kategori')]
class KategoriController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:kategori-list|kategori-create|kategori-edit|kategori-delete', ['only' => ['index','show']]);
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
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of kategori'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(Kategori::with('parent')->paginate($perPage));
    }

    #[OA\Post(
        path: '/api/kategori',
        summary: 'Create kategori',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreKategoriRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Kategori created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreKategoriRequest $request)
    {
        return response()->json(Kategori::create($request->validated()), 201);
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
            new OA\Response(response: 200, description: 'Kategori detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Kategori $kategori)
    {
        return response()->json($kategori->load(['parent', 'children']));
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
            new OA\Response(response: 200, description: 'Kategori updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateKategoriRequest $request, Kategori $kategori)
    {
        $kategori->update($request->validated());
        return response()->json($kategori->load(['parent', 'children']));
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
            new OA\Response(response: 204, description: 'Kategori deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(Kategori $kategori)
    {
        $kategori->delete();
        return response()->json(null, 204);
    }
}