<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Http\Requests\StoreAbsensiRequest;
use App\Http\Requests\UpdateAbsensiRequest;
use Illuminate\Http\Request;

#[OA\Tag(name: 'Absensi')]
class AbsensiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:absensi-list|absensi-create|absensi-edit|absensi-delete', ['only' => ['index','show']]);
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
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of absensi'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(Absensi::with(['user', 'gudang', 'shift'])->paginate($perPage));
    }

    #[OA\Post(
        path: '/api/absensi',
        summary: 'Create absensi',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreAbsensiRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Absensi created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreAbsensiRequest $request)
    {
        return response()->json(Absensi::create($request->validated()), 201);
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
            new OA\Response(response: 200, description: 'Absensi detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Absensi $absensi)
    {
        return response()->json($absensi->load(['user', 'gudang', 'shift', 'approvedBy']));
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
            new OA\Response(response: 200, description: 'Absensi updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateAbsensiRequest $request, Absensi $absensi)
    {
        $absensi->update($request->validated());
        return response()->json($absensi->load(['user', 'gudang', 'shift', 'approvedBy']));
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
            new OA\Response(response: 204, description: 'Absensi deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(Absensi $absensi)
    {
        $absensi->delete();
        return response()->json(null, 204);
    }
}