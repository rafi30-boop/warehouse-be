<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\MutasiStok;
use App\Http\Requests\StoreMutasiStokRequest;
use App\Http\Requests\UpdateMutasiStokRequest;
use Illuminate\Http\Request;

#[OA\Tag(name: 'Mutasi Stok')]
class MutasiStokController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:mutasi-stok-list|mutasi-stok-create|mutasi-stok-edit|mutasi-stok-delete', ['only' => ['index','show']]);
        $this->middleware('permission:mutasi-stok-create', ['only' => ['store']]);
        $this->middleware('permission:mutasi-stok-edit', ['only' => ['update']]);
        $this->middleware('permission:mutasi-stok-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/mutasi-stok',
        summary: 'List all mutasi stok',
        tags: ['Mutasi Stok'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of mutasi stok'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(MutasiStok::with(['barang', 'gudangAsal', 'gudangTujuan', 'lokasiRakAsal', 'lokasiRakTujuan', 'createdBy'])->paginate($perPage));
    }

    #[OA\Post(
        path: '/api/mutasi-stok',
        summary: 'Create mutasi stok',
        tags: ['Mutasi Stok'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreMutasiStokRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Mutasi stok created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreMutasiStokRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        return response()->json(MutasiStok::create($data), 201);
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
            new OA\Response(response: 200, description: 'Mutasi stok detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(MutasiStok $mutasiStok)
    {
        return response()->json($mutasiStok->load(['barang', 'gudangAsal', 'gudangTujuan', 'lokasiRakAsal', 'lokasiRakTujuan', 'createdBy', 'approvedBy']));
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
            new OA\Response(response: 200, description: 'Mutasi stok updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateMutasiStokRequest $request, MutasiStok $mutasiStok)
    {
        $mutasiStok->update($request->validated());
        return response()->json($mutasiStok->load(['barang', 'gudangAsal', 'gudangTujuan', 'lokasiRakAsal', 'lokasiRakTujuan', 'createdBy', 'approvedBy']));
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
            new OA\Response(response: 204, description: 'Mutasi stok deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(MutasiStok $mutasiStok)
    {
        $mutasiStok->delete();
        return response()->json(null, 204);
    }
}