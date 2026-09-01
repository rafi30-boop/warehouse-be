<?php

namespace App\Exports\PdfReports;

use App\Models\LaporanAbsensi;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanAbsensiPdf
{
    protected $fromDate;
    protected $toDate;
    protected $gudangId;
    protected $userId;
    protected $data;

    public function __construct(string $fromDate, string $toDate, ?string $gudangId = null, ?string $userId = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->gudangId = $gudangId;
        $this->userId = $userId;
    }

    public function generate(): Pdf
    {
        // Fetch laporan data
        $query = LaporanAbsensi::with(['user', 'gudang'])
            ->where('tanggal', '>=', $this->fromDate)
            ->where('tanggal', '<=', $this->toDate);

        if ($this->gudangId) {
            $query->where('gudang_id', $this->gudangId);
        }

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        $data = $query->get();

        $pdf = Pdf::loadView('pdf.reports.laporan-absensi', [
            'title' => 'Laporan Absensi Petugas',
            'dateRange' => "{$this->fromDate} s/d {$this->toDate}",
            'items' => $data,
            'summary' => $this->getSummary($data),
        ]);

        $pdf->setPaper('a4', 'portrait');
        
        return $pdf;
    }

    private function getSummary($data)
    {
        $hadir = $data->where('status', 'hadir')->count();
        $terlambat = $data->where('status', 'terlambat')->count();
        $sakit = $data->where('status', 'sakit')->count();
        $izin = $data->where('status', 'izin')->count();
        $cuti = $data->where('status', 'cuti')->count();
        $alpha = $data->where('status', 'alpha')->count();

        return [
            'total_records' => $data->count(),
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'sakit' => $sakit,
            'izin' => $izin,
            'cuti' => $cuti,
            'alpha' => $alpha,
        ];
    }
}
