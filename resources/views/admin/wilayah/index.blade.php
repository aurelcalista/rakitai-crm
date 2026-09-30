@php $pageTitle = 'Data Wilayah'; @endphp

<x-app-layout :title="'Data Wilayah - CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalAdd: false,
        modalEdit: false,
        modalDetail: false,
        selectedWil: null,
        searchQuery: '',
        filterStatus: 'all',
        currentPage: 1,
        perPage: 25,
        newKecamatan: '',
        newKecamatanList: [],
        wilayah: {{ json_encode($wilayah) }},
        get filtered() {
            return this.wilayah.filter(w => {
                const q = this.searchQuery.toLowerCase();
                const matchQ = !q || w.kode.toLowerCase().includes(q) || w.nama.toLowerCase().includes(q) || w.kecamatan.some(k => k.toLowerCase().includes(q));
                const matchS = this.filterStatus === 'all' || w.status.toLowerCase() === this.filterStatus.toLowerCase();
                return matchQ && matchS;
            });
        },
        get paginated() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },
        get totalPages() {
            return Math.ceil(this.filtered.length / this.perPage) || 1;
        },
        addKecamatan() {
            if (this.newKecamatan.trim() && !this.newKecamatanList.includes(this.newKecamatan.trim())) {
                this.newKecamatanList.push(this.newKecamatan.trim());
                this.newKecamatan = '';
            }
        },
        removeKecamatan(k) { this.newKecamatanList = this.newKecamatanList.filter(x => x !== k); }
    }" x-effect="searchQuery; filterStatus; currentPage = 1">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Data Wilayah</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola wilayah dan kecamatan yang digunakan untuk data sekolah & perusahaan.</p>
            </div>
            <button type="button" @click="newKecamatanList = []; modalAdd = true" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Wilayah</span>
            </button>
        </div>

        <!-- Search & Filter -->
        <div class="crm-card bg-white p-4 flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="searchQuery" placeholder="Cari kode, nama wilayah, kecamatan..." class="w-full text-xs pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-200">
            </div>
            <select x-model="filterStatus" class="text-xs px-3 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none sm:w-36">
                <option value="all">Semua Status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </select>
        </div>

        <!-- Table -->
        <div class="crm-card bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-3 w-10 text-center">No</th>
                            <th class="py-3.5 px-4">Kode</th>
                            <th class="py-3.5 px-3">Nama Wilayah</th>
                            <th class="py-3.5 px-3">Kecamatan</th>
                            <th class="py-3.5 px-3 text-center">Jml Kec.</th>
                            <th class="py-3.5 px-3 text-center">Sekolah</th>
                            <th class="py-3.5 px-3 text-center">Perusahaan</th>
                            <th class="py-3.5 px-3 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(w, index) in paginated" :key="w.id + '-' + currentPage">
                            <tr class="hover:bg-slate-50/80 transition crm-table-slide">
                                <td class="py-3.5 px-3 text-center font-bold text-slate-400 text-xs" x-text="(currentPage - 1) * perPage + index + 1"></td>
                                <td class="py-3.5 px-4 font-mono font-bold text-purple-700 text-[11px]" x-text="w.kode"></td>
                                <td class="py-3.5 px-3 font-bold text-slate-900" x-text="w.nama"></td>
                                <td class="py-3.5 px-3 max-w-[200px]">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="(kec, i) in w.kecamatan.slice(0,3)" :key="i">
                                            <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600 font-medium" x-text="kec"></span>
                                        </template>
                                        <template x-if="w.kecamatan.length > 3">
                                            <button @click="selectedWil = w; modalDetail = true" class="px-1.5 py-0.5 rounded text-[10px] bg-purple-50 hover:bg-purple-100 text-purple-600 font-medium cursor-pointer transition" x-text="'+' + (w.kecamatan.length - 3) + ' lagi'"></button>
                                        </template>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-800" x-text="w.kecamatan.length"></td>
                                <td class="py-3.5 px-3 text-center font-bold text-blue-700" x-text="w.jumlah_sekolah"></td>
                                <td class="py-3.5 px-3 text-center font-bold text-purple-700" x-text="w.jumlah_perusahaan"></td>
                                <td class="py-3.5 px-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="w.status === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-600 border border-red-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="w.status === 'Aktif' ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                        <span x-text="w.status"></span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="selectedWil = w; modalDetail = true" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer">Detail</button>
                                        <button @click="selectedWil = w; modalEdit = true" class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold cursor-pointer">Edit</button>
                                        <form :action="'/admin/wilayah/' + w.id + '/toggle-status'" method="POST" class="inline" @submit="if(!confirm(w.status === 'Aktif' ? 'Nonaktifkan wilayah ini?' : 'Aktifkan wilayah ini?')) $event.preventDefault()">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold cursor-pointer"
                                                :class="w.status === 'Aktif' ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700'"
                                                x-text="w.status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan'"></button>
                                        </form>
                                        <form :action="'/admin/wilayah/' + w.id" method="POST" class="inline" @submit="if(!confirm('Hapus wilayah ini?')) $event.preventDefault()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filtered.length === 0">
                            <td colspan="9" class="py-12 text-center text-slate-400 text-xs">
                                Tidak ada data wilayah yang cocok.
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
                color="purple"
            />
        </div>

        <!-- MODAL DETAIL -->
        <div x-show="modalDetail" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalDetail = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Detail Wilayah</h3>
                        <button @click="modalDetail = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <template x-if="selectedWil">
                        <div class="mt-4 space-y-4 text-xs">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-purple-50 border border-purple-200 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                </div>
                                <div>
                                    <div class="text-[11px] font-mono text-purple-600 font-bold" x-text="selectedWil.kode"></div>
                                    <div class="text-lg font-extrabold text-slate-900" x-text="selectedWil.nama"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="p-3 rounded-xl bg-blue-50 border border-blue-100 text-center">
                                    <div class="text-2xl font-extrabold text-blue-700" x-text="selectedWil.jumlah_sekolah"></div>
                                    <div class="text-[11px] text-blue-600">Sekolah</div>
                                </div>
                                <div class="p-3 rounded-xl bg-purple-50 border border-purple-100 text-center">
                                    <div class="text-2xl font-extrabold text-purple-700" x-text="selectedWil.jumlah_perusahaan"></div>
                                    <div class="text-[11px] text-purple-600">Perusahaan</div>
                                </div>
                            </div>
                            <div>
                                <p class="font-bold text-slate-700 mb-2 uppercase tracking-wider text-[11px]">Daftar Kecamatan (<span x-text="selectedWil.kecamatan.length"></span>)</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="(kec, i) in selectedWil.kecamatan" :key="i">
                                        <span class="px-2.5 py-1 rounded-lg text-[11px] bg-slate-100 text-slate-700 font-medium" x-text="kec"></span>
                                    </template>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                                <span class="text-slate-500">Status</span>
                                <span class="font-bold" :class="selectedWil.status === 'Aktif' ? 'text-emerald-600' : 'text-red-600'" x-text="selectedWil.status"></span>
                            </div>
                            <button @click="modalDetail = false; modalEdit = true" class="w-full py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold cursor-pointer">Edit Wilayah</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- MODAL TAMBAH WILAYAH -->
        <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAdd = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah Wilayah Baru</h3>
                        <button @click="modalAdd = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form action="{{ route('admin.wilayah.store') }}" method="POST" class="mt-4 space-y-3 text-xs">
                        @csrf
                        <input type="hidden" name="kecamatans" :value="newKecamatanList.join(',')">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kode Wilayah *</label>
                                <input type="text" name="kode" required placeholder="WIL-08" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Wilayah *</label>
                                <input type="text" name="nama" required placeholder="Contoh: Cirebon Tengah" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>
                        <!-- Kecamatan Input -->
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tambah Kecamatan</label>
                            <div class="flex gap-2">
                                <input type="text" x-model="newKecamatan" @keydown.enter.prevent="addKecamatan()" placeholder="Nama kecamatan, tekan Enter" class="flex-1 text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                                <button type="button" @click="addKecamatan()" class="px-3 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold cursor-pointer">+</button>
                            </div>
                            <div class="flex flex-wrap gap-1.5 mt-2" x-show="newKecamatanList.length > 0">
                                <template x-for="(kec, i) in newKecamatanList" :key="i">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-medium">
                                        <span x-text="kec"></span>
                                        <button type="button" @click="removeKecamatan(kec)" class="text-slate-400 hover:text-red-500 cursor-pointer">×</button>
                                    </span>
                                </template>
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Status</label>
                            <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                <option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="modalAdd = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white">Simpan Wilayah</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT WILAYAH -->
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalEdit = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Edit Wilayah</h3>
                        <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form :action="selectedWil ? '/admin/wilayah/' + selectedWil.id : ''" method="POST" class="mt-4 space-y-3 text-xs">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="kecamatans" :value="selectedWil && selectedWil.kecamatan ? selectedWil.kecamatan.join(',') : ''">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Kode Wilayah</label>
                                <input type="text" name="kode" :value="selectedWil ? selectedWil.kode : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Nama Wilayah</label>
                                <input type="text" name="nama" :value="selectedWil ? selectedWil.nama : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>
                        <div x-show="selectedWil">
                            <label class="block font-semibold text-slate-700 mb-2">Kecamatan</label>
                            <div class="flex flex-wrap gap-1.5 p-2 rounded-xl border border-slate-200 bg-slate-50 min-h-[40px]">
                                <template x-for="(kec, i) in (selectedWil ? selectedWil.kecamatan : [])" :key="i">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-white border border-slate-200 text-[11px] font-medium text-slate-700">
                                        <span x-text="kec"></span>
                                        <button type="button" @click="selectedWil.kecamatan.splice(i,1)" class="text-slate-400 hover:text-red-500 cursor-pointer">×</button>
                                    </span>
                                </template>
                            </div>
                            <div class="flex gap-2 mt-2">
                                <input type="text" x-model="newKecamatan" @keydown.enter.prevent="if(newKecamatan.trim()) { selectedWil.kecamatan.push(newKecamatan.trim()); newKecamatan=''; }" placeholder="Tambah kecamatan baru" class="flex-1 text-xs px-3.5 py-2 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                                <button type="button" @click="if(newKecamatan.trim()) { selectedWil.kecamatan.push(newKecamatan.trim()); newKecamatan=''; }" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer">+</button>
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Status</label>
                            <select name="status" :value="selectedWil ? selectedWil.status : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none">
                                <option value="Aktif">Aktif</option><option value="Nonaktif">Nonaktif</option>
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

    </div>

</x-app-layout>
