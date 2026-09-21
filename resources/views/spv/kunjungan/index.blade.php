@php
    $pageTitle = 'Monitoring Kunjungan Tim (SPV)';
    $pageSubtitle = 'Log & Evaluasi Kunjungan Lapangan Tim Sales';
@endphp

<x-app-layout :title="'Kunjungan Tim - Supervisor CRM'">

    <div class="space-y-6" x-data="{
        selectedSales: 'all',
        selectedJenis: 'all',
        visitsList: {{ json_encode($visits) }},
        
        get filteredVisits() {
            return this.visitsList.filter(v => {
                const matchSales = this.selectedSales === 'all' || String(v.sales_id) === String(this.selectedSales);
                const matchJenis = this.selectedJenis === 'all' || v.jenis === this.selectedJenis;
                return matchSales && matchJenis;
            });
        }
    }">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Kunjungan Lapangan Tim Sales</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Review foto dokumentasi, laporan audiensi sekolah, dan follow up hasil kunjungan tim Sales.</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="crm-card bg-white p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4 text-xs">
            <div class="flex flex-wrap items-center gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Sales Personil</label>
                    <select x-model="selectedSales" class="text-xs px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20">
                        <option value="all">Semua Sales ({{ count($visits) }} Kunjungan)</option>
                        @foreach($teamSales as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Jenis Kunjungan</label>
                    <select x-model="selectedJenis" class="text-xs px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20">
                        <option value="all">Semua Kategori</option>
                        <option value="Sekolah">Sekolah (SMA/SMK)</option>
                        <option value="Perusahaan">Perusahaan / Corporate</option>
                    </select>
                </div>
            </div>

            <div class="text-slate-500 text-xs font-medium">
                Total Ditemukan: <strong class="text-slate-900" x-text="filteredVisits.length"></strong> kunjungan
            </div>
        </div>

        <!-- Visits Grid Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <template x-for="visit in filteredVisits" :key="visit.id">
                <div class="crm-card bg-white p-5 space-y-4 hover:shadow-md transition">
                    
                    <!-- Header of card -->
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="visit.jenis === 'Sekolah' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200'"
                                x-text="visit.jenis">
                            </span>
                            <h3 class="font-bold text-sm text-slate-900 mt-1.5 line-clamp-1" x-text="visit.nama_institusi"></h3>
                        </div>
                        <div class="text-right">
                            <span class="text-[11px] font-semibold text-slate-700 block" x-text="visit.tanggal"></span>
                            <span class="text-[10px] text-slate-400" x-text="visit.waktu"></span>
                        </div>
                    </div>

                    <!-- Sales & PIC info -->
                    <div class="bg-slate-50 p-3 rounded-xl space-y-1.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">Sales:</span>
                            <span class="font-bold text-slate-800" x-text="visit.sales_name"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">PIC:</span>
                            <span class="font-semibold text-slate-700" x-text="visit.pic_name"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">WhatsApp:</span>
                            <span class="font-semibold text-emerald-600" x-text="visit.pic_whatsapp"></span>
                        </div>
                    </div>

                    <!-- Photo preview if available -->
                    <template x-if="visit.foto">
                        <div class="relative rounded-xl overflow-hidden h-36 bg-slate-100 border border-slate-200">
                            <img :src="visit.foto" alt="Foto Kunjungan" class="w-full h-full object-cover">
                        </div>
                    </template>

                    <!-- Notes -->
                    <div class="text-xs text-slate-600 line-clamp-2 bg-slate-50/50 p-2.5 rounded-lg border border-slate-100 italic" x-text="visit.catatan"></div>

                    <!-- Footer Action -->
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-[10px] text-slate-400" x-text="visit.created_at"></span>
                        <a :href="'/spv/kunjungan/' + visit.id" class="text-blue-600 hover:text-blue-700 font-semibold inline-flex items-center gap-1">
                            <span>Detail Kunjungan</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>

                </div>
            </template>
        </div>

        <div x-show="filteredVisits.length === 0" class="crm-card bg-white p-12 text-center text-slate-400 text-xs">
            Belum ada data kunjungan tim yang sesuai dengan filter.
        </div>

    </div>

</x-app-layout>
