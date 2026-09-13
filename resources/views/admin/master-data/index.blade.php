@php
    $pageTitle = 'Master Data CRM UCIC';
    $pageSubtitle = 'Konfigurasi Program Studi, Tipe Prospek & Gelombang Pendaftaran';
@endphp

<x-app-layout :title="'Master Data - CRM UCIC'">

    <div class="space-y-6" x-data="{ 
        activeTab: 'prodi',
        modalAddProdi: false,
        modalAddBatch: false 
    }">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Master Data & Konfigurasi PMB</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Konfigurasi kuota program studi, kategori inbound prospek, dan gelombang beasiswa UCIC.</p>
            </div>
            
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    @click="modalAddProdi = true"
                    class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    <span>+ Tambah Program Studi</span>
                </button>
            </div>
        </div>

        <!-- Master Data Navigation Tabs -->
        <div class="flex border-b border-slate-200 bg-white px-4 rounded-2xl border">
            <button 
                type="button" 
                @click="activeTab = 'prodi'"
                :class="activeTab === 'prodi' ? 'border-purple-600 text-purple-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-4 text-xs sm:text-sm font-semibold transition cursor-pointer"
            >
                Program Studi & Kuota ({{ count($masterData['prodi']) }})
            </button>
            <button 
                type="button" 
                @click="activeTab = 'categories'"
                :class="activeTab === 'categories' ? 'border-purple-600 text-purple-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-4 text-xs sm:text-sm font-semibold transition cursor-pointer"
            >
                Tipe & Kategori Prospek ({{ count($masterData['categories']) }})
            </button>
            <button 
                type="button" 
                @click="activeTab = 'batches'"
                :class="activeTab === 'batches' ? 'border-purple-600 text-purple-700 font-bold border-b-2' : 'border-transparent text-slate-500 hover:text-slate-700'"
                class="py-3.5 px-4 text-xs sm:text-sm font-semibold transition cursor-pointer"
            >
                Gelombang Pendaftaran ({{ count($masterData['batches']) }})
            </button>
        </div>

        <!-- TAB 1: PROGRAM STUDI & KUOTA -->
        <div x-show="activeTab === 'prodi'" class="crm-card bg-white overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Daftar Program Studi UCIC</h3>
                <span class="text-xs text-slate-500">Tahun Akademik 2026/2027</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3.5 px-4">Kode & Nama Program Studi</th>
                            <th class="py-3.5 px-3">Fakultas</th>
                            <th class="py-3.5 px-3 text-center">Target Kuota</th>
                            <th class="py-3.5 px-3 text-center">Terdaftar</th>
                            <th class="py-3.5 px-3">Biaya SPP / Biaya Studi</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($masterData['prodi'] as $p)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900">{{ $p['name'] }}</div>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600 font-mono">{{ $p['code'] }}</span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600 font-medium">{{ $p['faculty'] }}</td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-800">{{ $p['quota'] }} Mhs</td>
                                <td class="py-3.5 px-3 text-center font-bold text-emerald-600">{{ $p['registered'] }} Mhs</td>
                                <td class="py-3.5 px-3 text-slate-600">{{ $p['tuition'] }}</td>
                                <td class="py-3.5 px-4 text-right">
                                    <button @click="$store.crm.showToast('Edit data prodi: {{ $p['name'] }}')" class="text-purple-600 hover:text-purple-700 font-semibold cursor-pointer">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: KATEGORI & TIPE PROSPEK -->
        <div x-show="activeTab === 'categories'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($masterData['categories'] as $cat)
                <div class="crm-card bg-white p-5 space-y-3 hover:border-purple-300 transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-sm text-slate-900">{{ $cat['name'] }}</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                {{ $cat['leads_count'] }} Prospek
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-2 leading-relaxed">{{ $cat['description'] }}</p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-400 font-medium">Status: Aktif</span>
                        <button @click="$store.crm.showToast('Pengaturan kategori disimpan')" class="text-xs font-semibold text-purple-600 hover:text-purple-700 cursor-pointer">
                            Edit Kategori
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- TAB 3: GELOMBANG PENDAFTARAN -->
        <div x-show="activeTab === 'batches'" x-cloak class="crm-card bg-white p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Gelombang Penerimaan Mahasiswa Baru</h3>
                <button @click="$store.crm.showToast('Modal tambah gelombang dibuka')" class="text-xs font-bold text-purple-600 hover:text-purple-700">
                    + Gelombang Baru
                </button>
            </div>

            <div class="space-y-3">
                @foreach($masterData['batches'] as $batch)
                    <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/40 hover:bg-white transition flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-sm text-slate-900">{{ $batch['name'] }}</h4>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $batch['status'] === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $batch['status'] }}
                                </span>
                            </div>
                            <div class="text-slate-500">Periode: <strong>{{ $batch['period'] }}</strong> &bull; Promo: <span class="text-blue-600 font-medium">{{ $batch['discount'] }}</span></div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="$store.crm.showToast('Gelombang berhasil diperbarui!')" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50">
                                Edit
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- MODAL TAMBAH PRODI -->
        <div x-show="modalAddProdi" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div x-show="modalAddProdi" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalAddProdi = false"></div>

                <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah Program Studi Baru</h3>
                        <button @click="modalAddProdi = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form @submit.prevent="modalAddProdi = false; $store.crm.showToast('Program studi baru berhasil ditambahkan!')" class="mt-4 space-y-4 text-xs">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Program Studi *</label>
                            <input type="text" required placeholder="Contoh: S1 Rekayasa Perangkat Lunak AI" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Prodi</label>
                                <input type="text" placeholder="RPL-S1" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Fakultas</label>
                                <select class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                    <option value="FTI">Fakultas Teknologi Informasi (FTI)</option>
                                    <option value="FEB">Fakultas Ekonomi & Bisnis (FEB)</option>
                                    <option value="Pascasarjana">Pascasarjana</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Target Kuota Mahasiswa</label>
                                <input type="number" placeholder="80" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Biaya SPP Per Semester</label>
                                <input type="text" placeholder="Rp 4.500.000" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="modalAddProdi = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white shadow-xs">Simpan Prodi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
