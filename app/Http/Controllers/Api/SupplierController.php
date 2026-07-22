<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return response()->json(Supplier::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => 'required|string|unique:suppliers',
            'nama' => 'required|string',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string',
            'email' => 'nullable|email',
            'kontak_person' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return response()->json(Supplier::create($data), 201);
    }

    public function show(Supplier $supplier)
    {
        return response()->json($supplier);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'kode' => 'string|unique:suppliers,kode,' . $supplier->id,
            'nama' => 'string',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string',
            'email' => 'nullable|email',
            'kontak_person' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $supplier->update($data);
        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(null, 204);
    }
}
