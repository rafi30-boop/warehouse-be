<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJadwalPetugasRequest;
use App\Http\Requests\UpdateJadwalPetugasRequest;
use App\Models\JadwalPetugas;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Jadwal Petugas')]
class JadwalPetugasController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:jadwal-petugas-list|jadwal-petugas-create|jadwal-petugas-edit|jadwal-petugas-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:jadwal-petugas-create', ['only' => ['store']]);
        $this->middleware('permission:jadwal-petugas-edit', ['only' => ['update']]);
        $this->middleware('permission:jadwal-petugas-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/jadwal-petugas',
        summary: 'List all jadwal petugas',
        tags: ['Jadwal Petugas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'shift_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of jadwal petugas', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar jadwal petugas berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/JadwalPetugas')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = JadwalPetugas::with(['user', 'shift', 'createdBy']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        if ($request->filled('from')) {
            $query->whereDate('tanggal', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('tanggal', '<=', $request->to);
        }

        return $this->paginated($query->orderByDesc('tanggal')->paginate($perPage), message: 'Daftar jadwal petugas berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/jadwal-petugas',
        summary: 'Create jadwal petugas',
        tags: ['Jadwal Petugas'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreJadwalPetugasRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Jadwal petugas created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Jadwal petugas berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/JadwalPetugas'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreJadwalPetugasRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $jadwal = JadwalPetugas::create($data);

        return $this->success($this->loadRelations($jadwal), 'Jadwal petugas berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/jadwal-petugas/{jadwal_petugas}',
        summary: 'Get jadwal petugas by ID',
        tags: ['Jadwal Petugas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'jadwal_petugas', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Jadwal petugas detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail jadwal petugas berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/JadwalPetugas'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(JadwalPetugas $jadwalPetugas)
    {
        return $this->success($this->loadRelations($jadwalPetugas), 'Detail jadwal petugas berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/jadwal-petugas/{jadwal_petugas}',
        summary: 'Update jadwal petugas',
        tags: ['Jadwal Petugas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'jadwal_petugas', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreJadwalPetugasRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Jadwal petugas updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Jadwal petugas berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/JadwalPetugas'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateJadwalPetugasRequest $request, JadwalPetugas $jadwalPetugas)
    {
        $jadwalPetugas->update($request->validated());

        return $this->success($this->loadRelations($jadwalPetugas), 'Jadwal petugas berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/jadwal-petugas/{jadwal_petugas}',
        summary: 'Delete jadwal petugas',
        tags: ['Jadwal Petugas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'jadwal_petugas', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Jadwal petugas deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Jadwal petugas berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(JadwalPetugas $jadwalPetugas)
    {
        $jadwalPetugas->delete();

        return $this->success(null, 'Jadwal petugas berhasil dihapus');
    }

    private function loadRelations(JadwalPetugas $jadwalPetugas): JadwalPetugas
    {
        return $jadwalPetugas->load(['user', 'shift', 'createdBy']);
    }
}
