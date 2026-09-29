@extends('layouts.app', ['title' => 'Monitoring Rekap Presensi'])

@section('content')
<div class="space-y-6" x-data="{ detailModal: false, selectedItem: null }">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Monitoring Rekap Presensi</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] bg-blue-50 text-blue-700 font-bold border border-blue-200">
                    {{ auth()->user()->role }} Scope
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Pemantauan data kehadiran GPS dan foto selfie tim lapangan berdasarkan hierarki wewenang.</p>
        </div>
        <div class="text-xs text-slate-500">
            Total Tercatat: <strong class="text-slate-800">{{ $attendances->total() }}</strong> Data
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    <!-- Attendance Percentage & Summary Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-500 uppercase tracking-wider text-[11px]">Kehadiran Hari Ini</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $monitoringStats['attendance_rate_today'] >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                    {{ $monitoringStats['attendance_rate_today'] }}%
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                {{ $monitoringStats['present_today_count'] }} <span class="text-xs font-semibold text-slate-400">/ {{ $monitoringStats['total_personil'] }} Personil</span>
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-700" style="width: {{ $monitoringStats['attendance_rate_today'] }}%"></div>
            </div>
            <p class="text-[10px] text-slate-400">Tingkat kehadiran personil pada {{ $monitoringStats['target_date_label'] }}</p>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-500 uppercase tracking-wider text-[11px]">Validitas Presensi</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $monitoringStats['valid_rate'] >= 85 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                    {{ $monitoringStats['valid_rate'] }}%
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                {{ $monitoringStats['total_present'] }} <span class="text-xs font-semibold text-slate-400">/ {{ $monitoringStats['total_records'] }} Valid</span>
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-700" style="width: {{ $monitoringStats['valid_rate'] }}%"></div>
            </div>
            <p class="text-[10px] text-slate-400">Persentase presensi yang berada dalam radius GPS resmi</p>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-1">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[11px] block">Presensi Hadir</span>
            <div class="text-2xl sm:text-3xl font-black text-emerald-600 tracking-tight">
                {{ $monitoringStats['total_present'] }}
            </div>
            <p class="text-xs text-slate-500 font-medium">Presensi Terverifikasi</p>
            <p class="text-[10px] text-slate-400">Data selfie dan koordinat valid</p>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-1">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[11px] block">Presensi Ditolak</span>
            <div class="text-2xl sm:text-3xl font-black {{ $monitoringStats['total_rejected'] > 0 ? 'text-rose-600' : 'text-slate-400' }} tracking-tight">
                {{ $monitoringStats['total_rejected'] }}
            </div>
            <p class="text-xs text-slate-500 font-medium">Di Luar Radius</p>
            <p class="text-[10px] text-slate-400">Jarak melebihi batas radius</p>
        </div>
    </div>

    <!-- Filter Card (2 Baris Horizontal Layout) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('attendance.monitoring') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Baris 1 -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tanggal</label>
                <input type="date" name="date" value="{{ request('date') }}" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500 bg-slate-50/50">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Pengguna</label>
                <select name="user_id" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500 bg-slate-50/50">
                    <option value="">Semua Anggota</option>
                    @foreach($monitoredUsers as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->role }}) — {{ $u->attendance_percentage }}% Hadir
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Role</label>
                <select name="role" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500 bg-slate-50/50">
                    <option value="">Semua Role</option>
                    <option value="SPV" {{ request('role') === 'SPV' ? 'selected' : '' }}>SPV</option>
                    <option value="Sales" {{ request('role') === 'Sales' ? 'selected' : '' }}>Sales</option>
                    <option value="EO" {{ request('role') === 'EO' ? 'selected' : '' }}>EO</option>
                </select>
            </div>

            <!-- Baris 2 -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Titik Lokasi</label>
                <select name="location_id" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500 bg-slate-50/50">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status</label>
                <select name="status" class="w-full text-xs rounded-xl border-slate-200 py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500 bg-slate-50/50">
                    <option value="">Semua Status</option>
                    <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Hadir</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2.5 px-4 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition cursor-pointer shadow-xs">
                    Terapkan
                </button>
                <a href="{{ route('attendance.monitoring') }}" class="py-2.5 px-4 rounded-xl bg-slate-100 text-slate-600 text-xs font-semibold hover:bg-slate-200 transition text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Attendance Monitoring Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                    <tr>
                        <th class="py-3.5 px-4">Pengguna</th>
                        <th class="py-3.5 px-4">Role</th>
                        <th class="py-3.5 px-4">Tanggal & Jam</th>
                        <th class="py-3.5 px-4">Titik Lokasi</th>
                        <th class="py-3.5 px-4">Jarak</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Foto</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($attendances as $row)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-800">{{ $row->user?->name ?? 'User Tidak Diketahui' }}</div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-[11px] text-slate-400">{{ $row->user?->kode ?? '-' }}</span>
                                    @if(isset($userStatsMap[$row->user_id]))
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold {{ $userStatsMap[$row->user_id]['percentage'] >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}" title="{{ $userStatsMap[$row->user_id]['present_days'] }} hadir dari {{ $userStatsMap[$row->user_id]['work_days'] }} hari kerja bulan ini">
                                            {{ $userStatsMap[$row->user_id]['percentage'] }}% Hadir
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold border
                                    {{ $row->user?->role === 'SPV' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : '' }}
                                    {{ $row->user?->role === 'Sales' ? 'bg-blue-50 text-blue-700 border-blue-200' : '' }}
                                    {{ $row->user?->role === 'EO' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : '' }}">
                                    {{ $row->user?->role ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-800">{{ $row->check_in_at->timezone('Asia/Jakarta')->format('d M Y') }}</div>
                                <div class="text-[11px] text-slate-400">{{ $row->check_in_at->timezone('Asia/Jakarta')->format('H:i:s') }} WIB</div>
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-700">
                                {{ $row->location?->name ?? 'Titik Lokasi' }}
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                <span class="font-mono font-medium">{{ number_format($row->distance, 2) }}</span> m
                            </td>
                            <td class="py-3 px-4">
                                @if($row->status === 'present')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Hadir
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Ditolak
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <button type="button" @click="selectedItem = {
                                    userName: '{{ addslashes($row->user?->name ?? '-') }}',
                                    userRole: '{{ $row->user?->role ?? '-' }}',
                                    date: '{{ $row->check_in_at->timezone('Asia/Jakarta')->format('d M Y') }}',
                                    time: '{{ $row->check_in_at->timezone('Asia/Jakarta')->format('H:i:s') }} WIB',
                                    location: '{{ addslashes($row->location?->name ?? 'Lokasi') }}',
                                    lat: '{{ $row->latitude }}',
                                    lng: '{{ $row->longitude }}',
                                    distance: '{{ number_format($row->distance, 2) }} m',
                                    status: '{{ $row->status === 'present' ? 'Hadir' : 'Ditolak' }}',
                                    notes: '{{ addslashes($row->notes ?? '-') }}',
                                    photoUrl: '{{ route('attendance.photo', $row->id) }}'
                                }; detailModal = true;" class="relative group cursor-pointer">
                                    <img src="{{ route('attendance.photo', $row->id) }}" alt="Foto Selfie" class="w-9 h-9 rounded-lg object-cover border border-slate-200 group-hover:scale-105 transition">
                                </button>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <button type="button" @click="selectedItem = {
                                    userName: '{{ addslashes($row->user?->name ?? '-') }}',
                                    userRole: '{{ $row->user?->role ?? '-' }}',
                                    date: '{{ $row->check_in_at->timezone('Asia/Jakarta')->format('d M Y') }}',
                                    time: '{{ $row->check_in_at->timezone('Asia/Jakarta')->format('H:i:s') }} WIB',
                                    location: '{{ addslashes($row->location?->name ?? 'Lokasi') }}',
                                    lat: '{{ $row->latitude }}',
                                    lng: '{{ $row->longitude }}',
                                    distance: '{{ number_format($row->distance, 2) }} m',
                                    status: '{{ $row->status === 'present' ? 'Hadir' : 'Ditolak' }}',
                                    notes: '{{ addslashes($row->notes ?? '-') }}',
                                    photoUrl: '{{ route('attendance.photo', $row->id) }}'
                                }; detailModal = true;" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 transition">
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-xs text-slate-400">
                                Tidak ada data absensi yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

    <!-- Detail Monitoring Modal -->
    <div x-show="detailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="detailModal = false"></div>
            
            <div class="relative inline-block w-full max-w-lg p-6 overflow-hidden text-left align-middle bg-white rounded-2xl shadow-xl transform transition-all border border-slate-100">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Detail Monitoring Presensi</h3>
                        <span class="text-[11px] text-slate-400" x-text="selectedItem ? selectedItem.userName + ' (' + selectedItem.userRole + ')' : ''"></span>
                    </div>
                    <button type="button" @click="detailModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <template x-if="selectedItem">
                    <div class="mt-4 space-y-4 text-xs">
                        <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50 flex items-center justify-center">
                            <img :src="selectedItem.photoUrl" alt="Selfie Absensi" class="w-full max-h-72 object-cover">
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] text-slate-400 block font-semibold uppercase">Waktu Check-in</span>
                                <span class="font-bold text-slate-800" x-text="selectedItem.date + ', ' + selectedItem.time"></span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] text-slate-400 block font-semibold uppercase">Titik Lokasi</span>
                                <span class="font-bold text-slate-800" x-text="selectedItem.location"></span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] text-slate-400 block font-semibold uppercase">Jarak Terverifikasi</span>
                                <span class="font-bold text-slate-800" x-text="selectedItem.distance"></span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] text-slate-400 block font-semibold uppercase">Koordinat GPS</span>
                                <span class="font-mono text-[11px] text-slate-800 font-medium" x-text="selectedItem.lat + ', ' + selectedItem.lng"></span>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                            <span class="text-[11px] text-slate-600 font-medium">Status Validasi Radius Server:</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                  :class="selectedItem.status === 'Hadir' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                  x-text="selectedItem.status"></span>
                        </div>

                        <template x-if="selectedItem.notes && selectedItem.notes !== '-'">
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] text-slate-400 block font-semibold uppercase">Catatan Petugas</span>
                                <p class="text-slate-700 mt-0.5" x-text="selectedItem.notes"></p>
                            </div>
                        </template>
                    </div>
                </template>

                <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end">
                    <button type="button" @click="detailModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
