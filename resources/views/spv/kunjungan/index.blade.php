@php
    $pageTitle = 'Monitoring Kunjungan Tim (SPV)';
    $pageSubtitle = 'Log, Evaluasi & Verifikasi Radius Kunjungan Lapangan Tim Sales';
@endphp

<x-app-layout :title="'Kunjungan Tim - Supervisor CRM'">

    <div class="space-y-6" x-data="{
        activeTab: 'sekolah', // 'sekolah', 'company', 'verifikasi'
        salesFilter: 'all',
        allVisits: {{ json_encode($allVisits) }},
        schoolVisits: {{ json_encode($schoolVisits) }},
        companyVisits: {{ json_encode($companyVisits) }},
        selectedVisit: null,
        modalDetailVisit: false,

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

            if (this.salesFilter === 'all') {
                return list;
            }
            return list.filter(v => String(v.sales_id) === String(this.salesFilter));
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
            
            <div class="flex items-center gap-2">
                <template x-if="allVisits.filter(v => v.status_verifikasi === 'Perlu Verifikasi' || v.is_outside_radius).length > 0">
                    <button 
                        type="button" 
                        @click="activeTab = 'verifikasi'" 
                        class="px-3.5 py-2 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 font-bold text-xs flex items-center gap-1.5 shadow-xs transition hover:bg-amber-100 cursor-pointer"
                    >
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span><strong x-text="allVisits.filter(v => v.status_verifikasi === 'Perlu Verifikasi' || v.is_outside_radius).length"></strong> Kunjungan Perlu Verifikasi</span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Tab Navigasi Pemisahan Sekolah vs Company -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs">
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

            <!-- Filter Sales -->
            <div class="flex items-center gap-2 text-xs">
                <span class="text-slate-400 font-semibold uppercase text-[11px] whitespace-nowrap">Filter Sales:</span>
                <select x-model="salesFilter" class="text-xs px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20 cursor-pointer">
                    <option value="all">Semua Sales Tim</option>
                    @foreach($teamSales as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
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
                                    <span class="px-2 py-1 rounded-md text-[10px] font-extrabold bg-amber-500 text-white shadow-xs animate-bounce">
                                        ⚠️ Perlu Verifikasi
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
                            <a
                                :href="'/spv/kunjungan/' + visit.id"
                                class="text-xs font-bold text-blue-600 hover:text-blue-700 cursor-pointer"
                            >
                                Detail &rarr;
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
                                <!-- Catatan Lengkap -->
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-slate-400 block font-semibold text-[10px] uppercase mb-1">Catatan Hasil Kunjungan</span>
                                    <p class="text-slate-700 leading-relaxed" x-text="selectedVisit.notes"></p>
                                </div>

                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <span class="text-slate-400 block font-semibold text-[10px] uppercase mb-1">Catatan Hasil Kunjungan</span>
                                    <p class="text-slate-700 leading-relaxed" x-text="selectedVisit.notes"></p>
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
        </div>

    </div>

</x-app-layout>
