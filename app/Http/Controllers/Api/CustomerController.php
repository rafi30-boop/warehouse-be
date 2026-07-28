<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\Request;

#[OA\Tag(name: 'Customer')]
class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:customer-list|customer-create|customer-edit|customer-delete', ['only' => ['index','show']]);
        $this->middleware('permission:customer-create', ['only' => ['store']]);
        $this->middleware('permission:customer-edit', ['only' => ['update']]);
        $this->middleware('permission:customer-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/customer',
        summary: 'List all customer',
        tags: ['Customer'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of customer'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(Customer::paginate($perPage));
    }

    #[OA\Post(
        path: '/api/customer',
        summary: 'Create customer',
        tags: ['Customer'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreCustomerRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Customer created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreCustomerRequest $request)
    {
        return response()->json(Customer::create($request->validated()), 201);
    }

    #[OA\Get(
        path: '/api/customer/{customer}',
        summary: 'Get customer by ID',
        tags: ['Customer'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'customer', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Customer detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Customer $customer)
    {
        return response()->json($customer);
    }

    #[OA\Put(
        path: '/api/customer/{customer}',
        summary: 'Update customer',
        tags: ['Customer'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'customer', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreCustomerRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Customer updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());
        return response()->json($customer);
    }

    #[OA\Delete(
        path: '/api/customer/{customer}',
        summary: 'Delete customer',
        tags: ['Customer'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'customer', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Customer deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(null, 204);
    }
}