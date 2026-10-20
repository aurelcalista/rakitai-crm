@php
    $pageTitle = 'Monitoring Kunjungan Tim (SPV)';
    $pageSubtitle = 'Log, Evaluasi & Verifikasi Radius Kunjungan Lapangan Tim Sales';
@endphp

<x-app-layout :title="'Kunjungan Tim - Supervisor CRM'">

    <div class="space-y-6" x-data="{
        activeTab: 'sekolah', // 'sekolah', 'company', 'verifikasi'
        salesFilter: 'all',
        startDate: '',
        endDate: '',
        allVisits: {{ json_encode($allVisits) }},
        schoolVisits: {{ json_encode($schoolVisits) }},
        companyVisits: {{ json_encode($companyVisits) }},
        selectedVisit: null,
        modalDetailVisit: false,
        modalEditVisit: false,
        editVisit: null,
        confirmDeleteModal: false,
        deleteVisitId: null,

        get currentList() {
            let list = [];
            if (this.activeTab === 'sekolah') {
                list = this.schoolVisits;
            } else if (this.activeTab === 'company') {
                list = this.companyVisits;
            } else if (this.activeTab === 'verifikasi') {
                list = this.allVisits.filter(v => v.status_verifikasi === 'Perlu Verifikasi' || v.is_outside_radius);
            } else {
                list = this.allVisits;
            }

            if (this.salesFilter !== 'all') {
                list = list.filter(v => String(v.sales_id) === String(this.salesFilter));
            }

            if (this.startDate) {
                list = list.filter(v => (v.raw_date || '') >= this.startDate);
            }
            if (this.endDate) {
                list = list.filter(v => (v.raw_date || '') <= this.endDate);
            }

            return list;
        }
    }">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Data Kunjungan Tim Sales</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Pemisahan data kunjungan Sekolah dan Company, review foto dokumentasi, dan verifikasi geo-tagging radius.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-2">
                {{-- Tombol Unduh Spreadsheet --}}
                <a 
                    :href="'{{ route('export.kunjungan') }}?' + new URLSearchParams(Object.fromEntries(Object.entries({
                        jenis: activeTab === 'sekolah' ? 'Sekolah' : (activeTab === 'company' ? 'Corporate' : ''),
                        verifikasi_only: activeTab === 'verifikasi' ? '1' : '',
                        sales_id: salesFilter !== 'all' ? salesFilter : '',
                        start_date: startDate || '',
                        end_date: endDate || ''
                    }).filter(([_, v]) => v !== '' && v !== null && v !== undefined)))"
                    target="_blank"
                    class="px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs flex items-center gap-1.5 shadow-xs transition cursor-pointer"
                    title="Unduh Spreadsheet Kunjungan Tim Sales (.xlsx)"
                >
                    <svg class="w-4 h-4 text-emerald-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Unduh Spreadsheet</span>
                </a>

                <template x-if="allVisits.filter(v => v.status_verifikasi === 'Perlu Verifikasi' || v.is_outside_radius).length > 0">
                    <button 
                        type="button" 
                        @click="activeTab = 'verifikasi'" 
                        class="px-3.5 py-2.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 font-bold text-xs flex items-center gap-1.5 shadow-xs transition hover:bg-amber-100 cursor-pointer"
                    >
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span><strong x-text="allVisits.filter(v => v.status_verifikasi === 'Perlu Verifikasi' || v.is_outside_radius).length"></strong> Perlu Verifikasi</span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Tab Navigasi Pemisahan Sekolah vs Company & Filter Bar -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex flex-wrap items-center gap-2">
                <button 
                    type="button" 
                    @click="activeTab = 'sekolah'" 
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer"
                    :class="activeTab === 'sekolah' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                    <span>Kunjungan Sekolah ({{ count($schoolVisits) }})</span>
                </button>

                <button 
                    type="button" 
                    @click="activeTab = 'company'" 
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer"
                    :class="activeTab === 'company' ? 'bg-purple-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Kunjungan Company ({{ count($companyVisits) }})</span>
                </button>

                <button 
                    type="button" 
                    @click="activeTab = 'verifikasi'" 
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer"
                    :class="activeTab === 'verifikasi' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200'"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Perlu Verifikasi Radius (<span x-text="allVisits.filter(v => v.status_verifikasi === 'Perlu Verifikasi' || v.is_outside_radius).length"></span>)</span>
                </button>
            </div>

            <!-- Filter Tanggal & Sales Tim -->
            <div class="flex flex-wrap items-center gap-3 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-400 font-semibold uppercase text-[11px] whitespace-nowrap">Tanggal:</span>
                    <input type="date" x-model="startDate" title="Tanggal Mulai" class="text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 font-medium focus:ring-2 focus:ring-blue-500/20">
                    <span class="text-slate-300">-</span>
                    <input type="date" x-model="endDate" title="Tanggal Selesai" class="text-xs px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 font-medium focus:ring-2 focus:ring-blue-500/20">
                    <template x-if="startDate || endDate">
                        <button type="button" @click="startDate = ''; endDate = ''" class="text-xs text-rose-600 hover:text-rose-700 font-semibold px-1 cursor-pointer">Reset</button>
                    </template>
                </div>

                <div class="flex items-center gap-1.5">
                    <span class="text-slate-400 font-semibold uppercase text-[11px] whitespace-nowrap">Sales:</span>
                    <select x-model="salesFilter" class="text-xs px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20 cursor-pointer">
                        <option value="all">Semua Sales Tim</option>
                        @foreach($teamSales as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Cards Grid Kunjungan -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="visit in currentList" :key="visit.id">
                <div class="crm-card bg-white overflow-hidden flex flex-col justify-between hover:border-blue-300 transition group shadow-xs border border-slate-200/80 rounded-2xl">

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
                            <div class="absolute top-3 left-3 flex items-center gap-1.5">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-slate-900/80 backdrop-blur-md text-white border border-white/20" x-text="visit.type"></span>
                                <template x-if="visit.is_outside_radius || visit.status_verifikasi === 'Perlu Verifikasi'">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-extrabold bg-amber-500 text-white shadow-xs animate-bounce">
                                        <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>Perlu Verifikasi</span>
                                    </span>
                                </template>
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

                            <!-- Warning Radius Banner if triggered -->
                            <template x-if="visit.is_outside_radius || visit.status_verifikasi === 'Perlu Verifikasi'">
                                <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center justify-between text-[11px]">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span class="font-semibold">Warning: Lokasi di luar radius</span>
                                    </div>
                                    <form :action="'/spv/kunjungan/' + visit.id + '/verifikasi'" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="px-2 py-0.5 rounded bg-amber-600 text-white font-bold hover:bg-amber-700 transition cursor-pointer">
                                            Verifikasi
                                        </button>
                                    </form>
                                </div>
                            </template>

                            <div class="space-y-1.5 pt-2 border-t border-slate-100 text-slate-600">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">PIC / Kontak:</span>
                                    <span class="font-semibold text-slate-800" x-text="visit.pic + ' (' + visit.whatsapp + ')'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Sales Handler:</span>
                                    <span class="font-semibold text-blue-600" x-text="visit.sales"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Potensi Mahasiswa:</span>
                                    <span class="font-semibold text-slate-800" x-text="visit.potensi_mahasiswa"></span>
                                </div>
                                <template x-if="visit.dosen && visit.dosen !== '-'">
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Dosen Pemateri:</span>
                                        <span class="font-semibold text-indigo-600 truncate max-w-[150px]" x-text="visit.dosen"></span>
                                    </div>
                                </template>
                                <template x-if="visit.type === 'Perusahaan' && visit.bidang_usaha">
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Bidang Usaha:</span>
                                        <span class="font-semibold text-slate-800" x-text="visit.bidang_usaha"></span>
                                    </div>
                                </template>
                            </div>

                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-slate-600 italic leading-relaxed text-[11px]">
                                "<span x-text="visit.notes"></span>"
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400 font-medium" x-text="visit.time"></span>
                        <div class="flex items-center gap-2">
                            <template x-if="visit.is_outside_radius || visit.status_verifikasi === 'Perlu Verifikasi'">
                                <form :action="'/spv/kunjungan/' + visit.id + '/verifikasi'" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition cursor-pointer">
                                        Verifikasi SPV
                                    </button>
                                </form>
                            </template>
                            <button
                                type="button"
                                @click="editVisit = Object.assign({}, visit); modalEditVisit = true"
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-amber-600 hover:text-amber-700 hover:bg-amber-100/80 border border-amber-200/70 transition cursor-pointer"
                                title="Edit Kunjungan Tim"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                @click="confirmDeleteModal = true; deleteVisitId = visit.id"
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-rose-500 hover:text-rose-700 hover:bg-rose-100/80 border border-rose-200/70 transition cursor-pointer"
                                title="Hapus Kunjungan Tim"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                            <a
                                :href="'/spv/kunjungan/' + visit.id"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg border border-transparent hover:border-blue-200 transition cursor-pointer"
                                title="Lihat detail lengkap kunjungan"
                            >
                                <span>Detail Data</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    </div>

                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="currentList.length === 0" class="crm-card bg-white p-12 text-center text-slate-400 text-xs rounded-2xl border border-slate-200">
            Tidak ada data kunjungan pada kategori atau filter yang dipilih.
        </div>

        <!-- MODAL DETAIL KUNJUNGAN -->
        <div
            x-show="modalDetailVisit"
            class="fixed inset-0 z-50 overflow-y-auto"
            x-cloak
            @keydown.escape.window="modalDetailVisit = false"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalDetailVisit = false"></div>

                <div class="inline-block w-full max-w-lg my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10 border border-slate-200">
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

                                <!-- Foto Dokumentasi Banner -->
                                <template x-if="selectedVisit.photo">
                                    <div class="relative rounded-xl overflow-hidden border border-slate-200 bg-slate-900 group h-48">
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

                                    <!-- Sales Incharge -->
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

                                    <!-- Dosen Pemateri -->
                                    <template x-if="selectedVisit.dosen && selectedVisit.dosen !== '-'">
                                        <div class="col-span-2 p-2.5 bg-indigo-50/70 rounded-xl border border-indigo-100 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="p-1.5 rounded-lg bg-indigo-100 text-indigo-700">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                                    </svg>
                                                </span>
                                                <div>
                                                    <p class="text-[10px] font-semibold text-indigo-500 uppercase tracking-wider">Dosen Pemateri</p>
                                                    <p class="text-xs font-bold text-indigo-900" x-text="selectedVisit.dosen"></p>
                                                </div>
                                            </div>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 text-indigo-700">Pemateri / Narasumber</span>
                                        </div>
                                    </template>

                                </div>

                                <!-- Detail Kemitraan Khusus Sekolah -->
                                <template x-if="selectedVisit.type === 'Sekolah'">
                                    <div class="p-3 bg-blue-50/60 rounded-xl border border-blue-100 space-y-2 text-slate-700">
                                        <div class="font-bold text-blue-900 text-[11px] uppercase tracking-wider">Potensi Kemitraan Sekolah</div>
                                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                                            <div>
                                                <span class="text-slate-500">Potensi Mahasiswa:</span>
                                                <p class="font-bold text-slate-800" x-text="selectedVisit.potensi_mahasiswa || '-'"></p>
                                            </div>
                                            <div>
                                                <span class="text-slate-500">Workshop AI:</span>
                                                <p class="font-bold" :class="selectedVisit.kesediaan_training_ai ? 'text-emerald-600' : 'text-slate-600'" x-text="selectedVisit.kesediaan_training_ai ? 'Bersedia' : 'Belum Bersedia'"></p>
                                            </div>
                                            <template x-if="selectedVisit.detail_potensi_mahasiswa && selectedVisit.detail_potensi_mahasiswa !== '-'">
                                                <div class="col-span-2">
                                                    <span class="text-slate-500">Detail Potensi Mahasiswa:</span>
                                                    <p class="text-slate-800 mt-0.5 bg-white p-2 rounded-lg border border-blue-100" x-text="selectedVisit.detail_potensi_mahasiswa"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Detail Kemitraan Khusus Corporate -->
                                <template x-if="selectedVisit.type === 'Perusahaan'">
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

                                <!-- Catatan Lengkap -->
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-slate-400 block font-semibold text-[10px] uppercase mb-1">Catatan Hasil Kunjungan</span>
                                    <p class="text-slate-700 leading-relaxed whitespace-pre-line" x-text="selectedVisit.notes"></p>
                                </div>

                                <template x-if="selectedVisit.is_outside_radius || selectedVisit.status_verifikasi === 'Perlu Verifikasi'">
                                    <form :action="'/spv/kunjungan/' + selectedVisit.id + '/verifikasi'" method="POST" class="pt-2">
                                        @csrf
                                        <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition">
                                            Verifikasi Kunjungan Ini (SPV)
                                        </button>
                                    </form>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        <!-- MODAL: EDIT KUNJUNGAN TIM (SPV) -->
        <div 
            x-show="modalEditVisit" 
            class="fixed inset-0 z-50 overflow-y-auto" 
            x-cloak
            @keydown.escape.window="modalEditVisit = false; editVisit = null"
        >
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalEditVisit = false; editVisit = null"></div>

                <div class="relative z-10 bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg p-6 overflow-hidden">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-xl bg-amber-50 text-amber-600 border border-amber-200">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Edit Kunjungan Tim (SPV)</h3>
                                <p class="text-xs text-slate-500">Perbarui data laporan kunjungan sales tim.</p>
                            </div>
                        </div>
                        <button type="button" @click="modalEditVisit = false; editVisit = null" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form 
                        :action="'/spv/kunjungan/' + (editVisit ? editVisit.id : '')" 
                        method="POST" 
                        enctype="multipart/form-data" 
                        class="space-y-4 text-xs"
                    >
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal Kunjungan</label>
                                <input type="date" name="tanggal" required :value="editVisit ? (editVisit.raw_date || '') : ''" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Waktu (WIB)</label>
                                <input type="time" name="waktu" :value="editVisit ? (editVisit.raw_time || editVisit.time || '') : ''" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Institusi / Tujuan</label>
                            <input type="text" name="nama_institusi" required :value="editVisit ? editVisit.name : ''" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama PIC</label>
                                <input type="text" name="pic_name" :value="editVisit ? editVisit.pic : ''" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">WhatsApp PIC</label>
                                <x-input-whatsapp name="pic_whatsapp" bindValue="editVisit ? editVisit.whatsapp : ''" placeholder="8123456789" />
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Alamat Institusi</label>
                            <input type="text" name="alamat" :value="editVisit ? editVisit.address : ''" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Dosen Pemateri / Narasumber</label>
                            <input type="text" name="dosen_pemateri" :value="editVisit ? (editVisit.dosen_pemateri || (editVisit.dosen && editVisit.dosen !== '-' ? editVisit.dosen : '')) : ''" placeholder="Nama dosen pemateri atau pendamping..." class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>

                        <template x-if="editVisit && (editVisit.type === 'Sekolah')">
                            <div class="space-y-3 p-3 bg-blue-50/50 rounded-xl border border-blue-100">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Potensi Mahasiswa</label>
                                    <input type="text" name="potensi_mahasiswa" :value="editVisit.potensi_mahasiswa || editVisit.potential || ''" placeholder="Contoh: 50 Siswa Kelas XII" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Detail Potensi</label>
                                    <input type="text" name="detail_potensi_mahasiswa" :value="editVisit.detail_beasiswa || editVisit.detail_potensi_mahasiswa || ''" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                </div>
                            </div>
                        </template>

                        <template x-if="editVisit && (editVisit.type === 'Perusahaan' || editVisit.type === 'Corporate')">
                            <div class="space-y-3 p-3 bg-purple-50/50 rounded-xl border border-purple-100">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Bidang Usaha</label>
                                    <input type="text" name="bidang_usaha" :value="editVisit.bidang_usaha || ''" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Potensi S1</label>
                                        <input type="text" name="potensi_s1" :value="editVisit.potensi_s1 || ''" class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Potensi S2</label>
                                        <input type="text" name="potensi_s2" :value="editVisit.potensi_s2 || ''" class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">CSR</label>
                                        <input type="text" name="potensi_csr" :value="editVisit.potensi_csr || ''" class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Catatan & Hasil Kunjungan</label>
                            <textarea name="catatan" rows="3" :value="editVisit ? editVisit.notes : ''" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3 items-center">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Status Kunjungan</label>
                                <select name="status" :value="editVisit ? editVisit.status : 'Selesai'" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 bg-white">
                                    <option value="Selesai">Selesai</option>
                                    <option value="Proses">Proses</option>
                                    <option value="Menunggu">Menunggu</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Ganti Foto (Opsional)</label>
                                <input type="file" name="foto" accept="image/*" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="modalEditVisit = false; editVisit = null" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold cursor-pointer shadow-xs">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL: KONFIRMASI HAPUS KUNJUNGAN TIM (SPV) -->
        <div 
            x-show="confirmDeleteModal" 
            class="fixed inset-0 z-50 overflow-y-auto"
            x-cloak
            @keydown.escape.window="confirmDeleteModal = false; deleteVisitId = null"
        >
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="confirmDeleteModal = false; deleteVisitId = null"></div>

                <div class="relative z-10 bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-sm p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-2">Hapus Kunjungan Tim?</h3>
                    <p class="text-sm text-slate-500 mb-6">Data kunjungan ini akan dihapus secara permanen dan tidak dapat dikembalikan.</p>
                    <div class="flex items-center gap-3 justify-center">
                        <button
                            type="button"
                            @click="confirmDeleteModal = false; deleteVisitId = null"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <form
                            :action="'/spv/kunjungan/' + deleteVisitId"
                            method="POST"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition cursor-pointer shadow-xs"
                            >
                                Ya, Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
