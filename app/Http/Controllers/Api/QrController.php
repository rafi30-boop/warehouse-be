<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\QrService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'QR')]
class QrController extends Controller
{
    use ApiResponse;

    public function __construct(private QrService $qrService) {}

    #[OA\Post(
        path: '/api/qr/issue',
        summary: 'Issue signed QR payload (own by default, others require user-edit)',
        tags: ['QR'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'user_id', type: 'integer', example: 5),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Signed QR payload', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', ref: '#/components/schemas/QrPayload'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error / revoked QR', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function issue(Request $request)
    {
        $request->validate(['user_id' => 'nullable|integer|exists:users,id']);

        $target = $request->filled('user_id') ? User::findOrFail($request->integer('user_id')) : $request->user();

        if ($target->id !== $request->user()->id && ! $request->user()->can('user-edit')) {
            return $this->error('Forbidden - insufficient permissions', 403);
        }

        if ($target->qr_revoked_at !== null) {
            return $this->error('QR pegawai ini sedang dicabut. Lakukan regenerate terlebih dahulu.', 422);
        }

        return $this->success($this->buildPayloadData($target), 'QR berhasil diterbitkan');
    }

    #[OA\Post(
        path: '/api/qr/{user}/regenerate',
        summary: 'Regenerate QR version (invalidates previously printed cards). Self or user-edit.',
        tags: ['QR'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'New signed QR payload', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', ref: '#/components/schemas/QrPayload'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function regenerate(Request $request, User $user)
    {
        if ($user->id !== $request->user()->id && ! $request->user()->can('user-edit')) {
            return $this->error('Forbidden - insufficient permissions', 403);
        }

        $this->qrService->regenerate($user);

        return $this->success($this->buildPayloadData($user), 'QR berhasil dibuat ulang. Kartu lama tidak berlaku.');
    }

    #[OA\Post(
        path: '/api/qr/{user}/revoke',
        summary: 'Revoke QR (requires user-edit)',
        tags: ['QR'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'QR revoked', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'revoked_at', type: 'string', format: 'date-time'),
                ], type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function revoke(Request $request, User $user)
    {
        if (! $request->user()->can('user-edit')) {
            return $this->error('Forbidden - insufficient permissions', 403);
        }

        $this->qrService->revoke($user);

        return $this->success([
            'user_id' => $user->id,
            'revoked_at' => $user->qr_revoked_at?->toISOString(),
        ], 'QR berhasil dicabut');
    }

    private function buildPayloadData(User $user): array
    {
        return [
            'payload' => $this->qrService->issue($user),
            'version' => (int) $user->qr_version,
            'issued_at' => now()->toISOString(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'no_pegawai' => $user->no_pegawai,
            ],
        ];
    }
}
