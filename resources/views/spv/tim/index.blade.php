@php
    $pageTitle = 'Direktori Tim (SPV)';
    $pageSubtitle = 'Daftar Anggota Tim Sales & CS di Bawah Pengawasan SPV';
@endphp

<x-app-layout :title="'Anggota Tim - Supervisor CRM'">

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
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Anggota Tim & Penugasan Wilayah</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Pengawasan SPV</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Wilayah Kerja Utama SPV: <strong class="text-slate-800">{{ $myWilayah }}</strong></p>
                <p class="text-[11px] text-slate-400 mt-0.5">SPV menugaskan <strong>Area / Kecamatan</strong> untuk Sales dan CS (CS mendukung multi-wilayah).</p>
            </div>
            <button onclick="document.getElementById('modalAddMember').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs transition cursor-pointer">
                + Tambah / Penugasan Tim SPV
            </button>
        </div>

        <!-- Modal Penugasan Tim SPV (Landscape Format) -->
        <div id="modalAddMember" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-3xl w-full p-6 space-y-5 shadow-2xl">
                <div class="flex items-center justify-between border-b pb-3 border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wide">PENUGASAN TIM SPV</h3>
                            <p class="text-[11px] text-slate-400">Atur pembagian wilayah kecamatan untuk Sales dan CS</p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('modalAddMember').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition cursor-pointer text-xl font-bold">&times;</button>
                </div>

                <form action="{{ route('spv.tim.territory.assign') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    
                    <!-- 1. Wilayah Utama (Banner Header Landscape) -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3.5 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold block uppercase tracking-wider">Wilayah Utama Scope SPV</span>
                            <span class="text-sm font-bold text-slate-800">{{ $myWilayah }}</span>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                            Kota/Kabupaten Scope
                        </span>
                    </div>

                    <!-- Landscape 2-Column Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        
                        <!-- SISI KIRI: Sales & Area Sales -->
                        <div class="bg-blue-50/40 border border-blue-100 rounded-xl p-4 space-y-4">
                            <div class="flex items-center gap-2 border-b border-blue-100 pb-2">
                                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Penugasan Sales</h4>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Pilih Sales</label>
                                <select name="sales_id" id="salesSelect" onchange="onSalesChange(this.value)" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Pilih Sales (Opsional) --</option>
                                    @foreach($salesCandidates as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->email }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Area / Kecamatan Sales</label>
                                <select name="sales_area_id" id="salesAreaSelect" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Pilih Area / Kecamatan --</option>
                                    @foreach($kecamatanList as $kec)
                                        <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                                    @endforeach
                                </select>
                                <p class="text-[10px] text-slate-400 mt-1">1 Area = 1 Sales Utama Aktif</p>
                            </div>
                        </div>

                        <!-- SISI KANAN: CS & Area CS (Multi-Select) -->
                        <div class="bg-purple-50/40 border border-purple-100 rounded-xl p-4 space-y-4">
                            <div class="flex items-center gap-2 border-b border-purple-100 pb-2">
                                <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                                <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Penugasan CS (Multi-Wilayah)</h4>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Pilih CS</label>
                                <select name="cs_id" id="csSelect" onchange="onCsChange(this.value)" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:ring-2 focus:ring-purple-500">
                                    <option value="">-- Pilih CS (Opsional) --</option>
                                    @foreach($csCandidates as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Area / Kecamatan CS (Bisa Pilih Banyak)</label>
                                <div class="border border-slate-200 rounded-xl p-3 bg-white max-h-48 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach($kecamatanList as $kec)
                                        <label class="flex items-center gap-2 text-xs text-slate-800 font-medium cursor-pointer hover:bg-purple-50/80 p-1.5 rounded-lg border border-slate-100 transition">
                                            <input type="checkbox" name="cs_area_ids[]" value="{{ $kec->id }}" class="cs-area-checkbox rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                                            <span class="truncate">{{ $kec->nama }}</span>
                                        </label>
                                    @endforeach
                                    @if(count($kecamatanList) === 0)
                                        <div class="col-span-2 text-slate-400 text-[11px] text-center py-2">
                                            Belum ada data kecamatan di wilayah ini.
                                        </div>
                                    @endif
                                </div>
                            </div>
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
            const csWilayahMap = {
                @foreach($csCandidates as $c)
                    "{{ $c->id }}": @json($c->activeWilayahes->pluck('id')->toArray()),
                @endforeach
            };
            const salesWilayahMap = {
                @foreach($salesCandidates as $s)
                    "{{ $s->id }}": @json($s->activeWilayahes->pluck('id')->first()),
                @endforeach
            };

            function onCsChange(csId) {
                const checkboxes = document.querySelectorAll('.cs-area-checkbox');
                const activeIds = csWilayahMap[csId] || [];
                checkboxes.forEach(cb => {
                    cb.checked = activeIds.map(String).includes(String(cb.value));
                });
            }

            function onSalesChange(salesId) {
                const select = document.getElementById('salesAreaSelect');
                const activeAreaId = salesWilayahMap[salesId];
                if (activeAreaId) {
                    select.value = activeAreaId;
                }
            }

            function openModalWithMember(member) {
                document.getElementById('modalAddMember').classList.remove('hidden');
                if (member.role === 'Sales' || member.role === 'sales') {
                    const salesSel = document.getElementById('salesSelect');
                    if (salesSel) {
                        salesSel.value = member.id;
                        onSalesChange(member.id);
                    }
                } else if (member.role === 'CS' || member.role === 'cs') {
                    const csSel = document.getElementById('csSelect');
                    if (csSel) {
                        csSel.value = member.id;
                        onCsChange(member.id);
                    }
                }
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
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $member['role'] === 'Sales' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">
                                        {{ $member['role'] }}
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

                    <!-- 1. Pilihan Master Kecamatan -->
                    <div x-show="!useOtherCity" class="space-y-1">
                        <label class="block font-semibold text-slate-700">Pilih Kecamatan *</label>
                        <select name="wilayah_id" :required="!useOtherCity" x-model="selectedWilayahId"
                            class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs focus:ring-2 focus:ring-indigo-500 bg-white"
                        >
                            <option value="">-- Pilih Kecamatan --</option>
                            @foreach($kecamatanList as $kec)
                                <option value="{{ $kec->id }}">{{ $kec->nama }}</option>
                            @endforeach
                        </select>
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
