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
            background: #2196F3; 
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
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin: 20px 0;
        }
        .summary-item {
            text-align: center;
            padding: 8px;
            border-radius: 4px;
        }
        .hadir { background: #e8f5e9; }
        .terlambat { background: #fff9c4; }
        .sakit { background: #e3f2fd; }
        .izin { background: #f3e5f5; }
        .cuti { background: #e1f5fe; }
        .alpha { background: #ffebee; }
        
        .summary-item .number {
            font-size: 20px;
            font-weight: bold;
        }
        .hadir .number { color: #2e7d32; }
        .terlambat .number { color: #f57c00; }
        .sakit .number { color: #1976d2; }
        .izin .number { color: #7b1fa2; }
        .cuti .number { color: #0288d1; }
        .alpha .number { color: #c62828; }
        
        .summary-item .label {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }
        .footer {
            margin-top: 40px;
            text-align: right;
            font-size: 11px;
            color: #999;
        }
        .status-hadir { color: green; font-weight: bold; }
        .status-terlambat { color: orange; font-weight: bold; }
        .status-sakit { color: blue; font-weight: bold; }
        .status-izin { color: purple; font-weight: bold; }
        .status-cuti { color: darkblue; font-weight: bold; }
        .status-alpha { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Periode: {{ $dateRange }}</p>
        <p>Dicetak pada: {{ now()->format('d F Y H:i') }}</p>
    </div>

    @if(isset($summary))
    <div style="background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
        <h3 style="margin: 0 0 15px 0; font-size: 14px;">Statistik Kehadiran</h3>
        <div class="summary-grid">
            <div class="summary-item hadir">
                <div class="number">{{ $summary['hadir'] ?? 0 }}</div>
                <div class="label">Hadir</div>
            </div>
            <div class="summary-item terlambat">
                <div class="number">{{ $summary['terlambat'] ?? 0 }}</div>
                <div class="label">Terlambat</div>
            </div>
            <div class="summary-item sakit">
                <div class="number">{{ $summary['sakit'] ?? 0 }}</div>
                <div class="label">Sakit</div>
            </div>
            <div class="summary-item izin">
                <div class="number">{{ $summary['izin'] ?? 0 }}</div>
                <div class="label">Izin</div>
            </div>
            <div class="summary-item cuti">
                <div class="number">{{ $summary['cuti'] ?? 0 }}</div>
                <div class="label">Cuti</div>
            </div>
            <div class="summary-item alpha">
                <div class="number">{{ $summary['alpha'] ?? 0 }}</div>
                <div class="label">Alpha</div>
            </div>
        </div>
        <p style="text-align: right; font-weight: bold; margin-top: 10px;">
            Total Rekor: {{ number_format($summary['total_records'] ?? 0) }}
        </p>
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Tanggal</th>
                <th style="width: 20%;">Petugas</th>
                <th style="width: 12%;">Shift</th>
                <th style="width: 15%;">Gudang</th>
                <th style="width: 10%;">Jam Masuk</th>
                <th style="width: 10%;">Jam Pulang</th>
                <th style="width: 12%; text-align: center;">Status</th>
                <th style="width: 13%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                <td><strong>{{ $item->user->name ?? '-' }}</strong></td>
                <td>{{ $item->shift->nama ?? '-' }}</td>
                <td>{{ $item->gudang->nama ?? '-' }}</td>
                <td style="text-align: center;">
                    @if($item->jam_masuk)
                        {{ \Carbon\Carbon::parse($item->jam_masuk)->format('H:i') }}
                    @else
                        -
                    @endif
                </td>
                <td style="text-align: center;">
                    @if($item->jam_pulang)
                        {{ \Carbon\Carbon::parse($item->jam_pulang)->format('H:i') }}
                    @else
                        -
                    @endif
                </td>
                <td style="text-align: center;">
                    @if($item->status === 'hadir')
                        <span class="status-hadir">✓ Hadir</span>
                    @elseif($item->status === 'terlambat')
                        <span class="status-terlambat">● Terlambat</span>
                    @elseif($item->status === 'sakit')
                        <span class="status-sakit">✉ Sakit</span>
                    @elseif($item->status === 'izin')
                        <span class="status-izin">📝 Izin</span>
                    @elseif($item->status === 'cuti')
                        <span class="status-cuti">🏖 Cuti</span>
                    @elseif($item->status === 'alpha')
                        <span class="status-alpha">⚠ Alpha</span>
                    @endif
                </td>
                <td>{{ $item->keterangan ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 30px;">
                    Tidak ada data absensi untuk periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Laporan otomatis dicetak dari sistem Warehouse Management System</p>
        <p>Dokumen resmi untuk arsip dan audit</p>
    </div>
</body>
</html>
