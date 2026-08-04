<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KartuStok;
use App\Services\StokService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Kartu Stok')]
class KartuStokController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:kartu-stok-list', ['only' => ['index', 'show', 'riwayat']]);
    }

    #[OA\Get(
        path: '/api/kartu-stok/riwayat',
        summary: 'Kartu stok riwayat transaksi per barang (dari dokumen approved/delivered/completed)',
        tags: ['Kartu Stok'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'barang_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Riwayat kartu stok', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Riwayat kartu stok berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function riwayat(Request $request)
    {
        $validated = $request->validate([
            'barang_id' => 'required|exists:barang,id',
            'gudang_id' => 'nullable|exists:gudang,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $rows = app(StokService::class)->riwayatKartuStok(
            (int) $validated['barang_id'],
            isset($validated['gudang_id']) ? (int) $validated['gudang_id'] : null,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        return $this->success($rows, 'Riwayat kartu stok berhasil dimuat');
    }

    #[OA\Get(
        path: '/api/kartu-stok',
        summary: 'List all kartu stok',
        tags: ['Kartu Stok'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'barang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'lokasi_rak_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tipe', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['in', 'out', 'mutasi_in', 'mutasi_out', 'opname'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of kartu stok', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar kartu stok berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/KartuStok')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = KartuStok::with(['barang', 'gudang', 'lokasiRak', 'createdBy']);

        foreach (['barang_id', 'gudang_id', 'lokasi_rak_id', 'tipe'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->{$field});
            }
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar kartu stok berhasil dimuat');
    }

    #[OA\Get(
        path: '/api/kartu-stok/{kartu_stok}',
        summary: 'Get kartu stok by ID',
        tags: ['Kartu Stok'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'kartu_stok', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Kartu stok detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail kartu stok berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/KartuStok'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(KartuStok $kartuStok)
    {
        return $this->success($kartuStok->load(['barang', 'gudang', 'lokasiRak', 'createdBy']), 'Detail kartu stok berhasil dimuat');
    }
}
