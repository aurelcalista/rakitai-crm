@php
    $mobileSchoolOptions = [];
    if(isset($availableProspekSekolah) && $availableProspekSekolah->isNotEmpty()) {
        foreach($availableProspekSekolah as $p) {
            $mobileSchoolOptions[] = [
                'val' => 'prospek_' . $p->id,
                'label' => $p->name,
                'sub' => 'Prospek • Status: ' . $p->status . ($p->pic ? ' • PIC: ' . $p->pic : ''),
            ];
        }
    }
    if(isset($sekolahs) && $sekolahs->isNotEmpty()) {
        foreach($sekolahs as $s) {
            $mobileSchoolOptions[] = [
                'val' => 'sekolah_' . $s->id,
                'label' => $s->nama,
                'sub' => 'Master Database Sekolah' . ($s->kota ? ' • ' . $s->kota : ''),
            ];
        }
    $mobilePerusahaanOptions = [];
    if(isset($perusahaans) && $perusahaans->isNotEmpty()) {
        foreach($perusahaans as $p) {
            $mobilePerusahaanOptions[] = [
                'val' => (string)$p->id,
                'label' => $p->nama,
                'sub' => 'Database Perusahaan' . ($p->kota ? ' • ' . $p->kota : ''),
            ];
        }
    }
@endphp

<script>
    window.mobileSchoolOptionsList = @json($mobileSchoolOptions);
    window.mobilePerusahaanOptionsList = @json($mobilePerusahaanOptions);
</script>

<x-mobile-form-layout :title="'Laporan Kunjungan Lapangan - UCIC'" :pageHeader="'Laporan Kunjungan'">
    <div 
        class="bg-white rounded-3xl border border-slate-200/90 p-5 shadow-xs"
        x-data="{
            jenisTab: 'Sekolah',
            selectedSource: '',
            prospek_id: '',
            sekolah_id: '',
            perusahaan_id: '',
            namaInstitusi: '',
            picName: '',
            picWhatsapp: '',
            lat: '',
            lng: '',
            geoStatus: 'idle', /* idle | loading | ok | error */
            photoPreview: null,
            prospekMap: {{ json_encode($availableProspekSekolah->keyBy('id')->toArray()) }},
            sekolahMap: {{ json_encode($sekolahs->keyBy('id')->toArray()) }},
            perusahaanMap: {{ json_encode($perusahaans->keyBy('id')->toArray()) }},
            handleSchoolSelect(val) {
                if (val && val.startsWith('prospek_')) {
                    const pid = val.replace('prospek_', '');
                    const p = this.prospekMap[pid];
                    if (p) {
                        this.prospek_id = p.id;
                        this.sekolah_id = p.sekolah_id || '';
                        this.namaInstitusi = p.name || '';
                        this.picName = p.pic || '';
                        this.picWhatsapp = p.whatsapp || '';
                    }
                } else if (val && val.startsWith('sekolah_')) {
                    const sid = val.replace('sekolah_', '');
                    this.prospek_id = '';
                    this.sekolah_id = sid;
                    const s = this.sekolahMap[sid];
                    if (s) {
                        this.namaInstitusi = s.nama || '';
                        this.picName = s.pic_name || '';
                        this.picWhatsapp = s.pic_phone || '';
                    }
                } else {
                    this.prospek_id = '';
                    this.sekolah_id = '';
                }
            },
            handlePerusahaanSelect(id) {
                if (id && this.perusahaanMap[id]) {
                    this.perusahaan_id = id;
                    this.namaInstitusi = this.perusahaanMap[id].nama || '';
                    this.picName = this.perusahaanMap[id].pic_name || '';
                    this.picWhatsapp = this.perusahaanMap[id].pic_phone || '';
                }
            },
            captureLocation() {
                this.geoStatus = 'loading';
                if (!navigator.geolocation) {
                    this.geoStatus = 'error';
                    alert('Browser atau perangkat Anda tidak mendukung geolokasi GPS.');
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.lat = pos.coords.latitude;
                        this.lng = pos.coords.longitude;
                        this.geoStatus = 'ok';
                    },
                    (err) => {
                        this.geoStatus = 'error';
                        alert('Gagal mengambil titik GPS. Harap berikan izin akses lokasi pada aplikasi.');
                    },
                    { enableHighAccuracy: true, timeout: 15000 }
                );
            },
            handlePhotoChange(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.photoPreview = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }
        }"
    >
        <div class="mb-4 pb-3 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Input Kunjungan Offline</h2>
                <p class="text-xs text-slate-500">Laporan verifikasi kunjungan langsung Sales UCIC.</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                GPS Verified
            </span>
        </div>

        <form action="{{ route('mobile.kunjungan.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @if(request()->filled('user_id'))
                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
            @endif

            <!-- Tab Jenis Kunjungan -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Jenis Kunjungan *</label>
                <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-100 rounded-2xl">
                    <button 
                        type="button" 
                        @click="jenisTab = 'Sekolah'" 
                        :class="jenisTab === 'Sekolah' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'text-slate-600 font-medium'"
                        class="py-2 text-xs rounded-xl transition text-center"
                    >
                        Sekolah (SMA/SMK)
                    </button>
                    <button 
                        type="button" 
                        @click="jenisTab = 'Perusahaan'" 
                        :class="jenisTab === 'Perusahaan' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'text-slate-600 font-medium'"
                        class="py-2 text-xs rounded-xl transition text-center"
                    >
                        Corporate / Mitra
                    </button>
                </div>
                <input type="hidden" name="jenis" :value="jenisTab">
            </div>

            <!-- Tanggal Kunjungan -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Kunjungan *</label>
                <input 
                    type="date" 
                    name="tanggal" 
                    value="{{ date('Y-m-d') }}" 
                    required 
                    class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 font-medium"
                >
            </div>

            <!-- DROPDOWN NAMA SEKOLAH DARI DATA PROSPEK (SEARCHABLE DROPDOWN PLUGIN) -->
            <div x-show="jenisTab === 'Sekolah'" class="space-y-3">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700">Pilih Nama Sekolah *</label>
                        <span class="text-[10px] text-blue-600 font-bold bg-blue-50 px-2 py-0.5 rounded-md">Searchable</span>
                    </div>

                    <div class="relative" x-data="{
                        open: false,
                        search: '',
                        get items() { return window.mobileSchoolOptionsList || []; },
                        get filteredItems() {
                            if (!this.search.trim()) return this.items;
                            const q = this.search.toLowerCase();
                            return this.items.filter(i => i.label.toLowerCase().includes(q) || (i.sub && i.sub.toLowerCase().includes(q)));
                        },
                        get selectedLabel() {
                            const found = this.items.find(i => i.val === selectedSource);
                            return found ? found.label : '';
                        },
                        selectSchool(item) {
                            selectedSource = item.val;
                            handleSchoolSelect(item.val);
                            this.open = false;
                        }
                    }" @click.outside="open = false">

                        <!-- Trigger Button -->
                        <button type="button" @click="open = !open" class="w-full text-xs font-semibold px-3.5 py-3 rounded-2xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-blue-500/20 flex items-center justify-between cursor-pointer transition shadow-xs">
                            <div class="flex items-center gap-2 truncate">
                                <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 v5m-4 0h4" />
                                </svg>
                                <span class="truncate font-semibold" x-text="selectedSource ? selectedLabel : '-- Cari & Pilih Nama Sekolah --'"></span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Menu Popup -->
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
                                       placeholder="Cari nama sekolah / PIC..." 
                                       class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-slate-800 font-medium"
                                       @keydown.escape="open = false">
                            </div>

                            <div class="max-h-56 overflow-y-auto space-y-1 custom-scrollbar">
                                <template x-for="item in filteredItems" :key="item.val">
                                    <div @click="selectSchool(item)" 
                                         class="w-full px-3 py-2 text-xs rounded-xl transition cursor-pointer font-semibold border flex flex-col gap-0.5"
                                         :class="selectedSource === item.val ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-700 hover:bg-slate-50 border-transparent'">
                                        <span x-text="item.label" class="font-bold"></span>
                                        <span x-text="item.sub" class="text-[10px] text-slate-400 font-normal"></span>
                                    </div>
                                </template>
                                
                                <div x-show="filteredItems.length === 0" class="px-3 py-3 text-xs text-slate-400 italic text-center">
                                    Sekolah tidak ditemukan
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="prospek_id" :value="prospek_id">
                    <input type="hidden" name="sekolah_id" :value="sekolah_id">
                    <input type="hidden" name="nama_institusi" :value="namaInstitusi">
                </div>
            </div>

            <!-- DROPDOWN PERUSAHAAN -->
            <div x-show="jenisTab === 'Perusahaan'" class="space-y-3" style="display: none;">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Perusahaan *</label>
                    
                    <div class="relative" x-data="{
                        open: false,
                        search: '',
                        get items() { return window.mobilePerusahaanOptionsList || []; },
                        get filteredItems() {
                            if (!this.search.trim()) return this.items;
                            const q = this.search.toLowerCase();
                            return this.items.filter(i => i.label.toLowerCase().includes(q) || (i.sub && i.sub.toLowerCase().includes(q)));
                        },
                        get selectedLabel() {
                            const found = this.items.find(i => i.val === String(perusahaan_id));
                            return found ? found.label : '';
                        },
                        selectPerusahaan(item) {
                            handlePerusahaanSelect(item.val);
                            this.open = false;
                        }
                    }" @click.outside="open = false">

                        <input type="hidden" name="perusahaan_id" :value="perusahaan_id">

                        <!-- Trigger Button -->
                        <button type="button" @click="open = !open" class="w-full text-xs font-semibold px-3.5 py-3 rounded-2xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-purple-500/20 flex items-center justify-between cursor-pointer transition shadow-xs">
                            <div class="flex items-center gap-2 truncate">
                                <svg class="w-4 h-4 text-purple-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 v5m-4 0h4" />
                                </svg>
                                <span class="truncate font-semibold" x-text="perusahaan_id ? selectedLabel : '-- Cari & Pilih Perusahaan --'"></span>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
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
                                       placeholder="Ketik nama perusahaan..." 
                                       class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 text-slate-800 font-medium"
                                       @keydown.escape="open = false">
                            </div>

                            <div class="max-h-56 overflow-y-auto space-y-1 custom-scrollbar">
                                <template x-for="item in filteredItems" :key="item.val">
                                    <div @click="selectPerusahaan(item)" 
                                         class="w-full px-3 py-2.5 text-xs rounded-xl transition cursor-pointer font-semibold border flex flex-col gap-0.5"
                                         :class="String(perusahaan_id) === item.val ? 'bg-purple-50 text-purple-700 font-bold border-purple-200' : 'text-slate-700 hover:bg-slate-50 border-transparent'">
                                        <span x-text="item.label" class="font-bold"></span>
                                        <span x-text="item.sub" class="text-[10px] text-slate-400 font-normal"></span>
                                    </div>
                                </template>
                                
                                <div x-show="filteredItems.length === 0" class="px-3 py-3 text-xs text-slate-400 italic text-center">
                                    Perusahaan tidak ditemukan
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="nama_institusi" :value="namaInstitusi">
                </div>
            </div>

            <!-- PIC & WhatsApp (Auto-filled) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama PIC / Guru BK *</label>
                    <input 
                        type="text" 
                        name="pic_name" 
                        x-model="picName"
                        required 
                        placeholder="Nama kontak institusi" 
                        class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                    >
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor WhatsApp PIC *</label>
                    <input 
                        type="tel" 
                        name="pic_whatsapp" 
                        x-model="picWhatsapp"
                        required 
                        placeholder="08xxxxxxxxxx" 
                        class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                    >
                </div>
            </div>

            <!-- Prodi Target -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-700">Program Studi yang Dipromosikan</label>
                    <span class="text-[10px] text-blue-700 bg-blue-50 font-bold px-2 py-0.5 rounded-full">Bisa Banyak</span>
                </div>
                <div class="grid grid-cols-1 gap-2 max-h-48 overflow-y-auto p-1">
                    @foreach($prodis as $prd)
                        <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 bg-white active:bg-blue-50 transition cursor-pointer">
                            <input type="checkbox" name="prodi_ids[]" value="{{ $prd->id }}" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                            <span class="text-xs font-medium text-slate-800">{{ $prd->nama }} ({{ $prd->jenjang }})</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- GPS LOCATION CAPTURE -->
            <div class="p-3.5 rounded-2xl border border-slate-200 bg-slate-50/70 space-y-2.5">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="block text-xs font-bold text-slate-800">Koordinat GPS Lapangan *</span>
                        <span class="text-[11px] text-slate-500">Wajib diambil saat berada di lokasi.</span>
                    </div>
                    <button 
                        type="button" 
                        @click="captureLocation()"
                        :disabled="geoStatus === 'loading'"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs"
                        :class="geoStatus === 'ok' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-blue-600 text-white hover:bg-blue-700'"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span x-text="geoStatus === 'loading' ? 'Mencari...' : (geoStatus === 'ok' ? 'Update GPS ✓' : 'Ambil GPS')"></span>
                    </button>
                </div>

                <div x-show="lat && lng" class="p-2.5 bg-white rounded-xl border border-slate-200 text-[11px] text-slate-700 font-mono flex items-center justify-between">
                    <span x-text="'Lat: ' + lat + ', Lng: ' + lng"></span>
                    <span class="text-emerald-600 font-bold">Akurat ✓</span>
                </div>

                <input type="hidden" name="lat" :value="lat" required>
                <input type="hidden" name="lng" :value="lng" required>
            </div>

            <!-- FOTO DOKUMENTASI LAPANGAN -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Foto Dokumentasi Kunjungan *</label>
                <div class="relative border-2 border-dashed border-slate-200 rounded-2xl p-4 text-center hover:bg-slate-50 transition cursor-pointer">
                    <input 
                        type="file" 
                        name="foto" 
                        accept="image/*" 
                        capture="environment" 
                        required 
                        @change="handlePhotoChange($event)"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    >
                    <template x-if="!photoPreview">
                        <div class="flex flex-col items-center justify-center py-2 text-slate-400">
                            <svg class="w-8 h-8 mb-1.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-xs font-bold text-slate-700">Ambil Foto / Pilih dari Galeri</span>
                            <span class="text-[10px] text-slate-400 mt-0.5">Maks. 5MB (JPG, PNG, WebP)</span>
                        </div>
                    </template>
                    <template x-if="photoPreview">
                        <div class="flex flex-col items-center">
                            <img :src="photoPreview" class="h-36 w-full object-cover rounded-xl border border-slate-200 mb-2">
                            <span class="text-xs font-bold text-blue-600 underline">Ganti Foto</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Potensi Siswa & Training AI -->
            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-200 space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Estimasi Potensi Pendaftar</label>
                    <input 
                        type="text" 
                        name="potensi_mahasiswa" 
                        placeholder="Contoh: 80 Siswa Kelas XII IPA/IPS" 
                        class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                    >
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="kesediaan_ai_m" name="kesediaan_training_ai" value="1" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                    <label for="kesediaan_ai_m" class="text-xs font-semibold text-slate-700 cursor-pointer">
                        Sekolah bersedia dijadwalkan Training / Workshop AI UCIC
                    </label>
                </div>
            </div>

            <!-- Catatan Kunjungan -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan & Rangkuman Kunjungan</label>
                <textarea 
                    name="catatan" 
                    rows="2" 
                    placeholder="Hasil pertemuan, respon guru BK, agenda presentasi berikutnya..." 
                    class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                ></textarea>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button 
                    type="submit" 
                    :disabled="!lat || !lng"
                    class="w-full py-3.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold text-sm rounded-2xl shadow-sm transition flex items-center justify-center gap-2 cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span x-text="lat && lng ? 'Kirim Laporan Kunjungan' : 'Ambil Titik GPS Terlebih Dahulu'"></span>
                </button>
            </div>
        </form>
    </div>
</x-mobile-form-layout>
