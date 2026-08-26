<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScanAbsensiRequest;
use App\Http\Requests\StoreAbsensiRequest;
use App\Http\Requests\UpdateAbsensiRequest;
use App\Models\Absensi;
use App\Models\Gudang;
use App\Models\JadwalPetugas;
use App\Models\Shift;
use App\Services\QrService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Absensi')]
class AbsensiController extends Controller
{
    use ApiResponse;

    private const SCAN_COOLDOWN_SECONDS = 120;

    public function __construct()
    {
        $this->middleware('permission:absensi-list|absensi-create|absensi-edit|absensi-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:absensi-create', ['only' => ['store']]);
        $this->middleware('permission:absensi-edit', ['only' => ['update']]);
        $this->middleware('permission:absensi-delete', ['only' => ['destroy']]);
        $this->middleware('permission:absensi-scan', ['only' => ['scan']]);
    }

    #[OA\Get(
        path: '/api/absensi',
        summary: 'List all absensi',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gudang_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of absensi', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Daftar absensi berhasil dimuat'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Absensi')),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = Absensi::with(['user', 'petugas', 'gudang', 'shift']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('petugas_id')) {
            $query->where('petugas_id', $request->petugas_id);
        }

        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        $paginated = $query->paginate($perPage);

        return $this->paginated(
            $paginated,
            items: \App\Http\Resources\AbsensiResource::collection($paginated->items()),
            message: 'Daftar absensi berhasil dimuat'
        );
    }

    #[OA\Post(
        path: '/api/absensi',
        summary: 'Create absensi',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreAbsensiRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Absensi created', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Absensi berhasil dibuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Absensi'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreAbsensiRequest $request)
    {
        return $this->success(Absensi::create($request->validated()), 'Absensi berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/absensi/{absensi}',
        summary: 'Get absensi by ID',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'absensi', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Absensi detail', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Detail absensi berhasil dimuat'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Absensi'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Absensi $absensi)
    {
        $absensi->load(['user', 'petugas', 'gudang', 'shift', 'approvedBy']);

        return $this->success(new \App\Http\Resources\AbsensiResource($absensi), 'Detail absensi berhasil dimuat');
    }

    #[OA\Put(
        path: '/api/absensi/{absensi}',
        summary: 'Update absensi',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'absensi', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreAbsensiRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Absensi updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Absensi berhasil diperbarui'),
                new OA\Property(property: 'data', ref: '#/components/schemas/Absensi'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function update(UpdateAbsensiRequest $request, Absensi $absensi)
    {
        $absensi->update($request->validated());

        return $this->success($absensi->load(['user', 'gudang', 'shift', 'approvedBy']), 'Absensi berhasil diperbarui');
    }

    #[OA\Delete(
        path: '/api/absensi/{absensi}',
        summary: 'Delete absensi',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'absensi', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Absensi deleted', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Absensi berhasil dihapus'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Absensi $absensi)
    {
        $absensi->delete();

        return $this->success(null, 'Absensi berhasil dihapus');
    }

    #[OA\Post(
        path: '/api/absensi/scan/sync',
        summary: 'Batch sync offline scans from kiosk',
        description: 'Idempotent endpoint for kiosk to sync queued scans after reconnecting. Each scan is processed independently; duplicates are silently skipped.',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['scans'], properties: [
            new OA\Property(property: 'scans', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'qr_payload', type: 'string', example: 'WQR1.eyJ1aWQiOjV9.c2ln'),
                new OA\Property(property: 'gudang_id', type: 'integer', example: 1),
                new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2026-08-24T08:15:30+07:00'),
            ])),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Sync completed', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Sync selesai: 3 berhasil, 1 duplikat, 0 gagal'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'processed', type: 'integer', example: 4),
                    new OA\Property(property: 'success', type: 'integer', example: 3),
                    new OA\Property(property: 'duplicate', type: 'integer', example: 1),
                    new OA\Property(property: 'failed', type: 'integer', example: 0),
                    new OA\Property(property: 'results', type: 'array', items: new OA\Items(properties: [
                        new OA\Property(property: 'index', type: 'integer'),
                        new OA\Property(property: 'status', type: 'string', enum: ['success', 'duplicate', 'failed']),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'absensi_id', type: 'integer', nullable: true),
                    ])),
                ], type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function scanSync(Request $request, QrService $qrService)
    {
        $request->validate([
            'scans' => 'required|array|min:1|max:100',
            'scans.*.qr_payload' => 'required|string',
            'scans.*.gudang_id' => 'nullable|integer|exists:gudang,id',
            'scans.*.waktu_scan' => 'required|date',
            'scans.*.client_ref' => 'required|string',
        ]);

        $scans = $request->input('scans');
        $results = [];

        foreach ($scans as $scan) {
            try {
                $mockRequest = new Request([
                    'qr_payload' => $scan['qr_payload'],
                    'gudang_id' => $scan['gudang_id'] ?? null,
                ]);
                $mockRequest->setUserResolver($request->getUserResolver());

                $response = $this->scan($mockRequest, $qrService);
                $data = $response->getData(true);

                if ($data['success'] ?? false) {
                    $scanData = $data['data'] ?? [];
                    
                    $results[] = [
                        'client_ref' => $scan['client_ref'],
                        'ok' => true,
                        'tipe' => $scanData['tipe'] ?? null,
                    ];
                } else {
                    $results[] = [
                        'client_ref' => $scan['client_ref'],
                        'ok' => false,
                        'error_message' => $data['message'] ?? 'Unknown error',
                    ];
                }
            } catch (\Exception $e) {
                $results[] = [
                    'client_ref' => $scan['client_ref'],
                    'ok' => false,
                    'error_message' => $e->getMessage(),
                ];
            }
        }

        return $this->success([
            'results' => $results,
        ], 'Sync selesai');
    }

    #[OA\Post(
        path: '/api/absensi/scan',
        summary: 'Check-in/check-out via signed QR scan',
        description: 'Verifies the signed QR payload server-side. First valid scan of the day = check-in (masuk), second = check-out (pulang). Scans within the cooldown window are ignored idempotently.',
        tags: ['Absensi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['qr_payload'], properties: [
            new OA\Property(property: 'qr_payload', type: 'string', example: 'WQR1.eyJ1aWQiOjV9.c2ln'),
            new OA\Property(property: 'gudang_id', type: 'integer', example: 1),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Scan processed (check-in, check-out, or duplicate)', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AbsensiScanResult'),
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid QR / missing gudang / no shift available', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function scan(ScanAbsensiRequest $request, QrService $qrService)
    {
        $result = $qrService->verify($request->input('qr_payload'));

        if (! $result['ok']) {
            return $this->error($this->qrErrorMessage($result['error']), 422);
        }

        if ($result['subject']['jenis'] === 'petugas') {
            $petugas = $result['petugas'];

            if ($petugas->status_operasional === 'Non-Aktif') {
                return $this->error("Karyawan {$petugas->nama} berstatus Non-Aktif dan tidak dapat melakukan absensi.", 422);
            }

            $subject = [
                'col' => 'petugas_id',
                'id' => $petugas->id,
                'nama' => $petugas->nama,
                'shiftUserId' => null,
                'gudangId' => $request->user()->gudang_id,
                'identitas' => [
                    'jenis' => 'petugas',
                    'id' => $petugas->id,
                    'nama' => $petugas->nama,
                    'kode' => $petugas->kode,
                    'jabatan' => $petugas->jabatan,
                ],
                'petugas' => [
                    'id' => $petugas->id,
                    'nama' => $petugas->nama,
                    'kode' => $petugas->kode,
                    'jabatan' => $petugas->jabatan,
                    'status_operasional' => $petugas->status_operasional,
                ],
            ];
        } else {
            $pegawai = $result['user'];
            $pegawai->load('petugas');
            $profil = $pegawai->petugas;

            $subject = [
                'col' => 'user_id',
                'id' => $pegawai->id,
                'nama' => $pegawai->name,
                'shiftUserId' => $pegawai->id,
                'gudangId' => $pegawai->gudang_id ?: $request->user()->gudang_id,
                'identitas' => [
                    'jenis' => 'user',
                    'id' => $pegawai->id,
                    'nama' => $pegawai->name,
                    'no_pegawai' => $pegawai->no_pegawai,
                    'kode' => $profil?->kode,
                    'jabatan' => $profil?->jabatan,
                ],
            ];
        }

        $gudangId = $request->filled('gudang_id')
            ? (int) $request->input('gudang_id')
            : $subject['gudangId'];

        if (! $gudangId) {
            $gudangId = Gudang::orderBy('id')->value('id') ?: 0;
        }

        if (! $gudangId) {
            return $this->error('Gudang tidak diketahui. Kirim gudang_id atau atur gudang akun pemindai.', 422);
        }

        $today = Date::today()->toDateString();
        $now = Date::now();

        $absensi = Absensi::where($subject['col'], $subject['id'])
            ->whereDate('tanggal', $today)
            ->orderByDesc('id')
            ->first();

        if (! $absensi || $absensi->jam_masuk === null) {
            return $this->handleCheckIn($subject, $absensi, $gudangId, $today, $now);
        }

        if ($absensi->jam_pulang === null) {
            return $this->handleCheckOut($subject, $absensi, $now);
        }

        return $this->scanResponse('duplicate', true, 'Scan duplikat diabaikan. Absensi hari ini sudah lengkap.', $subject, $absensi);
    }

    private function handleCheckIn(array $subject, ?Absensi $absensi, int $gudangId, string $today, $now)
    {
        if ($absensi && $absensi->jam_masuk === null && $absensi->updated_at?->diffInSeconds($now) < self::SCAN_COOLDOWN_SECONDS) {
            return $this->scanResponse('duplicate', true, 'Scan duplikat diabaikan.', $subject, $absensi);
        }

        $shift = null;
        $diLuarJadwal = false;

        if ($subject['shiftUserId']) {
            $shift = JadwalPetugas::where('user_id', $subject['shiftUserId'])
                ->whereDate('tanggal', $today)
                ->with('shift')
                ->first()
                ?->shift;

            if (! $shift) {
                $diLuarJadwal = true;
            }
        }

        $shift ??= Shift::where('status', 'aktif')->orderBy('id')->first();

        if (! $shift) {
            return $this->error('Tidak ada shift yang tersedia untuk karyawan ini.', 422);
        }

        $status = 'hadir';
        $jamMasukShift = $now->copy()->setTimeFromTimeString($shift->jam_masuk);

        if ($now->greaterThan($jamMasukShift->addMinutes((int) $shift->toleransi_masuk))) {
            $status = 'terlambat';
        }

        $attributes = [
            'jam_masuk' => $now->format('H:i:s'),
            'status' => $status,
            'sumber' => 'qr',
            'di_luar_jadwal' => $diLuarJadwal,
        ];

        if ($absensi) {
            $absensi->update($attributes + [
                'gudang_id' => $gudangId,
                'shift_id' => $shift->id,
            ]);
            $absensi->refresh();
        } else {
            $absensi = Absensi::create($attributes + [
                $subject['col'] => $subject['id'],
                'gudang_id' => $gudangId,
                'shift_id' => $shift->id,
                'tanggal' => $today,
            ]);
        }

        $message = $status === 'terlambat'
            ? "Absensi masuk dicatat. {$subject['nama']} terlambat."
            : "Absensi masuk berhasil dicatat untuk {$subject['nama']}.";

        if ($diLuarJadwal) {
            $message .= ' (Di luar jadwal - perlu review)';
        }

        return $this->scanResponse('masuk', false, $message, $subject, $absensi);
    }

    private function handleCheckOut(array $subject, Absensi $absensi, $now)
    {
        $lastTouch = $absensi->updated_at ?? $absensi->created_at;

        if ($lastTouch && $lastTouch->diffInSeconds($now) < self::SCAN_COOLDOWN_SECONDS) {
            return $this->scanResponse('duplicate', true, 'Scan duplikat diabaikan.', $subject, $absensi);
        }

        $absensi->update(['jam_pulang' => $now->format('H:i:s')]);
        $absensi->refresh();

        return $this->scanResponse('pulang', false, "Absensi pulang berhasil dicatat untuk {$subject['nama']}.", $subject, $absensi);
    }

    private function scanResponse(string $tipe, bool $duplicate, string $message, array $subject, Absensi $absensi)
    {
        $absensi->load(['shift', 'gudang']);

        return $this->success([
            'tipe' => $tipe,
            'duplicate' => $duplicate,
            'identitas' => $subject['identitas'],
            'petugas' => $subject['petugas'] ?? null,
            'user' => isset($subject['identitas']['no_pegawai']) || ($subject['col'] === 'user_id') ? [
                'id' => $subject['id'],
                'name' => $subject['nama'],
                'kode_petugas' => $subject['identitas']['kode'] ?? null,
                'jabatan' => $subject['identitas']['jabatan'] ?? null,
            ] : null,
            'absensi' => [
                'id' => $absensi->id,
                'tanggal' => $absensi->tanggal,
                'jam_masuk' => $absensi->jam_masuk,
                'jam_pulang' => $absensi->jam_pulang,
                'status' => $absensi->status,
                'sumber' => $absensi->sumber,
                'di_luar_jadwal' => $absensi->di_luar_jadwal,
            ],
            'shift' => $absensi->shift ? [
                'id' => $absensi->shift->id,
                'nama' => $absensi->shift->nama,
                'jam_masuk' => $absensi->shift->jam_masuk,
                'jam_pulang' => $absensi->shift->jam_pulang,
            ] : null,
            'gudang' => $absensi->gudang ? [
                'id' => $absensi->gudang->id,
                'nama' => $absensi->gudang->nama,
            ] : null,
        ], $message);
    }

    private function qrErrorMessage(string $error): string
    {
        return match ($error) {
            QrService::ERROR_FORMAT => 'QR tidak valid (format tidak dikenali).',
            QrService::ERROR_SIGNATURE => 'QR tidak valid (signature salah).',
            QrService::ERROR_EXPIRED => 'QR sudah kedaluwarsa.',
            QrService::ERROR_REVOKED => 'QR telah dicabut. Hubungi admin untuk regenerate.',
            QrService::ERROR_STALE_VERSION => 'QR kartu lama sudah tidak berlaku. Gunakan kartu terbaru.',
            QrService::ERROR_INACTIVE => 'Akun pegawai tidak aktif.',
            QrService::ERROR_NOT_FOUND => 'Pegawai tidak ditemukan.',
            QrService::ERROR_PETUGAS_NOT_FOUND => 'Karyawan tidak ditemukan.',
            default => 'QR tidak valid.',
        };
    }
}
