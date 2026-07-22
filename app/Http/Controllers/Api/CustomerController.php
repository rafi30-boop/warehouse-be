<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return response()->json(Customer::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => 'required|string|unique:customers',
            'nama' => 'required|string',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string',
            'email' => 'nullable|email',
            'kontak_person' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return response()->json(Customer::create($data), 201);
    }

    public function show(Customer $customer)
    {
        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'kode' => 'string|unique:customers,kode,' . $customer->id,
            'nama' => 'string',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string',
            'email' => 'nullable|email',
            'kontak_person' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $customer->update($data);
        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(null, 204);
    }
}
