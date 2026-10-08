@extends('layouts.app')

@section('title', 'Detail Absensi')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('attendance.index') }}" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-slate-900">Detail Absensi</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="crm-card bg-white p-6 space-y-6">
            <h3 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-4">Informasi Absensi</h3>
            
            <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                <div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Nama User</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $attendance->user->name }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Role</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $attendance->user->role }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Tanggal</div>
                    <div class="text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($attendance->date)->translatedFormat('d F Y') }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Jam Absen</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $attendance->time ? \Carbon\Carbon::parse($attendance->time)->format('H:i') : '-' }}</div>
                </div>
                
                @if(!in_array($attendance->user->role, ['CS', 'EO']))
                <div class="col-span-2">
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Wilayah</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $attendance->wilayah ? $attendance->wilayah->nama : '-' }}</div>
                </div>
                @endif
                
                <div class="col-span-2">
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Status Kehadiran</div>
                    @php
                        $color = match($attendance->status) {
                            'Hadir' => 'bg-green-100 text-green-700',
                            'Izin' => 'bg-amber-100 text-amber-700',
                            'Sakit' => 'bg-orange-100 text-orange-700',
                            'Tidak Hadir' => 'bg-red-100 text-red-700',
                            default => 'bg-slate-100 text-slate-700'
                        };
                    @endphp
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $color }}">
                        {{ $attendance->status }}
                    </span>
                </div>

                @if($attendance->notes)
                <div class="col-span-2">
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Keterangan</div>
                    <div class="text-sm text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-100">
                        {{ $attendance->notes }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if($attendance->photo)
        <div class="crm-card bg-white p-6 space-y-6">
            <h3 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-4">
                @if($attendance->status === 'Sakit')
                    Foto Surat Dokter
                @elseif($attendance->status === 'Izin')
                    Foto Pendukung Izin
                @else
                    Foto Bukti Hadir
                @endif
            </h3>
            
            <div class="rounded-xl overflow-hidden border border-slate-200">
                <img src="{{ asset('storage/' . $attendance->photo) }}" alt="Foto Absensi" class="w-full h-auto object-cover max-h-[500px]">
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
