<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Jalan - {{ $data->no_referensi }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; }
        .info { margin-bottom: 15px; }
        .info td { border: none; padding: 2px 0; }
        .footer { margin-top: 30px; }
        .signature { margin-top: 40px; }
        .signature td { width: 33%; text-align: center; border: none; }
        .ttd { margin-top: 50px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SURAT JALAN</h1>
        <p>{{ $data->gudang->nama ?? '' }}</p>
        <p>{{ $data->gudang->alamat ?? '' }}</p>
        <hr>
    </div>

    <table class="info">
        <tr><td width="120"><strong>No. Referensi</strong></td><td>: {{ $data->no_referensi }}</td></tr>
        <tr><td><strong>Nomor Surat Jalan</strong></td><td>: {{ $data->nomor_surat_jalan ?? '-' }}</td></tr>
        <tr><td><strong>Tanggal</strong></td><td>: {{ $data->tanggal }}</td></tr>
@if ($type === 'barang_keluar')
        <tr><td><strong>Customer</strong></td><td>: {{ $data->customer->nama ?? '-' }}</td></tr>
        <tr><td><strong>Alamat Customer</strong></td><td>: {{ $data->customer->alamat ?? '-' }}</td></tr>
@else
        <tr><td><strong>Supplier</strong></td><td>: {{ $data->supplier->nama ?? '-' }}</td></tr>
        <tr><td><strong>Alamat Supplier</strong></td><td>: {{ $data->supplier->alamat ?? '-' }}</td></tr>
@endif
        <tr><td><strong>Keterangan</strong></td><td>: {{ $data->keterangan ?? '-' }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Nama Barang</th>
                <th>SKU</th>
                <th>Lokasi Rak</th>
                <th width="60">Qty</th>
                <th>Satuan</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data->details as $index => $detail)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $detail->barang->nama ?? '-' }}</td>
                <td>{{ $detail->barang->sku ?? '-' }}</td>
                <td>{{ $detail->lokasiRak->kode_rak ?? '-' }}</td>
                <td class="text-right">{{ number_format($detail->qty, 2) }}</td>
                <td>{{ $detail->barang->satuan->singkatan ?? '-' }}</td>
                <td>{{ $detail->keterangan ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p><strong>Total Item: {{ $data->details->sum('qty') }}</strong></p>
        <p><strong>Status: {{ ucfirst($data->status) }}</strong></p>
    </div>

    <table class="signature">
        <tr>
            <td>
                <p>Dibuat Oleh,</p>
                <br><br><br>
                <p>({{ $data->createdBy->name ?? '_______________' }})</p>
            </td>
            <td>
                <p>Mengetahui,</p>
                <br><br><br>
                <p>(_______________)</p>
            </td>
            <td>
                <p>Penerima,</p>
                <br><br><br>
                <p>(_______________)</p>
            </td>
        </tr>
    </table>
</body>
</html>