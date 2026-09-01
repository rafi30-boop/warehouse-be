<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - {{ $dateRange }}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            line-height: 1.4;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        .header h1 { 
            margin: 0; 
            font-size: 18px; 
            font-weight: bold;
        }
        .header p { 
            margin: 2px 0; 
            color: #666;
        }
        .info-section {
            background: #fff3e0;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: bold;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left;
        }
        th { 
            background: #ff9800; 
            color: white; 
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .summary-item {
            text-align: center;
            padding: 10px;
            border-radius: 4px;
        }
        .summary-item.delivered { background: #e8f5e9; }
        .summary-item.partial { background: #fff9c4; }
        .summary-item.rejected { background: #ffebee; }
        .summary-item.total { background: #fff3e0; }
        
        .summary-item .number {
            font-size: 24px;
            font-weight: bold;
        }
        .delivered .number { color: #2e7d32; }
        .partial .number { color: #f57c00; }
        .rejected .number { color: #c62828; }
        .total .number { color: #ef6c00; }
        
        .summary-item .label {
            font-size: 11px;
            color: #666;
        }
        .footer {
            margin-top: 40px;
            text-align: right;
            font-size: 11px;
            color: #999;
        }
        .status-delivered { color: green; font-weight: bold; }
        .status-partial { color: orange; font-weight: bold; }
        .status-rejected { color: red; font-weight: bold; }
        .status-approved { color: blue; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Periode: {{ $dateRange }}</p>
        <p>Dicetak pada: {{ now()->format('d F Y H:i') }}</p>
    </div>

    @if(isset($summary))
    <div class="info-section">
        <h3>Ringkasan</h3>
        <div class="summary-grid">
            <div class="summary-item total">
                <div class="number">{{ $summary['total_transaksi'] ?? 0 }}</div>
                <div class="label">Total Transaksi</div>
            </div>
            <div class="summary-item delivered">
                <div class="number">{{ number_format($summary['total_qty'] ?? 0) }}</div>
                <div class="label">Total Item (Qty)</div>
            </div>
            <div class="summary-item delivered">
                <div class="number">{{ $summary['delivered'] ?? 0 }}</div>
                <div class="label">Terselesai</div>
            </div>
            <div class="summary-item partial">
                <div class="number">{{ $summary['partial'] ?? 0 }}</div>
                <div class="label">Sebagian</div>
            </div>
            <div class="summary-item rejected">
                <div class="number">{{ $summary['rejected'] ?? 0 }}</div>
                <div class="label">Ditolak</div>
            </div>
        </div>
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Tanggal</th>
                <th style="width: 15%;">No. Referensi</th>
                <th style="width: 20%;">Customer</th>
                <th style="width: 20%;">Gudang</th>
                <th style="width: 10%; text-align: center;">Status</th>
                <th style="width: 10%; text-align: right;">Total Qty</th>
                <th style="width: 15%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                <td><strong>{{ $item->no_referensi }}</strong></td>
                <td>{{ $item->customer->nama ?? '-' }}</td>
                <td>{{ $item->gudang->nama ?? '-' }}</td>
                <td style="text-align: center;">
                    @if($item->status === 'delivered')
                        <span class="status-delivered">✓ Selesai</span>
                    @elseif($item->status === 'partial')
                        <span class="status-partial">● Sebagian</span>
                    @elseif($item->status === 'rejected')
                        <span class="status-rejected">✗ Ditolak</span>
                    @else
                        <span class="status-approved">✓ Disetujui</span>
                    @endif
                </td>
                <td style="text-align: right; font-weight: bold;">
                    {{ number_format($item->details->sum('qty')) }}
                </td>
                <td>{{ $item->keterangan ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 30px;">
                    Tidak ada data untuk periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Laporan otomatis dicetak dari sistem Warehouse Management System</p>
        <p>Halaman 1 dari 1</p>
    </div>
</body>
</html>
