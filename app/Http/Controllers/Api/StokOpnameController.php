<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StokOpname;
use Illuminate\Http\Request;

class StokOpnameController extends Controller
{
    public function index()
    {
        return response()->json(StokOpname::with(['barang', 'gudang', 'user'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'no_opname' => 'required|string|unique:stok_opnames',
            'tanggal' => 'required|date',
            'barang_id' => 'required|exists:barangs,id',
            'gudang_id' => 'required|exists:gudangs,id',
            'stok_sistem' => 'required|integer',
            'stok_fisik' => 'required|integer',
            'keterangan' => 'nullable|string',
        ]);

        $data['selisih'] = $data['stok_fisik'] - $data['stok_sistem'];
        $data['user_id'] = $request->user()->id;

        return response()->json(StokOpname::create($data), 201);
    }

    public function show(StokOpname $stokOpname)
    {
        return response()->json($stokOpname->load(['barang', 'gudang', 'user']));
    }

    public function update(Request $request, StokOpname $stokOpname)
    {
        $data = $request->validate([
            'no_opname' => 'string|unique:stok_opnames,no_opname,' . $stokOpname->id,
            'tanggal' => 'date',
            'barang_id' => 'exists:barangs,id',
            'gudang_id' => 'exists:gudangs,id',
            'stok_sistem' => 'integer',
            'stok_fisik' => 'integer',
            'keterangan' => 'nullable|string',
        ]);

        if (isset($data['stok_fisik']) || isset($data['stok_sistem'])) {
            $data['selisih'] = ($data['stok_fisik'] ?? $stokOpname->stok_fisik) - ($data['stok_sistem'] ?? $stokOpname->stok_sistem);
        }

        $stokOpname->update($data);
        return response()->json($stokOpname->load(['barang', 'gudang', 'user']));
    }

    public function destroy(StokOpname $stokOpname)
    {
        $stokOpname->delete();
        return response()->json(null, 204);
    }
}
