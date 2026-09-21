@php $pageTitle = 'Kelola Kunjungan'; @endphp

<x-app-layout :title="'Kelola Kunjungan - CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalDetail: false,
        modalEdit: false,
        modalFoto: false,
        selectedKjg: null,
        selectedFoto: null,
        searchQuery: '',
        filterJenis: 'all',
        filterStatus: 'all',
        filterSales: 'all',
        kunjungan: {{ json_encode($kunjungan) }},
        get filtered() {
            return this.kunjungan.filter(k => {
                const q = this.searchQuery.toLowerCase();
                const matchQ = !q || k.id.toLowerCase().includes(q) || k.nama_tempat.toLowerCase().includes(q) || k.sales.toLowerCase().includes(q);
                const matchJ = this.filterJenis === 'all' || k.jenis.toLowerCase() === this.filterJenis.toLowerCase();
                const matchS = this.filterStatus === 'all' || k.status.toLowerCase() === this.filterStatus.toLowerCase();
                const matchSales = this.filterSales === 'all' || k.sales === this.filterSales;
                return matchQ && matchJ && matchS && matchSales;
            });
        }
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Kelola Kunjungan</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Monitoring dan pengelolaan seluruh data kunjungan Sales ke sekolah & perusahaan.</p>
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold">
                <span class="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 border border-blue-200">{{ count($kunjungan) }} Total Kunjungan</span>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="crm-card bg-white p-4 space-y-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="searchQuery" placeholder="Cari nomor, sekolah/perusahaan, nama Sales..." class="w-full text-xs pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-200">
                </div>
                <select x-model="filterSales" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none sm:w-44">
                    <option value="all">Semua Sales</option>
                    @foreach($salesList as $s)
                        @if($s !== 'Semua Sales')<option value="{{ $s }}">{{ $s }}</option>@endif
                    @endforeach
                </select>
                <select x-model="filterJenis" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none sm:w-40">
                    <option value="all">Semua Jenis</option>
                    <option value="sekolah">Sekolah</option>
                    <option value="korporasi">Korporasi</option>
                </select>
                <select x-model="filterStatus" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none sm:w-36">
                    <option value="all">Semua Status</option>
                    <option value="selesai">Selesai</option>
                    <option value="proses">Proses</option>
                    <option value="menunggu">Menunggu</option>
                </select>
            </div>
            <div class="text-[11px] text-slate-400">Menampilkan <span class="font-bold text-slate-700" x-text="filtered.length"></span> kunjungan</div>
        </div>

        <!-- Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-4">No. Kunjungan</th>
                            <th class="py-3.5 px-3">Tanggal</th>
                            <th class="py-3.5 px-3">Sales</th>
                            <th class="py-3.5 px-3">Jenis</th>
                            <th class="py-3.5 px-3">Nama Sekolah</th>
                            <th class="py-3.5 px-3 text-center">Foto Dokumentasi</th>
                            <th class="py-3.5 px-3 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="k in filtered" :key="k.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-purple-700 text-[11px]" x-text="k.id"></td>
                                <td class="py-3.5 px-3">
                                    <div class="font-semibold text-slate-800" x-text="k.tanggal"></div>
                                    <div class="text-[11px] text-slate-400" x-text="k.waktu + ' WIB'"></div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold text-[10px] flex items-center justify-center shrink-0" x-text="k.sales.split(' ').map(w=>w[0]).join('').slice(0,2)"></div>
                                        <span class="font-semibold text-slate-800" x-text="k.sales"></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                        :class="k.jenis === 'Sekolah' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-purple-50 text-purple-700 border-purple-200'"
                                        x-text="k.jenis"></span>
                                </td>
                                <td class="py-3.5 px-3 font-semibold text-slate-900 max-w-[160px]" x-text="k.tujuan"></td>
                                <td class="py-3.5 px-3 text-center">
                                    <template x-if="k.foto">
                                        <button type="button" @click="selectedFoto = k.foto; modalFoto = true" class="inline-block cursor-pointer">
                                            <img :src="k.foto" class="h-10 w-10 rounded-md object-cover shadow-sm border border-slate-200 hover:scale-105 transition" alt="Foto">
                                        </button>
                                    </template>
                                    <template x-if="!k.foto">
                                        <span class="text-[10px] text-slate-400 italic">Tidak ada foto</span>
                                    </template>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200': k.status === 'Selesai',
                                            'bg-amber-50 text-amber-700 border border-amber-200': k.status === 'Proses',
                                            'bg-slate-100 text-slate-600 border border-slate-200': k.status === 'Menunggu'
                                        }"
                                        x-text="k.status"></span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="selectedKjg = k; modalDetail = true" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer">Detail</button>
                                        <button @click="selectedKjg = k; modalEdit = true" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold transition cursor-pointer">Edit</button>
                                        <form :action="'/admin/kunjungan/' + k.id" method="POST" class="inline" @submit="if(!confirm('Hapus kunjungan ini?')) $event.preventDefault()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL DETAIL KUNJUNGAN -->
        <div x-show="modalDetail" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalDetail = false"></div>
                <div class="inline-block w-full max-w-xl bg-white shadow-2xl rounded-2xl relative z-10 overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Detail Kunjungan</h3>
                            <span class="text-xs font-mono text-purple-600 font-bold" x-text="selectedKjg ? selectedKjg.id : ''"></span>
                        </div>
                        <button @click="modalDetail = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <template x-if="selectedKjg">
                        <div class="p-6 space-y-4 text-xs overflow-y-auto max-h-[70vh]">
                            <!-- Info Grid -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <p class="text-slate-400 mb-0.5">Tanggal & Waktu</p>
                                    <p class="font-bold text-slate-800" x-text="selectedKjg.tanggal + ', ' + selectedKjg.waktu + ' WIB'"></p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <p class="text-slate-400 mb-0.5">Sales</p>
                                    <p class="font-bold text-slate-800" x-text="selectedKjg.sales"></p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <p class="text-slate-400 mb-0.5">Jenis Kunjungan</p>
                                    <p class="font-bold text-slate-800" x-text="selectedKjg.jenis"></p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    <p class="text-slate-400 mb-0.5">Status</p>
                                    <p class="font-bold text-slate-800" x-text="selectedKjg.status"></p>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <p class="text-slate-400 mb-0.5">Nama Sekolah / Perusahaan</p>
                                <p class="font-bold text-slate-900 text-sm" x-text="selectedKjg.nama_tempat"></p>
                            </div>
                            <div class="p-3 rounded-xl bg-blue-50 border border-blue-100">
                                <p class="text-blue-600 font-semibold mb-0.5">PIC / Kontak</p>
                                <p class="font-bold text-slate-800" x-text="selectedKjg.pic"></p>
                                <p class="text-slate-500 mt-0.5" x-text="selectedKjg.pic_phone"></p>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <p class="text-slate-400 mb-0.5">Tujuan Kunjungan</p>
                                <p class="text-slate-800" x-text="selectedKjg.tujuan"></p>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100">
                                <p class="text-emerald-700 font-semibold mb-0.5">Hasil Kunjungan</p>
                                <p class="text-slate-800" x-text="selectedKjg.hasil"></p>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <p class="text-slate-400 mb-0.5">Catatan</p>
                                <p class="text-slate-600 italic" x-text="selectedKjg.catatan"></p>
                            </div>
                            <!-- Riwayat -->
                            <div>
                                <p class="font-bold text-slate-700 mb-2 uppercase tracking-wider text-[11px]">Riwayat Perubahan</p>
                                <div class="space-y-2">
                                    <template x-for="(r, i) in selectedKjg.riwayat" :key="i">
                                        <div class="flex items-start gap-3">
                                            <div class="w-2 h-2 rounded-full bg-purple-400 mt-1.5 shrink-0"></div>
                                            <div>
                                                <div class="font-semibold text-slate-700" x-text="r.aksi"></div>
                                                <div class="text-[11px] text-slate-400" x-text="r.waktu"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-2">
                        <button @click="modalDetail = false; modalEdit = true" class="px-4 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white cursor-pointer">Edit Data</button>
                        <button @click="modalDetail = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 cursor-pointer">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT KUNJUNGAN -->
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalEdit = false"></div>
                <div class="inline-block w-full max-w-lg p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Edit Data Kunjungan</h3>
                        <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form :action="selectedKjg ? '/admin/kunjungan/' + selectedKjg.id : ''" method="POST" class="mt-4 space-y-3 text-xs">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal</label>
                                <input type="date" name="tanggal" required :value="selectedKjg ? new Date(selectedKjg.tanggal).toISOString().split('T')[0] : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Jenis Kunjungan</label>
                                <select name="jenis" required :value="selectedKjg ? selectedKjg.jenis : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none">
                                    <option value="Sekolah">Sekolah</option>
                                    <option value="Perusahaan">Perusahaan</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Sekolah / Perusahaan (Hanya Baca)</label>
                            <input type="text" disabled :value="selectedKjg ? selectedKjg.nama_tempat : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tujuan Kunjungan</label>
                            <textarea name="tujuan_kunjungan" rows="2" required :value="selectedKjg ? selectedKjg.tujuan : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Hasil Kunjungan</label>
                            <textarea name="hasil" rows="2" :value="selectedKjg ? selectedKjg.hasil : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Status</label>
                            <select name="status" required :value="selectedKjg ? selectedKjg.status : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none">
                                <option value="Selesai">Selesai</option>
                                <option value="Proses">Proses</option>
                                <option value="Menunggu">Menunggu</option>
                            </select>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="modalEdit = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL FOTO DOKUMENTASI -->
        <div x-show="modalFoto" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="modalFoto = false"></div>
                
                <!-- Modal Panel -->
                <div class="relative max-w-xl w-auto mx-auto flex flex-col items-center justify-center" @click.away="modalFoto = false">
                    
                    <!-- Tombol Silang (Tutup) -->
                    <button @click="modalFoto = false" class="absolute top-3 right-3 p-1.5 bg-slate-900/60 hover:bg-rose-600 text-white rounded-full transition-colors cursor-pointer z-10 shadow-sm backdrop-blur-md border border-white/20">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Foto Full -->
                    <img :src="selectedFoto" alt="Dokumentasi Full" class="max-w-full max-h-[70vh] object-contain rounded-xl shadow-2xl bg-white p-2">
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
