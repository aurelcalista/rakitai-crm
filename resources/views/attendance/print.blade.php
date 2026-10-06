<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Absensi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .header p {
            margin: 0;
            color: #555;
        }
        .filter-info {
            margin-bottom: 20px;
        }
        .filter-info p {
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 10px; }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        @media print {
            body { padding: 0; }
            button { display: none; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" style="padding: 8px 16px; background: #0ea5e9; color: white; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 20px;">
        Print Laporan
    </button>

    <div class="header">
        <h1>LAPORAN ABSENSI</h1>
        <p>Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}</p>
    </div>

    <div class="filter-info">
        @php
            $periodText = 'Semua Waktu';
            if (request('tipe_waktu') === 'harian' && request('tanggal')) {
                $periodText = \Carbon\Carbon::parse(request('tanggal'))->translatedFormat('d F Y');
            } elseif (request('tipe_waktu') === 'mingguan' && request('start_date') && request('end_date')) {
                $periodText = \Carbon\Carbon::parse(request('start_date'))->translatedFormat('d F Y') . ' s/d ' . \Carbon\Carbon::parse(request('end_date'))->translatedFormat('d F Y');
            } elseif (request('tipe_waktu') === 'bulanan' && request('bulan') && request('tahun')) {
                $periodText = \Carbon\Carbon::create()->month(request('bulan'))->translatedFormat('F') . ' ' . request('tahun');
            } elseif (request('tipe_waktu') === 'tahunan' && request('tahun_only')) {
                $periodText = 'Tahun ' . request('tahun_only');
            }
        @endphp
        <p><strong>Periode:</strong> {{ $periodText }}</p>
        @if(request('filter_role')) <p><strong>Role:</strong> {{ request('filter_role') }}</p> @endif
        @if(request('filter_status')) <p><strong>Kehadiran:</strong> {{ request('filter_status') }}</p> @endif
        @if(request('search_nama')) <p><strong>Nama:</strong> {{ request('search_nama') }}</p> @endif
    </div>

    <div class="section-title">Ringkasan Absensi</div>
    <table>
        <thead>
            <tr>
                <th>Total Absen</th>
                <th class="text-center">Hadir</th>
                <th class="text-center">Izin</th>
                <th class="text-center">Sakit</th>
                <th class="text-center">Tidak Hadir</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold">{{ $summary['total'] }}</td>
                <td class="text-center">{{ $summary['hadir'] }}</td>
                <td class="text-center">{{ $summary['izin'] }}</td>
                <td class="text-center">{{ $summary['sakit'] }}</td>
                <td class="text-center">{{ $summary['tidak_hadir'] }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Rekap Per Orang</div>
    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Role</th>
                <th class="text-center">Hadir</th>
                <th class="text-center">Izin</th>
                <th class="text-center">Sakit</th>
                <th class="text-center">Tidak Hadir</th>
                <th class="text-center">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekapPerOrang as $userId => $rekap)
                <tr>
                    <td>{{ $rekap['user']->name ?? 'Unknown' }}</td>
                    <td>{{ $rekap['user']->role ?? '-' }}</td>
                    <td class="text-center">{{ $rekap['Hadir'] }}</td>
                    <td class="text-center">{{ $rekap['Izin'] }}</td>
                    <td class="text-center">{{ $rekap['Sakit'] }}</td>
                    <td class="text-center">{{ $rekap['Tidak Hadir'] }}</td>
                    <td class="text-center font-bold">{{ $rekap['Total'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada rekap data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Data Absensi Detail</div>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Nama</th>
                <th>Role</th>
                <th>Wilayah</th>
                <th>Kehadiran</th>
                <th>Jam</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $attendance)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($attendance->date)->translatedFormat('d M Y') }}</td>
                    <td>{{ optional($attendance->user)->name ?? 'Unknown' }}</td>
                    <td>{{ optional($attendance->user)->role ?? '-' }}</td>
                    <td>
                        @if(in_array(optional($attendance->user)->role, ['CS', 'EO']))
                            -
                        @else
                            {{ optional($attendance->wilayah)->nama ?? 'Tidak ada' }}
                        @endif
                    </td>
                    <td>{{ $attendance->status }}</td>
                    <td>{{ $attendance->time ? \Carbon\Carbon::parse($attendance->time)->format('H:i') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Belum ada data absensi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        window.onload = function() {
            // Uncomment line below to auto print when opened
            // window.print();
        }
    </script>
</body>
</html>
