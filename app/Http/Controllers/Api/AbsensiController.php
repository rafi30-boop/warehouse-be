<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAbsensiRequest;
use App\Http\Requests\UpdateAbsensiRequest;
use App\Models\Absensi;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Absensi')]
class AbsensiController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:absensi-list|absensi-create|absensi-edit|absensi-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:absensi-create', ['only' => ['store']]);
        $this->middleware('permission:absensi-edit', ['only' => ['update']]);
        $this->middleware('permission:absensi-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/absensi',
        summary: 'List all absensi',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of absensi', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar absensi berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Absensi')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = Absensi::with(['user', 'gudang', 'shift']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar absensi berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/absensi',
        summary: 'Create absensi',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreAbsensiRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Absensi created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Absensi berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Absensi'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreAbsensiRequest $request)
    {
        return $this->success(Absensi::create($request->validated()), 'Absensi berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/absensi/{absensi}',
        summary: 'Get absensi by ID',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'absensi', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Absensi detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail absensi berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Absensi'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Absensi $absensi)
    {
        return $this->success($absensi->load(['user', 'gudang', 'shift', 'approvedBy']), 'Detail absensi berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/absensi/{absensi}',
        summary: 'Update absensi',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'absensi', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreAbsensiRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Absensi updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Absensi berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Absensi'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateAbsensiRequest $request, Absensi $absensi)
    {
        $absensi->update($request->validated());

        return $this->success($absensi->load(['user', 'gudang', 'shift', 'approvedBy']), 'Absensi berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/absensi/{absensi}',
        summary: 'Delete absensi',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'absensi', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Absensi deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Absensi berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Absensi $absensi)
    {
        $absensi->delete();

        return $this->success(null, 'Absensi berhasil dihapus');
    }
}
