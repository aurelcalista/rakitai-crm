<x-app-layout title="Dashboard Event Organizer">
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white px-5 py-4 sm:px-6 rounded-2xl border border-slate-200/80 shadow-xs mb-6">
    <div>
        <div class="flex items-center gap-2 flex-wrap">
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Dashboard Event Organizer </h1>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">Event Organizer</span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">Ringkasan aktivitas dan jadwal event Universitas Catur Insan Cendekia.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('eo.events.index') }}" class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ Kelola Event</span>
        </a>
    </div>
</div>

<!-- Metrics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Event</p>
                <h3 class="text-2xl font-bold text-slate-900">{{ $totalEvents }}</h3>
            </div>
            <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Event Hari Ini</p>
                <h3 class="text-2xl font-bold text-slate-900">{{ $todayEventsCount }}</h3>
            </div>
            <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Mendatang</p>
                <h3 class="text-2xl font-bold text-slate-900">{{ $upcomingEventsCount }}</h3>
            </div>
            <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Selesai</p>
                <h3 class="text-2xl font-bold text-slate-900">{{ $completedEventsCount }}</h3>
            </div>
            <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Event Mendatang Terdekat -->
    <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col h-full">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="font-bold text-slate-900">Agenda Terdekat</h3>
                <p class="text-xs text-slate-500 mt-0.5">Jadwal event yang akan datang</p>
            </div>
            <a href="{{ route('eo.events.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Lihat Semua</a>
        </div>
        <div class="p-6 flex-1">
            @forelse($upcomingEvents as $event)
                <div class="mb-4 pb-4 border-b border-slate-100 last:mb-0 last:pb-0 last:border-0 flex items-start gap-4">
                    <div class="flex-shrink-0 w-14 text-center">
                        <div class="text-xs font-bold uppercase text-slate-500 mb-1">{{ \Carbon\Carbon::parse($event->tanggal_mulai)->translatedFormat('M') }}</div>
                        <div class="text-2xl font-black text-slate-900 leading-none">{{ \Carbon\Carbon::parse($event->tanggal_mulai)->format('d') }}</div>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-slate-900 text-base mb-1">{{ $event->nama ?? $event->name }}</h4>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ \Carbon\Carbon::parse($event->tanggal_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($event->tanggal_selesai ?? $event->tanggal_mulai)->format('H:i') }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                {{ $event->lokasi }}
                            </span>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700">
                                {{ $event->type ? $event->type->nama : 'Umum' }}
                            </span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <span class="text-xs font-medium text-slate-600">{{ $event->spvs->count() }} SPV ditugaskan</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center h-full py-10 text-slate-500">
                    <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <span class="font-medium text-sm">Tidak ada event terdekat.</span>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Jenis Event & Status -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col h-full">
        <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-slate-900">Distribusi Event</h3>
            <p class="text-xs text-slate-500 mt-0.5">Berdasarkan jenis kegiatan</p>
        </div>
        <div class="p-6 flex-1 flex flex-col justify-center">
            @if($eventsByType->isEmpty())
                <div class="text-center text-slate-500 py-6">
                    <span class="text-sm font-medium">Belum ada data event.</span>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($eventsByType as $typeData)
                        <div>
                            <div class="flex justify-between items-end mb-1.5">
                                <span class="text-sm font-semibold text-slate-700">{{ $typeData->type ? $typeData->type->nama : 'Lainnya' }}</span>
                                <span class="text-xs font-bold text-slate-900">{{ $typeData->count }} <span class="text-slate-400 font-normal">event</span></span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $totalEvents > 0 ? ($typeData->count / $totalEvents) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            
            <div class="mt-8 pt-6 border-t border-slate-100">
                <a href="{{ route('calendar.index') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-white text-slate-700 border border-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 hover:text-blue-600 transition shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Buka Kalender Internal
                </a>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
