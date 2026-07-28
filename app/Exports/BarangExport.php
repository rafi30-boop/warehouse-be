<?php

namespace App\Exports;

use App\Models\Barang;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BarangExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return Barang::query()->with(['kategori', 'satuan']);
    }

    public function headings(): array
    {
        return ['SKU', 'Nama', 'Kategori', 'Satuan', 'Harga Beli', 'Harga Jual', 'Min Stok', 'Max Stok', 'Status'];
    }

    public function map($barang): array
    {
        return [
            $barang->sku,
            $barang->nama,
            $barang->kategori->nama ?? '',
            $barang->satuan->singkatan ?? '',
            $barang->harga_beli,
            $barang->harga_jual,
            $barang->min_stok,
            $barang->max_stok,
            $barang->status,
        ];
    }
}
