@php
    $pageTitle = 'Direktori Tim Sales (SPV)';
    $pageSubtitle = 'Daftar Anggota Tim Sales di Bawah Pengawasan SPV';
    $kecamatanJson = $kecamatanList->map(function($kec) {
        return [
            'id' => (string)$kec->id,
            'nama' => $kec->nama,
            'hasActiveSales' => !empty($kec->has_active_sales),
            'salesName' => $kec->active_sales_name ?? 'Aktif'
        ];
    })->values();
@endphp

<x-app-layout :title="'Anggota Tim Sales - Supervisor CRM'">
    <script>
        window.spvKecamatanList = @json($kecamatanJson);
    </script>

    <div class="space-y-6" x-data="{
        modalKecamatan: false,
        selectedMemberId: null,
        selectedMemberName: '',
        selectedWilayahId: '',
        customCity: '',
        useOtherCity: false,
        openKecamatanModal(member) {
            this.selectedMemberId = member.id;
            this.selectedMemberName = member.name;
            this.selectedWilayahId = member.wilayah_id ? String(member.wilayah_id) : '';
            this.customCity = member.lokasi_penugasan || '';
            this.useOtherCity = Boolean(member.is_other_city);
            this.modalKecamatan = true;
        }
    }">

        @if(session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif
        @if(session('error'))
            <x-alert type="error" :message="session('error')" />
        @endif
        @if(isset($errors) && $errors->any())
            <x-alert type="error">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Anggota Tim Sales & Penugasan Wilayah</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Wilayah Kerja Utama SPV: <strong class="text-slate-800">{{ $myWilayah }}</strong></p>
                <p class="text-[11px] text-slate-400 mt-0.5">SPV menugaskan <strong>Area / Kecamatan</strong> untuk Sales.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button onclick="document.getElementById('modalAddSales').classList.remove('hidden')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition cursor-pointer flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Sales Baru</span>
                </button>
                <button onclick="openModalGeneral()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs transition cursor-pointer">
                    + Penugasan Sales
                </button>
            </div>
        </div>

        <!-- Modal Tambah Sales Baru (SPV) -->
        <div id="modalAddSales" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl">
                <div class="flex items-center justify-between border-b pb-3 border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-emerald-50 text-emerald-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wide">TAMBAH SALES BARU</h3>
                            <p class="text-[11px] text-slate-400">Buat akun Sales baru & tentukan wilayah penugasan area</p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('modalAddSales').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition cursor-pointer text-xl font-bold">&times;</button>
                </div>

                <form action="{{ route('spv.tim.sales.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <!-- Informasi Role & Scope -->
                    <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-emerald-600 font-bold uppercase tracking-wider block">Scope Wilayah SPV: {{ $myWilayah }}</span>
                            <span class="text-xs text-slate-600">Role & Jabatan otomatis: <strong class="text-emerald-700">Sales</strong></span>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            Domain @cic.ac.id
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap Sales *</label>
                            <input type="text" name="name" required placeholder="Contoh: Budi Santoso" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs outline-none focus:ring-2 focus:ring-emerald-200">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Email Sales *</label>
                            <div class="flex items-center rounded-xl border border-slate-200 overflow-hidden focus-within:ring-2 focus-within:ring-emerald-200 focus-within:border-emerald-400 bg-white">
                                <input type="text" name="email_username" required placeholder="budi.sales" class="w-full text-xs px-3.5 py-2.5 outline-none border-0 bg-transparent text-slate-800">
                                <span class="bg-slate-100 text-slate-600 px-3 py-2.5 text-xs font-bold border-l border-slate-200 shrink-0 font-mono">
                                    @cic.ac.id
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="text-xs text-slate-700 font-semibold">Password Awal Akun: <strong class="font-mono text-emerald-700">123</strong></span>
                        </div>
                        <span class="text-[10px] text-slate-400">Sales dapat mengganti password via Edit Profile</span>
                    </div>

                    <!-- Pilih Area / Kecamatan Active Scope SPV (SEARCHABLE DROPDOWN PLUGIN) -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <label class="block font-bold text-slate-800 text-xs">Pilih Area / Kecamatan Sales (Scope {{ $myWilayah }}) *</label>
                        <p class="text-[11px] text-slate-400">Hanya wilayah yang belum memiliki Sales aktif yang dapat dipilih.</p>

                        <div class="relative" x-data="{
                            open: false,
                            search: '',
                            selectedId: '',
                            selectedNama: '',
                            get items() { return window.spvKecamatanList || []; },
                            get filteredItems() {
                                if (!this.search.trim()) return this.items;
                                return this.items.filter(item => item.nama.toLowerCase().includes(this.search.toLowerCase()));
                            },
                            selectArea(item) {
                                if (item.hasActiveSales) return;
                                this.selectedId = String(item.id);
                                this.selectedNama = item.nama;
                                this.open = false;
                            }
                        }" @click.outside="open = false">

                            <input type="hidden" name="area_id" :value="selectedId" required>

                            <!-- Trigger Button Plugin -->
                            <button type="button" @click="open = !open" class="w-full text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-emerald-500/20 flex items-center justify-between cursor-pointer transition shadow-xs">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span x-text="selectedId ? selectedNama : '-- Pilih Area / Kecamatan --'"></span>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu Plugin -->
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 p-2.5 overflow-hidden"
                                 style="display: none;">
                                
                                <div class="relative mb-2">
                                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input type="text" 
                                           x-model="search" 
                                           placeholder="Cari Area / Kecamatan..." 
                                           class="w-full text-xs pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-slate-800 font-medium"
                                           @keydown.escape="open = false">
                                </div>

                                <div class="max-h-52 overflow-y-auto space-y-1 custom-scrollbar">
                                    <template x-for="item in filteredItems" :key="item.id">
                                        <div @click="selectArea(item)" 
                                             class="w-full px-3 py-2 text-xs rounded-xl transition flex items-center justify-between font-semibold"
                                             :class="item.hasActiveSales ? 'bg-slate-100/70 text-slate-400 cursor-not-allowed border border-slate-100' : (selectedId == item.id ? 'bg-emerald-50 text-emerald-700 font-bold border border-emerald-200 cursor-pointer' : 'text-slate-700 hover:bg-slate-50 border border-transparent cursor-pointer')">
                                            
                                            <span x-text="item.nama"></span>
                                            
                                            <template x-if="item.hasActiveSales">
                                                <span class="text-[10px] font-medium text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-100 shrink-0 ml-2" x-text="'Sudah ada Sales (' + item.salesName + ')'"></span>
                                            </template>
                                            <template x-if="!item.hasActiveSales">
                                                <span class="text-[10px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 shrink-0 ml-2">Tersedia</span>
                                            </template>
                                        </div>
                                    </template>
                                    
                                    <div x-show="filteredItems.length === 0" class="px-3 py-3 text-xs text-slate-400 italic text-center">
                                        Kecamatan tidak ditemukan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" onclick="document.getElementById('modalAddSales').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs">Simpan & Buat Sales</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Penugasan Sales (Dedicated Sales Only) -->
        <div id="modalAddMember" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div id="territoryModalBox" class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl transition-all duration-200">
                <div class="flex items-center justify-between border-b pb-3 border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <h3 id="territoryModalTitle" class="font-bold text-slate-900 text-sm uppercase tracking-wide">PENUGASAN SALES</h3>
                            <p id="territoryModalSubtitle" class="text-[11px] text-slate-400">Atur pembagian wilayah kecamatan untuk Sales</p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('modalAddMember').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition cursor-pointer text-xl font-bold">&times;</button>
                </div>

                <form action="{{ route('spv.tim.territory.assign') }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <!-- Wilayah Utama Scope SPV -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3.5 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold block uppercase tracking-wider">Wilayah Utama Scope SPV</span>
                            <span class="text-sm font-bold text-slate-800">{{ $myWilayah }}</span>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                            Kota/Kabupaten Scope
                        </span>
                    </div>

                    <!-- Single Column Sales Assignment -->
                    <div class="bg-blue-50/40 border border-blue-100 rounded-xl p-4 space-y-4">
                        <div class="flex items-center gap-2 border-b border-blue-100 pb-2">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Penugasan Sales</h4>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Pilih Sales *</label>
                            <select name="sales_id" id="salesSelect" required onchange="onSalesChange(this.value)" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Pilih Sales --</option>
                                @foreach($salesCandidates as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->email }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Area / Kecamatan Sales *</label>
                            
                            <div class="relative" x-data="{
                                open: false,
                                search: '',
                                selectedId: '',
                                selectedNama: '',
                                get items() { return window.spvKecamatanList || []; },
                                get filteredItems() {
                                    if (!this.search.trim()) return this.items;
                                    return this.items.filter(item => item.nama.toLowerCase().includes(this.search.toLowerCase()));
                                },
                                selectArea(item) {
                                    this.selectedId = String(item.id);
                                    this.selectedNama = item.nama;
                                    this.open = false;
                                },
                                init() {
                                    window.addEventListener('sales-selected', (e) => {
                                        const areaId = e.detail.areaId;
                                        this.selectedId = areaId ? String(areaId) : '';
                                        const found = this.items.find(i => String(i.id) === String(this.selectedId));
                                        this.selectedNama = found ? found.nama : '';
                                    });
                                }
                            }" @click.outside="open = false" id="salesAreaContainer">

                                <input type="hidden" name="sales_area_id" id="salesAreaSelect" :value="selectedId" required>

                                <!-- Trigger Button Plugin -->
                                <button type="button" @click="open = !open" class="w-full text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-indigo-500/20 flex items-center justify-between cursor-pointer transition shadow-xs">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span x-text="selectedId ? selectedNama : '-- Pilih Area / Kecamatan --'"></span>
                                    </div>
                                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <!-- Dropdown Menu Plugin -->
                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 p-2.5 overflow-hidden"
                                     style="display: none;">
                                    
                                    <div class="relative mb-2">
                                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        <input type="text" 
                                               x-model="search" 
                                               placeholder="Cari Area / Kecamatan..." 
                                               class="w-full text-xs pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-slate-800 font-medium"
                                               @keydown.escape="open = false">
                                    </div>

                                    <div class="max-h-52 overflow-y-auto space-y-1 custom-scrollbar">
                                        <template x-for="item in filteredItems" :key="item.id">
                                            <div @click="selectArea(item)" 
                                                 class="w-full px-3 py-2 text-xs rounded-xl transition flex items-center justify-between font-semibold border cursor-pointer"
                                                 :class="selectedId == item.id ? 'bg-indigo-50 text-indigo-700 font-bold border-indigo-200' : 'text-slate-700 hover:bg-slate-50 border-transparent'">
                                                
                                                <span x-text="item.nama"></span>
                                                
                                                <template x-if="item.hasActiveSales">
                                                    <span class="text-[10px] font-medium text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-100 shrink-0 ml-2" x-text="'Sudah ada Sales (' + item.salesName + ')'"></span>
                                                </template>
                                                <template x-if="!item.hasActiveSales">
                                                    <span class="text-[10px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 shrink-0 ml-2">Tersedia</span>
                                                </template>
                                            </div>
                                        </template>
                                        
                                        <div x-show="filteredItems.length === 0" class="px-3 py-3 text-xs text-slate-400 italic text-center">
                                            Kecamatan tidak ditemukan
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">1 Area = 1 Sales Utama Aktif</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <span class="text-[10px] text-slate-400">Pastikan pembagian wilayah sudah sesuai sebelum menyimpan.</span>
                        <div class="flex gap-2">
                            <button type="button" onclick="document.getElementById('modalAddMember').classList.add('hidden')" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer transition">Batal</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-xs font-semibold hover:bg-indigo-700 shadow-xs cursor-pointer transition">Simpan Penugasan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <script>
            const salesWilayahMap = {
                @foreach($salesCandidates as $s)
                    "{{ $s->id }}": @json($s->wilayah_id ?? $s->activeWilayahes->pluck('id')->last()),
                @endforeach
            };

            function onSalesChange(salesId) {
                const activeAreaId = salesWilayahMap[salesId] ? String(salesWilayahMap[salesId]) : '';
                window.dispatchEvent(new CustomEvent('sales-selected', { detail: { areaId: activeAreaId } }));
            }

            function openModalGeneral() {
                document.getElementById('modalAddMember').classList.remove('hidden');
                document.getElementById('territoryModalTitle').textContent = 'PENUGASAN SALES';
                document.getElementById('territoryModalSubtitle').textContent = 'Atur pembagian wilayah kecamatan untuk Sales';
            }

            function openModalWithMember(member) {
                document.getElementById('modalAddMember').classList.remove('hidden');
                const salesSel = document.getElementById('salesSelect');
                if (salesSel) {
                    salesSel.value = member.id;
                    onSalesChange(member.id);
                }
                const subtitle = document.getElementById('territoryModalSubtitle');
                if (subtitle) subtitle.textContent = 'Atur wilayah kecamatan penugasan untuk Sales: ' + member.name;
            }
        </script>

        <!-- Team Members Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($teamData as $member)
                <div class="crm-card bg-white p-5 space-y-4 hover:shadow-md transition border border-slate-200">

                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full {{ $member['role'] === 'Sales' ? 'bg-blue-600' : 'bg-purple-600' }} text-white font-bold flex items-center justify-center text-sm">
                                {{ strtoupper(substr($member['name'], 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-slate-900">{{ $member['name'] }}</h3>
                                <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $member['role'] === 'Sales' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                                        {{ $member['role'] }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        🆔 {{ $member['kode'] ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $member['status'] === 'Aktif' || $member['status'] === 'aktif' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ ucfirst($member['status']) }}
                        </span>
                    </div>

                    <!-- Contact & Wilayah -->
                    <div class="bg-slate-50 p-3 rounded-xl space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">Kode User:</span>
                            <span class="font-mono font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-100 text-[11px]">{{ $member['kode'] ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">Email:</span>
                            <span class="font-semibold text-slate-800">{{ $member['email'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">WhatsApp:</span>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $member['phone']) }}" target="_blank" class="font-semibold text-emerald-600">
                                {{ $member['phone'] }}
                            </a>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">Wilayah Tugas:</span>
                            <span class="font-bold {{ $member['role'] === 'Sales' ? 'text-blue-700' : 'text-purple-700' }}">
                                {{ $member['wilayah'] }} @if($member['kota'] && $member['kota'] !== '-')({{ $member['kota'] }})@endif
                            </span>
                        </div>
                    </div>

                    <!-- Activity Summary -->
                    <div class="grid grid-cols-3 gap-2 text-center text-xs py-2 bg-slate-50/50 rounded-xl border border-slate-100">
                        <div>
                            <span class="text-slate-400 text-[10px] block">Potensi Mahasiswa</span>
                            <span class="font-bold text-slate-900">{{ $member['prospects'] }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] block">Maba Lunas</span>
                            <span class="font-bold text-emerald-600">{{ $member['closings'] }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] block">Kunjungan</span>
                            <span class="font-bold text-blue-600">{{ $member['visits'] }}</span>
                        </div>
                    </div>

                    <!-- Wilayah Assignment Action -->
                    <div class="pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            onclick="openModalWithMember({{ json_encode($member) }})"
                            class="w-full px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition flex items-center justify-center gap-1.5 border border-indigo-200 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Ubah Penugasan Wilayah
                        </button>
                    </div>

                    <div class="text-[10px] text-slate-400 pt-1 border-t border-slate-100 text-right">
                        Login terakhir: {{ $member['last_login'] }}
                    </div>

                </div>
            @empty
                <div class="col-span-3 crm-card bg-white p-12 text-center text-slate-400 text-xs">
                    Belum ada anggota tim yang terdaftar di bawah pengawasan Anda.
                </div>
            @endforelse
        </div>

    </div>

    <!-- Modal 2: Ubah Wilayah Kecamatan -->
    <div
        x-show="modalKecamatan"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4"
        @click.self="modalKecamatan = false"
        @keydown.escape.window="modalKecamatan = false"
    >
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4" @click.stop>
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Ubah Wilayah Kecamatan</h3>
                <button type="button" @click="modalKecamatan = false" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <p class="text-xs text-slate-500">Menugaskan wilayah kecamatan untuk: <strong x-text="selectedMemberName" class="text-slate-900 font-bold"></strong></p>

            <template x-if="selectedMemberId">
                <form
                    :action="`/spv/tim/${selectedMemberId}/wilayah`"
                    method="POST"
                    class="space-y-4 text-xs"
                >
                    @csrf
                    @method('PATCH')

                    <!-- Opsi Penugasan: Master Kecamatan vs Di Kota Lainnya -->
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl text-xs font-semibold">
                        <button
                            type="button"
                            @click="useOtherCity = false"
                            class="py-1.5 px-2 rounded-lg text-center transition cursor-pointer"
                            :class="!useOtherCity ? 'bg-white text-indigo-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Master Kecamatan
                        </button>
                        <button
                            type="button"
                            @click="useOtherCity = true"
                            class="py-1.5 px-2 rounded-lg text-center transition cursor-pointer"
                            :class="useOtherCity ? 'bg-white text-indigo-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Di Kota Lainnya ✨
                        </button>
                    </div>

                    <input type="hidden" name="is_other_city" :value="useOtherCity ? '1' : '0'">

                    <!-- 1. Pilihan Master Kecamatan (SEARCHABLE DROPDOWN PLUGIN) -->
                    <div x-show="!useOtherCity" class="space-y-1">
                        <label class="block font-semibold text-slate-700">Pilih Kecamatan *</label>
                        
                        <div class="relative" x-data="{
                            open: false,
                            search: '',
                            get items() { return window.spvKecamatanList || []; },
                            get filteredItems() {
                                if (!this.search.trim()) return this.items;
                                return this.items.filter(item => item.nama.toLowerCase().includes(this.search.toLowerCase()));
                            },
                            get selectedNama() {
                                const found = this.items.find(i => String(i.id) === String(selectedWilayahId));
                                return found ? found.nama : '';
                            },
                            selectArea(item) {
                                selectedWilayahId = String(item.id);
                                this.open = false;
                            }
                        }" @click.outside="open = false">

                            <input type="hidden" name="wilayah_id" :value="selectedWilayahId" :required="!useOtherCity">

                            <!-- Trigger Button Plugin -->
                            <button type="button" @click="open = !open" class="w-full text-xs font-semibold px-3.5 py-2.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-indigo-500/20 flex items-center justify-between cursor-pointer transition shadow-xs">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span x-text="selectedWilayahId ? selectedNama : '-- Pilih Kecamatan --'"></span>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu Plugin -->
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute left-0 right-0 mt-1.5 bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 p-2.5 overflow-hidden"
                                 style="display: none;">
                                
                                <div class="relative mb-2">
                                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input type="text" 
                                           x-model="search" 
                                           placeholder="Cari Kecamatan..." 
                                           class="w-full text-xs pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-slate-800 font-medium"
                                           @keydown.escape="open = false">
                                </div>

                                <div class="max-h-52 overflow-y-auto space-y-1 custom-scrollbar">
                                    <template x-for="item in filteredItems" :key="item.id">
                                        <div @click="selectArea(item)" 
                                             class="w-full px-3 py-2 text-xs rounded-xl transition flex items-center justify-between font-semibold border cursor-pointer"
                                             :class="selectedWilayahId == item.id ? 'bg-indigo-50 text-indigo-700 font-bold border-indigo-200' : 'text-slate-700 hover:bg-slate-50 border-transparent'">
                                            
                                            <span x-text="item.nama"></span>
                                            
                                            <template x-if="item.hasActiveSales">
                                                <span class="text-[10px] font-medium text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-100 shrink-0 ml-2" x-text="'Sudah ada Sales (' + item.salesName + ')'"></span>
                                            </template>
                                            <template x-if="!item.hasActiveSales">
                                                <span class="text-[10px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100 shrink-0 ml-2">Tersedia</span>
                                            </template>
                                        </div>
                                    </template>
                                    
                                    <div x-show="filteredItems.length === 0" class="px-3 py-3 text-xs text-slate-400 italic text-center">
                                        Kecamatan tidak ditemukan
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if(count($kecamatanList) == 0)
                            <p class="text-[11px] text-amber-600 font-semibold mt-1">⚠️ Belum ada kecamatan di bawah wilayah SPV ini. Silakan gunakan opsi 'Di Kota Lainnya' atau koordinasikan dengan HM.</p>
                        @endif
                    </div>

                    <!-- 2. Pilihan Di Kota Lainnya -->
                    <div x-show="useOtherCity" class="space-y-1">
                        <label class="block font-semibold text-slate-700">Nama Kota / Wilayah Khusus *</label>
                        <input
                            type="text"
                            name="custom_city"
                            x-model="customCity"
                            :required="useOtherCity"
                            placeholder="Contoh: Majalengka Kota, Brebes, Tegal, Subang..."
                            class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500 bg-white"
                        />
                        <p class="text-[10px] text-amber-700 mt-1">Gunakan opsi ini jika wilayah penugasan belum terdaftar di master data kota/kecamatan.</p>
                    </div>

                    <div class="flex gap-2 pt-3 border-t border-slate-100 justify-end">
                        <button type="button" @click="modalKecamatan = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-xs transition cursor-pointer">Simpan Penugasan</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</x-app-layout>
