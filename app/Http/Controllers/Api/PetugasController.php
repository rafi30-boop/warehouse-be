<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePetugasRequest;
use App\Http\Requests\UpdatePetugasRequest;
use App\Http\Resources\PetugasResource;
use App\Models\Petugas;
use App\Services\QrService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Petugas')]
class PetugasController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:petugas-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:petugas-create', ['only' => ['store']]);
        $this->middleware('permission:petugas-edit', ['only' => ['update']]);
        $this->middleware('permission:petugas-delete', ['only' => ['destroy']]);
    }

    #[OA\Get(
        path: '/api/petugas',
        summary: 'List petugas profiles',
        tags: ['Petugas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['Aktif', 'Cuti', 'Non-Aktif'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of petugas'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = Petugas::with('user')->orderBy('kode');

        if ($request->filled('status')) {
            $query->where('status_operasional', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', $search)
                    ->orWhere('kode', 'like', $search)
                    ->orWhere('jabatan', 'like', $search)
                    ->orWhere('area_kerja', 'like', $search)
                    ->orWhere('telepon', 'like', $search)
                    ->orWhereHas('user', fn ($uq) => $uq
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('no_pegawai', 'like', $search));
            });
        }

        return $this->paginated(
            $query->paginate($perPage),
            items: PetugasResource::collection($query->paginate($perPage)->items()),
            message: 'Daftar petugas berhasil dimuat'
        );
    }

    #[OA\Post(
        path: '/api/petugas',
        summary: 'Create petugas profile for a user (kode auto PG-xxx when omitted)',
        tags: ['Petugas'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['user_id'], properties: [
            new OA\Property(property: 'user_id', type: 'integer', example: 5),
            new OA\Property(property: 'kode', type: 'string', nullable: true, example: 'PG-001'),
            new OA\Property(property: 'telepon', type: 'string', nullable: true, example: '081234567890'),
            new OA\Property(property: 'jabatan', type: 'string', nullable: true, example: 'Operator Gudang'),
            new OA\Property(property: 'area_kerja', type: 'string', nullable: true, example: 'Gudang Utama'),
            new OA\Property(property: 'tanggal_bergabung', type: 'string', format: 'date', nullable: true, example: '2026-08-23'),
            new OA\Property(property: 'status_operasional', type: 'string', enum: ['Aktif', 'Cuti', 'Non-Aktif'], example: 'Aktif'),
        ])),
        responses: [
            new OA\Response(response: 201, description: 'Petugas created'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error (user already has profile / kode taken)', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StorePetugasRequest $request)
    {
        $data = $request->validated();
        $data['kode'] = $data['kode'] ?? $this->generateKode();
        $data['status_operasional'] = $data['status_operasional'] ?? 'Aktif';

        $petugas = DB::transaction(fn () => Petugas::create($data));

        return $this->success(new PetugasResource($petugas->load('user')), 'Profil petugas berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/petugas/{petugas}',
        summary: 'Get petugas detail',
        tags: ['Petugas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'petugas', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Petugas detail'),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Petugas $petugas)
    {
        return $this->success(new PetugasResource($petugas->load('user')), 'Detail petugas berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/petugas/{petugas}',
        summary: 'Update petugas profile (partial ok)',
        tags: ['Petugas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'petugas', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Petugas updated'),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdatePetugasRequest $request, Petugas $petugas)
    {
        $petugas->update($request->validated());

        return $this->success(new PetugasResource($petugas->refresh()->load('user')), 'Profil petugas berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/petugas/{petugas}',
        summary: 'Soft-delete petugas profile (user account untouched)',
        tags: ['Petugas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'petugas', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Petugas deleted'),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Petugas $petugas)
    {
        $petugas->delete();

        return $this->success(null, 'Profil petugas berhasil dihapus. Akun user tetap aktif.');
    }

    public function issueQr(Request $request, Petugas $petugas, QrService $qrService)
    {
        if ($petugas->user_id !== $request->user()->id && ! $request->user()->can('petugas-list')) {
            return $this->error('Forbidden - insufficient permissions', 403);
        }

        if ($petugas->qr_revoked_at !== null) {
            return $this->error('QR karyawan ini sedang dicabut. Lakukan regenerate terlebih dahulu.', 422);
        }

        return $this->success([
            'payload' => $qrService->issueForPetugas($petugas),
            'version' => (int) $petugas->qr_version,
            'issued_at' => now()->toISOString(),
            'petugas' => [
                'id' => $petugas->id,
                'nama' => $petugas->nama,
                'kode' => $petugas->kode,
            ],
        ], 'QR karyawan berhasil diterbitkan');
    }

    public function regenerateQr(Request $request, Petugas $petugas, QrService $qrService)
    {
        if (! $request->user()->can('petugas-edit')) {
            return $this->error('Forbidden - insufficient permissions', 403);
        }

        $qrService->regeneratePetugas($petugas);

        return $this->success([
            'payload' => $qrService->issueForPetugas($petugas),
            'version' => (int) $petugas->qr_version,
            'issued_at' => now()->toISOString(),
            'petugas' => [
                'id' => $petugas->id,
                'nama' => $petugas->nama,
                'kode' => $petugas->kode,
            ],
        ], 'QR karyawan berhasil dibuat ulang. Kartu lama tidak berlaku.');
    }

    public function revokeQr(Request $request, Petugas $petugas, QrService $qrService)
    {
        if (! $request->user()->can('petugas-edit')) {
            return $this->error('Forbidden - insufficient permissions', 403);
        }

        $qrService->revokePetugas($petugas);

        return $this->success([
            'petugas_id' => $petugas->id,
            'revoked_at' => $petugas->qr_revoked_at?->toISOString(),
        ], 'QR karyawan berhasil dicabut');
    }

    private function generateKode(): string
    {
        $lastNumber = (int) Petugas::withTrashed()
            ->whereRaw("kode regexp '^PG-[0-9]+$'")
            ->selectRaw('COALESCE(MAX(CAST(SUBSTRING(kode, 4) AS UNSIGNED)), 0) as max_num')
            ->value('max_num');

        do {
            $kode = 'PG-'.str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
            $lastNumber++;
        } while (Petugas::withTrashed()->where('kode', $kode)->exists());

        return $kode;
    }
}
