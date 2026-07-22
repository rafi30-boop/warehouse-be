<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    public function index()
    {
        return response()->json(Absensi::with(['user', 'shift'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'shift_id' => 'required|exists:shifts,id',
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_keluar' => 'nullable|date_format:H:i',
            'status' => 'required|in:hadir,izin,sakit,alpha',
            'keterangan' => 'nullable|string',
        ]);

        return response()->json(Absensi::create($data), 201);
    }

    public function show(Absensi $absensi)
    {
        return response()->json($absensi->load(['user', 'shift']));
    }

    public function update(Request $request, Absensi $absensi)
    {
        $data = $request->validate([
            'user_id' => 'exists:users,id',
            'shift_id' => 'exists:shifts,id',
            'tanggal' => 'date',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_keluar' => 'nullable|date_format:H:i',
            'status' => 'in:hadir,izin,sakit,alpha',
            'keterangan' => 'nullable|string',
        ]);

        $absensi->update($data);
        return response()->json($absensi->load(['user', 'shift']));
    }

    public function destroy(Absensi $absensi)
    {
        $absensi->delete();
        return response()->json(null, 204);
    }
}
