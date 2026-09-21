@php
    $pageTitle = 'Daftar Prospek Tim (SPV)';
    $pageSubtitle = 'Monitoring & Pengelolaan Database Prospek Tim Sales';
@endphp

<x-app-layout :title="'Prospek Tim - Supervisor CRM'">

    <div class="space-y-6" x-data="{
        searchQuery: '',
        selectedStatus: 'all',
        selectedType: 'all',
        selectedSales: 'all',
        prospectsList: {{ json_encode($prospects) }},
        modalReassign: false,
        reassignTarget: null,
        openReassign(p) { this.reassignTarget = p; this.modalReassign = true; },

        get filteredProspects() {
            return this.prospectsList.filter(p => {
                const matchSearch = !this.searchQuery ||
                    (p.name && p.name.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (p.pic && p.pic.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (p.whatsapp && p.whatsapp.toLowerCase().includes(this.searchQuery.toLowerCase()));

                const matchStatus = this.selectedStatus === 'all' || p.status === this.selectedStatus;
                const matchType   = this.selectedType   === 'all' || p.type   === this.selectedType;
                const matchSales  = this.selectedSales  === 'all' || String(p.sales_id) === String(this.selectedSales);

                return matchSearch && matchStatus && matchType && matchSales;
            });
        }
    }" @keydown.escape.window="modalReassign = false">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Prospek Tim Sales</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Monitoring dan evaluasi seluruh database prospek anggota tim Sales di bawah wilayah Anda.</p>
            </div>
            <div>
                <a 
                    href="{{ route('spv.prospek.create') }}"
                    class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>+ Tambah Prospek Baru</span>
                </a>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <div class="crm-card bg-white p-4 sm:p-5 space-y-4">
            <!-- Search Bar -->
            <div class="relative">
                <input 
                    type="text" 
                    x-model="searchQuery"
                    placeholder="Cari nama prospek/sekolah, kontak PIC, atau nomor WhatsApp..." 
                    class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 text-xs sm:text-sm transition bg-slate-50/50 focus:bg-white"
                >
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- Filter Pills -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Filter Sales -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Sales Penanggung Jawab</label>
                    <select x-model="selectedSales" class="w-full text-xs px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                        <option value="all">Semua Sales Tim</option>
                        @foreach($teamSales as $sales)
                            <option value="{{ $sales->id }}">{{ $sales->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Status Pipeline</label>
                    <select x-model="selectedStatus" class="w-full text-xs px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                        <option value="all">Semua Status</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st }}">{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Type -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Tipe Entitas</label>
                    <select x-model="selectedType" class="w-full text-xs px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                        <option value="all">Semua Tipe</option>
                        <option value="Sekolah">Sekolah</option>
                        <option value="Corporate">Corporate / Mitra</option>
                        <option value="Individu">Individu / Siswa</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Prospects Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4">Nama Prospek</th>
                            <th class="py-3 px-3">PIC & Kontak</th>
                            <th class="py-3 px-3">Sales Handler</th>
                            <th class="py-3 px-3 text-center">Status Pipeline</th>
                            <th class="py-3 px-3">Follow Up Terakhir</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="p in filteredProspects" :key="p.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900" x-text="p.name"></div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 text-[10px] font-medium" x-text="p.type"></span>
                                        <span class="text-slate-400 text-[10px]" x-text="'Dibuat: ' + p.created_at"></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="font-medium text-slate-800" x-text="p.pic"></div>
                                    <div class="text-[11px] text-emerald-600 font-semibold mt-0.5" x-text="p.whatsapp"></div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="font-semibold text-slate-700" x-text="p.takeover_sales || 'Belum Ditugaskan'"></div>
                                    <div class="text-[10px] text-slate-400" x-text="p.active_takeover"></div>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                        :class="{
                                            'bg-blue-50 text-blue-700 border border-blue-200': p.status === 'Cold Lead',
                                            'bg-indigo-50 text-indigo-700 border border-indigo-200': p.status === 'Interested',
                                            'bg-amber-50 text-amber-700 border border-amber-200': p.status === 'Follow Up',
                                            'bg-purple-50 text-purple-700 border border-purple-200': p.status === 'Beli Formulir',
                                            'bg-cyan-50 text-cyan-700 border border-cyan-200': p.status === 'Pembayaran Termin 1',
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200': p.status === 'Closing',
                                            'bg-rose-50 text-rose-700 border border-rose-200': p.status === 'Lost'
                                        }"
                                        x-text="p.status">
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600">
                                    <div class="font-medium" x-text="p.last_contact"></div>
                                    <div class="text-[10px] text-slate-400" x-text="'Next: ' + p.next_follow_up"></div>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openReassign(p)"
                                            class="px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold text-xs transition inline-flex items-center gap-1 border border-amber-200"
                                        >
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                            <span>Realokasi</span>
                                        </button>
                                        <a :href="'/spv/prospek/' + p.id" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 font-semibold text-xs transition inline-flex items-center gap-1">
                                            <span>Detail</span>
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredProspects.length === 0">
                            <td colspan="6" class="py-12 text-center text-slate-400 text-xs">
                                Tidak ada prospek yang cocok dengan kriteria pencarian / filter Anda.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── MODAL REALOKASI SALES ── -->
        <div
            x-show="modalReassign"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
        >
            <div
                class="bg-white rounded-2xl w-full max-w-md shadow-2xl border border-slate-200 overflow-hidden"
                @click.away="modalReassign = false"
                x-show="modalReassign"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            >
                <template x-if="reassignTarget">
                    <div>
                        <!-- Header -->
                        <div class="px-5 py-4 bg-amber-50 border-b border-amber-100 flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-amber-100">
                                        <svg class="w-3.5 h-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    </span>
                                    <span class="text-xs font-bold text-amber-800 uppercase tracking-wider">Realokasi Sales Handler</span>
                                </div>
                                <p class="text-sm font-bold text-slate-900 mt-1" x-text="reassignTarget.name"></p>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Handler saat ini: <strong class="text-slate-700" x-text="reassignTarget.takeover_sales || 'Belum Ditugaskan'"></strong>
                                </p>
                            </div>
                            <button @click="modalReassign = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Form -->
                        <form
                            :action="'/spv/prospek/' + reassignTarget.id + '/reassign'"
                            method="POST"
                            class="p-5 space-y-4"
                        >
                            @csrf

                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5">Sales Baru <span class="text-red-500">*</span></label>
                                <select
                                    name="sales_id"
                                    required
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition"
                                >
                                    <option value="">-- Pilih Sales --</option>
                                    @foreach($teamSales as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5">Alasan Realokasi</label>
                                <textarea
                                    name="reason"
                                    rows="3"
                                    placeholder="Contoh: Sales sebelumnya resign, wilayah direstrukturisasi, dll."
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition resize-none"
                                ></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-1">
                                <button
                                    type="button"
                                    @click="modalReassign = false"
                                    class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer"
                                >Batal</button>
                                <button
                                    type="submit"
                                    class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-sm transition cursor-pointer flex items-center gap-1.5"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    Simpan Realokasi
                                </button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>
        <!-- ── END MODAL ── -->

    </div>

</x-app-layout>
