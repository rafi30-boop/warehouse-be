<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Permission;

#[OA\Tag(name: 'Permission')]
class PermissionController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:role-list|role-create|role-edit');
    }

    #[OA\Get(
        path: '/api/permissions',
        summary: 'List all permission names (master list for role editor)',
        tags: ['Permission'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Daftar permission berhasil dimuat', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar permission berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string')),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index()
    {
        $permissions = Permission::orderBy('name')->pluck('name');

        return $this->success($permissions, 'Daftar permission berhasil dimuat');
    }
}
