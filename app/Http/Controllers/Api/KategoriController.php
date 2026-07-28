<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index()
    {
        return response()->json(Kategori::with('parent')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_id' => 'nullable|exists:kategori_barang,id',
            'nama' => 'required|string',
            'deskripsi' => 'nullable|string',
        ]);

        return response()->json(Kategori::create($data), 201);
    }

    public function show(Kategori $kategori)
    {
        return response()->json($kategori->load(['parent', 'children']));
    }

    public function update(Request $request, Kategori $kategori)
    {
        $data = $request->validate([
            'parent_id' => 'nullable|exists:kategori_barang,id',
            'nama' => 'string',
            'deskripsi' => 'nullable|string',
        ]);

        $kategori->update($data);
        return response()->json($kategori);
    }

    public function destroy(Kategori $kategori)
    {
        $kategori->delete();
        return response()->json(null, 204);
    }
}