<?php

namespace App\Exports;

use App\Models\BarangKeluar;
use Barryvdh\DomPDF\Facade\Pdf;

class SuratJalanExport
{
    protected $model;
    protected $type;
    protected $loadRelations;

    public function __construct($model, string $type, array $loadRelations)
    {
        $this->model = $model;
        $this->type = $type;
        $this->loadRelations = $loadRelations;
    }

    public static function forBarangKeluar(BarangKeluar $barangKeluar): self
    {
        return new self($barangKeluar, 'barang_keluar', [
            'gudang', 'customer', 'createdBy', 'details.barang', 'details.lokasiRak',
        ]);
    }

    public static function forBarangMasuk($barangMasuk): self
    {
        return new self($barangMasuk, 'barang_masuk', [
            'gudang', 'supplier', 'createdBy', 'details.barang', 'details.lokasiRak',
        ]);
    }

    protected function generatePdf()
    {
        $data = $this->model->load($this->loadRelations);
        return Pdf::loadView('pdf.surat-jalan', [
            'data' => $data,
            'type' => $this->type,
        ]);
    }

    public function download()
    {
        return $this->generatePdf()->download("surat-jalan-{$this->model->no_referensi}.pdf");
    }

    public function stream()
    {
        return $this->generatePdf()->stream("surat-jalan-{$this->model->no_referensi}.pdf");
    }
}
