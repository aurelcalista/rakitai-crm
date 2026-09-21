@php
    $pageTitle = 'Detail Kunjungan';
    $pageSubtitle = $visit['nomor'];
@endphp

<x-app-layout :title="'Detail Kunjungan - CRM UCIC'">

    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <a href="{{ url()->previous() }}" class="p-1.5 rounded-lg bg-slate-100 text-slate-500 hover:text-slate-700 hover:bg-slate-200 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </a>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $visit['name'] }}</h2>
                </div>
                <div class="flex items-center gap-2 mt-2 ml-10">
                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">{{ $visit['type'] }}</span>
                    <span class="text-xs text-slate-500">{{ $visit['nomor'] }}</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1.5 rounded-xl text-xs font-bold {{ $visit['status'] === 'Selesai' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-700 border-slate-200' }} border">
                    {{ $visit['status'] }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-6">
                <div class="crm-card bg-white p-5 sm:p-6">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-100 pb-3">Informasi Kunjungan</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6 text-sm">
                        <div>
                            <span class="block text-xs font-medium text-slate-400 mb-1">Tanggal & Waktu</span>
                            <span class="font-bold text-slate-800">{{ $visit['date'] }}, {{ $visit['time'] }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-slate-400 mb-1">Sales / PIC Internal</span>
                            <span class="font-bold text-slate-800">{{ $visit['sales'] }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-slate-400 mb-1">Nama PIC Institusi</span>
                            <span class="font-bold text-slate-800">{{ $visit['pic'] }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-slate-400 mb-1">WhatsApp PIC</span>
                            <span class="font-bold text-emerald-600">{{ $visit['whatsapp'] }}</span>
                        </div>
                        <div class="md:col-span-2">
                            <span class="block text-xs font-medium text-slate-400 mb-1">Alamat Institusi</span>
                            <span class="font-medium text-slate-700">{{ $visit['address'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="crm-card bg-white p-5 sm:p-6">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-100 pb-3">Hasil & Potensi</h3>
                    
                    <div class="space-y-4 text-sm">
                        @if($visit['type'] === 'Sekolah')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 rounded-xl bg-blue-50/50 border border-blue-100">
                                    <span class="block text-xs font-medium text-slate-500 mb-1">Potensi Beasiswa</span>
                                    <span class="font-bold text-slate-800">{{ $visit['potensi_beasiswa'] }}</span>
                                </div>
                                <div class="md:col-span-2 p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="block text-xs font-medium text-slate-500 mb-1">Detail Potensi</span>
                                    <span class="font-medium text-slate-700">{{ $visit['detail_beasiswa'] }}</span>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="p-4 rounded-xl bg-blue-50/50 border border-blue-100">
                                    <span class="block text-xs font-medium text-slate-500 mb-1">Potensi Kuliah S1</span>
                                    <span class="font-bold text-slate-800">{{ $visit['potensi_s1'] }}</span>
                                </div>
                                <div class="p-4 rounded-xl bg-indigo-50/50 border border-indigo-100">
                                    <span class="block text-xs font-medium text-slate-500 mb-1">Potensi Kuliah S2</span>
                                    <span class="font-bold text-slate-800">{{ $visit['potensi_s2'] }}</span>
                                </div>
                                <div class="p-4 rounded-xl bg-emerald-50/50 border border-emerald-100">
                                    <span class="block text-xs font-medium text-slate-500 mb-1">Kerjasama CSR</span>
                                    <span class="font-bold text-slate-800">{{ $visit['potensi_csr'] }}</span>
                                </div>
                                <div class="md:col-span-3 p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <span class="block text-xs font-medium text-slate-500 mb-1">Bidang Usaha</span>
                                    <span class="font-medium text-slate-700">{{ $visit['bidang_usaha'] }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="mt-6">
                            <span class="block text-xs font-medium text-slate-400 mb-2">Catatan Kunjungan</span>
                            <div class="p-4 bg-amber-50/50 border border-amber-100 rounded-xl text-amber-900 leading-relaxed italic whitespace-pre-wrap">
                                {{ $visit['notes'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Photo -->
            <div class="space-y-6">
                <div class="crm-card bg-white p-5">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-100 pb-3">Dokumentasi Foto</h3>
                    
                    @if($visit['photo'])
                        <div class="rounded-xl overflow-hidden border border-slate-200">
                            <a href="{{ $visit['photo'] }}" target="_blank" class="block group relative">
                                <img src="{{ $visit['photo'] }}" alt="Foto Kunjungan" class="w-full object-cover group-hover:opacity-90 transition">
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-slate-900/20 transition">
                                    <span class="px-3 py-1.5 bg-white text-slate-900 text-xs font-bold rounded-lg shadow-lg">Buka Foto Penuh</span>
                                </div>
                            </a>
                        </div>
                    @else
                        <div class="w-full h-48 rounded-xl bg-slate-50 border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400">
                            <svg class="w-8 h-8 mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2-2v12a2 2 0 002 2z" /></svg>
                            <span class="text-xs font-medium">Tidak ada dokumentasi foto</span>
                        </div>
                    @endif
                    
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <span class="block text-xs font-medium text-slate-400 mb-1">Dibuat pada</span>
                        <span class="text-xs font-bold text-slate-700">{{ $visit['created_at'] }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

</x-app-layout>
