<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIzinRequest;
use App\Models\Absensi;
use App\Models\IzinRequest;
use App\Models\JadwalPetugas;
use App\Models\Notifikasi;
use App\Models\Shift;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'IzinRequest')]
class IzinRequestController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('permission:izin-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:izin-create', ['only' => ['store']]);
        // update/destroy: gate view-level saja — controller menegakkan
        // "owner atau pemegang izin-edit/izin-delete".
        $this->middleware('permission:izin-list', ['only' => ['update', 'destroy']]);
        $this->middleware('permission:izin-approve', ['only' => ['approve', 'reject']]);
    }

    #[OA\Get(
        path: '/api/izin',
        summary: 'List izin requests (non-approvers only see their own)',
        tags: ['IzinRequest'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['menunggu', 'disetujui', 'ditolak'])),
            new OA\Parameter(name: 'jenis', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['izin', 'sakit', 'cuti'])),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $perPage = min(100, (int) $request->per_page ?: 15);

        $query = IzinRequest::with(['user', 'petugas', 'approvedBy'])->orderByDesc('id');

        if (! $request->user()->can('izin-approve')) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->filled('from')) {
            $query->whereDate('tanggal_mulai', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('tanggal_selesai', '<=', $request->to);
        }

        return $this->paginated($query->paginate($perPage), message: 'Daftar pengajuan izin berhasil dimuat');
    }

    #[OA\Post(
        path: '/api/izin',
        summary: 'Submit izin/sakit/cuti request (always for the authenticated user)',
        tags: ['IzinRequest'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['jenis', 'tanggal_mulai', 'tanggal_selesai', 'alasan'], properties: [
            new OA\Property(property: 'jenis', type: 'string', enum: ['izin', 'sakit', 'cuti'], example: 'sakit'),
            new OA\Property(property: 'tanggal_mulai', type: 'string', format: 'date', example: '2026-08-25'),
            new OA\Property(property: 'tanggal_selesai', type: 'string', format: 'date', example: '2026-08-26'),
            new OA\Property(property: 'alasan', type: 'string', example: 'Demam, ada surat dokter'),
            new OA\Property(property: 'bukti', type: 'string', nullable: true, example: 'http://localhost:8000/storage/uploads/surat.pdf'),
        ])),
        responses: [
            new OA\Response(response: 201, description: 'Request created'),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function store(StoreIzinRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // On-behalf & backdate: only izin-edit can submit for others or backdate
        if ($user->can('izin-edit')) {
            $userId = $data['user_id'] ?? $user->id;
        } else {
            $userId = $user->id;
            
            // Prevent backdate for self-service
            if (isset($data['tanggal_mulai']) && $data['tanggal_mulai'] < now()->toDateString()) {
                return $this->error('Tidak dapat mengajukan izin untuk tanggal yang sudah lewat.', 422);
            }
        }

        // Overlap validation
        $overlap = IzinRequest::where('user_id', $userId)
            ->whereIn('status', ['menunggu', 'disetujui'])
            ->where(function ($q) use ($data) {
                $q->whereBetween('tanggal_mulai', [$data['tanggal_mulai'], $data['tanggal_selesai']])
                  ->orWhereBetween('tanggal_selesai', [$data['tanggal_mulai'], $data['tanggal_selesai']])
                  ->orWhere(function ($q2) use ($data) {
                      $q2->where('tanggal_mulai', '<=', $data['tanggal_mulai'])
                         ->where('tanggal_selesai', '>=', $data['tanggal_selesai']);
                  });
            })
            ->first();

        if ($overlap) {
            return $this->error(
                "Pengajuan izin bentrok dengan pengajuan #{$overlap->id} ({$overlap->tanggal_mulai->format('d/m/Y')} - {$overlap->tanggal_selesai->format('d/m/Y')}).",
                422
            );
        }

        // array_merge (bukan union +): user_id & status hasil forcing
        // harus menimpa apa pun yang dikirim di payload.
        $izin = IzinRequest::create(array_merge($data, [
            'user_id' => $userId,
            'status' => 'menunggu',
        ]));

        // Notify all users with izin-approve permission
        $approvers = User::permission('izin-approve')->get();
        $pengajuNama = User::find($userId)->name;
        $rangeText = date('d/m/Y', strtotime($data['tanggal_mulai'])) . ' - ' . date('d/m/Y', strtotime($data['tanggal_selesai']));

        foreach ($approvers as $approver) {
            Notifikasi::create([
                'user_id' => $approver->id,
                'judul' => 'Pengajuan Izin Baru',
                'pesan' => "{$pengajuNama} · {$data['jenis']} · {$rangeText}",
                'tipe' => 'info',
                'priority' => 'high',
                'link' => '/absensi/cuti-izin',
            ]);
        }

        return $this->success($izin->load('user'), 'Pengajuan izin berhasil dibuat', 201);
    }

    #[OA\Get(
        path: '/api/izin/{izin_request}',
        summary: 'Get izin request detail (own unless approver)',
        tags: ['IzinRequest'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'izin_request', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Request detail'),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Request $request, IzinRequest $izinRequest)
    {
        if ($izinRequest->user_id !== $request->user()->id && ! $request->user()->can('izin-approve')) {
            return $this->error('Forbidden - insufficient permissions', 403);
        }

        return $this->success($izinRequest->load(['user', 'approvedBy']), 'Detail pengajuan izin berhasil dimuat');
    }

    public function update(Request $request, IzinRequest $izinRequest)
    {
        $user = $request->user();

        // Permission check: owner or izin-edit
        if ($izinRequest->user_id !== $user->id && ! $user->can('izin-edit')) {
            return $this->error('Forbidden - insufficient permissions', 403);
        }

        if ($izinRequest->status !== 'menunggu') {
            return $this->error('Hanya pengajuan berstatus menunggu yang dapat diubah.', 422);
        }

        $data = validator($request->all(), [
            'jenis' => 'sometimes|required|in:izin,sakit,cuti',
            'tanggal_mulai' => 'sometimes|required|date',
            'tanggal_selesai' => 'sometimes|required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'sometimes|required|string|max:1000',
            'bukti' => 'nullable|string|max:255',
        ])->validate();

        // Backdate check for self-service
        if ($izinRequest->user_id === $user->id && ! $user->can('izin-edit')) {
            if (isset($data['tanggal_mulai']) && $data['tanggal_mulai'] < now()->toDateString()) {
                return $this->error('Tidak dapat mengubah tanggal menjadi tanggal yang sudah lewat.', 422);
            }
        }

        // Overlap validation if dates changed
        if (isset($data['tanggal_mulai']) || isset($data['tanggal_selesai'])) {
            $tanggalMulai = $data['tanggal_mulai'] ?? $izinRequest->tanggal_mulai->toDateString();
            $tanggalSelesai = $data['tanggal_selesai'] ?? $izinRequest->tanggal_selesai->toDateString();

            $overlap = IzinRequest::where('user_id', $izinRequest->user_id)
                ->where('id', '!=', $izinRequest->id)
                ->whereIn('status', ['menunggu', 'disetujui'])
                ->where(function ($q) use ($tanggalMulai, $tanggalSelesai) {
                    $q->whereBetween('tanggal_mulai', [$tanggalMulai, $tanggalSelesai])
                      ->orWhereBetween('tanggal_selesai', [$tanggalMulai, $tanggalSelesai])
                      ->orWhere(function ($q2) use ($tanggalMulai, $tanggalSelesai) {
                          $q2->where('tanggal_mulai', '<=', $tanggalMulai)
                             ->where('tanggal_selesai', '>=', $tanggalSelesai);
                      });
                })
                ->first();

            if ($overlap) {
                return $this->error(
                    "Pengajuan izin bentrok dengan pengajuan #{$overlap->id} ({$overlap->tanggal_mulai->format('d/m/Y')} - {$overlap->tanggal_selesai->format('d/m/Y')}).",
                    422
                );
            }
        }

        $izinRequest->update($data);
        $izinRequest->refresh();

        return $this->success($izinRequest->load('user'), 'Pengajuan izin berhasil diperbarui');
    }

    public function destroy(Request $request, IzinRequest $izinRequest)
    {
        if ($izinRequest->user_id !== $request->user()->id && ! $request->user()->can('izin-delete')) {
            return $this->error('Forbidden - insufficient permissions', 403);
        }

        if ($izinRequest->status !== 'menunggu') {
            return $this->error('Hanya pengajuan berstatus menunggu yang dapat dihapus.', 422);
        }

        $izinRequest->delete();

        return $this->success(null, 'Pengajuan izin berhasil dihapus');
    }

    #[OA\Post(
        path: '/api/izin/{izin_request}/approve',
        summary: 'Approve request; auto-creates absensi records (izin/sakit/cuti) for the date range',
        tags: ['IzinRequest'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'izin_request', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Approved; data includes created absensi ids'),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Not pending / missing gudang or shift for pegawai', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function approve(Request $request, IzinRequest $izinRequest)
    {
        if ($izinRequest->status !== 'menunggu') {
            return $this->error('Hanya pengajuan berstatus menunggu yang dapat disetujui.', 422);
        }

        // SoD: approver cannot be the requester
        if ($izinRequest->user_id === $request->user()->id) {
            return $this->error('Anda tidak dapat menyetujui pengajuan izin Anda sendiri (Separation of Duties).', 403);
        }

        // Dual-subject: resolve petugas or user — petugas tidak punya gudang_id sendiri, gudang diambil dari akun user terhubung
        $izinRequest->loadMissing(['petugas.user', 'user']);
        $pegawai = $izinRequest->petugas;
        $user = $izinRequest->user;

        // Gudang petugas via relasi user (area_kerja hanya string, bukan FK)
        $pegawaiGudangId = $pegawai?->user?->gudang_id ?? $pegawai?->getAttribute('gudang_id');
        $userGudangId = $user?->gudang_id;
        $subjectName = $pegawai?->nama ?? $user?->name ?? 'Petugas';

        if ($pegawai && !$pegawaiGudangId) {
            return $this->error("Petugas {$pegawai->nama} belum memiliki gudang. Atur gudang pada akun user terhubung (Pengaturan → Users → Edit) terlebih dahulu.", 422);
        }

        if (!$pegawai && $user && !$userGudangId) {
            return $this->error("Pegawai {$user->name} belum memiliki gudang. Atur gudang terlebih dahulu.", 422);
        }

        $gudangId = $pegawaiGudangId ?? $userGudangId;
        if (!$gudangId) {
            return $this->error("Gagal menentukan gudang untuk {$subjectName}. Pastikan akun terkait sudah terhubung ke gudang.", 422);
        }

        $created = DB::transaction(function () use ($request, $izinRequest, $pegawai, $user, $gudangId) {
            $izinRequest->update([
                'status' => 'disetujui',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $absensiIds = [];
            $skipped = 0;
            $period = $izinRequest->tanggal_mulai->toPeriod($izinRequest->tanggal_selesai, '1 day');

            foreach ($period as $date) {
                $tanggal = $date->toDateString();

                // Check existing record for this subject
                $existsQuery = Absensi::whereDate('tanggal', $tanggal);
                if ($pegawai) {
                    $existsQuery->where('petugas_id', $pegawai->id);
                } else {
                    $existsQuery->where('user_id', $user->id);
                }
                
                if ($existsQuery->exists()) {
                    $skipped++;
                    continue;
                }

                // Find shift from jadwal (user only) or fallback
                $shiftId = null;
                if ($user) {
                    $shiftId = JadwalPetugas::where('user_id', $user->id)
                        ->whereDate('tanggal', $tanggal)
                        ->value('shift_id');
                }
                $shiftId = $shiftId ?? Shift::where('status', 'aktif')->orderBy('id')->value('id');

                if (!$shiftId) {
                    continue;
                }

                $absensi = Absensi::create([
                    'user_id' => $user?->id,
                    'petugas_id' => $pegawai?->id,
                    'gudang_id' => $gudangId,
                    'shift_id' => $shiftId,
                    'tanggal' => $tanggal,
                    'status' => $izinRequest->jenis,
                    'sumber' => 'pengajuan',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                    'keterangan' => "Otomatis dari pengajuan {$izinRequest->jenis} #{$izinRequest->id}",
                ]);

                $absensiIds[] = $absensi->id;
            }

            return ['created' => $absensiIds, 'skipped' => $skipped];
        });

        $message = 'Pengajuan izin disetujui';
        if ($created['skipped'] > 0) {
            $message .= ". {$created['skipped']} tanggal dilewati karena sudah memiliki record absensi";
        }

        // Notify the requester (user only, skip petugas native)
        if ($user) {
            Notifikasi::create([
                'user_id' => $user->id,
                'judul' => 'Pengajuan Izin Disetujui',
                'pesan' => "Pengajuan {$izinRequest->jenis} Anda telah disetujui oleh {$request->user()->name}",
                'tipe' => 'success',
                'priority' => 'medium',
                'link' => '/absensi/cuti-izin',
            ]);
        }

        return $this->success([
            'izin_request' => $izinRequest->fresh()->load(['user', 'petugas', 'approvedBy']),
            'absensi_created_ids' => $created['created'],
            'skipped_count' => $created['skipped'],
        ], $message);
    }

    #[OA\Post(
        path: '/api/izin/{izin_request}/reject',
        summary: 'Reject request',
        tags: ['IzinRequest'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'izin_request', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Rejected'),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Not pending', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function reject(Request $request, IzinRequest $izinRequest)
    {
        if ($izinRequest->status !== 'menunggu') {
            return $this->error('Hanya pengajuan berstatus menunggu yang dapat ditolak.', 422);
        }

        // SoD: rejector cannot be the requester
        if ($izinRequest->user_id === $request->user()->id) {
            return $this->error('Anda tidak dapat menolak pengajuan izin Anda sendiri (Separation of Duties).', 403);
        }

        $validated = $request->validate([
            'catatan_penolakan' => 'required|string|min:5',
        ]);

        $izinRequest->update([
            'status' => 'ditolak',
            'catatan_penolakan' => $validated['catatan_penolakan'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        // Notify the requester
        Notifikasi::create([
            'user_id' => $izinRequest->user_id,
            'judul' => 'Pengajuan Izin Ditolak',
            'pesan' => "Pengajuan {$izinRequest->jenis} Anda ditolak: {$validated['catatan_penolakan']}",
            'tipe' => 'error',
            'priority' => 'medium',
            'link' => '/absensi/cuti-izin',
        ]);

        return $this->success($izinRequest->fresh()->load(['user', 'approvedBy']), 'Pengajuan izin ditolak');
    }

    #[OA\Post(
        path: '/api/izin/{izin_request}/cancel',
        summary: 'Cancel request (owner when menunggu; izin-delete when disetujui - deletes generated absensi)',
        tags: ['IzinRequest'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'izin_request', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cancelled'),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid status', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function cancel(Request $request, IzinRequest $izinRequest)
    {
        $user = $request->user();

        if ($izinRequest->status === 'menunggu') {
            if ($izinRequest->user_id !== $user->id) {
                return $this->error('Forbidden - insufficient permissions', 403);
            }

            $izinRequest->update(['status' => 'dibatalkan']);

            // Notify the owner
            Notifikasi::create([
                'user_id' => $izinRequest->user_id,
                'judul' => 'Pengajuan Izin Dibatalkan',
                'pesan' => "Pengajuan {$izinRequest->jenis} Anda telah dibatalkan",
                'tipe' => 'warning',
                'priority' => 'low',
                'link' => '/absensi/cuti-izin',
            ]);

            return $this->success($izinRequest->fresh()->load(['user', 'approvedBy']), 'Pengajuan izin berhasil dibatalkan');
        }

        if ($izinRequest->status === 'disetujui') {
            if (! $user->can('izin-delete')) {
                return $this->error('Forbidden - insufficient permissions', 403);
            }

            DB::transaction(function () use ($izinRequest) {
                Absensi::where('user_id', $izinRequest->user_id)
                    ->whereBetween('tanggal', [$izinRequest->tanggal_mulai, $izinRequest->tanggal_selesai])
                    ->where('status', $izinRequest->jenis)
                    ->where('sumber', 'pengajuan')
                    ->delete();

                $izinRequest->update(['status' => 'dibatalkan']);
            });

            // Notify the owner
            Notifikasi::create([
                'user_id' => $izinRequest->user_id,
                'judul' => 'Pengajuan Izin Dibatalkan',
                'pesan' => "Pengajuan {$izinRequest->jenis} Anda telah dibatalkan oleh admin dan absensi terkait dihapus",
                'tipe' => 'warning',
                'priority' => 'medium',
                'link' => '/absensi/cuti-izin',
            ]);

            return $this->success($izinRequest->fresh()->load(['user', 'approvedBy']), 'Pengajuan izin dibatalkan dan absensi terkait dihapus');
        }

        return $this->error('Hanya pengajuan berstatus menunggu atau disetujui yang dapat dibatalkan.', 422);
    }
}
