<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BarangMasuk;
use Illuminate\Http\Request;

class BarangMasukController extends Controller
{
    public function index()
    {
        return response()->json(BarangMasuk::with(['gudang', 'supplier', 'createdBy', 'details.barang', 'details.lokasiRak'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'no_referensi' => 'required|string|unique:barang_masuk',
            'nomor_surat_jalan' => 'nullable|string',
            'gudang_id' => 'required|exists:gudang,id',
            'supplier_id' => 'required|exists:supplier,id',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected,partial',
            'dokumen' => 'nullable|string',
        ]);

        $data['created_by'] = $request->user()->id;

        return response()->json(BarangMasuk::create($data), 201);
    }

    public function show(BarangMasuk $barangMasuk)
    {
        return response()->json($barangMasuk->load(['gudang', 'supplier', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']));
    }

    public function update(Request $request, BarangMasuk $barangMasuk)
    {
        $data = $request->validate([
            'no_referensi' => 'string|unique:barang_masuk,no_referensi,' . $barangMasuk->id,
            'nomor_surat_jalan' => 'nullable|string',
            'gudang_id' => 'exists:gudang,id',
            'supplier_id' => 'exists:supplier,id',
            'tanggal' => 'date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected,partial',
            'dokumen' => 'nullable|string',
        ]);

        $barangMasuk->update($data);
        return response()->json($barangMasuk->load(['gudang', 'supplier', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']));
    }

    public function destroy(BarangMasuk $barangMasuk)
    {
        $barangMasuk->delete();
        return response()->json(null, 204);
    }
}