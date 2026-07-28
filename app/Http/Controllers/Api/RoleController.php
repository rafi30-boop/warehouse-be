<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

#[OA\Tag(name: 'Role')]
class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index','show']]);
        $this->middleware('permission:role-create', ['only' => ['store']]);
        $this->middleware('permission:role-edit', ['only' => ['update']]);
        $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/role',
        summary: 'List all role',
        tags: ['Role'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of role'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(Role::with('permissions')->paginate($perPage));
    }

    #[OA\Post(
        path: '/api/role',
        summary: 'Create role',
        tags: ['Role'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreRoleRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Role created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreRoleRequest $request)
    {
        $data = $request->validated();
        $role = Role::create(['name' => $data['name']]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json($role->load('permissions'), 201);
    }

    #[OA\Get(
        path: '/api/role/{role}',
        summary: 'Get role by ID',
        tags: ['Role'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Role detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Role $role)
    {
        return response()->json($role->load('permissions'));
    }

    #[OA\Put(
        path: '/api/role/{role}',
        summary: 'Update role',
        tags: ['Role'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreRoleRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Role updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $data = $request->validated();
        $role->update($data);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json($role->load('permissions'));
    }

    #[OA\Delete(
        path: '/api/role/{role}',
        summary: 'Delete role',
        tags: ['Role'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Role deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(null, 204);
    }
}