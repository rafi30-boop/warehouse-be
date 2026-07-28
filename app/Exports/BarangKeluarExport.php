<?php

namespace App\Exports;

use App\Models\BarangKeluar;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BarangKeluarExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return BarangKeluar::query()->with(['gudang', 'customer']);
    }

    public function headings(): array
    {
        return ['No Referensi', 'Tanggal', 'Gudang', 'Customer', 'Status', 'Keterangan'];
    }

    public function map($barangKeluar): array
    {
        return [
            $barangKeluar->no_referensi,
            $barangKeluar->tanggal,
            $barangKeluar->gudang->nama ?? '',
            $barangKeluar->customer->nama ?? '',
            $barangKeluar->status,
            $barangKeluar->keterangan,
        ];
    }
}
