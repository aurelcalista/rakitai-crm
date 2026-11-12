@php $pageTitle = 'Data Program Studi'; @endphp

<x-app-layout :title="'Data Prodi - CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalAdd: false, modalEdit: false, modalDetail: false,
        selectedProdi: null, searchQuery: '', filterFakultas: 'all', filterJenjang: 'all',
        currentPage: 1,
        perPage: 25,
        prodi: {{ json_encode($prodi) }},
        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },
        get filtered() {
            return this.prodi.filter(p => {
                const q = this.searchQuery.toLowerCase();
                const mQ = !q || p.kode.toLowerCase().includes(q) || p.nama.toLowerCase().includes(q) || p.fakultas.toLowerCase().includes(q);
                const mF = this.filterFakultas === 'all' || p.fakultas === this.filterFakultas;
                const mJ = this.filterJenjang === 'all' || p.jenjang === this.filterJenjang;
                return mQ && mF && mJ;
            });
        },
        get paginated() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.ceil(this.filtered.length / this.perPage) || 1;
        }
    }" x-effect="searchQuery; filterFakultas; filterJenjang; currentPage = 1">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Data Program Studi & Biaya Kuliah</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola daftar program studi UCIC, 5 komponen biaya (Pendaftaran, Perlengkapan, DPP, Uang Gedung, UKT) serta pilihan periode pembayaran UKT.</p>
            </div>
            <button type="button" @click="modalAdd = true" class="px-4 py-2.5 rounded-xl bg-[#0b3d82] hover:bg-[#082a5c] text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Prodi</span>
            </button>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach([['label'=>'S1','color'=>'blue'],['label'=>'D3','color'=>'teal'],['label'=>'S2','color'=>'purple']] as $j)
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs text-center">
                <div class="text-2xl font-extrabold text-{{ $j['color'] }}-700">{{ $prodi->where('jenjang', $j['label'])->count() }}</div>
                <p class="text-xs font-bold text-slate-500 mt-1">Program Studi {{ $j['label'] }}</p>
            </div>
            @endforeach
        </div>

        <!-- Filter -->
        <div class="crm-card bg-white p-4 flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="searchQuery" placeholder="Cari nama prodi, kode, atau fakultas..." class="w-full text-xs pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
            <select x-model="filterFakultas" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none sm:w-64">
                <option value="all">Semua Fakultas</option>
                @foreach($fakultasList as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach
            </select>
            <select x-model="filterJenjang" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none sm:w-28">
                <option value="all">Semua Jenjang</option>
                @foreach($jenjangList as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
            </select>
        </div>

        <!-- Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs whitespace-nowrap min-w-[1050px]">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider text-[11px] border-b border-slate-100">
                            <th class="py-3.5 px-3 w-10 text-center">No</th>
                            <th class="py-3.5 px-4">Kode / Nama Prodi</th>
                            <th class="py-3.5 px-3">Fakultas</th>
                            <th class="py-3.5 px-3 text-center">Jenjang</th>
                            <th class="py-3.5 px-3 text-center">Kuota</th>
                            <th class="py-3.5 px-4">Komponen 1x Bayar</th>
                            <th class="py-3.5 px-4">Pilihan Periode UKT (Aktif)</th>
                            <th class="py-3.5 px-3 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(p, index) in paginated" :key="p.id + '-' + currentPage">
                            <tr class="hover:bg-slate-50/80 transition crm-table-slide">
                                <td class="py-3.5 px-3 text-center font-bold text-slate-400 text-xs" x-text="(currentPage - 1) * perPage + index + 1"></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900" x-text="p.nama"></div>
                                    <div class="text-[11px] font-mono text-[#0b3d82] mt-0.5" x-text="p.kode"></div>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600 font-medium text-[11px]" x-text="p.fakultas"></td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold"
                                        :class="{
                                            'bg-blue-50 text-blue-700 border border-blue-200': p.jenjang === 'S1',
                                            'bg-teal-50 text-teal-700 border border-teal-200': p.jenjang === 'D3',
                                            'bg-purple-50 text-purple-700 border border-purple-200': p.jenjang === 'S2'
                                        }"
                                        x-text="p.jenjang"></span>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-800" x-text="p.kuota + ' mhs'"></td>
                                
                                <!-- 4 Komponen 1x Bayar -->
                                <td class="py-3.5 px-4">
                                    <div class="space-y-0.5 text-[11px]">
                                        <div class="flex items-center gap-1.5 text-slate-600">
                                            <span class="text-slate-400">Pendaftaran:</span>
                                            <span class="font-bold text-slate-800" x-text="formatRupiah(p.biaya_pendaftaran)"></span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-slate-600">
                                            <span class="text-slate-400">Perlengkapan:</span>
                                            <span class="font-bold text-slate-800" x-text="formatRupiah(p.biaya_perlengkapan)"></span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-slate-600">
                                            <span class="text-slate-400">DPP:</span>
                                            <span class="font-bold text-slate-800" x-text="formatRupiah(p.biaya_dpp)"></span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-slate-600">
                                            <span class="text-slate-400">Uang Gedung:</span>
                                            <span class="font-bold text-slate-800" x-text="formatRupiah(p.biaya_uang_gedung)"></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Opsi Periode UKT Aktif -->
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        <template x-for="(opt, optKey) in (p.ukt_options || {})" :key="optKey">
                                            <span x-show="opt && opt.is_active" 
                                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-[#0b3d82] border border-blue-200 shadow-2xs"
                                                  :title="opt.label + ': ' + formatRupiah(opt.nominal)">
                                                <span x-text="opt.key ? opt.key.replace('_', ' ') : optKey"></span>
                                                <span class="text-slate-400">•</span>
                                                <span x-text="opt.nominal > 0 ? (opt.nominal >= 1000000 ? (opt.nominal/1000000).toFixed(1) + 'jt' : (opt.nominal/1000) + 'rb') : 'Rp 0'"></span>
                                            </span>
                                        </template>
                                        <span x-show="!p.ukt_options || Object.values(p.ukt_options).filter(o => o.is_active).length === 0" class="text-slate-400 text-[11px] italic">
                                            Belum diatur
                                        </span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="p.status === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-600 border border-red-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="p.status === 'Aktif' ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                        <span x-text="p.status"></span>
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="selectedProdi = p; modalDetail = true" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer">Detail</button>
                                        <button @click="selectedProdi = p; modalEdit = true" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-[#0b3d82] text-xs font-semibold cursor-pointer">Edit</button>
                                        <form :action="'/admin/prodi/' + p.id + '/toggle-status'" method="POST" class="inline" @submit="if(!confirm(p.status === 'Aktif' ? 'Nonaktifkan prodi ini?' : 'Aktifkan prodi ini?')) $event.preventDefault()">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold cursor-pointer"
                                                :class="p.status === 'Aktif' ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700'"
                                                x-text="p.status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan'"></button>
                                        </form>
                                        <form :action="'/admin/prodi/' + p.id" method="POST" class="inline" @submit="if(!confirm('Hapus prodi ini?')) $event.preventDefault()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filtered.length === 0">
                            <td colspan="9" class="py-12 text-center text-slate-400 text-xs">
                                Tidak ada program studi yang cocok.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Control -->
            <x-table-pagination
                total="filtered.length"
                page="currentPage"
                perPage="perPage"
                totalPages="totalPages"
                color="blue"
            />
        </div>

        <!-- ============================================================== -->
        <!-- MODAL DETAIL PRODI -->
        <!-- ============================================================== -->
        <div x-show="modalDetail" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="modalDetail = false"></div>
                <div class="inline-block w-full max-w-2xl bg-white shadow-2xl rounded-3xl relative z-10 overflow-hidden border border-slate-100">
                    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-start bg-slate-50/70">
                        <div>
                            <span class="text-[11px] font-mono text-[#0b3d82] font-black uppercase tracking-wider" x-text="selectedProdi ? selectedProdi.kode : ''"></span>
                            <h3 class="text-lg font-black text-slate-900 mt-0.5" x-text="selectedProdi ? selectedProdi.nama : ''"></h3>
                            <p class="text-xs text-slate-500" x-text="selectedProdi ? selectedProdi.fakultas + ' • Jenjang ' + selectedProdi.jenjang : ''"></p>
                        </div>
                        <button @click="modalDetail = false" class="text-slate-400 hover:text-slate-600 cursor-pointer p-1">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <template x-if="selectedProdi">
                        <div class="p-6 space-y-5 text-xs max-h-[75vh] overflow-y-auto">
                            <!-- Info Dasar -->
                            <div class="grid grid-cols-3 gap-3">
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="text-slate-400 text-[11px] font-bold">Jenjang</p>
                                    <p class="font-black text-slate-900 text-sm mt-0.5" x-text="selectedProdi.jenjang"></p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="text-slate-400 text-[11px] font-bold">Status</p>
                                    <p class="font-black text-sm mt-0.5" :class="selectedProdi.status === 'Aktif' ? 'text-emerald-600' : 'text-red-600'" x-text="selectedProdi.status"></p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="text-slate-400 text-[11px] font-bold">Target Kuota</p>
                                    <p class="font-black text-slate-900 text-sm mt-0.5" x-text="selectedProdi.kuota + ' Mahasiswa'"></p>
                                </div>
                            </div>

                            <!-- 1. 4 Komponen Biaya 1x Bayar -->
                            <div class="bg-slate-50/80 rounded-2xl border border-slate-200/80 p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-black text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-[#0b3d82]"></span>
                                        4 Komponen Biaya (1x Bayar Saja - Bisa Rp 0)
                                    </h4>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                    <div class="bg-white p-3 rounded-xl border border-slate-100">
                                        <span class="text-slate-400 text-[10px] uppercase font-bold block">1. Pendaftaran</span>
                                        <span class="font-extrabold text-slate-900 text-xs mt-1 block" x-text="formatRupiah(selectedProdi.biaya_pendaftaran)"></span>
                                    </div>
                                    <div class="bg-white p-3 rounded-xl border border-slate-100">
                                        <span class="text-slate-400 text-[10px] uppercase font-bold block">2. Perlengkapan</span>
                                        <span class="font-extrabold text-slate-900 text-xs mt-1 block" x-text="formatRupiah(selectedProdi.biaya_perlengkapan)"></span>
                                    </div>
                                    <div class="bg-white p-3 rounded-xl border border-slate-100">
                                        <span class="text-slate-400 text-[10px] uppercase font-bold block">3. DPP</span>
                                        <span class="font-extrabold text-slate-900 text-xs mt-1 block" x-text="formatRupiah(selectedProdi.biaya_dpp)"></span>
                                    </div>
                                    <div class="bg-white p-3 rounded-xl border border-slate-100">
                                        <span class="text-slate-400 text-[10px] uppercase font-bold block">4. Uang Gedung</span>
                                        <span class="font-extrabold text-slate-900 text-xs mt-1 block" x-text="formatRupiah(selectedProdi.biaya_uang_gedung)"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Pilihan Skema Periode UKT (7 Pilihan) -->
                            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-3">
                                <div>
                                    <h4 class="font-black text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        Pilihan Periode Pembayaran UKT (7 Opsi)
                                    </h4>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Admin dapat mengaktifkan atau menonaktifkan opsi periode pembayaran UKT yang tersedia untuk calon mahasiswa.</p>
                                </div>

                                <div class="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden">
                                    <template x-for="(opt, optKey) in (selectedProdi.ukt_options || {})" :key="optKey">
                                        <div class="p-3 flex items-center justify-between hover:bg-slate-50/70 transition">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full" :class="opt.is_active ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                                <span class="font-bold text-slate-800" x-text="opt.label"></span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="font-black font-display text-slate-900" x-text="formatRupiah(opt.nominal)"></span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                      :class="opt.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-400 border border-slate-200'"
                                                      x-text="opt.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-2 bg-slate-50/50">
                        <button @click="modalDetail = false; modalEdit = true" class="px-4 py-2 text-xs font-bold rounded-xl bg-[#0b3d82] hover:bg-[#082a5c] text-white cursor-pointer shadow-2xs">Edit Prodi</button>
                        <button @click="modalDetail = false" class="px-4 py-2 text-xs font-bold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL TAMBAH PRODI (Dengan 4 Komponen Biaya & 7 Pilihan UKT) -->
        <!-- ============================================================== -->
        <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="modalAdd = false"></div>
                <div class="inline-block w-full max-w-3xl p-6 bg-white shadow-2xl rounded-3xl relative z-10 border border-slate-100">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-black text-slate-900">Tambah Program Studi</h3>
                            <p class="text-xs text-slate-500">Atur profil prodi, 4 komponen biaya 1x bayar, dan 7 opsi periode pembayaran UKT.</p>
                        </div>
                        <button @click="modalAdd = false" class="text-slate-400 hover:text-slate-600 cursor-pointer p-1">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form action="{{ route('admin.prodi.store') }}" method="POST" class="mt-4 space-y-5 text-xs max-h-[75vh] overflow-y-auto pr-1">
                        @csrf

                        <!-- Profil Prodi -->
                        <div class="space-y-3">
                            <h4 class="font-black text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#0b3d82]"></span>
                                Informasi Umum Program Studi
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Kode Prodi *</label>
                                    <input type="text" name="kode" required placeholder="TI-S1" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Jenjang *</label>
                                    <select name="jenjang" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-blue-100">
                                        <option value="">Pilih Jenjang</option>
                                        @foreach($jenjangList as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Target Kuota *</label>
                                    <input type="number" name="kuota" required placeholder="100" min="0" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Nama Program Studi *</label>
                                    <input type="text" name="nama" required placeholder="Contoh: Teknik Informatika" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div x-data="{ modeManualFakultas: false }">
                                    <label class="flex justify-between items-end font-bold text-slate-700 mb-1">
                                        <span>Fakultas *</span>
                                        <button type="button" @click="modeManualFakultas = !modeManualFakultas" class="text-[10px] text-[#0b3d82] hover:underline" x-text="modeManualFakultas ? 'Pilih dari List' : 'Input Manual'"></button>
                                    </label>
                                    <select x-show="!modeManualFakultas" name="fakultas" :required="!modeManualFakultas" :disabled="modeManualFakultas" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-blue-100">
                                        <option value="">Pilih Fakultas</option>
                                        @foreach($fakultasList as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach
                                    </select>
                                    <input x-show="modeManualFakultas" type="text" name="fakultas" :required="modeManualFakultas" :disabled="!modeManualFakultas" placeholder="Ketik nama fakultas baru..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Status Prodi</label>
                                <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none"><option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option></select>
                            </div>
                        </div>

                        <!-- 1. 4 Komponen Biaya 1x Bayar (Bisa Rp 0) -->
                        <div class="p-4 rounded-2xl bg-slate-50/90 border border-slate-200/90 space-y-3">
                            <div>
                                <h4 class="font-black text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-[#0b3d82]"></span>
                                    1. Komponen Biaya 1x Bayar (Bisa Rp 0)
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Pendaftaran, perlengkapan, DPP, dan uang gedung hanya 1x bayar dan dapat diatur Rp 0 jika digratiskan.</p>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Biaya Pendaftaran</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-[11px]">Rp</span>
                                        <input type="number" name="biaya_pendaftaran" value="300000" min="0" step="1000" class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Biaya Perlengkapan</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-[11px]">Rp</span>
                                        <input type="number" name="biaya_perlengkapan" value="600000" min="0" step="1000" class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">DPP PMB</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-[11px]">Rp</span>
                                        <input type="number" name="biaya_dpp" value="500000" min="0" step="1000" class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Uang Gedung</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-[11px]">Rp</span>
                                        <input type="number" name="biaya_uang_gedung" value="0" min="0" step="1000" class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Opsi Pembayaran UKT (7 Pilihan Periode) -->
                        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 space-y-3">
                            <div>
                                <h4 class="font-black text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    2. Opsi Pilihan Periode Pembayaran UKT
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">UKT bisa dipilih per bulan, per 3 bulan, per 6 bulan, per 12 bulan, per 24 bulan, per 36 bulan, atau per 48 bulan. Centang untuk mengaktifkan opsi.</p>
                            </div>

                            <div class="space-y-2 border border-slate-100 rounded-xl p-3 bg-slate-50/50">
                                @php
                                    $defaultPeriods = [
                                        ['key' => '1_bulan',  'label' => 'Per Bulan (1 Bulan)',     'def_nom' => 750000,   'active' => true],
                                        ['key' => '3_bulan',  'label' => 'Per 3 Bulan (Triwulan)',  'def_nom' => 2250000,  'active' => true],
                                        ['key' => '6_bulan',  'label' => 'Per 6 Bulan (Semester)',  'def_nom' => 4500000,  'active' => true],
                                        ['key' => '12_bulan', 'label' => 'Per 12 Bulan (1 Tahun)', 'def_nom' => 8500000,  'active' => true],
                                        ['key' => '24_bulan', 'label' => 'Per 24 Bulan (2 Tahun)', 'def_nom' => 16500000, 'active' => false],
                                        ['key' => '36_bulan', 'label' => 'Per 36 Bulan (3 Tahun)', 'def_nom' => 24500000, 'active' => false],
                                        ['key' => '48_bulan', 'label' => 'Per 48 Bulan (4 Tahun)', 'def_nom' => 32000000, 'active' => false],
                                    ];
                                @endphp

                                @foreach($defaultPeriods as $dp)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2.5 rounded-lg bg-white border border-slate-100">
                                        <label class="flex items-center gap-2.5 cursor-pointer">
                                            <input type="checkbox" name="ukt_options[{{ $dp['key'] }}][is_active]" value="1" {{ $dp['active'] ? 'checked' : '' }} class="rounded border-slate-300 text-[#0b3d82] focus:ring-blue-100">
                                            <span class="font-bold text-slate-800 text-xs">{{ $dp['label'] }}</span>
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <span class="text-slate-400 text-[11px] font-bold">Nominal:</span>
                                            <div class="relative w-36">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-[10px]">Rp</span>
                                                <input type="number" name="ukt_options[{{ $dp['key'] }}][nominal]" value="{{ $dp['def_nom'] }}" min="0" step="1000" class="w-full text-xs pl-7 pr-2 py-1.5 rounded-lg border border-slate-200 outline-none focus:ring-1 focus:ring-blue-100 font-bold text-slate-800 text-right">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="modalAdd = false" class="px-4 py-2.5 text-xs font-bold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2.5 text-xs font-bold rounded-xl bg-[#0b3d82] hover:bg-[#082a5c] text-white shadow-xs cursor-pointer">Simpan Program Studi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL EDIT PRODI (Dengan 4 Komponen Biaya & 7 Pilihan UKT) -->
        <!-- ============================================================== -->
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="modalEdit = false"></div>
                <div class="inline-block w-full max-w-3xl p-6 bg-white shadow-2xl rounded-3xl relative z-10 border border-slate-100">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-black text-slate-900">Edit Program Studi & Komponen Biaya</h3>
                            <p class="text-xs text-slate-500">Perbarui profil prodi, 4 komponen 1x bayar, dan status aktif serta nominal 7 skema UKT.</p>
                        </div>
                        <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 cursor-pointer p-1">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form :action="selectedProdi ? '/admin/prodi/' + selectedProdi.id : ''" method="POST" class="mt-4 space-y-5 text-xs max-h-[75vh] overflow-y-auto pr-1">
                        @csrf
                        @method('PUT')

                        <!-- Profil Prodi -->
                        <div class="space-y-3">
                            <h4 class="font-black text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#0b3d82]"></span>
                                Informasi Umum Program Studi
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Kode Prodi</label>
                                    <input type="text" name="kode" required :value="selectedProdi ? selectedProdi.kode : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Jenjang</label>
                                    <select name="jenjang" required :value="selectedProdi ? selectedProdi.jenjang : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none">
                                        <option value="">Pilih Jenjang</option>
                                        @foreach($jenjangList as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Target Kuota</label>
                                    <input type="number" name="kuota" required :value="selectedProdi ? selectedProdi.kuota : ''" min="0" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Nama Program Studi</label>
                                    <input type="text" name="nama" required :value="selectedProdi ? selectedProdi.nama : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div x-data="{ modeManualFakultasEdit: false }">
                                    <label class="flex justify-between items-end font-bold text-slate-700 mb-1">
                                        <span>Fakultas *</span>
                                        <button type="button" @click="modeManualFakultasEdit = !modeManualFakultasEdit" class="text-[10px] text-[#0b3d82] hover:underline" x-text="modeManualFakultasEdit ? 'Pilih dari List' : 'Input Manual'"></button>
                                    </label>
                                    <select x-show="!modeManualFakultasEdit" name="fakultas" :required="!modeManualFakultasEdit" :disabled="modeManualFakultasEdit" :value="selectedProdi ? selectedProdi.fakultas : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-blue-100">
                                        <option value="">Pilih Fakultas</option>
                                        @foreach($fakultasList as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach
                                    </select>
                                    <input x-show="modeManualFakultasEdit" type="text" name="fakultas" :required="modeManualFakultasEdit" :disabled="!modeManualFakultasEdit" :value="selectedProdi ? selectedProdi.fakultas : ''" placeholder="Ketik nama fakultas baru..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Status</label>
                                <select name="status" :value="selectedProdi ? selectedProdi.status : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none">
                                    <option value="Aktif">Aktif</option>
                                    <option value="Nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        <!-- 1. 4 Komponen Biaya 1x Bayar (Bisa Rp 0) -->
                        <div class="p-4 rounded-2xl bg-slate-50/90 border border-slate-200/90 space-y-3">
                            <div>
                                <h4 class="font-black text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-[#0b3d82]"></span>
                                    1. Komponen Biaya 1x Bayar (Bisa Rp 0)
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Pendaftaran, perlengkapan, DPP, dan uang gedung hanya 1x bayar dan dapat diatur Rp 0 jika digratiskan.</p>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Biaya Pendaftaran</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-[11px]">Rp</span>
                                        <input type="number" name="biaya_pendaftaran" :value="selectedProdi ? selectedProdi.biaya_pendaftaran : 0" min="0" step="1000" class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Biaya Perlengkapan</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-[11px]">Rp</span>
                                        <input type="number" name="biaya_perlengkapan" :value="selectedProdi ? selectedProdi.biaya_perlengkapan : 0" min="0" step="1000" class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">DPP PMB</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-[11px]">Rp</span>
                                        <input type="number" name="biaya_dpp" :value="selectedProdi ? selectedProdi.biaya_dpp : 0" min="0" step="1000" class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Uang Gedung</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-[11px]">Rp</span>
                                        <input type="number" name="biaya_uang_gedung" :value="selectedProdi ? selectedProdi.biaya_uang_gedung : 0" min="0" step="1000" class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-100">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Opsi Pembayaran UKT (7 Pilihan Periode) -->
                        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 space-y-3">
                            <div>
                                <h4 class="font-black text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    2. Opsi Pilihan Periode Pembayaran UKT
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">UKT bisa dipilih per bulan, per 3 bulan, per 6 bulan, per 12 bulan, per 24 bulan, per 36 bulan, atau per 48 bulan. Centang untuk mengaktifkan opsi.</p>
                            </div>

                            <div class="space-y-2 border border-slate-100 rounded-xl p-3 bg-slate-50/50">
                                @php
                                    $editPeriods = [
                                        ['key' => '1_bulan',  'label' => 'Per Bulan (1 Bulan)'],
                                        ['key' => '3_bulan',  'label' => 'Per 3 Bulan (Triwulan)'],
                                        ['key' => '6_bulan',  'label' => 'Per 6 Bulan (Semester)'],
                                        ['key' => '12_bulan', 'label' => 'Per 12 Bulan (1 Tahun)'],
                                        ['key' => '24_bulan', 'label' => 'Per 24 Bulan (2 Tahun)'],
                                        ['key' => '36_bulan', 'label' => 'Per 36 Bulan (3 Tahun)'],
                                        ['key' => '48_bulan', 'label' => 'Per 48 Bulan (4 Tahun)'],
                                    ];
                                @endphp

                                @foreach($editPeriods as $ep)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2.5 rounded-lg bg-white border border-slate-100">
                                        <label class="flex items-center gap-2.5 cursor-pointer">
                                            <input type="checkbox" name="ukt_options[{{ $ep['key'] }}][is_active]" value="1"
                                                :checked="selectedProdi && selectedProdi.ukt_options && selectedProdi.ukt_options['{{ $ep['key'] }}'] && selectedProdi.ukt_options['{{ $ep['key'] }}'].is_active"
                                                class="rounded border-slate-300 text-[#0b3d82] focus:ring-blue-100">
                                            <span class="font-bold text-slate-800 text-xs">{{ $ep['label'] }}</span>
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <span class="text-slate-400 text-[11px] font-bold">Nominal:</span>
                                            <div class="relative w-36">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-[10px]">Rp</span>
                                                <input type="number" name="ukt_options[{{ $ep['key'] }}][nominal]"
                                                    :value="selectedProdi && selectedProdi.ukt_options && selectedProdi.ukt_options['{{ $ep['key'] }}'] ? selectedProdi.ukt_options['{{ $ep['key'] }}'].nominal : 0"
                                                    min="0" step="1000" class="w-full text-xs pl-7 pr-2 py-1.5 rounded-lg border border-slate-200 outline-none focus:ring-1 focus:ring-blue-100 font-bold text-slate-800 text-right">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="modalEdit = false" class="px-4 py-2.5 text-xs font-bold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2.5 text-xs font-bold rounded-xl bg-[#0b3d82] hover:bg-[#082a5c] text-white shadow-xs cursor-pointer">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
