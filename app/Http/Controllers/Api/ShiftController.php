<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Models\Shift;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Shift')]
class ShiftController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:shift-list|shift-create|shift-edit|shift-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:shift-create', ['only' => ['store']]);
        $this->middleware('permission:shift-edit', ['only' => ['update']]);
        $this->middleware('permission:shift-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/shift',
        summary: 'List all shift',
        tags: ['Shift'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string', description: 'Search by nama')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of shift', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar shift berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Shift')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = Shift::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('nama', 'like', "%{$s}%");
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar shift berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/shift',
        summary: 'Create shift',
        tags: ['Shift'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreShiftRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Shift created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Shift berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Shift'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreShiftRequest $request)
    {
        return $this->success(Shift::create($request->validated()), 'Shift berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/shift/{shift}',
        summary: 'Get shift by ID',
        tags: ['Shift'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'shift', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Shift detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail shift berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Shift'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Shift $shift)
    {
        return $this->success($shift, 'Detail shift berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/shift/{shift}',
        summary: 'Update shift',
        tags: ['Shift'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'shift', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreShiftRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Shift updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Shift berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Shift'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateShiftRequest $request, Shift $shift)
    {
        $shift->update($request->validated());

        return $this->success($shift, 'Shift berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/shift/{shift}',
        summary: 'Delete shift',
        tags: ['Shift'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'shift', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Shift deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Shift berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Shift $shift)
    {
        $shift->delete();

        return $this->success(null, 'Shift berhasil dihapus');
    }
}
