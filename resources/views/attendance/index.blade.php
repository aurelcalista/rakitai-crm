@extends('layouts.app')

@section('title', 'Kelola Absensi')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Kelola Absensi</h1>
        <a href="{{ route('attendance.index', array_merge(request()->all(), ['print' => 1])) }}" target="_blank" class="px-4 py-2 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak Laporan
        </a>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white px-4 py-3 rounded-xl border border-slate-200 shadow-sm flex flex-row items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</span>
            <span class="text-2xl font-bold text-blue-600">{{ $summary['total'] }}</span>
        </div>
        <div class="bg-white px-4 py-3 rounded-xl border border-slate-200 shadow-sm flex flex-row items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Hadir</span>
            <span class="text-2xl font-bold text-green-600">{{ $summary['hadir'] }}</span>
        </div>
        <div class="bg-white px-4 py-3 rounded-xl border border-slate-200 shadow-sm flex flex-row items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Izin</span>
            <span class="text-2xl font-bold text-amber-500">{{ $summary['izin'] }}</span>
        </div>
        <div class="bg-white px-4 py-3 rounded-xl border border-slate-200 shadow-sm flex flex-row items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sakit</span>
            <span class="text-2xl font-bold text-orange-500">{{ $summary['sakit'] }}</span>
        </div>
        <div class="bg-white px-4 py-3 rounded-xl border border-slate-200 shadow-sm flex flex-row items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Alpa</span>
            <span class="text-2xl font-bold text-red-600">{{ $summary['tidak_hadir'] }}</span>
        </div>
    </div>

    <!-- FILTER -->
    <div class="crm-card bg-white p-4 sm:p-5">
        <form method="GET" action="{{ route('attendance.index') }}" x-data="{ tipeWaktu: '{{ request('tipe_waktu', '') }}' }" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Tipe Waktu -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Tipe Waktu</label>
                    <select name="tipe_waktu" x-model="tipeWaktu" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua Waktu</option>
                        <option value="harian">Harian</option>
                        <option value="mingguan">Mingguan</option>
                        <option value="bulanan">Bulanan</option>
                        <option value="tahunan">Tahunan</option>
                    </select>
                </div>

                <!-- Harian -->
                <div x-show="tipeWaktu === 'harian'" x-cloak>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Mingguan -->
                <div x-show="tipeWaktu === 'mingguan'" x-cloak class="col-span-1 lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Rentang Tanggal</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500">
                        <span class="text-slate-500 text-sm">s/d</span>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Bulanan -->
                <div x-show="tipeWaktu === 'bulanan'" x-cloak class="col-span-1 lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Bulan & Tahun</label>
                    <div class="flex items-center gap-2">
                        <select name="bulan" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="">Pilih Bulan</option>
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                        <input type="number" name="tahun" value="{{ request('tahun', date('Y')) }}" placeholder="Tahun" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Tahunan -->
                <div x-show="tipeWaktu === 'tahunan'" x-cloak>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Tahun</label>
                    <input type="number" name="tahun_only" value="{{ request('tahun_only', date('Y')) }}" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Nama -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Nama</label>
                    <input type="text" name="search_nama" value="{{ request('search_nama') }}" placeholder="Cari nama..." class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Role</label>
                    <select name="filter_role" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua Role</option>
                        <option value="Sales" {{ request('filter_role') == 'Sales' ? 'selected' : '' }}>Sales</option>
                        <option value="SPV" {{ request('filter_role') == 'SPV' ? 'selected' : '' }}>SPV</option>
                        <option value="CS" {{ request('filter_role') == 'CS' ? 'selected' : '' }}>CS</option>
                        <option value="EO" {{ request('filter_role') == 'EO' ? 'selected' : '' }}>EO</option>
                    </select>
                </div>

                <!-- Kehadiran -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Jenis Kehadiran</label>
                    <select name="filter_status" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua</option>
                        <option value="Hadir" {{ request('filter_status') == 'Hadir' ? 'selected' : '' }}>Hadir</option>
                        <option value="Izin" {{ request('filter_status') == 'Izin' ? 'selected' : '' }}>Izin</option>
                        <option value="Sakit" {{ request('filter_status') == 'Sakit' ? 'selected' : '' }}>Sakit</option>
                        <option value="Tidak Hadir" {{ request('filter_status') == 'Tidak Hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                    </select>
                </div>

                @if(in_array(auth()->user()->role, ['Admin']))
                <!-- HM -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">HM</label>
                    <select name="filter_hm" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua HM</option>
                        @foreach($hms as $hm)
                            <option value="{{ $hm->id }}" {{ request('filter_hm') == $hm->id ? 'selected' : '' }}>{{ $hm->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if(in_array(auth()->user()->role, ['Admin', 'HM']))
                <!-- SPV -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">SPV</label>
                    <select name="filter_spv" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua SPV</option>
                        @foreach($spvs as $spv)
                            <option value="{{ $spv->id }}" {{ request('filter_spv') == $spv->id ? 'selected' : '' }}>{{ $spv->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Wilayah -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Wilayah</label>
                    <select name="wilayah_id" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">Semua Wilayah</option>
                        @foreach($wilayahs as $w)
                            <option value="{{ $w->id }}" {{ request('wilayah_id') == $w->id ? 'selected' : '' }}>{{ $w->nama }}</option>
                        @endforeach
                    </select>
                </div>

            </div>
            
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <a href="{{ route('attendance.index') }}" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Reset</a>
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">Terapkan Filter</button>
            </div>
        </form>
    </div>

    <!-- REKAP PER ORANG -->
    <div class="crm-card bg-white p-0">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-800">Rekap Per Orang</h2>
        </div>
        <div class="max-h-80 overflow-y-auto">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 bg-slate-50 border-b border-slate-100 shadow-sm z-10">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Hadir</th>
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Izin</th>
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Sakit</th>
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Tidak Hadir</th>
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rekapPerOrang as $userId => $rekap)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-3 whitespace-nowrap text-sm font-semibold text-slate-900">{{ $rekap['user']->name ?? 'Unknown' }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-xs text-slate-500">{{ $rekap['user']->role ?? '-' }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-center font-medium text-green-600">{{ $rekap['Hadir'] }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-center font-medium text-amber-500">{{ $rekap['Izin'] }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-center font-medium text-orange-500">{{ $rekap['Sakit'] }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-center font-medium text-red-600">{{ $rekap['Tidak Hadir'] }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-center font-bold text-blue-600">{{ $rekap['Total'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-slate-500 text-sm">Tidak ada rekap data.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- DATA ABSENSI -->
    <div class="crm-card bg-white p-0">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-800">Data Absensi</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama & Role</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Wilayah</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Jenis Kehadiran</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Jam Absen</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($attendances as $attendance)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-slate-900">
                                    {{ \Carbon\Carbon::parse($attendance->date)->translatedFormat('d M Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-slate-900">{{ optional($attendance->user)->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-slate-500">{{ optional($attendance->user)->role ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if(in_array(optional($attendance->user)->role, ['CS', 'EO']))
                                    <span class="text-sm text-slate-400">-</span>
                                @else
                                    <div class="text-sm text-slate-700">
                                        @if($attendance->wilayah)
                                            {{ $attendance->wilayah->nama }}
                                        @else
                                            <span class="text-slate-400">Tidak ada</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $color = match($attendance->status) {
                                        'Hadir' => 'bg-green-100 text-green-700',
                                        'Izin' => 'bg-amber-100 text-amber-700',
                                        'Sakit' => 'bg-orange-100 text-orange-700',
                                        'Tidak Hadir' => 'bg-red-100 text-red-700',
                                        default => 'bg-slate-100 text-slate-700'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $color }}">
                                    {{ $attendance->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-700">
                                    {{ $attendance->time ? \Carbon\Carbon::parse($attendance->time)->format('H:i') : '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('attendance.show', $attendance->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition" title="Detail">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500 text-sm">
                                Belum ada data absensi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($attendances->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $attendances->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
