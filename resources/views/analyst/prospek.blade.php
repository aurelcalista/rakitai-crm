@php
    $pageTitle = 'Data Prospek';
    $pageSubtitle = 'Daftar Seluruh Data Calon Mahasiswa & Pipeline PMB';
@endphp

<x-app-layout :title="'Data Prospek - CRM UCIC'">

    <!-- State Simulation Wrappers -->
    <div x-show="$store.crm.activeState === 'loading'" x-cloak class="no-print">
        <x-loading-skeleton type="table" />
    </div>

    <div x-show="$store.crm.activeState === 'empty'" x-cloak class="no-print">
        <x-empty-state 
            title="Tidak ada data prospek" 
            description="Tidak ditemukan prospek dengan parameter filter aktif."
        />
    </div>

    <div x-show="$store.crm.activeState === 'error'" x-cloak class="no-print">
        <x-error-state />
    </div>

    <!-- NORMAL DATA STATE -->
    <div x-show="$store.crm.activeState === 'normal'" class="space-y-6">

        <!-- Header Screen -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Data Prospek CRM UCIC (Read-Only)</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">Global Read Access</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Total: {{ number_format($prospeks->total()) }} Prospek</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Eksplorasi seluruh data calon mahasiswa dari semua tim dan wilayah untuk keperluan verifikasi dan analisis laporan.</p>
            </div>

            <div class="flex items-center gap-2">
                <a 
                    href="{{ route('analyst.export', array_merge(request()->query(), ['type' => 'prospek'])) }}" 
                    class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-2"
                >
                    <svg class="w-4 h-4 text-emerald-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Download Hasil Filter (Excel/CSV)</span>
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('analyst.prospek.index') }}" class="no-print bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs mb-3">
                <!-- Search text -->
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Cari Nama / WhatsApp</label>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Ketik nama calon mahasiswa atau no WA..." 
                        class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-sky-500/20"
                    >
                </div>

                <!-- Wilayah -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Wilayah</label>
                    <select name="wilayah_id" onchange="this.form.submit()" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Wilayah</option>
                        @foreach($wilayahOptions as $w)
                            <option value="{{ $w->id }}" {{ ($filters['wilayah_id'] ?? '') == $w->id ? 'selected' : '' }}>{{ $w->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Status Lead</label>
                    <select name="status" onchange="this.form.submit()" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Status</option>
                        <option value="BARU" {{ ($filters['status'] ?? '') === 'BARU' ? 'selected' : '' }}>BARU</option>
                        <option value="KONTAK" {{ ($filters['status'] ?? '') === 'KONTAK' ? 'selected' : '' }}>KONTAK</option>
                        <option value="PROSPEK" {{ ($filters['status'] ?? '') === 'PROSPEK' ? 'selected' : '' }}>HANGAT</option>
                        <option value="HOT PROSPEK" {{ ($filters['status'] ?? '') === 'HOT PROSPEK' ? 'selected' : '' }}>PANAS</option>
                        <option value="FORMULIR" {{ ($filters['status'] ?? '') === 'FORMULIR' ? 'selected' : '' }}>FORMULIR</option>
                        <option value="BERKAS" {{ ($filters['status'] ?? '') === 'BERKAS' ? 'selected' : '' }}>BERKAS</option>
                        <option value="LUNAS" {{ ($filters['status'] ?? '') === 'LUNAS' ? 'selected' : '' }}>LUNAS</option>
                        <option value="DINGIN" {{ ($filters['status'] ?? '') === 'DINGIN' ? 'selected' : '' }}>DINGIN</option>
                        <option value="CANCEL" {{ ($filters['status'] ?? '') === 'CANCEL' ? 'selected' : '' }}>BATAL</option>
                    </select>
                </div>

                <!-- Prodi -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Program Studi</label>
                    <select name="prodi_id" onchange="this.form.submit()" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Prodi</option>
                        @foreach($prodiOptions as $p)
                            <option value="{{ $p->id }}" {{ ($filters['prodi_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sales -->
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Sales Handler</label>
                    <select name="sales_id" onchange="this.form.submit()" class="w-full px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                        <option value="all">Semua Sales</option>
                        @foreach($salesOptions as $s)
                            <option value="{{ $s->id }}" {{ ($filters['sales_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs transition">Filter Data</button>
                    <a href="{{ route('analyst.prospek.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs transition">Reset</a>
                </div>
                <span class="text-[11px] text-slate-400">Menampilkan 20 baris per halaman</span>
            </div>
        </form>

        <!-- Table Card -->
        <div class="crm-card bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] border-b border-slate-200/80">
                        <tr>
                            <th class="py-3 px-3.5">ID</th>
                            <th class="py-3 px-3.5">Nama Calon Mahasiswa</th>
                            <th class="py-3 px-3.5">Kontak WhatsApp</th>
                            <th class="py-3 px-3.5">Status Lead</th>
                            <th class="py-3 px-3.5">Prodi & Kelas</th>
                            <th class="py-3 px-3.5">Asal Sekolah / Mitra</th>
                            <th class="py-3 px-3.5">Wilayah</th>
                            <th class="py-3 px-3.5">Sales / CS</th>
                            <th class="py-3 px-3.5 text-center">Follow-Up</th>
                            <th class="py-3 px-3.5 text-center">Kelayakan Closing</th>
                            <th class="py-3 px-3.5">Tanggal Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($prospeks as $p)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-3.5 font-mono text-[11px] text-slate-400">#{{ $p->id }}</td>
                                <td class="py-3 px-3.5">
                                    <div class="font-bold text-slate-900">{{ $p->name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $p->source ?: 'Sumber tidak tercatat' }}</div>
                                </td>
                                <td class="py-3 px-3.5 font-mono text-slate-700">
                                    {{ $p->whatsapp ?: '-' }}
                                </td>
                                <td class="py-3 px-3.5">
                                    <x-status-badge :status="$p->status" />
                                    <div class="text-[10px] text-slate-500 font-medium mt-0.5">PRD: {{ \App\Services\AnalystExportService::mapToPrdStatus($p->status) }}</div>
                                </td>
                                <td class="py-3 px-3.5">
                                    <div class="font-semibold text-slate-800">{{ $p->prodi?->nama ?: ($p->prodi_lainnya ?: '-') }}</div>
                                    <span class="inline-block px-1.5 py-0.2 rounded text-[9.5px] font-semibold bg-slate-100 text-slate-600">{{ $p->kelas ?: 'Reguler' }}</span>
                                </td>
                                <td class="py-3 px-3.5 text-slate-700">
                                    {{ $p->sekolah?->nama ?: ($p->perusahaan?->nama ?: '-') }}
                                </td>
                                <td class="py-3 px-3.5 text-slate-600">
                                    {{ $p->wilayah?->nama ?: '-' }}
                                </td>
                                <td class="py-3 px-3.5">
                                    <div class="font-medium text-slate-800">{{ $p->sales?->name ?: 'Direct / Tanpa Sales' }}</div>
                                    @if($p->cs)
                                        <div class="text-[10px] text-slate-400">CS: {{ $p->cs->name }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-3.5 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $p->follow_up_count > 0 ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ $p->follow_up_count ?: 0 }}x
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 text-center">
                                    @if($p->isMabaLunas())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">
                                            ✓ LUNAS VALID
                                        </span>
                                    @elseif($p->status === 'LUNAS')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800">
                                            LUNAS
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-[10px]">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3.5 text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ $p->created_at ? $p->created_at->format('d M Y, H:i') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-10 text-slate-400">
                                    Tidak ada data prospek yang sesuai dengan kriteria filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($prospeks->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $prospeks->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
