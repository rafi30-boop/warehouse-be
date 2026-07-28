<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

#[OA\Tag(name: 'User')]
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user-list|user-create|user-edit|user-delete', ['only' => ['index','show']]);
        $this->middleware('permission:user-create', ['only' => ['store']]);
        $this->middleware('permission:user-edit', ['only' => ['update']]);
        $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/user',
        summary: 'List all user',
        tags: ['User'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of user'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);
        return response()->json(User::with(['roles', 'gudang'])->paginate($perPage));
    }

    #[OA\Post(
        path: '/api/user',
        summary: 'Create user',
        tags: ['User'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreUserRequest')),
        responses: [
            new OA\Response(response: 201, description: 'User created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        if (isset($data['roles'])) {
            $user->assignRole($data['roles']);
        }

        return response()->json($user->load(['roles', 'gudang']), 201);
    }

    #[OA\Get(
        path: '/api/user/{user}',
        summary: 'Get user by ID',
        tags: ['User'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(User $user)
    {
        return response()->json($user->load(['roles.permissions', 'gudang']));
    }

    #[OA\Put(
        path: '/api/user/{user}',
        summary: 'Update user',
        tags: ['User'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreUserRequest')),
        responses: [
            new OA\Response(response: 200, description: 'User updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return response()->json($user->load(['roles', 'gudang']));
    }

    #[OA\Delete(
        path: '/api/user/{user}',
        summary: 'Delete user',
        tags: ['User'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'User deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
}