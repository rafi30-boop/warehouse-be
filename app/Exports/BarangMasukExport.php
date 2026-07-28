<?php

namespace App\Exports;

use App\Models\BarangMasuk;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BarangMasukExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return BarangMasuk::query()->with(['gudang', 'supplier']);
    }

    public function headings(): array
    {
        return ['No Referensi', 'Tanggal', 'Gudang', 'Supplier', 'Status', 'Keterangan'];
    }

    public function map($barangMasuk): array
    {
        return [
            $barangMasuk->no_referensi,
            $barangMasuk->tanggal,
            $barangMasuk->gudang->nama ?? '',
            $barangMasuk->supplier->nama ?? '',
            $barangMasuk->status,
            $barangMasuk->keterangan,
        ];
    }
}
