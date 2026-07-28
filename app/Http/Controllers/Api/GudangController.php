<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use Illuminate\Http\Request;

class GudangController extends Controller
{
    public function index()
    {
        return response()->json(Gudang::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => 'required|string|unique:gudang',
            'nama' => 'required|string',
            'alamat' => 'nullable|string',
            'pic' => 'nullable|string',
            'telepon' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'in:aktif,nonaktif',
        ]);

        return response()->json(Gudang::create($data), 201);
    }

    public function show(Gudang $gudang)
    {
        return response()->json($gudang->load('lokasiRak'));
    }

    public function update(Request $request, Gudang $gudang)
    {
        $data = $request->validate([
            'kode' => 'string|unique:gudang,kode,' . $gudang->id,
            'nama' => 'string',
            'alamat' => 'nullable|string',
            'pic' => 'nullable|string',
            'telepon' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'in:aktif,nonaktif',
        ]);

        $gudang->update($data);
        return response()->json($gudang);
    }

    public function destroy(Gudang $gudang)
    {
        $gudang->delete();
        return response()->json(null, 204);
    }
}