<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        return response()->json(Shift::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string',
            'jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'required|date_format:H:i',
            'toleransi_masuk' => 'integer|min:0',
            'toleransi_pulang' => 'integer|min:0',
            'status' => 'in:aktif,nonaktif',
        ]);

        return response()->json(Shift::create($data), 201);
    }

    public function show(Shift $shift)
    {
        return response()->json($shift);
    }

    public function update(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'nama' => 'string',
            'jam_masuk' => 'date_format:H:i',
            'jam_pulang' => 'date_format:H:i',
            'toleransi_masuk' => 'integer|min:0',
            'toleransi_pulang' => 'integer|min:0',
            'status' => 'in:aktif,nonaktif',
        ]);

        $shift->update($data);
        return response()->json($shift);
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();
        return response()->json(null, 204);
    }
}