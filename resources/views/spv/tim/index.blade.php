@php
    $pageTitle = 'Direktori Tim (SPV)';
    $pageSubtitle = 'Daftar Anggota Tim Sales & CS di Bawah Pengawasan SPV';
@endphp

<x-app-layout :title="'Anggota Tim - Supervisor CRM'">

    <div class="space-y-6" x-data="{ assignModal: false, selectedMemberId: null, selectedMemberName: '' }">

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
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Anggota Tim & Penugasan Wilayah</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Wilayah Kerja Utama SPV: <strong class="text-slate-800">{{ $myWilayah }}</strong></p>
                <p class="text-[11px] text-slate-400 mt-0.5">SPV menugaskan <strong>Kecamatan</strong> ke Sales. CS bekerja <strong>Centralized</strong> tanpa wilayah kecamatan.</p>
            </div>
            <button onclick="document.getElementById('assignModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                + Tambah / Pilih Anggota Tim
            </button>
        </div>

        <!-- Modal Assign Member -->
        <div id="assignModal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-slate-900">Penugasan Sales / CS ke Tim SPV</h3>
                    <button onclick="document.getElementById('assignModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form action="{{ route('spv.tim.assign') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Kandidat Sales / CS</label>
                        <select name="user_id" class="w-full rounded-xl border-slate-300 text-xs text-slate-800" required>
                            <option value="">-- Pilih Sales / CS --</option>
                            @foreach($candidates as $cand)
                                <option value="{{ $cand->id }}">{{ $cand->name }} ({{ $cand->role }}) - Wilayah: {{ $cand->wilayah ? $cand->wilayah->nama : 'Belum Ada' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Wilayah / Area Detail Spesifik (Cakupan {{ $myWilayah }})</label>
                        <select name="area_id" class="w-full rounded-xl border-slate-300 text-xs text-slate-800">
                            <option value="">-- Samakan dengan Wilayah Utama SPV --</option>
                            @foreach($availableAreas as $area)
                                <option value="{{ $area->id }}">{{ $area->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="document.getElementById('assignModal').classList.add('hidden')" class="px-4 py-2 border rounded-xl text-xs font-semibold text-slate-600">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-semibold hover:bg-indigo-700">Simpan Penugasan</button>
                    </div>
                </form>
            </div>
        </div>

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
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $member['role'] === 'Sales' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">
                                        {{ $member['role'] }}
                                    </span>
                                    @if($member['is_cs'])
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-violet-100 text-violet-700">Centralized</span>
                                    @endif
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
                            <span class="text-slate-400 font-medium">{{ $member['is_cs'] ? 'Mode Kerja' : 'Wilayah Tugas' }}:</span>
                            @if($member['is_cs'])
                                <span class="font-bold text-violet-700 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    Centralized (Tanpa Wilayah)
                                </span>
                            @else
                                <span class="font-bold text-blue-700">{{ $member['wilayah'] }} @if($member['kota'] && $member['kota'] !== '-')({{ $member['kota'] }})@endif</span>
                            @endif
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

                    <!-- Wilayah Assignment Action (Sales only) -->
                    @if(!$member['is_cs'] && count($kecamatanList) > 0)
                        <div class="pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="assignModal = true; selectedMemberId = {{ $member['id'] }}; selectedMemberName = '{{ addslashes($member['name']) }}'"
                                class="w-full px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition flex items-center justify-center gap-1.5 border border-indigo-200"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Ubah Wilayah Kecamatan
                            </button>
                        </div>
                    @endif

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

    <!-- Modal Assign Wilayah -->
    <div
        x-show="assignModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        @keydown.escape.window="assignModal = false"
    >
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4" @click.stop>
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">Ubah Wilayah Kecamatan</h3>
                <button @click="assignModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-sm text-slate-500">Menugaskan wilayah kecamatan untuk: <strong x-text="selectedMemberName" class="text-slate-800"></strong></p>
            <template x-for="member in {{ collect($teamData)->filter(fn($m) => !$m['is_cs'])->values()->toJson() }}" :key="member.id">
                <form
                    x-show="member.id == selectedMemberId"
                    :action="`/spv/tim/${selectedMemberId}/wilayah`"
                    method="POST"
                    class="space-y-4"
                    x-data="{ useOtherCity: member.is_other_city, customCity: member.lokasi_penugasan || '' }"
                >
                    @csrf
                    @method('PATCH')

                    <!-- Opsi Penugasan: Master vs Di Kota Lainnya (PRD Bab 3 & 9) -->
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl text-xs font-semibold">
                        <button
                            type="button"
                            @click="useOtherCity = false"
                            class="py-1.5 px-2 rounded-lg text-center transition"
                            :class="!useOtherCity ? 'bg-white text-indigo-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Master Kecamatan
                        </button>
                        <button
                            type="button"
                            @click="useOtherCity = true"
                            class="py-1.5 px-2 rounded-lg text-center transition"
                            :class="useOtherCity ? 'bg-white text-indigo-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                        >
                            Di Kota Lainnya ✨
                        </button>
                    </div>

                    <input type="hidden" name="is_other_city" :value="useOtherCity ? '1' : '0'">

                    <!-- 1. Pilihan Master Kecamatan -->
                    <div x-show="!useOtherCity">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Kecamatan</label>
                        <select name="wilayah_id" :required="!useOtherCity"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">-- Pilih Kecamatan --</option>
                            @foreach($kecamatanList as $kec)
                                <option value="{{ $kec->id }}" :selected="member.wilayah_id == {{ $kec->id }}">{{ $kec->nama }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">SPV hanya dapat menugaskan wilayah Kecamatan ke Sales dari master data aktif.</p>
                    </div>

                    <!-- 2. Pilihan Di Kota Lainnya -->
                    <div x-show="useOtherCity" x-cloak>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Kota / Wilayah Khusus</label>
                        <input
                            type="text"
                            name="custom_city"
                            x-model="customCity"
                            :required="useOtherCity"
                            placeholder="Contoh: Majalengka Kota, Brebes, Tegal, Subang..."
                            class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <p class="text-[10px] text-amber-700 mt-1">Gunakan opsi ini jika wilayah penugasan belum terdaftar di master data kota/kecamatan.</p>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm transition">Simpan Penugasan</button>
                        <button type="button" @click="assignModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm">Batal</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</x-app-layout>
