<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;

class BarangKeluarController extends Controller
{
    public function index()
    {
        return response()->json(BarangKeluar::with(['barang', 'gudang', 'customer', 'user'])->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'no_transaksi' => 'required|string|unique:barang_keluars',
            'tanggal' => 'required|date',
            'barang_id' => 'required|exists:barangs,id',
            'gudang_id' => 'required|exists:gudangs,id',
            'customer_id' => 'required|exists:customers,id',
            'jumlah' => 'required|integer|min:1',
            'harga_satuan' => 'required|numeric',
            'keterangan' => 'nullable|string',
        ]);

        $data['total_harga'] = $data['jumlah'] * $data['harga_satuan'];
        $data['user_id'] = $request->user()->id;

        return response()->json(BarangKeluar::create($data), 201);
    }

    public function show(BarangKeluar $barangKeluar)
    {
        return response()->json($barangKeluar->load(['barang', 'gudang', 'customer', 'user']));
    }

    public function update(Request $request, BarangKeluar $barangKeluar)
    {
        $data = $request->validate([
            'no_transaksi' => 'string|unique:barang_keluars,no_transaksi,' . $barangKeluar->id,
            'tanggal' => 'date',
            'barang_id' => 'exists:barangs,id',
            'gudang_id' => 'exists:gudangs,id',
            'customer_id' => 'exists:customers,id',
            'jumlah' => 'integer|min:1',
            'harga_satuan' => 'numeric',
            'keterangan' => 'nullable|string',
        ]);

        if (isset($data['jumlah']) || isset($data['harga_satuan'])) {
            $data['total_harga'] = ($data['jumlah'] ?? $barangKeluar->jumlah) * ($data['harga_satuan'] ?? $barangKeluar->harga_satuan);
        }

        $barangKeluar->update($data);
        return response()->json($barangKeluar->load(['barang', 'gudang', 'customer', 'user']));
    }

    public function destroy(BarangKeluar $barangKeluar)
    {
        $barangKeluar->delete();
        return response()->json(null, 204);
    }
}
