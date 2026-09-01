<?php

namespace App\Exports\PdfReports;

use App\Models\LaporanBarangKeluar;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class LaporanBarangKeluarPdf
{
    protected $fromDate;
    protected $toDate;
    protected $gudangId;
    protected $customerId;
    protected $data;

    public function __construct(string $fromDate, string $toDate, ?string $gudangId = null, ?string $customerId = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->gudangId = $gudangId;
        $this->customerId = $customerId;
    }

    public function generate(): Pdf
    {
        // Fetch laporan data
        $query = LaporanBarangKeluar::with(['customer', 'gudang'])
            ->where('tanggal', '>=', $this->fromDate)
            ->where('tanggal', '<=', $this->toDate);

        if ($this->gudangId) {
            $query->where('gudang_id', $this->gudangId);
        }

        if ($this->customerId) {
            $query->where('customer_id', $this->customerId);
        }

        $data = $query->get();

        $pdf = Pdf::loadView('pdf.reports.laporan-barang-keluar', [
            'title' => 'Laporan Barang Keluar',
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
            'delivered' => $data->where('status', 'delivered')->count(),
            'partial' => $data->where('status', 'partial')->count(),
            'rejected' => $data->where('status', 'rejected')->count(),
        ];
    }
}
