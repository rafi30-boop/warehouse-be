<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\BarangMasuk;
use App\Http\Requests\StoreBarangMasukRequest;
use App\Http\Requests\UpdateBarangMasukRequest;
use Illuminate\Http\Request;

#[OA\Tag(name: 'Barang Masuk')]
class BarangMasukController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:barang-masuk-list|barang-masuk-create|barang-masuk-edit|barang-masuk-delete', ['only' => ['index','show']]);
        $this->middleware('permission:barang-masuk-create', ['only' => ['store']]);
        $this->middleware('permission:barang-masuk-edit', ['only' => ['update']]);
        $this->middleware('permission:barang-masuk-delete', ['only' => ['destroy']]);
        $this->middleware('permission:barang-masuk-export', ['only' => ['exportExcel']]);
        $this->middleware('permission:barang-masuk-print', ['only' => ['printSuratJalan']]);
    }

    #[OA\Get(
        path: '/api/barang-masuk/export/excel',
        summary: 'Export barang masuk to Excel',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Excel file download'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function exportExcel()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\BarangMasukExport,
            'barang-masuk.xlsx'
        );
    }

    #[OA\Get(
        path: '/api/barang-masuk/{barang_masuk}/print-surat-jalan',
        summary: 'Print surat jalan for barang masuk',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_masuk', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF surat jalan'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function printSuratJalan(BarangMasuk $barangMasuk)
    {
        return \App\Exports\SuratJalanExport::forBarangMasuk($barangMasuk)->stream();
    }

    #[OA\Get(
        path: '/api/barang-masuk',
        summary: 'List all barang masuk',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of barang masuk'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(BarangMasuk::with(['gudang', 'supplier', 'createdBy', 'details.barang', 'details.lokasiRak'])->paginate($perPage));
    }

    #[OA\Post(
        path: '/api/barang-masuk',
        summary: 'Create barang masuk',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBarangMasukRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Barang masuk created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreBarangMasukRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        return response()->json(BarangMasuk::create($data), 201);
    }

    #[OA\Get(
        path: '/api/barang-masuk/{barang_masuk}',
        summary: 'Get barang masuk by ID',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_masuk', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang masuk detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(BarangMasuk $barangMasuk)
    {
        return response()->json($barangMasuk->load(['gudang', 'supplier', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']));
    }

    #[OA\Put(
        path: '/api/barang-masuk/{barang_masuk}',
        summary: 'Update barang masuk',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_masuk', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBarangMasukRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Barang masuk updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateBarangMasukRequest $request, BarangMasuk $barangMasuk)
    {
        $barangMasuk->update($request->validated());
        return response()->json($barangMasuk->load(['gudang', 'supplier', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']));
    }

    #[OA\Delete(
        path: '/api/barang-masuk/{barang_masuk}',
        summary: 'Delete barang masuk',
        tags: ['Barang Masuk'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_masuk', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Barang masuk deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(BarangMasuk $barangMasuk)
    {
        $barangMasuk->delete();
        return response()->json(null, 204);
    }
}