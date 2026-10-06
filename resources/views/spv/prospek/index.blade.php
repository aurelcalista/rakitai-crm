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
        currentPage: 1,
        perPage: 25,
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
        },
        get paginatedProspects() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredProspects.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.ceil(this.filteredProspects.length / this.perPage) || 1;
        }
    }" x-effect="searchQuery; selectedStatus; selectedType; selectedSales; currentPage = 1" @keydown.escape.window="modalReassign = false">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Prospek Tim Sales</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Monitoring dan evaluasi seluruh database prospek anggota tim Sales di bawah wilayah Anda.</p>
            </div>
            <div class="shrink-0">
                <a 
                    href="{{ route('spv.prospek.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-bold shadow-xs transition whitespace-nowrap shrink-0 cursor-pointer"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Prospek</span>
                </a>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <form method="GET" action="{{ route('spv.prospek.index') }}" class="crm-card bg-white p-4 sm:p-5 space-y-4">
            <!-- Search Bar -->
            <div class="relative">
                <input 
                    type="text" 
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari nama prospek/siswa, kontak PIC, atau nomor WhatsApp..." 
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 text-xs sm:text-sm transition bg-slate-50/50 focus:bg-white"
                >
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <!-- Typeahead Searchable Select Filters -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Dropdown Typeahead: Sekolah -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Nama Sekolah
                    </label>
                    <x-searchable-select 
                        name="sekolah_id" 
                        :options="$sekolahs" 
                        :value="request('sekolah_id')" 
                        placeholder="Semua Sekolah..." 
                    />
                </div>

                <!-- Dropdown Typeahead: Prodi -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                        Minat Prodi
                    </label>
                    <x-searchable-select 
                        name="prodi_id" 
                        :options="$prodis" 
                        :value="request('prodi_id')" 
                        placeholder="Semua Program Studi..." 
                    />
                </div>

                <!-- Dropdown Typeahead: Wilayah -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Wilayah / Teritori
                    </label>
                    <x-searchable-select 
                        name="wilayah_id" 
                        :options="$wilayahs" 
                        :value="request('wilayah_id')" 
                        placeholder="Semua Wilayah..." 
                    />
                </div>

                <!-- Dropdown Typeahead: Pemilik Lead (Sales/CS) -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Pemilik Lead (Sales / CS)
                    </label>
                    <x-searchable-select 
                        name="sales_id" 
                        :options="$teamMembers" 
                        :value="request('sales_id')" 
                        placeholder="Semua Handler..." 
                    />
                </div>
            </div>

            <!-- Row 3: Status & Tipe & Actions -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-end justify-between gap-3 pt-1 border-t border-slate-100">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 flex-1">
                    <!-- Filter Status (8 Status Pipeline PMB TA 2027/2028) -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Status Pipeline (TA 2027/2028)</label>
                        <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Semua Status (8 Tahapan)</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>
                                    {{ $st }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Sumber / Source -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Sumber Prospek</label>
                        <select name="source" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 font-semibold focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Semua Sumber</option>
                            @foreach($sources as $src)
                                <option value="{{ $src }}" {{ request('source') === $src ? 'selected' : '' }}>{{ $src }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @if(request()->hasAny(['q', 'sekolah_id', 'prodi_id', 'wilayah_id', 'sales_id', 'status', 'source']))
                        <a 
                            href="{{ route('spv.prospek.index') }}" 
                            class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition"
                        >
                            Reset
                        </a>
                    @endif
                    <button 
                        type="submit" 
                        class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Prospects Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3.5 px-3 w-10 text-center">No</th>
                            <th class="py-3.5 px-4">Nama & Kontak</th>
                            <th class="py-3.5 px-3">Pemilik Lead (Handler)</th>
                            <th class="py-3.5 px-3 text-center">Status Pipeline</th>
                            <th class="py-3.5 px-3">Follow Up & Respon</th>
                            <th class="py-3.5 px-4 text-right">Aksi SPV</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(p, index) in paginatedProspects" :key="p.id + '-' + currentPage">
                            <tr class="hover:bg-slate-50/80 transition crm-table-slide">
                                <td class="py-3.5 px-3 text-center font-bold text-slate-400 text-xs" x-text="(currentPage - 1) * perPage + index + 1"></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900" x-text="p.name === p.pic ? p.name : p.pic + ' - ' + p.name"></div>
                                    <template x-if="p.whatsapp && p.whatsapp !== '-'">
                                        <a :href="'https://wa.me/' + p.whatsapp.replace(/[^0-9]/g, '')" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-bold hover:underline mt-0.5 mb-1">
                                            <span x-text="p.whatsapp"></span>
                                        </a>
                                    </template>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-semibold" x-text="p.sekolah_nama || p.type"></span>
                                        <template x-if="p.prodi_nama && p.prodi_nama !== '-'">
                                             <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200" x-text="p.prodi_nama + ' (' + p.kelas + ')'"></span>
                                        </template>
                                        <template x-if="p.wilayah_nama">
                                            <span class="text-slate-400 text-[10px]" x-text="'• ' + p.wilayah_nama"></span>
                                        </template>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="font-bold text-slate-800" x-text="p.takeover_sales || p.takeover_cs || 'Belum Ditugaskan'"></div>
                                    <div class="text-[10px] text-slate-400" x-text="p.active_takeover"></div>
                                    <template x-if="p.sla_status && p.sla_status !== 'N/A'">
                                        <div class="mt-1 flex items-center">
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold"
                                                :class="{
                                                    'bg-emerald-100 text-emerald-800': p.sla_status === 'Dalam SLA',
                                                    'bg-blue-100 text-blue-800': p.sla_status === 'Sesuai SLA',
                                                    'bg-rose-100 text-rose-800': p.sla_status === 'Terlambat'
                                                }"
                                                x-text="p.sla_status"></span>
                                        </div>
                                    </template>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-block"
                                        :class="{
                                            'bg-slate-100 text-slate-700 border border-slate-200': p.status === 'BARU' || p.status === 'Cold Lead',
                                            'bg-blue-50 text-blue-700 border border-blue-200': p.status === 'KONTAK' || p.status === 'Interested',
                                            'bg-amber-50 text-amber-700 border border-amber-200': p.status === 'PROSPEK' || p.status === 'HANGAT' || p.status === 'Follow Up',
                                            'bg-orange-50 text-orange-700 border border-orange-200': p.status === 'HOT PROSPEK' || p.status === 'PANAS',
                                            'bg-purple-50 text-purple-700 border border-purple-200': p.status === 'FORMULIR' || p.status === 'Beli Formulir',
                                            'bg-cyan-50 text-cyan-700 border border-cyan-200': p.status === 'BERKAS' || p.status === 'Pembayaran Termin 1',
                                            'bg-emerald-100 text-emerald-800 border border-emerald-300 font-extrabold shadow-2xs': p.status === 'LUNAS' || p.status === 'Closing',
                                            'bg-gray-100 text-gray-600 border border-gray-200': p.status === 'NO RESPON' || p.status === 'DINGIN' || p.status === 'Lost'
                                        }"
                                        x-text="p.status">
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200" x-text="(p.follow_up_count || 0) + 'x FU'"></span>
                                        <span class="text-[11px] font-medium" x-text="p.last_contact !== '-' ? p.last_contact : 'Belum FU'"></span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5" x-text="'Next: ' + p.next_follow_up"></div>
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
                            <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                                Tidak ada prospek yang cocok dengan kriteria pencarian / filter Anda.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Control -->
            <x-table-pagination
                total="filteredProspects.length"
                page="currentPage"
                perPage="perPage"
                totalPages="totalPages"
                color="blue"
            />
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

                            <div class="p-3 bg-amber-50/80 rounded-xl border border-amber-200/70 text-[11px] text-amber-800 leading-relaxed flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <strong class="font-bold">Aturan Re-alokasi:</strong> Hitungan interaksi (<code class="bg-amber-100 px-1 py-0.5 rounded text-[10px] font-mono">follow_up_count</code>) akan di-reset ke 0 untuk penangan baru, namun seluruh riwayat aktivitas sebelumnya tetap tersimpan di timeline.
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5">Pilih Petugas Baru (Sales / CS) <span class="text-red-500">*</span></label>
                                <select
                                    name="sales_id"
                                    required
                                    class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition"
                                >
                                    <option value="">-- Pilih Sales atau CS --</option>
                                    @foreach($teamMembers as $m)
                                        <option value="{{ $m->id }}">[{{ $m->role }}] {{ $m->name }}</option>
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
