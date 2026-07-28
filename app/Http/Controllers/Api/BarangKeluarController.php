<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;

class BarangKeluarController extends Controller
{
    public function index()
    {
        return response()->json(BarangKeluar::with(['gudang', 'customer', 'createdBy', 'details.barang', 'details.lokasiRak'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'no_referensi' => 'required|string|unique:barang_keluar',
            'nomor_surat_jalan' => 'nullable|string',
            'gudang_id' => 'required|exists:gudang,id',
            'customer_id' => 'required|exists:customer,id',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected,delivered,partial',
            'dokumen' => 'nullable|string',
        ]);

        $data['created_by'] = $request->user()->id;

        return response()->json(BarangKeluar::create($data), 201);
    }

    public function show(BarangKeluar $barangKeluar)
    {
        return response()->json($barangKeluar->load(['gudang', 'customer', 'createdBy', 'approvedBy', 'deliveredBy', 'details.barang', 'details.lokasiRak']));
    }

    public function update(Request $request, BarangKeluar $barangKeluar)
    {
        $data = $request->validate([
            'no_referensi' => 'string|unique:barang_keluar,no_referensi,' . $barangKeluar->id,
            'nomor_surat_jalan' => 'nullable|string',
            'gudang_id' => 'exists:gudang,id',
            'customer_id' => 'exists:customer,id',
            'tanggal' => 'date',
            'keterangan' => 'nullable|string',
            'status' => 'in:pending,approved,rejected,delivered,partial',
            'dokumen' => 'nullable|string',
        ]);

        $barangKeluar->update($data);
        return response()->json($barangKeluar->load(['gudang', 'customer', 'createdBy', 'approvedBy', 'deliveredBy', 'details.barang', 'details.lokasiRak']));
    }

    public function destroy(BarangKeluar $barangKeluar)
    {
        $barangKeluar->delete();
        return response()->json(null, 204);
    }
}