@php
    $pageTitle = 'Tahun Akademik & Periode Transaksi';
    $pageSubtitle = 'Kelola Tahun Akademik Aktif & Arsip Historis Transaksi PMB UCIC (PRD 5.2)';
@endphp

<x-app-layout :title="'Tahun Akademik - CRM UCIC'">

    <div class="space-y-6 max-w-5xl mx-auto" x-data="{ modalAdd: false }">

        <!-- Header Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Tahun Akademik (Periode Transaksi)</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">PRD 5.2</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Aturan Bisnis: TEPAT SATU Tahun Akademik berstatus <strong>AKTIF</strong> dalam satu waktu. Seluruh data prospek & transaksi baru otomatis terikat ke periode yang sedang Aktif.
                </p>
            </div>
            <button 
                type="button" 
                @click="modalAdd = true"
                class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold shadow-xs transition flex items-center gap-2 shrink-0 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>+ Tambah Tahun Akademik</span>
            </button>
        </div>

        <!-- Alert Notification -->
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-2xl flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Active Period Highlight Card -->
        @if($active)
            <div class="p-5 rounded-2xl bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="text-[11px] font-bold text-blue-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Tahun Akademik Aktif Saat Ini
                    </div>
                    <h3 class="text-2xl font-black text-white tracking-tight">TA {{ $active->nama }}</h3>
                    <p class="text-xs text-slate-300">Seluruh target, pencapaian, dan prospek baru terikat ke periode berjalan ini (SOP PMB TA {{ $active->nama }}).</p>
                </div>
                <div class="shrink-0 self-start sm:self-center">
                    <span class="px-3.5 py-1.5 rounded-full text-xs font-extrabold bg-emerald-500 text-white shadow-xs inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        STATUS: AKTIF
                    </span>
                </div>
            </div>
        @endif

        <!-- Table List -->
        <div class="crm-card bg-white overflow-hidden rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Daftar Periode Tahun Akademik</h3>
                <span class="text-xs text-slate-500">Total: {{ count($list) }} Periode</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                            <th class="py-3 px-5">Tahun Akademik</th>
                            <th class="py-3 px-4">Status Transaksi</th>
                            <th class="py-3 px-4">Keterangan SOP</th>
                            <th class="py-3 px-5 text-right">Aksi Mode Periode</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($list as $ta)
                            <tr class="hover:bg-slate-50/80 transition {{ $ta->status === 'Aktif' ? 'bg-blue-50/40' : '' }}">
                                <td class="py-4 px-5">
                                    <div class="font-extrabold text-sm text-slate-900">{{ $ta->nama }}</div>
                                    <div class="text-[10px] text-slate-400">ID: TA-{{ $ta->id }}</div>
                                </td>
                                <td class="py-4 px-4">
                                    @if($ta->status === 'Aktif')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            AKTIF (Periode Berjalan)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                            Non-Aktif (Arsip Read-Only)
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-slate-600 text-xs">
                                    @if($ta->status === 'Aktif')
                                        Periode transaksi aktif saat ini. Mengacu ke SOP Siklus PMB TA {{ $ta->nama }}.
                                    @else
                                        Data historis & arsip laporan. Hanya dapat dilihat (read-only), tidak dapat menambah transaksi baru.
                                    @endif
                                </td>
                                <td class="py-4 px-5 text-right">
                                    @if($ta->status === 'Aktif')
                                        <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200">
                                            ✓ Sedang Aktif
                                        </span>
                                    @else
                                        <form action="{{ route('admin.tahun-akademik.activate', $ta) }}" method="POST" class="inline">
                                            @csrf
                                            <button 
                                                type="submit" 
                                                onclick="return confirm('Aktifkan Tahun Akademik {{ $ta->nama }}? Status periode lain akan otomatis berubah menjadi Non-Aktif (Arsip).')"
                                                class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition cursor-pointer"
                                            >
                                                Aktifkan Periode Ini
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah -->
        <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAdd = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10 text-left">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah Tahun Akademik Baru</h3>
                        <button @click="modalAdd = false" class="text-slate-400 hover:text-slate-600 p-1"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form action="{{ route('admin.tahun-akademik.store') }}" method="POST" class="mt-4 space-y-4 text-xs">
                        @csrf
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Nama Tahun Akademik *</label>
                            <input type="text" name="nama" required placeholder="Contoh: 2028/2029" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-blue-500/20">
                            <p class="text-[10px] text-slate-400 mt-1">Format standard: YYYY/YYYY (misal: 2028/2029)</p>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="modalAdd = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-xs">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
