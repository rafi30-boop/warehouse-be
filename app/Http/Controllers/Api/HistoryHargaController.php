<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoryHarga;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'History Harga')]
class HistoryHargaController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:history-harga-list|history-harga-create|history-harga-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:history-harga-create', ['only' => ['store']]);
        $this->middleware('permission:history-harga-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/history-harga',
        summary: 'List all history harga',
        tags: ['History Harga'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'barang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal_efektif', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of history harga', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar history harga berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/HistoryHarga')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = HistoryHarga::with(['barang', 'createdBy']);

        if ($request->filled('barang_id')) {
            $query->where('barang_id', $request->barang_id);
        }

        if ($request->filled('tanggal_efektif')) {
            $query->whereDate('tanggal_efektif', $request->tanggal_efektif);
        }

        return $this->paginated($query->orderByDesc('tanggal_efektif')->paginate($perPage), message: 'Daftar history harga berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/history-harga',
        summary: 'Create history harga',
        tags: ['History Harga'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'barang_id', type: 'integer', example: 1),
            new OA\Property(property: 'harga_beli', type: 'number', example: 10000),
            new OA\Property(property: 'harga_jual', type: 'number', example: 15000),
            new OA\Property(property: 'tanggal_efektif', type: 'string', format: 'date', example: '2026-08-04'),
        ])),
        responses: [
            new OA\Response(response: 201, description: 'History harga created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'History harga berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/HistoryHarga'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'barang_id' => 'required|exists:barang,id',
            'harga_beli' => 'nullable|numeric|min:0',
            'harga_jual' => 'nullable|numeric|min:0',
            'tanggal_efektif' => 'required|date',
        ]);

        $validated['created_by'] = $request->user()->id;
        $history = HistoryHarga::create($validated);

        return $this->success($history->load(['barang', 'createdBy']), 'History harga berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/history-harga/{history_harga}',
        summary: 'Get history harga by ID',
        tags: ['History Harga'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'history_harga', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'History harga detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail history harga berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/HistoryHarga'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(HistoryHarga $historyHarga)
    {
        return $this->success($historyHarga->load(['barang', 'createdBy']), 'Detail history harga berhasil dimuat');
    }

    #[OA\Delete(
        path: '/api/history-harga/{history_harga}',
        summary: 'Delete history harga',
        tags: ['History Harga'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'history_harga', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'History harga deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'History harga berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(HistoryHarga $historyHarga)
    {
        $historyHarga->delete();

        return $this->success(null, 'History harga berhasil dihapus');
    }
}
