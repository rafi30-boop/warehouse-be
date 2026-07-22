<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MutasiStok;
use Illuminate\Http\Request;

class MutasiStokController extends Controller
{
    public function index()
    {
        return response()->json(MutasiStok::with(['barang', 'gudangAsal', 'gudangTujuan', 'user'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'no_mutasi' => 'required|string|unique:mutasi_stoks',
            'tanggal' => 'required|date',
            'barang_id' => 'required|exists:barangs,id',
            'gudang_asal_id' => 'required|exists:gudangs,id|different:gudang_tujuan_id',
            'gudang_tujuan_id' => 'required|exists:gudangs,id|different:gudang_asal_id',
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $data['user_id'] = $request->user()->id;

        return response()->json(MutasiStok::create($data), 201);
    }

    public function show(MutasiStok $mutasiStok)
    {
        return response()->json($mutasiStok->load(['barang', 'gudangAsal', 'gudangTujuan', 'user']));
    }

    public function update(Request $request, MutasiStok $mutasiStok)
    {
        $data = $request->validate([
            'no_mutasi' => 'string|unique:mutasi_stoks,no_mutasi,' . $mutasiStok->id,
            'tanggal' => 'date',
            'barang_id' => 'exists:barangs,id',
            'gudang_asal_id' => 'exists:gudangs,id|different:gudang_tujuan_id',
            'gudang_tujuan_id' => 'exists:gudangs,id|different:gudang_asal_id',
            'jumlah' => 'integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $mutasiStok->update($data);
        return response()->json($mutasiStok->load(['barang', 'gudangAsal', 'gudangTujuan', 'user']));
    }

    public function destroy(MutasiStok $mutasiStok)
    {
        $mutasiStok->delete();
        return response()->json(null, 204);
    }
}
