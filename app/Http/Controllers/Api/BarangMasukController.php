<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BarangMasuk;
use Illuminate\Http\Request;

class BarangMasukController extends Controller
{
    public function index()
    {
        return response()->json(BarangMasuk::with(['barang', 'gudang', 'supplier', 'user'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'no_transaksi' => 'required|string|unique:barang_masuks',
            'tanggal' => 'required|date',
            'barang_id' => 'required|exists:barangs,id',
            'gudang_id' => 'required|exists:gudangs,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'jumlah' => 'required|integer|min:1',
            'harga_satuan' => 'required|numeric',
            'keterangan' => 'nullable|string',
        ]);

        $data['total_harga'] = $data['jumlah'] * $data['harga_satuan'];
        $data['user_id'] = $request->user()->id;

        return response()->json(BarangMasuk::create($data), 201);
    }

    public function show(BarangMasuk $barangMasuk)
    {
        return response()->json($barangMasuk->load(['barang', 'gudang', 'supplier', 'user']));
    }

    public function update(Request $request, BarangMasuk $barangMasuk)
    {
        $data = $request->validate([
            'no_transaksi' => 'string|unique:barang_masuks,no_transaksi,' . $barangMasuk->id,
            'tanggal' => 'date',
            'barang_id' => 'exists:barangs,id',
            'gudang_id' => 'exists:gudangs,id',
            'supplier_id' => 'exists:suppliers,id',
            'jumlah' => 'integer|min:1',
            'harga_satuan' => 'numeric',
            'keterangan' => 'nullable|string',
        ]);

        if (isset($data['jumlah']) || isset($data['harga_satuan'])) {
            $data['total_harga'] = ($data['jumlah'] ?? $barangMasuk->jumlah) * ($data['harga_satuan'] ?? $barangMasuk->harga_satuan);
        }

        $barangMasuk->update($data);
        return response()->json($barangMasuk->load(['barang', 'gudang', 'supplier', 'user']));
    }

    public function destroy(BarangMasuk $barangMasuk)
    {
        $barangMasuk->delete();
        return response()->json(null, 204);
    }
}
