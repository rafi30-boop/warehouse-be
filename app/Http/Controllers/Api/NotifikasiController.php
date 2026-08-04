<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Notifikasi')]
class NotifikasiController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:notifikasi-list|notifikasi-edit|notifikasi-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:notifikasi-edit', ['only' => ['markAsRead', 'markAllRead']]);
        $this->middleware('permission:notifikasi-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/notifikasi',
        summary: 'List all notifikasi',
        tags: ['Notifikasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_read', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'tipe', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of notifikasi', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar notifikasi berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Notifikasi')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = Notifikasi::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        } elseif (! $request->user()->hasRole('super-admin')) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('is_read')) {
            $query->where('is_read', filter_var($request->is_read, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        return $this->paginated($query->orderByDesc('created_at')->paginate($perPage), message: 'Daftar notifikasi berhasil dimuat');
    }

    #[OA\Get(
        path: '/api/notifikasi/{notifikasi}',
        summary: 'Get notifikasi by ID',
        tags: ['Notifikasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'notifikasi', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notifikasi detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail notifikasi berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Notifikasi'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Notifikasi $notifikasi)
    {
        return $this->success($notifikasi->load('user'), 'Detail notifikasi berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/notifikasi/read-all',
        summary: 'Mark all my notifikasi as read',
        tags: ['Notifikasi'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'All notifikasi marked as read', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Semua notifikasi ditandai sudah dibaca'),
                new OA\Property(property: 'data', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'integer')),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function markAllRead(Request $request)
    {
        $count = Notifikasi::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return $this->success(['updated' => $count], 'Semua notifikasi ditandai sudah dibaca');
    }

    #[OA\Post(
        path: '/api/notifikasi/{notifikasi}/read',
        summary: 'Mark notifikasi as read',
        tags: ['Notifikasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'notifikasi', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notifikasi marked as read', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Notifikasi ditandai sudah dibaca'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Notifikasi'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function markAsRead(Notifikasi $notifikasi)
    {
        $notifikasi->update(['is_read' => true, 'read_at' => now()]);

        return $this->success($notifikasi->load('user'), 'Notifikasi ditandai sudah dibaca');
    }

    #[OA\Delete(
        path: '/api/notifikasi/{notifikasi}',
        summary: 'Delete notifikasi',
        tags: ['Notifikasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'notifikasi', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notifikasi deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Notifikasi berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Notifikasi $notifikasi)
    {
        $notifikasi->delete();

        return $this->success(null, 'Notifikasi berhasil dihapus');
    }
}
