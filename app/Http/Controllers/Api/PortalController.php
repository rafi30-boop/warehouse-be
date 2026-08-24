<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IzinRequestResource;
use App\Models\IzinRequest;
use App\Models\Notifikasi;
use App\Models\User;
use App\Services\QrService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Portal')]
class PortalController extends Controller
{
    use ApiResponse;

    protected QrService $qrService;

    public function __construct(QrService $qrService)
    {
        $this->qrService = $qrService;
    }

    #[OA\Post(
        path: '/api/portal/auth',
        summary: 'Verify QR card and return petugas identity (portal auth without login)',
        tags: ['Portal'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['qr_payload'], properties: [
            new OA\Property(property: 'qr_payload', type: 'string', example: 'WQR1.eyJwaWQiOjEsInYiOjF9.signature'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'QR valid, petugas identity returned'),
            new OA\Response(response: 422, description: 'Invalid/expired/revoked QR', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function auth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_payload' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $result = $this->qrService->verify($request->qr_payload);

        if (!$result['ok']) {
            $messages = [
                QrService::ERROR_FORMAT => 'Format QR tidak valid. Scan ulang kartu Anda.',
                QrService::ERROR_SIGNATURE => 'Tanda tangan QR tidak valid. Scan ulang kartu Anda.',
                QrService::ERROR_EXPIRED => 'QR sudah kedaluwarsa. Hubungi admin untuk kartu baru.',
                QrService::ERROR_REVOKED => 'Kartu ini sudah dicabut. Hubungi admin untuk kartu baru.',
                QrService::ERROR_STALE_VERSION => 'Kartu lama. Scan kartu terbaru Anda.',
                QrService::ERROR_PETUGAS_NOT_FOUND => 'Petugas tidak ditemukan.',
                QrService::ERROR_NOT_FOUND => 'Pengguna tidak ditemukan.',
            ];

            return $this->error($messages[$result['error']] ?? 'QR tidak valid.', 422);
        }

        // Only petugas allowed (portal for native petugas without user accounts)
        if (!isset($result['petugas'])) {
            return $this->error('Portal hanya untuk petugas. Gunakan login untuk akun pegawai.', 422);
        }

        $petugas = $result['petugas'];

        if ($petugas->status_operasional === 'Non-Aktif') {
            return $this->error('Status operasional Non-Aktif. Hubungi admin.', 422);
        }

        return $this->success([
            'petugas' => [
                'id' => $petugas->id,
                'nama' => $petugas->nama,
                'kode' => $petugas->kode,
                'jabatan' => $petugas->jabatan,
                'status_operasional' => $petugas->status_operasional,
            ],
        ], 'Identitas berhasil diverifikasi');
    }

    #[OA\Post(
        path: '/api/portal/izin/riwayat',
        summary: 'Get izin history for the petugas identified by QR',
        tags: ['Portal'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['qr_payload'], properties: [
            new OA\Property(property: 'qr_payload', type: 'string', example: 'WQR1.eyJwaWQiOjEsInYiOjF9.signature'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Izin list for this petugas'),
            new OA\Response(response: 422, description: 'Invalid QR', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function riwayat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_payload' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $result = $this->qrService->verify($request->qr_payload);

        if (!$result['ok']) {
            return $this->error('QR tidak valid. Scan ulang kartu Anda.', 422);
        }

        if (!isset($result['petugas'])) {
            return $this->error('Portal hanya untuk petugas.', 422);
        }

        $izinList = IzinRequest::with(['petugas', 'approvedBy'])
            ->where('petugas_id', $result['petugas']->id)
            ->orderByDesc('id')
            ->get();

        return $this->success(IzinRequestResource::collection($izinList), 'Riwayat pengajuan izin dimuat');
    }

    #[OA\Post(
        path: '/api/portal/izin',
        summary: 'Submit izin request via portal (petugas identified by QR)',
        tags: ['Portal'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['qr_payload', 'jenis', 'tanggal_mulai', 'tanggal_selesai', 'alasan'], properties: [
            new OA\Property(property: 'qr_payload', type: 'string', example: 'WQR1.eyJwaWQiOjEsInYiOjF9.signature'),
            new OA\Property(property: 'jenis', type: 'string', enum: ['izin', 'sakit', 'cuti'], example: 'sakit'),
            new OA\Property(property: 'tanggal_mulai', type: 'string', format: 'date', example: '2026-08-25'),
            new OA\Property(property: 'tanggal_selesai', type: 'string', format: 'date', example: '2026-08-26'),
            new OA\Property(property: 'alasan', type: 'string', example: 'Demam tinggi'),
            new OA\Property(property: 'bukti', type: 'string', nullable: true, example: 'http://localhost:8000/storage/uploads/surat.pdf'),
        ])),
        responses: [
            new OA\Response(response: 201, description: 'Izin created'),
            new OA\Response(response: 422, description: 'Validation error / QR invalid / overlap', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_payload' => 'required|string',
            'jenis' => 'required|in:izin,sakit,cuti',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|max:1000',
            'bukti' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $result = $this->qrService->verify($request->qr_payload);

        if (!$result['ok']) {
            return $this->error('QR tidak valid. Scan ulang kartu Anda.', 422);
        }

        if (!isset($result['petugas'])) {
            return $this->error('Portal hanya untuk petugas.', 422);
        }

        $petugas = $result['petugas'];

        if ($petugas->status_operasional === 'Non-Aktif') {
            return $this->error('Status operasional Non-Aktif. Tidak dapat mengajukan izin.', 422);
        }

        // Overlap check
        $overlap = IzinRequest::where('petugas_id', $petugas->id)
            ->whereIn('status', ['menunggu', 'disetujui'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('tanggal_mulai', [$request->tanggal_mulai, $request->tanggal_selesai])
                  ->orWhereBetween('tanggal_selesai', [$request->tanggal_mulai, $request->tanggal_selesai])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('tanggal_mulai', '<=', $request->tanggal_mulai)
                         ->where('tanggal_selesai', '>=', $request->tanggal_selesai);
                  });
            })
            ->first();

        if ($overlap) {
            return $this->error(
                "Pengajuan izin bentrok dengan pengajuan #{$overlap->id} ({$overlap->tanggal_mulai->format('d/m/Y')} - {$overlap->tanggal_selesai->format('d/m/Y')}).",
                422
            );
        }

        $izinRequest = IzinRequest::create([
            'petugas_id' => $petugas->id,
            'jenis' => $request->jenis,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'alasan' => $request->alasan,
            'bukti' => $request->bukti,
            'status' => 'menunggu',
        ]);

        // Notify all approvers
        $approvers = User::permission('izin-approve')->get();
        foreach ($approvers as $approver) {
            Notifikasi::create([
                'user_id' => $approver->id,
                'judul' => 'Pengajuan Izin Baru',
                'pesan' => "Petugas {$petugas->nama} mengajukan {$request->jenis} dari {$request->tanggal_mulai} s/d {$request->tanggal_selesai}",
                'tipe' => 'info',
                'priority' => 'high',
                'link' => '/absensi/cuti-izin',
            ]);
        }

        return $this->success(
            new IzinRequestResource($izinRequest->load('petugas')),
            'Pengajuan izin berhasil dikirim. Menunggu persetujuan admin.',
            201
        );
    }

    #[OA\Post(
        path: '/api/portal/izin/{izin_request}/cancel',
        summary: 'Cancel own pending izin request via portal',
        tags: ['Portal'],
        parameters: [
            new OA\Parameter(name: 'izin_request', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['qr_payload'], properties: [
            new OA\Property(property: 'qr_payload', type: 'string', example: 'WQR1.eyJwaWQiOjEsInYiOjF9.signature'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Izin cancelled'),
            new OA\Response(response: 403, description: 'Forbidden - not your request', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid QR or not pending', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function cancel(Request $request, IzinRequest $izinRequest)
    {
        $validator = Validator::make($request->all(), [
            'qr_payload' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $result = $this->qrService->verify($request->qr_payload);

        if (!$result['ok']) {
            return $this->error('QR tidak valid. Scan ulang kartu Anda.', 422);
        }

        if (!isset($result['petugas'])) {
            return $this->error('Portal hanya untuk petugas.', 422);
        }

        if ($izinRequest->petugas_id !== $result['petugas']->id) {
            return $this->error('Anda hanya dapat membatalkan pengajuan sendiri.', 403);
        }

        if ($izinRequest->status !== 'menunggu') {
            return $this->error('Hanya pengajuan berstatus menunggu yang dapat dibatalkan.', 422);
        }

        $izinRequest->update(['status' => 'dibatalkan']);

        return $this->success(
            new IzinRequestResource($izinRequest->fresh()->load('petugas')),
            'Pengajuan izin berhasil dibatalkan'
        );
    }
}
