<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Policies\BasePolicy;
use App\Http\Requests\UpdateRoleRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Role;

#[OA\Tag(name: 'Role')]
class RoleController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index', 'show']]);
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
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of role', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar role berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Role')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        return $this->paginated(Role::with('permissions')->paginate($perPage), message: 'Daftar role berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/role',
        summary: 'Create role',
        tags: ['Role'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreRoleRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Role created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Role berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Role'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreRoleRequest $request)
    {
        $data = $request->validated();
        BasePolicy::denyUngrantedPermissionGrant($request->user(), $data['permissions'] ?? []);
        $role = Role::create(['name' => $data['name']]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $this->success($role->load('permissions'), 'Role berhasil dibuat', 201);
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
            new OA\Response(response: 200, description: 'Role detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail role berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Role'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Role $role)
    {
        return $this->success($role->load('permissions'), 'Detail role berhasil dimuat');
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
            new OA\Response(response: 200, description: 'Role updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Role berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Role'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $data = $request->validated();
        BasePolicy::denySystemRoleRename($role, $data['name'] ?? null);
        BasePolicy::denyUngrantedPermissionGrant($request->user(), $data['permissions'] ?? []);
        $role->update($data);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $this->success($role->load('permissions'), 'Role berhasil diperbarui');
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
            new OA\Response(response: 200, description: 'Role deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Role berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Request $request, Role $role)
    {
        abort_unless($request->user()->hasRole('super-admin'), 403, 'Hanya super-admin yang dapat menghapus role.');
        BasePolicy::denySystemRoleDelete($role);
        $role->delete();

        return $this->success(null, 'Role berhasil dihapus');
    }
}
