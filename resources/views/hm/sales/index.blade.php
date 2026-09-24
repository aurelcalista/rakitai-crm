@php
    $pageTitle = 'Kelola Sales (Head Marketing)';
    $pageSubtitle = 'Manajemen & Penugasan Wilayah Staf Sales di Bawah HM';
@endphp

<x-app-layout :title="'Kelola Sales - Head Marketing'">

    <div class="space-y-6">

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
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Kelola Sales (Marketing Executive)</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">Head Marketing Scope</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Wilayah Kerja Utama HM: <strong class="text-slate-800">{{ $myWilayah }}</strong></p>
                <p class="text-[11px] text-slate-400 mt-0.5">HM menugaskan & mengelola akun Sales serta <strong>Area / Kecamatan (1 Wilayah = 1 Sales Aktif)</strong> di bawah naungan HM.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button onclick="document.getElementById('modalAddSales').classList.remove('hidden')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition cursor-pointer flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Tambah Sales Baru</span>
                </button>
                <button onclick="openModalAssignSales()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs transition cursor-pointer">
                    + Penugasan Area Sales
                </button>
            </div>
        </div>

        <!-- Modal 1: Tambah Sales Baru (HM) -->
        <div id="modalAddSales" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-5 shadow-2xl">
                <div class="flex items-center justify-between border-b pb-3 border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-blue-50 text-blue-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wide">TAMBAH SALES BARU</h3>
                            <p class="text-[11px] text-slate-400">Buat akun Sales baru & tentukan penugasan area wilayah</p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('modalAddSales').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition cursor-pointer text-xl font-bold">&times;</button>
                </div>

                <form action="{{ route('hm.sales.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <!-- Informasi Role & Scope -->
                    <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-blue-600 font-bold uppercase tracking-wider block">Scope Wilayah HM: {{ $myWilayah }}</span>
                            <span class="text-xs text-slate-600">Role & Jabatan otomatis: <strong class="text-blue-700">Sales</strong></span>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                            Domain @cic.ac.id
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap Sales *</label>
                            <input type="text" name="name" required placeholder="Contoh: Ahmad Sales" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs outline-none focus:ring-2 focus:ring-blue-200">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Email Sales *</label>
                            <div class="flex items-center rounded-xl border border-slate-200 overflow-hidden focus-within:ring-2 focus-within:ring-blue-200 focus-within:border-blue-400 bg-white">
                                <input type="text" name="email_username" required placeholder="ahmad.sales" class="w-full text-xs px-3.5 py-2.5 outline-none border-0 bg-transparent text-slate-800">
                                <span class="bg-slate-100 text-slate-600 px-3 py-2.5 text-xs font-bold border-l border-slate-200 shrink-0 font-mono">
                                    @cic.ac.id
                                </span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kode Sales (Auto-Generated)</label>
                        <input type="text" disabled placeholder="Otomatis (contoh: 2609S001)" class="w-full rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-2.5 text-xs text-slate-400 italic">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Password *</label>
                            <input type="password" name="password" required placeholder="******" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs outline-none focus:ring-2 focus:ring-blue-200">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Konfirmasi Password *</label>
                            <input type="password" name="password_confirmation" required placeholder="******" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs outline-none focus:ring-2 focus:ring-blue-200">
                        </div>
                    </div>

                    <!-- Area Select Checkboxes -->
                    <div class="space-y-1.5 pt-2 border-t border-slate-100">
                        <label class="block font-bold text-slate-800 text-xs">Pilih Area / Kecamatan Penugasan Sales *</label>
                        <p class="text-[11px] text-amber-600">Catatan: Setiap kecamatan hanya dapat ditugaskan ke 1 Sales aktif.</p>
                        <div class="border border-slate-200 rounded-xl p-3 bg-white max-h-48 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($kecamatanList as $kec)
                                <label class="flex items-center gap-2 text-xs text-slate-800 font-medium cursor-pointer hover:bg-blue-50/80 p-1.5 rounded-lg border border-slate-100 transition">
                                    <input type="checkbox" name="sales_area_ids[]" value="{{ $kec->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span class="truncate">{{ $kec->nama }}</span>
                                </label>
                            @endforeach
                            @if(count($kecamatanList) === 0)
                                <div class="col-span-2 text-slate-400 text-[11px] text-center py-2">
                                    Belum ada data kecamatan di wilayah scope ini.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" onclick="document.getElementById('modalAddSales').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-xs">Simpan & Buat Sales</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 2: Penugasan Area Sales (HM) -->
        <div id="modalAssignSales" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl">
                <div class="flex items-center justify-between border-b pb-3 border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <h3 id="assignSalesModalTitle" class="font-bold text-slate-900 text-sm uppercase tracking-wide">PENUGASAN AREA SALES</h3>
                            <p class="text-[11px] text-slate-400">Atur area kecamatan untuk Sales (Global Unique Sales per Area)</p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('modalAssignSales').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition cursor-pointer text-xl font-bold">&times;</button>
                </div>

                <form action="{{ route('hm.sales.territory.assign') }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Pilih Sales *</label>
                        <select name="sales_id" id="hmSalesSelect" required onchange="onHmSalesChange(this.value)" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Sales --</option>
                            @foreach($salesUsers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->kode ?? $s->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Area / Kecamatan Sales *</label>
                        <p class="text-[11px] text-amber-600 mb-1">1 Wilayah hanya boleh memiliki 1 Sales aktif.</p>
                        <div class="border border-slate-200 rounded-xl p-3 bg-white max-h-48 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($kecamatanList as $kec)
                                <label class="flex items-center gap-2 text-xs text-slate-800 font-medium cursor-pointer hover:bg-blue-50/80 p-1.5 rounded-lg border border-slate-100 transition">
                                    <input type="checkbox" name="sales_area_ids[]" value="{{ $kec->id }}" class="hm-sales-area-cb rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span class="truncate">{{ $kec->nama }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" onclick="document.getElementById('modalAssignSales').classList.add('hidden')" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-xs font-semibold hover:bg-indigo-700 shadow-xs cursor-pointer">Simpan Penugasan</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            const hmSalesWilayahMap = {
                @foreach($salesUsers as $s)
                    "{{ $s->id }}": @json($s->activeWilayahes->pluck('id')->toArray()),
                @endforeach
            };

            function onHmSalesChange(salesId) {
                const checkboxes = document.querySelectorAll('.hm-sales-area-cb');
                const activeIds = hmSalesWilayahMap[salesId] || [];
                checkboxes.forEach(cb => {
                    cb.checked = activeIds.map(String).includes(String(cb.value));
                });
            }

            function openModalAssignSales(salesId = null) {
                document.getElementById('modalAssignSales').classList.remove('hidden');
                if (salesId) {
                    const sel = document.getElementById('hmSalesSelect');
                    if (sel) {
                        sel.value = salesId;
                        onHmSalesChange(salesId);
                    }
                }
            }
        </script>

        <!-- Sales Members Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($salesData as $member)
                <div class="crm-card bg-white p-5 space-y-4 hover:shadow-md transition border border-slate-200 rounded-2xl">

                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-sm">
                                {{ strtoupper(substr($member['name'], 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-slate-900">{{ $member['name'] }}</h3>
                                <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        Sales
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        🆔 {{ $member['kode'] ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $member['status'] === 'Aktif' || $member['status'] === 'aktif' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                            {{ ucfirst($member['status']) }}
                        </span>
                    </div>

                    <!-- Contact & Wilayah -->
                    <div class="bg-slate-50 p-3 rounded-xl space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">Kode Sales:</span>
                            <span class="font-mono font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-100 text-[11px]">{{ $member['kode'] ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 font-medium">Supervisor (SPV):</span>
                            <span class="font-semibold text-slate-800">{{ $member['spv_name'] }}</span>
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
                            <span class="font-bold {{ $member['wilayah'] === 'Belum Ditugaskan Area' ? 'text-amber-600' : 'text-blue-700' }}">
                                @if($member['wilayah'] === 'Belum Ditugaskan Area')
                                    <span class="italic text-xs text-amber-600">Belum Ditugaskan</span>
                                @else
                                    {{ $member['wilayah'] }}@if($member['kota'] && $member['kota'] !== '-' && strtolower($member['kota']) !== strtolower($member['wilayah'])) <span class="font-normal text-slate-500 text-[11px]">({{ $member['kota'] }})</span>@endif
                                @endif
                            </span>
                        </div>
                    </div>

                    <!-- Activity Summary -->
                    <div class="grid grid-cols-3 gap-2 text-center text-xs py-2 bg-slate-50/50 rounded-xl border border-slate-100">
                        <div>
                            <span class="text-slate-400 text-[10px] block">Prospek</span>
                            <span class="font-bold text-slate-900">{{ $member['prospects'] }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] block">Kunjungan</span>
                            <span class="font-bold text-blue-600">{{ $member['visits'] }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[10px] block">Lunas</span>
                            <span class="font-bold text-emerald-600">{{ $member['closings'] }}</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-2 border-t border-slate-100 grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            onclick="openModalAssignSales({{ $member['id'] }})"
                            class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition flex items-center justify-center gap-1 border border-indigo-200 cursor-pointer"
                        >
                            Ubah Wilayah
                        </button>

                        <form action="{{ route('hm.sales.status.update', $member['id']) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button
                                type="submit"
                                class="w-full px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer border {{ $member['status'] === 'Aktif' || $member['status'] === 'aktif' ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border-rose-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border-emerald-200' }}"
                            >
                                {{ $member['status'] === 'Aktif' || $member['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </div>

                    <div class="text-[10px] text-slate-400 pt-1 border-t border-slate-100 text-right">
                        Login terakhir: {{ $member['last_login'] }}
                    </div>

                </div>
            @empty
                <div class="col-span-3 crm-card bg-white p-12 text-center text-slate-400 text-xs rounded-2xl">
                    Belum ada anggota Sales yang terdaftar di bawah naungan HM Anda.
                </div>
            @endforelse
        </div>

    </div>

</x-app-layout>
