<x-app-layout :title="($pageTitle ?? 'Kalender Internal') . ' - CRM UCIC'">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $pageTitle ?? 'Kalender Internal' }}</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Daftar event dan jadwal kegiatan operasional Anda.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($events as $e)
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs relative overflow-hidden">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                            {{ $e->status === 'Selesai' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : '' }}
                            {{ $e->status === 'Sedang Berjalan' ? 'bg-blue-50 text-blue-700 border-blue-200' : '' }}
                            {{ $e->status === 'Rencana' ? 'bg-amber-50 text-amber-700 border-amber-200' : '' }}
                            {{ $e->status === 'Batal' ? 'bg-rose-50 text-rose-700 border-rose-200' : '' }} border">
                            {{ $e->status }}
                        </span>
                        <span class="text-xs font-semibold text-slate-500 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ \Carbon\Carbon::parse($e->tanggal_mulai)->format('d M Y H:i') }}
                        </span>
                    </div>
                    
                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $e->nama }}</h3>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex items-start gap-2 text-sm text-slate-600">
                            <svg class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span>{{ $e->lokasi }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 bg-white rounded-2xl border border-slate-200 border-dashed flex flex-col items-center justify-center text-slate-400">
                    <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <p>Belum ada event yang ditugaskan ke Anda.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
