<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Http\Requests\StoreBarangRequest;
use App\Http\Requests\UpdateBarangRequest;
use Illuminate\Http\Request;

#[OA\Tag(name: 'Barang')]
class BarangController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:barang-list|barang-create|barang-edit|barang-delete', ['only' => ['index','show']]);
        $this->middleware('permission:barang-create', ['only' => ['store']]);
        $this->middleware('permission:barang-edit', ['only' => ['update']]);
        $this->middleware('permission:barang-delete', ['only' => ['destroy']]);
        $this->middleware('permission:barang-export', ['only' => ['exportExcel']]);
    }

    #[OA\Get(
        path: '/api/barang/export/excel',
        summary: 'Export barang to Excel',
        tags: ['Barang'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Excel file download'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function exportExcel()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\BarangExport,
            'barang.xlsx'
        );
    }

    #[OA\Get(
        path: '/api/barang',
        summary: 'List all barang',
        tags: ['Barang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'kategori_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of barang'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(Barang::with(['kategori', 'satuan'])->paginate($perPage));
    }

    #[OA\Post(
        path: '/api/barang',
        summary: 'Create barang',
        tags: ['Barang'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreBarangRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Barang created'),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreBarangRequest $request)
    {
        return response()->json(Barang::create($request->validated()), 201);
    }

    #[OA\Get(
        path: '/api/barang/{barang}',
        summary: 'Get barang by ID',
        tags: ['Barang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang detail'),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Barang $barang)
    {
        return response()->json($barang->load(['kategori', 'satuan']));
    }

    #[OA\Put(
        path: '/api/barang/{barang}',
        summary: 'Update barang',
        tags: ['Barang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreBarangRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Barang updated'),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateBarangRequest $request, Barang $barang)
    {
        $barang->update($request->validated());
        return response()->json($barang->load(['kategori', 'satuan']));
    }

    #[OA\Delete(
        path: '/api/barang/{barang}',
        summary: 'Delete barang',
        tags: ['Barang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Barang deleted'),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Barang $barang)
    {
        $barang->delete();
        return response()->json(null, 204);
    }
}