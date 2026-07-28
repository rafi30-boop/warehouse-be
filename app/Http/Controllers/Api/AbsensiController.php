<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    public function index()
    {
        return response()->json(Absensi::with(['user', 'gudang', 'shift'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'gudang_id' => 'required|exists:gudang,id',
            'shift_id' => 'required|exists:shift,id',
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
            'status' => 'required|in:hadir,izin,sakit,alpha,cuti,terlambat',
            'lokasi_checkin' => 'nullable|string',
            'lokasi_checkout' => 'nullable|string',
            'radius_validasi' => 'nullable|integer',
            'foto_masuk' => 'nullable|string',
            'foto_pulang' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        return response()->json(Absensi::create($data), 201);
    }

    public function show(Absensi $absensi)
    {
        return response()->json($absensi->load(['user', 'gudang', 'shift', 'approvedBy']));
    }

    public function update(Request $request, Absensi $absensi)
    {
        $data = $request->validate([
            'user_id' => 'exists:users,id',
            'gudang_id' => 'exists:gudang,id',
            'shift_id' => 'exists:shift,id',
            'tanggal' => 'date',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
            'status' => 'in:hadir,izin,sakit,alpha,cuti,terlambat',
            'lokasi_checkin' => 'nullable|string',
            'lokasi_checkout' => 'nullable|string',
            'radius_validasi' => 'nullable|integer',
            'foto_masuk' => 'nullable|string',
            'foto_pulang' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        $absensi->update($data);
        return response()->json($absensi->load(['user', 'gudang', 'shift', 'approvedBy']));
    }

    public function destroy(Absensi $absensi)
    {
        $absensi->delete();
        return response()->json(null, 204);
    }
}