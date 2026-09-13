@php
    $pageTitle = 'Laporan Kunjungan Offline';
    $pageSubtitle = 'Dokumentasi Audiensi Sekolah & Kerjasama Corporate';
@endphp

<x-app-layout :title="'Kunjungan - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak>
        <x-loading-skeleton type="cards" />
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak>
        <x-empty-state 
            title="Belum ada data kunjungan" 
            description="Mulai catat kunjungan sekolah atau perusahaan untuk mendokumentasikan kegiatan sales."
            actionLabel="Tambah Kunjungan"
            actionClick="modalTambahKunjungan = true"
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak>
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6" x-data="{
        kunjunganFilter: 'all',
        visitsList: {{ json_encode($visits) }},
        get filteredVisits() {
            if (this.kunjunganFilter === 'all') return this.visitsList;
            return this.visitsList.filter(v => v.type.toLowerCase() === this.kunjunganFilter.toLowerCase());
        }
    }">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Kunjungan Sekolah & Corporate</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Dokumentasi hasil kunjungan lapangan, presentasi edu-fair, dan audiensi kemitraan.</p>
            </div>
            <div>
                <button 
                    type="button" 
                    @click="modalTambahKunjungan = true"
                    class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>+ Tambah Kunjungan</span>
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="crm-card bg-white p-4 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2">
                <span class="text-slate-400 font-semibold uppercase text-[11px]">Filter Kategori:</span>
                <button 
                    type="button" 
                    @click="kunjunganFilter = 'all'"
                    :class="kunjunganFilter === 'all' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg transition"
                >
                    Semua Kunjungan
                </button>
                <button 
                    type="button" 
                    @click="kunjunganFilter = 'sekolah'"
                    :class="kunjunganFilter === 'sekolah' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg transition"
                >
                    Sekolah (SMA/SMK)
                </button>
                <button 
                    type="button" 
                    @click="kunjunganFilter = 'corporate'"
                    :class="kunjunganFilter === 'corporate' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg transition"
                >
                    Corporate
                </button>
            </div>

            <div class="text-slate-400 font-medium text-[11px]">
                * Dokumentasi foto resmi tanpa tracking GPS/Lokasi
            </div>
        </div>

        <!-- Visits List Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="visit in filteredVisits" :key="visit.id">
                <div class="crm-card bg-white overflow-hidden flex flex-col justify-between hover:border-blue-300 transition group">
                    
                    <div>
                        <!-- Photo Documentation Preview -->
                        <div class="h-44 w-full bg-slate-100 relative overflow-hidden">
                            <img :src="visit.photo" :alt="visit.name" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            <div class="absolute top-3 left-3">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-slate-900/75 backdrop-blur-md text-white border border-white/20" x-text="visit.type"></span>
                            </div>
                            <div class="absolute bottom-3 right-3">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-medium bg-white/90 backdrop-blur-md text-slate-800 shadow-xs" x-text="visit.date"></span>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="p-5 space-y-3 text-xs">
                            <div>
                                <h3 class="font-bold text-sm text-slate-900 line-clamp-1" x-text="visit.name"></h3>
                                <p class="text-slate-500 text-[11px] mt-0.5 line-clamp-1" x-text="visit.address"></p>
                            </div>

                            <div class="space-y-1.5 pt-2 border-t border-slate-100 text-slate-600">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">PIC / Kontak:</span>
                                    <span class="font-semibold text-slate-800" x-text="visit.pic"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Sales Incharge:</span>
                                    <span class="font-semibold text-blue-600" x-text="visit.sales"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Potensi:</span>
                                    <span class="font-semibold text-slate-800" x-text="visit.potential"></span>
                                </div>
                            </div>

                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-slate-600 italic leading-relaxed text-[11px]">
                                "<span x-text="visit.notes"></span>"
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400 font-medium" x-text="visit.time"></span>
                        <button 
                            type="button" 
                            @click="$store.crm.showToast('Detail dokumentasi foto siap diunduh')"
                            class="text-xs font-bold text-blue-600 hover:text-blue-700 cursor-pointer"
                        >
                            Lihat Foto &rarr;
                        </button>
                    </div>

                </div>
            </template>
        </div>

    </div>

</x-app-layout>
