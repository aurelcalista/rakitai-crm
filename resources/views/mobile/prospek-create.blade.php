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
                        🏫 Sekolah
                    </button>
                    <button 
                        type="button" 
                        @click="prospekType = 'Corporate'" 
                        :class="prospekType === 'Corporate' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'text-slate-600 font-medium'"
                        class="py-2 text-xs rounded-xl transition text-center"
                    >
                        🏢 Corporate
                    </button>
                    <button 
                        type="button" 
                        @click="prospekType = 'Individu'" 
                        :class="prospekType === 'Individu' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'text-slate-600 font-medium'"
                        class="py-2 text-xs rounded-xl transition text-center"
                    >
                        👤 Siswa
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
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Sekolah / Instansi *</label>
                    <input 
                        type="text" 
                        name="name" 
                        x-model="namaInstansi"
                        placeholder="Contoh: SMA Negeri 1 Cirebon" 
                        class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                    >
                </div>
            </div>

            <div x-show="prospekType === 'Corporate'" class="space-y-3" style="display: none;">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Perusahaan Terdaftar</label>
                    <select 
                        name="perusahaan_id" 
                        x-model="selectedPerusahaanId" 
                        @change="handlePerusahaanChange($event.target.value)"
                        class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 font-medium"
                    >
                        <option value="">-- Pilih dari database corporate --</option>
                        @foreach($perusahaans as $per)
                            <option value="{{ $per->id }}">{{ $per->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Perusahaan *</label>
                    <input 
                        type="text" 
                        name="name" 
                        x-model="namaInstansi"
                        placeholder="Contoh: PT Surya Digital Nusantara" 
                        class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                    >
                </div>
            </div>

            <div x-show="prospekType === 'Individu'" style="display: none;">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Calon Mahasiswa *</label>
                <input 
                    type="text" 
                    name="name" 
                    x-model="namaInstansi"
                    placeholder="Nama lengkap calon pendaftar" 
                    class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"
                >
            </div>

            <!-- PIC & Contact -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama PIC / Kontak *</label>
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
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Program Studi Diminati *</label>
                <select name="prodi_id" required class="w-full text-xs px-3.5 py-3 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 font-medium">
                    <option value="">-- Pilih Program Studi --</option>
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
