<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MutasiStok;
use Illuminate\Http\Request;

class MutasiStokController extends Controller
{
    public function index()
    {
        return response()->json(MutasiStok::with(['barang', 'gudangAsal', 'gudangTujuan', 'lokasiRakAsal', 'lokasiRakTujuan', 'createdBy'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'no_referensi' => 'required|string|unique:mutasi_stok',
            'barang_id' => 'required|exists:barang,id',
            'gudang_asal_id' => 'required|exists:gudang,id|different:gudang_tujuan_id',
            'gudang_tujuan_id' => 'required|exists:gudang,id|different:gudang_asal_id',
            'lokasi_rak_asal_id' => 'nullable|exists:lokasi_rak,id',
            'lokasi_rak_tujuan_id' => 'nullable|exists:lokasi_rak,id',
            'qty' => 'required|numeric|min:0.01',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected,completed',
        ]);

        $data['created_by'] = $request->user()->id;

        return response()->json(MutasiStok::create($data), 201);
    }

    public function show(MutasiStok $mutasiStok)
    {
        return response()->json($mutasiStok->load(['barang', 'gudangAsal', 'gudangTujuan', 'lokasiRakAsal', 'lokasiRakTujuan', 'createdBy', 'approvedBy']));
    }

    public function update(Request $request, MutasiStok $mutasiStok)
    {
        $data = $request->validate([
            'no_referensi' => 'string|unique:mutasi_stok,no_referensi,' . $mutasiStok->id,
            'barang_id' => 'exists:barang,id',
            'gudang_asal_id' => 'exists:gudang,id|different:gudang_tujuan_id',
            'gudang_tujuan_id' => 'exists:gudang,id|different:gudang_asal_id',
            'lokasi_rak_asal_id' => 'nullable|exists:lokasi_rak,id',
            'lokasi_rak_tujuan_id' => 'nullable|exists:lokasi_rak,id',
            'qty' => 'numeric|min:0.01',
            'tanggal' => 'date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected,completed',
        ]);

        $mutasiStok->update($data);
        return response()->json($mutasiStok->load(['barang', 'gudangAsal', 'gudangTujuan', 'lokasiRakAsal', 'lokasiRakTujuan', 'createdBy', 'approvedBy']));
    }

    public function destroy(MutasiStok $mutasiStok)
    {
        $mutasiStok->delete();
        return response()->json(null, 204);
    }
}