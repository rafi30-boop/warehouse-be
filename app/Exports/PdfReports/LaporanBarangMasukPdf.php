<?php

namespace App\Exports\PdfReports;

use App\Models\LaporanBarangMasuk;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class LaporanBarangMasukPdf
{
    protected $fromDate;
    protected $toDate;
    protected $gudangId;
    protected $supplierId;
    protected $data;

    public function __construct(string $fromDate, string $toDate, ?string $gudangId = null, ?string $supplierId = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->gudangId = $gudangId;
        $this->supplierId = $supplierId;
    }

    public function generate(): Pdf
    {
        // Fetch laporan data
        $query = LaporanBarangMasuk::with(['supplier', 'gudang'])
            ->where('tanggal', '>=', $this->fromDate)
            ->where('tanggal', '<=', $this->toDate);

        if ($this->gudangId) {
            $query->where('gudang_id', $this->gudangId);
        }

        if ($this->supplierId) {
            $query->where('supplier_id', $this->supplierId);
        }

        $data = $query->get();

        $pdf = Pdf::loadView('pdf.reports.laporan-barang-masuk', [
            'title' => 'Laporan Barang Masuk',
            'dateRange' => "{$this->fromDate} s/d {$this->toDate}",
            'items' => $data,
            'summary' => $this->getSummary($data),
        ]);

        $pdf->setPaper('a4', 'portrait');
        
        return $pdf;
    }

    private function getSummary($data)
    {
        return [
            'total_transaksi' => $data->count(),
            'total_qty' => $data->sum(function($item) {
                return $item->details->sum('qty');
            }),
            'approved' => $data->where('status', 'approved')->count(),
            'rejected' => $data->where('status', 'rejected')->count(),
        ];
    }
}
