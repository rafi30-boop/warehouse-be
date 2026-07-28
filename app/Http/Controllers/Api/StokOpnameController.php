<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StokOpname;
use Illuminate\Http\Request;

class StokOpnameController extends Controller
{
    public function index()
    {
        return response()->json(StokOpname::with(['gudang', 'createdBy', 'details.barang', 'details.lokasiRak'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'no_referensi' => 'required|string|unique:stok_opname',
            'gudang_id' => 'required|exists:gudang,id',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'in:draft,in_progress,completed,cancelled',
        ]);

        $data['created_by'] = $request->user()->id;

        return response()->json(StokOpname::create($data), 201);
    }

    public function show(StokOpname $stokOpname)
    {
        return response()->json($stokOpname->load(['gudang', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']));
    }

    public function update(Request $request, StokOpname $stokOpname)
    {
        $data = $request->validate([
            'no_referensi' => 'string|unique:stok_opname,no_referensi,' . $stokOpname->id,
            'gudang_id' => 'exists:gudang,id',
            'tanggal' => 'date',
            'keterangan' => 'nullable|string',
            'status' => 'in:draft,in_progress,completed,cancelled',
        ]);

        $stokOpname->update($data);
        return response()->json($stokOpname->load(['gudang', 'createdBy', 'approvedBy', 'details.barang', 'details.lokasiRak']));
    }

    public function destroy(StokOpname $stokOpname)
    {
        $stokOpname->delete();
        return response()->json(null, 204);
    }
}