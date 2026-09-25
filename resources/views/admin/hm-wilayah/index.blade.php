@php $pageTitle = 'Penugasan Wilayah HM'; @endphp

<x-app-layout :title="'Penugasan Wilayah HM - Admin Control CRM'">

    <div class="space-y-6" x-data="{
        modalAssign: false,
        selectedWilayahId: '',
        selectedWilayahNama: '',
        selectedHmId: '',
        openAssign(wilayahId, wilayahNama, currentHmId) {
            this.selectedWilayahId = wilayahId;
            this.selectedWilayahNama = wilayahNama;
            this.selectedHmId = currentHmId ? String(currentHmId) : '';
            this.modalAssign = true;
        }
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Penugasan Wilayah Head Marketing (HM)</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">Admin Control</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Tentukan dan kelola Kota/Kabupaten yang menjadi tanggung jawab masing-masing HM agar hierarki organisasi HM &rarr; SPV &rarr; Sales/CS berjalan tertib.
                </p>
            </div>
            <span class="px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 font-bold text-xs border border-purple-200 shrink-0">
                Tahun Akademik: {{ $activeTA->nama ?? '2027/2028' }} (AKTIF)
            </span>
        </div>

        <!-- Hierarki Alur Organisasi Banner -->
        <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 text-white shadow-xs">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-purple-300 block mb-2">Hierarki Alur Struktur Organisasi Wilayah:</span>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                <div class="bg-white/10 backdrop-blur-xs p-3 rounded-xl border border-white/10">
                    <div class="font-bold text-purple-200">1. ADMIN</div>
                    <p class="text-[11px] text-slate-300 mt-0.5">Membuat Master Wilayah & menentukan Kota/Kabupaten untuk masing-masing HM.</p>
                </div>
                <div class="bg-white/10 backdrop-blur-xs p-3 rounded-xl border border-white/10">
                    <div class="font-bold text-indigo-200">2. HEAD MARKETING (HM)</div>
                    <p class="text-[11px] text-slate-300 mt-0.5">Memegang 1 Kota/Kabupaten, menunjuk SPV penanggung jawab, & mengunci Target Wilayah.</p>
                </div>
                <div class="bg-white/10 backdrop-blur-xs p-3 rounded-xl border border-white/10">
                    <div class="font-bold text-blue-200">3. SUPERVISOR (SPV)</div>
                    <p class="text-[11px] text-slate-300 mt-0.5">Membagi target ke bulanan, mengelola kecamatan, serta memilih tim Sales & CS.</p>
                </div>
                <div class="bg-white/10 backdrop-blur-xs p-3 rounded-xl border border-white/10">
                    <div class="font-bold text-emerald-200">4. SALES & CS</div>
                    <p class="text-[11px] text-slate-300 mt-0.5">Sales memegang area kecamatan spesifik (kontak harian & formulir mingguan); CS melayani tindak lanjut.</p>
                </div>
            </div>
        </div>

        <!-- Notification Banner -->
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between">
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs font-semibold">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs font-semibold">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- HM Tanpa Wilayah Alert (jika ada) -->
        @if($unassignedHms->count() > 0)
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <strong class="font-bold text-amber-800">Perhatian:</strong> Ada <strong>{{ $unassignedHms->count() }} Head Marketing (HM)</strong> yang belum memiliki penugasan Wilayah:
                    <div class="flex flex-wrap gap-2 mt-1.5">
                        @foreach($unassignedHms as $uHm)
                            <span class="px-2 py-0.5 rounded-md bg-white border border-amber-300 text-amber-900 font-semibold text-[11px]">
                                {{ $uHm->name }} ({{ $uHm->email }})
                            </span>
                        @endforeach
                    </div>
                </div>
                <button type="button" @click="openAssign('', '', '')" class="px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold transition shrink-0 cursor-pointer">
                    + Tugaskan Sekarang
                </button>
            </div>
        @endif

        <!-- Daftar Wilayah Kota/Kabupaten & Penugasan HM -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($wilayahs as $w)
                <div class="crm-card bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded">{{ $w->kode }}</span>
                            <h3 class="text-lg font-bold text-slate-900 mt-1">{{ $w->nama }}</h3>
                            <p class="text-xs text-slate-500">{{ $w->children->count() }} Kecamatan terdaftar</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $w->status === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-600' }}">
                            {{ $w->status }}
                        </span>
                    </div>

                    <!-- HM Penanggung Jawab Section -->
                    <div class="p-4 rounded-xl {{ $w->assigned_hm ? 'bg-purple-50/60 border border-purple-100' : 'bg-amber-50/60 border border-amber-200/80' }} flex items-center justify-between">
                        <div>
                            <div class="text-[10px] uppercase font-bold tracking-wider {{ $w->assigned_hm ? 'text-purple-700' : 'text-amber-800' }}">
                                Head Marketing (HM) Penanggung Jawab
                            </div>
                            <div class="text-xs font-extrabold text-slate-900 mt-0.5">
                                {{ $w->assigned_hm ? $w->assigned_hm->name : '⚠️ Belum Ada HM' }}
                            </div>
                            @if($w->assigned_hm)
                                <div class="text-[10px] text-slate-500">{{ $w->assigned_hm->email }}</div>
                            @else
                                <div class="text-[10px] text-amber-700">Wilayah ini belum memiliki penanggung jawab HM.</div>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if($w->assigned_hm)
                                <form action="{{ route('admin.hm-wilayah.unassign', $w->assigned_hm->id) }}" method="POST" onsubmit="return confirm('Lepas penugasan HM {{ $w->assigned_hm->name }} dari wilayah {{ $w->nama }}?')">
                                    @csrf
                                    <input type="hidden" name="wilayah_id" value="{{ $w->id }}">
                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-red-50 hover:text-red-700 text-slate-500 text-xs font-semibold transition" title="Lepas penugasan HM">
                                        Lepas
                                    </button>
                                </form>
                            @endif
                            <button 
                                type="button" 
                                @click="openAssign('{{ $w->id }}', '{{ $w->nama }}', '{{ $w->assigned_hm ? $w->assigned_hm->id : '' }}')" 
                                class="px-3.5 py-1.5 rounded-lg {{ $w->assigned_hm ? 'bg-white border border-purple-200 text-purple-700 hover:bg-purple-100' : 'bg-purple-600 hover:bg-purple-700 text-white font-bold' }} text-xs font-semibold transition cursor-pointer"
                            >
                                {{ $w->assigned_hm ? 'Ganti HM' : '+ Tugaskan HM' }}
                            </button>
                        </div>
                    </div>

                    <!-- Downstream Info (SPV, Tim, & Target) -->
                    <div class="grid grid-cols-3 gap-2.5 text-center text-xs">
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">SPV Wilayah</span>
                            <span class="font-bold text-slate-800 text-[11px] mt-0.5 block truncate" title="{{ $w->assigned_spv ? $w->assigned_spv->name : 'Belum Ditunjuk' }}">
                                {{ $w->assigned_spv ? $w->assigned_spv->name : '-' }}
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Tim Lapangan</span>
                            <span class="font-bold text-slate-800 text-[11px] mt-0.5 block">
                                {{ $w->sales_count }} Sales | {{ $w->cs_count }} CS
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Target Wilayah</span>
                            @if($w->target_wilayah)
                                <span class="font-bold text-emerald-600 text-[11px] mt-0.5 block">
                                     {{ number_format($w->target_wilayah->target_lunas) }} Lunas
                                </span>
                            @else
                                <span class="text-[11px] text-slate-400 mt-0.5 block">Belum Di-lock</span>
                            @endif
                        </div>
                    </div>

                    <!-- Preview Kecamatan -->
                    <div class="pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between text-[10px] uppercase font-bold text-slate-400 mb-1.5">
                            <span>Kecamatan di Wilayah Ini ({{ $w->children->count() }})</span>
                            <span class="text-indigo-600 font-semibold lowercase">dikelola oleh SPV</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @forelse($w->children as $child)
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">
                                    {{ $child->nama }}
                                </span>
                            @empty
                                <span class="text-slate-400 text-xs italic">Belum ada kecamatan turunan.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- MODAL TUGASKAN HM -->
        <div x-show="modalAssign" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAssign = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Penugasan Wilayah Head Marketing (HM)</h3>
                    
                    <form action="{{ route('admin.hm-wilayah.assign') }}" method="POST" class="mt-4 space-y-4 text-xs">
                        @csrf
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Pilih Kota / Kabupaten *</label>
                            <select name="wilayah_id" x-model="selectedWilayahId" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                <option value="">-- Pilih Kota / Kabupaten --</option>
                                @foreach($wilayahs as $w)
                                    <option value="{{ $w->id }}">{{ $w->nama }} ({{ $w->kode }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Pilih Head Marketing (HM) *</label>
                            <select name="hm_id" x-model="selectedHmId" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                <option value="">-- Pilih HM --</option>
                                @foreach($hmList as $hm)
                                    <option value="{{ $hm->id }}">
                                        {{ $hm->name }} ({{ $hm->email }}) {{ $hm->wilayah ? '— Saat ini: ' . $hm->wilayah->nama : '— [Belum ada wilayah]' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-slate-400 mt-1">
                                HM yang dipilih akan ditugaskan ke wilayah ini. HM dapat memegang lebih dari satu wilayah sekaligus.
                            </p>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="modalAssign = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 text-white">Simpan Penugasan HM</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
