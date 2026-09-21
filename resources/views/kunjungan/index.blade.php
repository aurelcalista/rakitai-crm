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
        selectedVisit: null,
        modalDetailVisit: false,
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
            @if(in_array(auth()->user()->role ?? '', ['Sales']))
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
            @endif
        </div>

        <!-- Filter Bar -->
        <div class="crm-card bg-white p-4 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2">
                <span class="text-slate-400 font-semibold uppercase text-[11px]">Filter Kategori:</span>
                <button 
                    type="button" 
                    @click="kunjunganFilter = 'all'"
                    :class="kunjunganFilter === 'all' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg transition cursor-pointer"
                >
                    Semua Kunjungan
                </button>
                <button 
                    type="button" 
                    @click="kunjunganFilter = 'sekolah'"
                    :class="kunjunganFilter === 'sekolah' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg transition cursor-pointer"
                >
                    Sekolah (SMA/SMK)
                </button>
                <button 
                    type="button" 
                    @click="kunjunganFilter = 'corporate'"
                    :class="kunjunganFilter === 'corporate' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    class="px-3 py-1.5 rounded-lg transition cursor-pointer"
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
                <div class="crm-card bg-white overflow-hidden flex flex-col justify-between hover:border-blue-300 transition group shadow-xs">
                    
                    <div>
                        <!-- Photo Documentation Preview -->
                        <div class="h-44 w-full bg-slate-100 relative overflow-hidden cursor-pointer" @click="selectedVisit = visit; modalDetailVisit = true">
                            <template x-if="visit.photo">
                                <img :src="visit.photo" :alt="visit.name" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            </template>
                            <template x-if="!visit.photo">
                                <div class="w-full h-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center group-hover:scale-105 transition duration-300">
                                    <svg class="w-10 h-10 text-slate-400/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2-2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </template>
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
                                <h3 
                                    class="font-bold text-sm text-slate-900 line-clamp-1 hover:text-blue-600 transition cursor-pointer" 
                                    x-text="visit.name"
                                    @click="selectedVisit = visit; modalDetailVisit = true"
                                ></h3>
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
                            @click="selectedVisit = visit; modalDetailVisit = true"
                            class="text-xs font-bold text-blue-600 hover:text-blue-700 cursor-pointer flex items-center gap-1 hover:underline"
                        >
                            <span>Lihat Detail</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                </div>
            </template>
        </div>

        <!-- MODAL: DETAIL LAPORAN KUNJUNGAN (COMPACT & SLEEK) -->
        <div 
            x-show="modalDetailVisit" 
            class="fixed inset-0 z-50 overflow-y-auto" 
            x-cloak
            @keydown.escape.window="modalDetailVisit = false"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Backdrop -->
                <div 
                    x-show="modalDetailVisit" 
                    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" 
                    @click="modalDetailVisit = false"
                ></div>

                <!-- Modal Panel -->
                <div 
                    x-show="modalDetailVisit" 
                    class="inline-block w-full max-w-lg my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10 border border-slate-200"
                >
                    <template x-if="selectedVisit">
                        <div>
                            <!-- Header Modal -->
                            <div class="px-5 py-4 bg-slate-50/80 border-b border-slate-100 flex items-start justify-between gap-3">
                                <div class="space-y-0.5 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span 
                                            class="px-2 py-0.5 text-[10px] font-extrabold rounded-md uppercase tracking-wider"
                                            :class="selectedVisit.type === 'Sekolah' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'"
                                            x-text="selectedVisit.type"
                                        ></span>
                                        <span class="text-[11px] text-slate-400 font-medium" x-text="selectedVisit.date + ' • ' + selectedVisit.time"></span>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-900 truncate" x-text="selectedVisit.name"></h3>
                                    <p class="text-[11px] text-slate-500 flex items-center gap-1 truncate">
                                        <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span x-text="selectedVisit.address"></span>
                                    </p>
                                </div>
                                <button 
                                    @click="modalDetailVisit = false" 
                                    class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-200/50 transition cursor-pointer shrink-0"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Body Modal -->
                            <div class="p-4 sm:p-5 space-y-3.5 max-h-[65vh] overflow-y-auto text-xs">
                                
                                <!-- Foto Dokumentasi Banner (Compact) -->
                                <template x-if="selectedVisit.photo">
                                    <div class="relative rounded-xl overflow-hidden border border-slate-200 bg-slate-900 group h-36">
                                        <img :src="selectedVisit.photo" :alt="selectedVisit.name" class="w-full h-full object-cover">
                                        <div class="absolute bottom-2 right-2">
                                            <a 
                                                :href="selectedVisit.photo" 
                                                target="_blank" 
                                                class="px-2.5 py-1 rounded-lg bg-slate-900/80 hover:bg-slate-900 text-white text-[11px] font-semibold backdrop-blur-xs transition flex items-center gap-1 shadow-xs"
                                            >
                                                <span>Buka Foto Asli</span>
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </template>

                                <!-- Compact Info Grid -->
                                <div class="grid grid-cols-2 gap-2.5">
                                    
                                    <!-- Personil Sales -->
                                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Petugas Sales</p>
                                        <p class="text-xs font-bold text-slate-800 mt-0.5 truncate flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span x-text="selectedVisit.sales"></span>
                                        </p>
                                    </div>

                                    <!-- PIC & WhatsApp -->
                                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">PIC / Kontak</p>
                                        <div class="mt-0.5 flex items-center justify-between gap-1">
                                            <p class="text-xs font-bold text-slate-800 truncate" x-text="selectedVisit.pic"></p>
                                            <template x-if="selectedVisit.whatsapp && selectedVisit.whatsapp !== '-'">
                                                <a 
                                                    :href="'https://wa.me/' + selectedVisit.whatsapp.replace(/[^0-9]/g, '')" 
                                                    target="_blank"
                                                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-[10px] rounded border border-emerald-200 transition shrink-0"
                                                >
                                                    <span>WA</span>
                                                </a>
                                            </template>
                                        </div>
                                    </div>

                                </div>

                                <!-- Detail Kemitraan Khusus Sekolah -->
                                <template x-if="selectedVisit.type === 'Sekolah'">
                                    <div class="p-3 bg-blue-50/60 rounded-xl border border-blue-100 space-y-2 text-slate-700">
                                        <div class="font-bold text-blue-900 text-[11px] uppercase tracking-wider">Potensi Kemitraan Sekolah</div>
                                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                                            <div>
                                                <span class="text-slate-500">Beasiswa:</span>
                                                <p class="font-bold text-slate-800" x-text="selectedVisit.potensi_beasiswa || '-'"></p>
                                            </div>
                                            <div>
                                                <span class="text-slate-500">Workshop AI:</span>
                                                <p class="font-bold" :class="selectedVisit.kesediaan_training_ai ? 'text-emerald-600' : 'text-slate-600'" x-text="selectedVisit.kesediaan_training_ai ? '✓ Bersedia' : 'Belum Bersedia'"></p>
                                            </div>
                                            <template x-if="selectedVisit.detail_beasiswa && selectedVisit.detail_beasiswa !== '-'">
                                                <div class="col-span-2 pt-1 border-t border-blue-100/80">
                                                    <span class="text-slate-500">Detail Beasiswa:</span>
                                                    <p class="text-slate-800 mt-0.5 bg-white p-2 rounded-lg border border-blue-100" x-text="selectedVisit.detail_beasiswa"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Detail Kemitraan Khusus Corporate -->
                                <template x-if="selectedVisit.type === 'Corporate'">
                                    <div class="p-3 bg-purple-50/60 rounded-xl border border-purple-100 space-y-2 text-slate-700">
                                        <div class="font-bold text-purple-900 text-[11px] uppercase tracking-wider">Potensi Kemitraan Corporate</div>
                                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                                            <div>
                                                <span class="text-slate-500">Bidang Usaha:</span>
                                                <p class="font-bold text-slate-800" x-text="selectedVisit.bidang_usaha || '-'"></p>
                                            </div>
                                            <div>
                                                <span class="text-slate-500">Potensi S1:</span>
                                                <p class="font-bold text-slate-800" x-text="selectedVisit.potensi_s1 || '-'"></p>
                                            </div>
                                            <div>
                                                <span class="text-slate-500">Potensi S2:</span>
                                                <p class="font-bold text-slate-800" x-text="selectedVisit.potensi_s2 || '-'"></p>
                                            </div>
                                            <div>
                                                <span class="text-slate-500">Program CSR:</span>
                                                <p class="font-bold text-slate-800" x-text="selectedVisit.potensi_csr || '-'"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Catatan Lengkap Hasil Kunjungan -->
                                <div>
                                    <p class="text-[11px] font-bold text-slate-800 mb-1">Catatan / Hasil Kunjungan:</p>
                                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-[11px] text-slate-700 leading-relaxed whitespace-pre-line">
                                        <span x-text="selectedVisit.notes"></span>
                                    </div>
                                </div>

                            </div>

                            <!-- Footer Modal -->
                            <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end">
                                <button 
                                    type="button" 
                                    @click="modalDetailVisit = false" 
                                    class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold transition cursor-pointer shadow-xs"
                                >
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
