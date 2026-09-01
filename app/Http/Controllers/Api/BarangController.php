<?php

namespace App\Http\Controllers\Api;

use App\Exports\BarangExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBarangRequest;
use App\Http\Requests\UpdateBarangRequest;
use App\Models\Barang;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Barang')]
class BarangController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:barang-list|barang-create|barang-edit|barang-delete', ['only' => ['index', 'show']]);
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
            new OA\Response(response: 200, description: 'Excel file download (application/vnd.openxmlformats-officedocument.spreadsheetml.sheet)'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function exportExcel()
    {
        return Excel::download(
            new BarangExport,
            'barang.xlsx'
        );
    }

    #[OA\Get(
        path: '/api/barang',
        summary: 'List all barang',
        tags: ['Barang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Search by nama or sku')),
            new OA\Parameter(name: 'kategori_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of barang', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar barang berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Barang')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
      public function index(Request $request)
      {
          $perPage = min(100, (int) $request->per_page ?: 15);
  
          $query = Barang::with(['kategori', 'satuan']);
  
          // Low stock filter
          if ($request->filled('filter_low_stock')) {
              // Filter items with min_stok > 0 (without checking current stock level)
              // This shows items that have minimum stock defined
              $query->whereNotNull('min_stok')
                    ->where('min_stok', '>', 0);
              
              return $this->collection($query->get(), message: 'Daftar barang dengan minimum stok');
          }
  
          if ($request->filled('search')) {
              $s = $request->search;
              $query->where(function ($q) use ($s) {
                  $q->where('nama', 'like', "%{$s}%")
                      ->orWhere('sku', 'like', "%{$s}%")
                      ->orWhere('barcode', 'like', "%{$s}%");
              });
          }
  
          if ($request->filled('kategori_id')) {
              $query->where('kategori_id', $request->kategori_id);
          }
  
          return $this->paginated($query->paginate($perPage), message: 'Daftar barang berhasil dimuat');
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
            new OA\Response(response: 201, description: 'Barang created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Barang'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreBarangRequest $request)
    {
        return $this->success(Barang::create($request->validated()), 'Barang berhasil dibuat', 201);
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
            new OA\Response(response: 200, description: 'Barang detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail barang berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Barang'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Barang $barang)
    {
        return $this->success($barang->load(['kategori', 'satuan']), 'Detail barang berhasil dimuat');
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
            new OA\Response(response: 200, description: 'Barang updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Barang'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateBarangRequest $request, Barang $barang)
    {
        $barang->update($request->validated());

        return $this->success($barang->load(['kategori', 'satuan']), 'Barang berhasil diperbarui');
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
            new OA\Response(response: 200, description: 'Barang deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Barang berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Barang $barang)
    {
        try {
            $barang->forceDelete();
        } catch (\Illuminate\Database\QueryException) {
            return $this->error('Barang tidak dapat dihapus karena masih digunakan pada data transaksi', 409);
        }

        return $this->success(null, 'Barang berhasil dihapus');
    }
}
