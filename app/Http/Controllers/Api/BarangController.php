<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    public function index()
    {
        return response()->json(Barang::with(['kategori', 'satuan'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku' => 'required|string|unique:barang',
            'barcode' => 'nullable|string|unique:barang',
            'nama' => 'required|string',
            'kategori_id' => 'required|exists:kategori_barang,id',
            'satuan_id' => 'required|exists:satuan,id',
            'min_stok' => 'numeric|min:0',
            'max_stok' => 'numeric|min:0',
            'berat' => 'nullable|numeric',
            'foto' => 'nullable|string',
            'harga_beli' => 'numeric|min:0',
            'harga_jual' => 'numeric|min:0',
            'deskripsi' => 'nullable|string',
            'status' => 'in:aktif,nonaktif',
        ]);

        return response()->json(Barang::create($data), 201);
    }

    public function show(Barang $barang)
    {
        return response()->json($barang->load(['kategori', 'satuan']));
    }

    public function update(Request $request, Barang $barang)
    {
        $data = $request->validate([
            'sku' => 'string|unique:barang,sku,' . $barang->id,
            'barcode' => 'nullable|string|unique:barang,barcode,' . $barang->id,
            'nama' => 'string',
            'kategori_id' => 'exists:kategori_barang,id',
            'satuan_id' => 'exists:satuan,id',
            'min_stok' => 'numeric|min:0',
            'max_stok' => 'numeric|min:0',
            'berat' => 'nullable|numeric',
            'foto' => 'nullable|string',
            'harga_beli' => 'numeric|min:0',
            'harga_jual' => 'numeric|min:0',
            'deskripsi' => 'nullable|string',
            'status' => 'in:aktif,nonaktif',
        ]);

        $barang->update($data);
        return response()->json($barang->load(['kategori', 'satuan']));
    }

    public function destroy(Barang $barang)
    {
        $barang->delete();
        return response()->json(null, 204);
    }
}