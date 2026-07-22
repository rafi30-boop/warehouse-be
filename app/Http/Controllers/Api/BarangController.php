<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    public function index()
    {
        return response()->json(Barang::with('kategori')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => 'required|string|unique:barangs',
            'nama' => 'required|string',
            'kategori_id' => 'required|exists:kategoris,id',
            'satuan' => 'required|string',
            'harga_beli' => 'numeric',
            'harga_jual' => 'numeric',
            'stok_minimum' => 'integer',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return response()->json(Barang::create($data), 201);
    }

    public function show(Barang $barang)
    {
        return response()->json($barang->load('kategori'));
    }

    public function update(Request $request, Barang $barang)
    {
        $data = $request->validate([
            'kode' => 'string|unique:barangs,kode,' . $barang->id,
            'nama' => 'string',
            'kategori_id' => 'exists:kategoris,id',
            'satuan' => 'string',
            'harga_beli' => 'numeric',
            'harga_jual' => 'numeric',
            'stok_minimum' => 'integer',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $barang->update($data);
        return response()->json($barang->load('kategori'));
    }

    public function destroy(Barang $barang)
    {
        $barang->delete();
        return response()->json(null, 204);
    }
}
