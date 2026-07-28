<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\BarangKeluar;
use App\Http\Requests\StoreBarangKeluarRequest;
use App\Http\Requests\UpdateBarangKeluarRequest;
use Illuminate\Http\Request;

#[OA\Tag(name: 'Barang Keluar')]
class BarangKeluarController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:barang-keluar-list|barang-keluar-create|barang-keluar-edit|barang-keluar-delete', ['only' => ['index','show']]);
        $this->middleware('permission:barang-keluar-create', ['only' => ['store']]);
        $this->middleware('permission:barang-keluar-edit', ['only' => ['update']]);
        $this->middleware('permission:barang-keluar-delete', ['only' => ['destroy']]);
        $this->middleware('permission:barang-keluar-export', ['only' => ['exportExcel']]);
        $this->middleware('permission:barang-keluar-print', ['only' => ['printSuratJalan']]);
    }

    #[OA\Get(
        path: '/api/barang-keluar/export/excel',
        summary: 'Export barang keluar to Excel',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Excel file download'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function exportExcel()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\BarangKeluarExport,
            'barang-keluar.xlsx'
        );
    }

    #[OA\Get(
        path: '/api/barang-keluar/{barang_keluar}/print-surat-jalan',
        summary: 'Print surat jalan for barang keluar',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_keluar', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF surat jalan'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function printSuratJalan(BarangKeluar $barangKeluar)
    {
        return \App\Exports\SuratJalanExport::forBarangKeluar($barangKeluar)->stream();
    }

    #[OA\Get(
        path: '/api/barang-keluar',
        summary: 'List all barang keluar',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of barang keluar'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(BarangKeluar::with(['gudang', 'customer', 'createdBy', 'details.barang', 'details.lokasiRak'])->paginate($perPage));
    }

    #[OA\Post(
        path: '/api/barang-keluar',
        summary: 'Create barang keluar',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBarangKeluarRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Barang keluar created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreBarangKeluarRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        return response()->json(BarangKeluar::create($data), 201);
    }

    #[OA\Get(
        path: '/api/barang-keluar/{barang_keluar}',
        summary: 'Get barang keluar by ID',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_keluar', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Barang keluar detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(BarangKeluar $barangKeluar)
    {
        return response()->json($barangKeluar->load(['gudang', 'customer', 'createdBy', 'approvedBy', 'deliveredBy', 'details.barang', 'details.lokasiRak']));
    }

    #[OA\Put(
        path: '/api/barang-keluar/{barang_keluar}',
        summary: 'Update barang keluar',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_keluar', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreBarangKeluarRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Barang keluar updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateBarangKeluarRequest $request, BarangKeluar $barangKeluar)
    {
        $barangKeluar->update($request->validated());
        return response()->json($barangKeluar->load(['gudang', 'customer', 'createdBy', 'approvedBy', 'deliveredBy', 'details.barang', 'details.lokasiRak']));
    }

    #[OA\Delete(
        path: '/api/barang-keluar/{barang_keluar}',
        summary: 'Delete barang keluar',
        tags: ['Barang Keluar'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_keluar', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Barang keluar deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(BarangKeluar $barangKeluar)
    {
        $barangKeluar->delete();
        return response()->json(null, 204);
    }
}