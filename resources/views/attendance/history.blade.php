@extends('layouts.app', ['title' => 'Riwayat Absensi Saya'])

@section('content')
<div class="space-y-6" x-data="{ detailModal: false, selectedItem: null }">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Riwayat Absensi</h1>
            <p class="text-xs text-slate-500 mt-1">Daftar rekaman presensi kehadiran mandiri Anda.</p>
        </div>
        <div>
            <a href="{{ route('attendance.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-xs transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Lakukan Absen Hari Ini
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    <!-- Summary & Attendance Percentage Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-500 uppercase tracking-wider text-[11px]">Persentase Kehadiran</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $userStats['percentage'] >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                    {{ $userStats['percentage'] }}%
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                {{ $userStats['percentage'] }}%
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="{{ $userStats['percentage'] >= 80 ? 'bg-emerald-500' : 'bg-amber-500' }} h-1.5 rounded-full transition-all duration-700" style="width: {{ $userStats['percentage'] }}%"></div>
            </div>
            <p class="text-[10px] text-slate-400">Periode {{ $userStats['month_name'] }}</p>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-1">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[11px] block">Hari Hadir</span>
            <div class="text-2xl sm:text-3xl font-black text-emerald-600 tracking-tight">
                {{ $userStats['present_days'] }} <span class="text-xs font-semibold text-slate-400">Hari</span>
            </div>
            <p class="text-xs text-slate-500 font-medium">Presensi Berhasil</p>
            <p class="text-[10px] text-slate-400">Tercatat dalam radius GPS</p>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-1">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[11px] block">Hari Kerja Berjalan</span>
            <div class="text-2xl sm:text-3xl font-black text-blue-600 tracking-tight">
                {{ $userStats['work_days'] }} <span class="text-xs font-semibold text-slate-400">Hari</span>
            </div>
            <p class="text-xs text-slate-500 font-medium">Senin - Jumat</p>
            <p class="text-[10px] text-slate-400">Total sebulan: {{ $userStats['total_work_days_in_month'] }} hari</p>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-1">
            <span class="font-bold text-slate-500 uppercase tracking-wider text-[11px] block">Presensi Ditolak</span>
            <div class="text-2xl sm:text-3xl font-black {{ $userStats['rejected_count'] > 0 ? 'text-rose-600' : 'text-slate-400' }} tracking-tight">
                {{ $userStats['rejected_count'] }} <span class="text-xs font-semibold text-slate-400">Kali</span>
            </div>
            <p class="text-xs text-slate-500 font-medium">Di Luar Radius</p>
            <p class="text-[10px] text-slate-400">Gagal verifikasi jarak GPS</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('attendance.history') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Bulan</label>
                <input type="month" name="month" value="{{ request('month') }}" class="w-full text-xs rounded-xl border-slate-200 py-2 px-3">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Titik Lokasi</label>
                <select name="location_id" class="w-full text-xs rounded-xl border-slate-200 py-2 px-3">
                    <option value="">Semua Lokasi</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Status</label>
                <select name="status" class="w-full text-xs rounded-xl border-slate-200 py-2 px-3">
                    <option value="">Semua Status</option>
                    <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Hadir</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition">
                    Filter
                </button>
                <a href="{{ route('attendance.history') }}" class="px-3 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-semibold hover:bg-slate-200 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Attendance Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                    <tr>
                        <th class="py-3.5 px-4">Tanggal & Waktu</th>
                        <th class="py-3.5 px-4">Lokasi Absensi</th>
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
                                <div class="font-bold text-slate-800">{{ $row->check_in_at->timezone('Asia/Jakarta')->format('d M Y') }}</div>
                                <div class="text-[11px] text-slate-400">{{ $row->check_in_at->timezone('Asia/Jakarta')->format('H:i') }} WIB</div>
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-700">
                                {{ $row->location?->name ?? 'Titik Lokasi' }}
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                <span class="font-mono">{{ number_format($row->distance, 2) }}</span> m
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
                                    date: '{{ $row->check_in_at->timezone('Asia/Jakarta')->format('d M Y') }}',
                                    time: '{{ $row->check_in_at->timezone('Asia/Jakarta')->format('H:i') }} WIB',
                                    location: '{{ addslashes($row->location?->name ?? 'Lokasi') }}',
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
                                    date: '{{ $row->check_in_at->timezone('Asia/Jakarta')->format('d M Y') }}',
                                    time: '{{ $row->check_in_at->timezone('Asia/Jakarta')->format('H:i') }} WIB',
                                    location: '{{ addslashes($row->location?->name ?? 'Lokasi') }}',
                                    distance: '{{ number_format($row->distance, 2) }} m',
                                    status: '{{ $row->status === 'present' ? 'Hadir' : 'Ditolak' }}',
                                    notes: '{{ addslashes($row->notes ?? '-') }}',
                                    photoUrl: '{{ route('attendance.photo', $row->id) }}'
                                }; detailModal = true;" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 transition">
                                    Lihat Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-xs text-slate-400">
                                Belum ada riwayat absensi yang tercatat.
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

    <!-- Detail Modal -->
    <div x-show="detailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="detailModal = false"></div>
            
            <div class="relative inline-block w-full max-w-md p-6 overflow-hidden text-left align-middle bg-white rounded-2xl shadow-xl transform transition-all border border-slate-100">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900">Detail Bukti Presensi</h3>
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
                                <span class="text-[10px] text-slate-400 block font-semibold uppercase">Lokasi Titik</span>
                                <span class="font-bold text-slate-800" x-text="selectedItem.location"></span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] text-slate-400 block font-semibold uppercase">Jarak Terverifikasi</span>
                                <span class="font-bold text-slate-800" x-text="selectedItem.distance"></span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] text-slate-400 block font-semibold uppercase">Status</span>
                                <span class="font-bold" :class="selectedItem.status === 'Hadir' ? 'text-emerald-700' : 'text-rose-700'" x-text="selectedItem.status"></span>
                            </div>
                        </div>

                        <template x-if="selectedItem.notes && selectedItem.notes !== '-'">
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-[10px] text-slate-400 block font-semibold uppercase">Catatan</span>
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
