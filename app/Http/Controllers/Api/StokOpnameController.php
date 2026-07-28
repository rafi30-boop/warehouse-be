<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\StokOpname;
use App\Http\Requests\StoreStokOpnameRequest;
use App\Http\Requests\UpdateStokOpnameRequest;
use Illuminate\Http\Request;

#[OA\Tag(name: 'Stok Opname')]
class StokOpnameController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:stok-opname-list|stok-opname-create|stok-opname-edit|stok-opname-delete', ['only' => ['index','show']]);
        $this->middleware('permission:stok-opname-create', ['only' => ['store']]);
        $this->middleware('permission:stok-opname-edit', ['only' => ['update']]);
        $this->middleware('permission:stok-opname-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/stok-opname',
        summary: 'List all stok opname',
        tags: ['Stok Opname'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of stok opname'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(StokOpname::with(['gudang', 'createdBy', 'details.barang', 'details.lokasiRak'])->paginate($perPage));
    }

    #[OA\Post(
        path: '/api/stok-opname',
        summary: 'Create stok opname',
        tags: ['Stok Opname'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreStokOpnameRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Stok opname created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreStokOpnameRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        return response()->json(StokOpname::create($data), 201);
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
            new OA\Response(response: 200, description: 'Stok opname detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(StokOpname $stokOpname)
    {
        return response()->json($stokOpname->load(['gudang', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']));
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
            new OA\Response(response: 200, description: 'Stok opname updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateStokOpnameRequest $request, StokOpname $stokOpname)
    {
        $stokOpname->update($request->validated());
        return response()->json($stokOpname->load(['gudang', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']));
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
            new OA\Response(response: 204, description: 'Stok opname deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(StokOpname $stokOpname)
    {
        $stokOpname->delete();
        return response()->json(null, 204);
    }
}