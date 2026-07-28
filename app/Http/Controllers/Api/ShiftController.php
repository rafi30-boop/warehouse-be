<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use Illuminate\Http\Request;

#[OA\Tag(name: 'Shift')]
class ShiftController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:shift-list|shift-create|shift-edit|shift-delete', ['only' => ['index','show']]);
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
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of shift'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(Shift::paginate($perPage));
    }

    #[OA\Post(
        path: '/api/shift',
        summary: 'Create shift',
        tags: ['Shift'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreShiftRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Shift created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreShiftRequest $request)
    {
        return response()->json(Shift::create($request->validated()), 201);
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
            new OA\Response(response: 200, description: 'Shift detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Shift $shift)
    {
        return response()->json($shift);
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
            new OA\Response(response: 200, description: 'Shift updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateShiftRequest $request, Shift $shift)
    {
        $shift->update($request->validated());
        return response()->json($shift);
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
            new OA\Response(response: 204, description: 'Shift deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(Shift $shift)
    {
        $shift->delete();
        return response()->json(null, 204);
    }
}