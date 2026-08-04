<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Aktivitas Log')]
class AktivitasLogController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:aktivitas-log-list|aktivitas-log-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:aktivitas-log-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/aktivitas-log',
        summary: 'List all aktivitas log',
        tags: ['Aktivitas Log'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'action', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'model', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of aktivitas log', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar aktivitas log berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AktivitasLog')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = AktivitasLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model')) {
            $query->where('model', 'like', "%{$request->model}%");
        }

        return $this->paginated($query->orderByDesc('id')->paginate($perPage), message: 'Daftar aktivitas log berhasil dimuat');
    }

    #[OA\Get(
        path: '/api/aktivitas-log/{aktivitas_log}',
        summary: 'Get aktivitas log by ID',
        tags: ['Aktivitas Log'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'aktivitas_log', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Aktivitas log detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail aktivitas log berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AktivitasLog'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(AktivitasLog $aktivitasLog)
    {
        return $this->success($aktivitasLog->load('user'), 'Detail aktivitas log berhasil dimuat');
    }

    #[OA\Delete(
        path: '/api/aktivitas-log/{aktivitas_log}',
        summary: 'Delete aktivitas log',
        tags: ['Aktivitas Log'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'aktivitas_log', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Aktivitas log deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Aktivitas log berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(AktivitasLog $aktivitasLog)
    {
        $aktivitasLog->delete();

        return $this->success(null, 'Aktivitas log berhasil dihapus');
    }
}
