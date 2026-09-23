@php $pageTitle = 'Wilayah Saya'; @endphp

<x-app-layout :title="'Wilayah Saya - HM CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalSpv: false,
        modalTarget: false,
        selectedWil: null,
    }">

        <!-- Header -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Wilayah Saya & Target Wilayah</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola penunjukan SPV dan tentukan Target Wilayah untuk mendistribusikan target ke tim lapangan.</p>
            </div>
            <span class="px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 font-bold text-xs border border-purple-200">
                Tahun Akademik: {{ $activeTA->nama ?? '2027/2028' }} (AKTIF)
            </span>
        </div>

        <!-- Notification Banner -->
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
                {{ session('success') }}
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

        <!-- List Wilayah -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($wilayahs as $w)
                @php
                    $targetWil = \App\Models\Target::where('target_type', 'Wilayah')->where('wilayah_id', $w->id)->where('status', 'Aktif')->latest()->first();
                    $assignedSpv = $w->users->where('role', 'SPV')->first();
                @endphp
                <div class="crm-card bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded">{{ $w->kode }}</span>
                            <h3 class="text-lg font-bold text-slate-900 mt-1">{{ $w->nama }}</h3>
                            <p class="text-xs text-slate-500">{{ count($w->kecamatan ?? $w->children->pluck('nama')) }} Kecamatan | {{ $w->sekolahs_count }} Sekolah | {{ $w->perusahaans_count }} Perusahaan</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $w->status === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-600' }}">
                            {{ $w->status }}
                        </span>
                    </div>

                    <!-- SPV Responsible -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                        <div>
                            <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">SPV Penanggung Jawab</div>
                            <div class="text-xs font-bold text-slate-800 mt-0.5">
                                {{ $assignedSpv ? $assignedSpv->name : 'Belum Ditunjuk' }}
                            </div>
                            @if($assignedSpv)
                                <div class="text-[10px] text-slate-500">{{ $assignedSpv->email }}</div>
                            @endif
                        </div>
                        <button type="button" @click="selectedWil = {{ json_encode($w) }}; modalSpv = true" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition cursor-pointer">
                            {{ $assignedSpv ? 'Ganti SPV' : '+ Penunjukan SPV' }}
                        </button>
                    </div>

                    <!-- Target Wilayah -->
                    <div class="p-4 rounded-xl bg-purple-50/50 border border-purple-100/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] uppercase font-bold text-purple-700 tracking-wider">Target Wilayah (HM $\rightarrow$ SPV)</span>
                            @if($targetWil && $targetWil->is_locked)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">🔒 DIKUNCI HM</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="bg-white p-2.5 rounded-lg border border-purple-100">
                                <div class="text-base font-extrabold text-slate-900">{{ $targetWil ? number_format($targetWil->target_kontak) : '-' }}</div>
                                <div class="text-[10px] text-slate-500 font-medium">Kontak</div>
                            </div>
                            <div class="bg-white p-2.5 rounded-lg border border-purple-100">
                                <div class="text-base font-extrabold text-blue-700">{{ $targetWil ? number_format($targetWil->target_formulir) : '-' }}</div>
                                <div class="text-[10px] text-blue-600 font-medium">Formulir</div>
                            </div>
                            <div class="bg-white p-2.5 rounded-lg border border-purple-100">
                                <div class="text-base font-extrabold text-emerald-700">{{ $targetWil ? number_format($targetWil->target_lunas) : '-' }}</div>
                                <div class="text-[10px] text-emerald-600 font-medium">Lunas</div>
                            </div>
                        </div>

                        <button type="button" @click="selectedWil = {{ json_encode($w) }}; modalTarget = true" class="w-full py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold transition cursor-pointer">
                            {{ $targetWil ? 'Edit & Lock Target Wilayah' : '+ Tentukan Target Wilayah' }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- MODAL PENUNJUKAN SPV -->
        <div x-show="modalSpv" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalSpv = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Penunjukan SPV Wilayah</h3>
                    <template x-if="selectedWil">
                        <form :action="'/hm/wilayah/' + selectedWil.id + '/assign-spv'" method="POST" class="mt-4 space-y-4 text-xs">
                            @csrf
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Wilayah Terpilih</label>
                                <input type="text" readonly :value="selectedWil.nama" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-100 border border-slate-200 font-bold text-slate-800">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Pilih SPV Penanggung Jawab *</label>
                                <select name="spv_id" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                    <option value="">-- Pilih SPV --</option>
                                    @foreach($spvCandidates as $cand)
                                        <option value="{{ $cand->id }}">{{ $cand->name }} ({{ $cand->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                <button type="button" @click="modalSpv = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600">Batal</button>
                                <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 text-white">Simpan Penunjukan SPV</button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>

        <!-- MODAL SET TARGET WILAYAH -->
        <div x-show="modalTarget" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalTarget = false"></div>
                <div class="inline-block w-full max-w-md p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Tentukan Target Wilayah (HM)</h3>
                    <template x-if="selectedWil">
                        <form :action="'/hm/wilayah/' + selectedWil.id + '/target'" method="POST" class="mt-4 space-y-3 text-xs">
                            @csrf
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Wilayah *</label>
                                <input type="text" readonly :value="selectedWil.nama" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-100 border border-slate-200 font-bold text-slate-800">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">SPV Penanggung Jawab *</label>
                                <select name="spv_id" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                    <option value="">-- Pilih SPV --</option>
                                    @foreach($spvCandidates as $cand)
                                        <option value="{{ $cand->id }}">{{ $cand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Periode *</label>
                                    <select name="tipe_periode" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                        <option value="Bulanan">Bulanan</option>
                                        <option value="Tahunan">Tahunan</option>
                                        <option value="Mingguan">Mingguan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Tgl Mulai *</label>
                                    <input type="date" name="tanggal_mulai" required value="{{ now()->startOfMonth()->toDateString() }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200">
                                </div>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tgl Selesai *</label>
                                <input type="date" name="tanggal_selesai" required value="{{ now()->endOfMonth()->toDateString() }}" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200">
                            </div>
                            <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Target Kontak</label>
                                    <input type="number" name="target_kontak" required min="0" value="1000" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Target Formulir</label>
                                    <input type="number" name="target_formulir" required min="0" value="300" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Target Lunas</label>
                                    <input type="number" name="target_lunas" required min="0" value="100" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200">
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                <button type="button" @click="modalTarget = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600">Batal</button>
                                <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 text-white">Simpan & Lock Target</button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
