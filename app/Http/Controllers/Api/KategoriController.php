<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index()
    {
        return response()->json(Kategori::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return response()->json(Kategori::create($data), 201);
    }

    public function show(Kategori $kategori)
    {
        return response()->json($kategori);
    }

    public function update(Request $request, Kategori $kategori)
    {
        $data = $request->validate([
            'nama' => 'string',
            'deskripsi' => 'nullable|string',
            'is_active' => 'boolean',
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
