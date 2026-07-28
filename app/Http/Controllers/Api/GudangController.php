<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Http\Requests\StoreGudangRequest;
use App\Http\Requests\UpdateGudangRequest;
use Illuminate\Http\Request;

#[OA\Tag(name: 'Gudang')]
class GudangController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:gudang-list|gudang-create|gudang-edit|gudang-delete', ['only' => ['index','show']]);
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
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of gudang'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(Gudang::paginate($perPage));
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
            new OA\Response(response: 201, description: 'Gudang created'),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreGudangRequest $request)
    {
        return response()->json(Gudang::create($request->validated()), 201);
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
            new OA\Response(response: 200, description: 'Gudang detail'),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Gudang $gudang)
    {
        return response()->json($gudang->load('lokasiRak'));
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
            new OA\Response(response: 200, description: 'Gudang updated'),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateGudangRequest $request, Gudang $gudang)
    {
        $gudang->update($request->validated());
        return response()->json($gudang->load('lokasiRak'));
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
            new OA\Response(response: 204, description: 'Gudang deleted'),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Gudang $gudang)
    {
        $gudang->delete();
        return response()->json(null, 204);
    }
}