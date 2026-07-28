<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use Illuminate\Http\Request;

#[OA\Tag(name: 'Supplier')]
class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:supplier-list|supplier-create|supplier-edit|supplier-delete', ['only' => ['index','show']]);
        $this->middleware('permission:supplier-create', ['only' => ['store']]);
        $this->middleware('permission:supplier-edit', ['only' => ['update']]);
        $this->middleware('permission:supplier-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/supplier',
        summary: 'List all supplier',
        tags: ['Supplier'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of supplier'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(Supplier::paginate($perPage));
    }

    #[OA\Post(
        path: '/api/supplier',
        summary: 'Create supplier',
        tags: ['Supplier'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreSupplierRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Supplier created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreSupplierRequest $request)
    {
        return response()->json(Supplier::create($request->validated()), 201);
    }

    #[OA\Get(
        path: '/api/supplier/{supplier}',
        summary: 'Get supplier by ID',
        tags: ['Supplier'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'supplier', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Supplier detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Supplier $supplier)
    {
        return response()->json($supplier);
    }

    #[OA\Put(
        path: '/api/supplier/{supplier}',
        summary: 'Update supplier',
        tags: ['Supplier'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'supplier', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreSupplierRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Supplier updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());
        return response()->json($supplier);
    }

    #[OA\Delete(
        path: '/api/supplier/{supplier}',
        summary: 'Delete supplier',
        tags: ['Supplier'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'supplier', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Supplier deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(null, 204);
    }
}