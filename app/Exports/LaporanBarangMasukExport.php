<?php

namespace App\Exports;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use App\Models\LaporanBarangMasuk;

class LaporanBarangMasukExport implements \Maatwebsite\Excel\Runs
{
    protected $fromDate;
    protected $toDate;
    protected $gudangId;
    protected $supplierId;

    public function __construct(string $fromDate, string $toDate, ?string $gudangId = null, ?string $supplierId = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->gudangId = $gudangId;
        $this->supplierId = $supplierId;
    }

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return LaporanBarangMasuk::with(['supplier', 'gudang'])
            ->where('tanggal', '>=', $this->fromDate)
            ->where('tanggal', '<=', $this->toDate);
    }

    public function map($row): array
    {
        return [
            'Tanggal' => \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y'),
            'No. Referensi' => $row->no_referensi,
            'Supplier' => $row->supplier->nama ?? '-',
            'Gudang' => $row->gudang->nama ?? '-',
            'Status' => ucfirst($row->status),
            'Total Qty' => $row->details->sum('qty'),
            'Keterangan' => $row->keterangan ?? '-',
        ];
    }
}
