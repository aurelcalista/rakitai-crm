@php
    $pageTitle = 'Tambah Prospek Tim (SPV)';
    $pageSubtitle = 'Input Prospek Baru & Penugasan Sales Handler';
@endphp

<x-app-layout :title="'Tambah Prospek - Supervisor CRM'">

    <div class="max-w-4xl mx-auto space-y-6" x-data="{
        prospectType: 'Sekolah',
        selectedSekolahId: '',
        selectedPerusahaanId: '',
        prospectName: ''
    }">

        <!-- Back Button & Breadcrumb -->
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('spv.prospek.index') }}" class="hover:text-blue-600 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                Kembali ke Daftar Prospek Tim
            </a>
        </div>

        <!-- Form Card -->
        <div class="crm-card bg-white p-6 sm:p-8">
            <div class="pb-6 border-b border-slate-100 mb-6">
                <h2 class="text-xl font-bold text-slate-900">Tambah Data Prospek Baru</h2>
                <p class="text-xs text-slate-500 mt-1">Isi rincian informasi prospek dan tentukan anggota tim Sales yang akan menanganinya.</p>
            </div>

            <form action="{{ route('spv.prospek.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Assignment to Sales -->
                <div class="bg-blue-50/70 p-4 rounded-xl border border-blue-200/80 space-y-2">
                    <label class="block text-xs font-bold text-blue-900 uppercase tracking-wider">Tugaskan ke Sales Handler</label>
                    <select name="sales_id" class="w-full text-xs px-3 py-2.5 rounded-lg bg-white border border-blue-300 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20">
                        <option value="">-- Pilih Anggota Tim Sales (Bisa di-assign nanti) --</option>
                        @foreach($teamSales as $sales)
                            <option value="{{ $sales->id }}">{{ $sales->name }} ({{ $sales->email }})</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-blue-700">Sales yang dipilih akan otomatis menerima prospek ini di dashboard & task list mereka.</p>
                </div>

                <!-- Entity Type -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Tipe Prospek <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                            :class="prospectType === 'Sekolah' ? 'bg-blue-50 border-blue-600 text-blue-700 ring-2 ring-blue-600/20' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                            <input type="radio" name="type" value="Sekolah" x-model="prospectType" class="hidden">
                            <span>🏫 Sekolah (SMA/SMK)</span>
                        </label>
                        <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                            :class="prospectType === 'Corporate' ? 'bg-blue-50 border-blue-600 text-blue-700 ring-2 ring-blue-600/20' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                            <input type="radio" name="type" value="Corporate" x-model="prospectType" class="hidden">
                            <span>🏢 Corporate / Mitra</span>
                        </label>
                        <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                            :class="prospectType === 'Individu' ? 'bg-blue-50 border-blue-600 text-blue-700 ring-2 ring-blue-600/20' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                            <input type="radio" name="type" value="Individu" x-model="prospectType" class="hidden">
                            <span>👤 Individu / Siswa</span>
                        </label>
                    </div>
                </div>

                <!-- Sekolah Selector -->
                <div x-show="prospectType === 'Sekolah'" class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Pilih Master Data Sekolah</label>
                    <select name="sekolah_id" x-model="selectedSekolahId" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium focus:ring-2 focus:ring-blue-500/20 focus:bg-white">
                        <option value="">-- Pilih dari Database Sekolah atau Ketik Manual di Bawah --</option>
                        @foreach($sekolahs as $sekolah)
                            <option value="{{ $sekolah->id }}">{{ $sekolah->nama }} ({{ $sekolah->kota ?? 'Wilayah CIC' }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Perusahaan Selector -->
                <div x-show="prospectType === 'Corporate'" class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Pilih Master Data Perusahaan</label>
                    <select name="perusahaan_id" x-model="selectedPerusahaanId" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium focus:ring-2 focus:ring-blue-500/20 focus:bg-white">
                        <option value="">-- Pilih dari Database Perusahaan atau Ketik Manual di Bawah --</option>
                        @foreach($perusahaans as $perusahaan)
                            <option value="{{ $perusahaan->id }}">{{ $perusahaan->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Nama Prospek -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Nama Prospek / Institusi <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="prospectName" placeholder="Contoh: SMAN 1 Cirebon atau Nama Siswa" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:bg-white" required>
                </div>

                <!-- PIC & WhatsApp Contact -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Nama PIC / Kontak <span class="text-rose-500">*</span></label>
                        <input type="text" name="pic" placeholder="Contoh: Ibu Siti Rahma (Guru BK / HRD)" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:bg-white" required>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Nomor WhatsApp Aktif <span class="text-rose-500">*</span></label>
                        <input type="text" name="whatsapp" placeholder="Contoh: 081234567890" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:bg-white" required>
                    </div>
                </div>

                <!-- Status & Sumber -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Status Awal Pipeline <span class="text-rose-500">*</span></label>
                        <select name="status" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20 focus:bg-white">
                            @foreach($statuses as $st)
                                <option value="{{ $st }}" {{ $st === 'Cold Lead' ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Sumber Prospek</label>
                        <select name="source" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-blue-500/20 focus:bg-white">
                            <option value="Supervisor Direct">Supervisor Direct (SPV)</option>
                            <option value="Kanvasing Sekolah">Kanvasing Sekolah</option>
                            <option value="Event / Expo">Event / Expo Pendidikan</option>
                            <option value="Sosial Media / Ads">Sosial Media / Ads</option>
                            <option value="Website UCIC">Website UCIC</option>
                            <option value="Referensi Mitra">Referensi Mitra</option>
                        </select>
                    </div>
                </div>

                <!-- Catatan / Kebutuhan -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Catatan Khusus / Instruksi untuk Sales</label>
                    <textarea name="notes" rows="3" placeholder="Instruksi tindak lanjut follow-up atau detail potensi calon mahasiswa..." class="w-full text-xs p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:bg-white"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="{{ route('spv.prospek.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition shadow-xs">
                        Simpan & Tugaskan Prospek
                    </button>
                </div>

            </form>
        </div>

    </div>

</x-app-layout>
