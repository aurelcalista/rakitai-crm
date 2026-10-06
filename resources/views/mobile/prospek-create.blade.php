@php
    $perusahaanOptions = [];
    if(isset($perusahaans) && $perusahaans->isNotEmpty()) {
        foreach($perusahaans as $p) {
            $perusahaanOptions[] = [
                'val' => (string)$p->id,
                'label' => $p->nama,
                'sub' => 'Database Perusahaan' . ($p->kota ? ' • ' . $p->kota : ''),
            ];
        }
    }
@endphp
<script>
    window.mobilePerusahaanList = @json($perusahaanOptions);
</script>

<x-mobile-form-layout :title="'Input Prospek Lapangan - UCIC'" :pageHeader="'Input Prospek Lapangan'">
    <div 
        class="bg-white rounded-3xl border border-slate-200/90 p-5 shadow-xs"
        x-data="{
            prospekType: 'Sekolah',
            sekolahMap: {{ json_encode($sekolahs->keyBy('id')->toArray()) }},
            perusahaanMap: {{ json_encode($perusahaans->keyBy('id')->toArray()) }},
            selectedSekolahId: '',
            selectedPerusahaanId: '',
            namaInstansi: '',
            pic: '',
            whatsapp: '',
            handleSekolahChange(id) {
                if (id && this.sekolahMap[id]) {
                    this.namaInstansi = this.sekolahMap[id].nama || '';
                    if (!this.pic && this.sekolahMap[id].pic_name) this.pic = this.sekolahMap[id].pic_name;
                    if (!this.whatsapp && this.sekolahMap[id].pic_phone) this.whatsapp = this.sekolahMap[id].pic_phone;
                }
            },
            handlePerusahaanChange(id) {
                if (id && this.perusahaanMap[id]) {
                    this.namaInstansi = this.perusahaanMap[id].nama || '';
                    if (!this.pic && this.perusahaanMap[id].pic_name) this.pic = this.perusahaanMap[id].pic_name;
                    if (!this.whatsapp && this.perusahaanMap[id].pic_phone) this.whatsapp = this.perusahaanMap[id].pic_phone;
                }
            }
        }"
    >
        <div class="mb-4 pb-3 border-b border-slate-100">
            <h2 class="text-base font-extrabold text-slate-900">Form Prospek Baru</h2>
            <p class="text-xs text-slate-500">Rekam prospek potensial langsung saat promosi di lapangan.</p>
        </div>

        <form action="{{ route('mobile.prospek.store') }}" method="POST" class="space-y-4">
            @csrf
            @if(request()->filled('user_id'))
                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
            @endif

            <!-- Segmented Control Type -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Tipe Prospek *</label>
                <div class="grid grid-cols-3 gap-1.5 p-1 bg-slate-100 rounded-2xl">
                    <button 
                        type="button" 
                        @click="prospekType = 'Sekolah'" 
                        :class="prospekType === 'Sekolah' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'text-slate-600 font-medium'"
                        class="py-2 text-xs rounded-xl transition text-center"
                    >
                        Sekolah
                    </button>
                    <button 
                        type="button" 
                        @click="prospekType = 'Corporate'" 
                        :class="prospekType === 'Corporate' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'text-slate-600 font-medium'"
                        class="py-2 text-xs rounded-xl transition text-center"
                    >
                        Corporate
                    </button>
                    <button 
                        type="button" 
                        @click="prospekType = 'Individu'" 
                        :class="prospekType === 'Individu' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'text-slate-600 font-medium'"
                        class="py-2 text-xs rounded-xl transition text-center"
                    >
                        Siswa
                    </button>
                </div>
                <input type="hidden" name="type" :value="prospekType">
            </div>

            <!-- If SPV/Admin, allow assigning sales -->
            @if(!empty($salesList) && count($salesList) > 0)
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Assign ke Sales *</label>
                    <select name="sales_id" class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 font-medium">
                        <option value="">-- Pilih Personil Sales --</option>
                        @foreach($salesList as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Conditional Instansi Source -->
            <div x-show="prospekType === 'Sekolah'" class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Sekolah Terdaftar</label>
                    <select 
                        name="sekolah_id" 
                        x-model="selectedSekolahId" 
                        @change="handleSekolahChange($event.target.value)"
                        class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 font-medium"
                    >
                        <option value="">-- Pilih dari database sekolah --</option>
                        @foreach($sekolahs as $sek)
                            <option value="{{ $sek->id }}">{{ $sek->nama }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div x-show="prospekType === 'Corporate'" class="space-y-3" style="display: none;">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Perusahaan Terdaftar</label>
                    <div class="relative" x-data="{
                        open: false,
                        search: '',
                        get items() { return window.mobilePerusahaanList || []; },
                        get filteredItems() {
                            if (!this.search.trim()) return this.items;
                            const q = this.search.toLowerCase();
                            return this.items.filter(i => i.label.toLowerCase().includes(q) || (i.sub && i.sub.toLowerCase().includes(q)));
                        },
                        get selectedLabel() {
                            const found = this.items.find(i => i.val === String(selectedPerusahaanId));
                            return found ? found.label : '';
                        },
                        selectPerusahaan(item) {
                            selectedPerusahaanId = item.val;
                            handlePerusahaanChange(item.val);
                            this.open = false;
                        }
                    }" @click.outside="open = false">

                        <input type="hidden" name="perusahaan_id" :value="selectedPerusahaanId">

                        <!-- Trigger Button -->
                        <button type="button" @click="open = !open" class="w-full text-xs font-semibold px-3.5 py-3 rounded-2xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-purple-500/20 flex items-center justify-between cursor-pointer transition shadow-xs">
                            <div class="flex items-center gap-2 truncate">
                                <svg class="w-4 h-4 text-purple-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 v5m-4 0h4" />
                                </svg>
                                <span class="truncate font-semibold" x-text="selectedPerusahaanId ? selectedLabel : '-- Cari & Pilih Perusahaan --'"></span>
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
                                         :class="String(selectedPerusahaanId) === item.val ? 'bg-purple-50 text-purple-700 font-bold border-purple-200' : 'text-slate-700 hover:bg-slate-50 border-transparent'">
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
                </div>
            </div>


            <!-- PIC & Contact -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama *</label>
                    <input 
                        type="text" 
                        name="pic" 
                        x-model="pic"
                        required 
                        placeholder="Contoh: Budi Santoso" 
                        class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                    >
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor WhatsApp *</label>
                    <input 
                        type="tel" 
                        name="whatsapp" 
                        x-model="whatsapp"
                        required 
                        placeholder="08xxxxxxxxxx" 
                        class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                    >
                </div>
            </div>

            <!-- Prodi Minat -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Program Studi Diminati <span class="text-slate-400 font-normal">(Opsional)</span></label>
                <select name="prodi_id" class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 font-medium">
                    <option value="">-- Pilih Program Studi (Boleh Dikosongkan) --</option>
                    @foreach($prodis as $prd)
                        <option value="{{ $prd->id }}">{{ $prd->nama }} ({{ $prd->jenjang }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Sumber & Kategori -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Sumber *</label>
                    <select name="source" required class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 font-medium">
                        <option value="Kunjungan Lapangan">Kunjungan Lapangan</option>
                        <option value="EduFair / Expo">EduFair / Expo</option>
                        <option value="Presentasi Kelas">Presentasi Kelas</option>
                        @foreach($sources as $src)
                            <option value="{{ $src->nama }}">{{ $src->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Kategori</label>
                    <select name="category" class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 font-medium">
                        <option value="SMA">SMA</option>
                        <option value="SMK">SMK</option>
                        <option value="MA">MA</option>
                        <option value="Corporate">Corporate</option>
                    </select>
                </div>
            </div>

            <!-- Catatan -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Hasil Pertemuan</label>
                <textarea 
                    name="notes" 
                    rows="2" 
                    placeholder="Minat beasiswa, jadwal tindak lanjut, dll..." 
                    class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                ></textarea>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button 
                    type="submit" 
                    class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm rounded-2xl shadow-sm transition flex items-center justify-center gap-2 cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Simpan Prospek ke CRM</span>
                </button>
            </div>
        </form>
    </div>
</x-mobile-form-layout>
