@php $pageTitle = 'Wilayah Saya'; @endphp

<x-app-layout :title="'Wilayah Saya - HM CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalSpv: false,
        modalTarget: false,
        modalAddSpv: false,
        selectedWil: null,
        spvList: {{ json_encode($spvCandidates->map(fn($c) => ['id' => (string)$c->id, 'name' => $c->name, 'email' => $c->email])) }},
        spvForm: {
            spv_id: '',
        },
        targetForm: {
            target_id: '',
            spv_id: '',
            tipe_periode: 'Tahunan',
            tanggal_mulai: '{{ now()->startOfMonth()->toDateString() }}',
            tanggal_selesai: '{{ now()->startOfMonth()->addYear()->subDay()->toDateString() }}',
            target_kontak: 1000,
            target_formulir: 300,
            target_lunas: 100,
        },
        openSpvModal(wilayah, currentSpvId) {
            this.selectedWil = wilayah;
            this.spvForm.spv_id = currentSpvId ? String(currentSpvId) : '';
            this.modalSpv = true;
        },
        openTargetModal(wilayah, target, assignedSpvId) {
            this.selectedWil = wilayah;
            if (target) {
                this.targetForm.target_id = target.id || '';
                this.targetForm.spv_id = target.spv_id ? String(target.spv_id) : (assignedSpvId ? String(assignedSpvId) : '');
                this.targetForm.tipe_periode = target.tipe_periode || 'Tahunan';
                this.targetForm.tanggal_mulai = target.tanggal_mulai ? String(target.tanggal_mulai).substring(0, 10) : '{{ now()->startOfMonth()->toDateString() }}';
                this.targetForm.tanggal_selesai = target.tanggal_selesai ? String(target.tanggal_selesai).substring(0, 10) : '{{ now()->startOfMonth()->addYear()->subDay()->toDateString() }}';
                this.targetForm.target_kontak = target.target_kontak ?? 1000;
                this.targetForm.target_formulir = target.target_formulir ?? 300;
                this.targetForm.target_lunas = target.target_lunas ?? 100;
            } else {
                this.targetForm.target_id = '';
                this.targetForm.spv_id = assignedSpvId ? String(assignedSpvId) : '';
                this.targetForm.tipe_periode = 'Tahunan';
                this.targetForm.tanggal_mulai = '{{ now()->startOfMonth()->toDateString() }}';
                this.targetForm.target_kontak = 1000;
                this.targetForm.target_formulir = 300;
                this.targetForm.target_lunas = 100;
                this.calculateEndDate();
            }
            this.modalTarget = true;
        },
        calculateEndDate() {
            if (!this.targetForm.tanggal_mulai) return;
            const parts = this.targetForm.tanggal_mulai.split('-').map(Number);
            if (parts.length < 3) return;
            const year = parts[0];
            const month = parts[1];
            const day = parts[2];

            if (this.targetForm.tipe_periode === 'Bulanan') {
                // Last day of the selected month
                const lastDay = new Date(year, month, 0);
                const yyyy = lastDay.getFullYear();
                const mm = String(lastDay.getMonth() + 1).padStart(2, '0');
                const dd = String(lastDay.getDate()).padStart(2, '0');
                this.targetForm.tanggal_selesai = `${yyyy}-${mm}-${dd}`;
            } else if (this.targetForm.tipe_periode === 'Mingguan') {
                // 7 days total (start date + 6 days)
                const d = new Date(year, month - 1, day);
                d.setDate(d.getDate() + 6);
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                this.targetForm.tanggal_selesai = `${yyyy}-${mm}-${dd}`;
            } else if (this.targetForm.tipe_periode === 'Tahunan') {
                // 1 full year period (e.g. 2026-09-01 to 2027-08-31)
                const d = new Date(year + 1, month - 1, day);
                d.setDate(d.getDate() - 1);
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                this.targetForm.tanggal_selesai = `${yyyy}-${mm}-${dd}`;
            } else if (this.targetForm.tipe_periode === 'Harian') {
                this.targetForm.tanggal_selesai = this.targetForm.tanggal_mulai;
            }
        }
    }">

        <!-- Header -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Wilayah Saya & Target Wilayah</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola penunjukan SPV dan tentukan Target Wilayah untuk mendistribusikan target ke tim lapangan.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-block px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 font-bold text-xs border border-purple-200">
                    TA: {{ $activeTA->nama ?? '2027/2028' }}
                </span>
                <button type="button" @click="modalAddSpv = true" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah SPV Baru</span>
                </button>
            </div>
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
                    $assignedSpv = $w->users->where('role', 'SPV')->first() ?? ($targetWil && $targetWil->spv_id ? \App\Models\User::find($targetWil->spv_id) : null);
                @endphp
                <div class="crm-card bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded">{{ $w->kode }}</span>
                            <h3 class="text-lg font-bold text-slate-900 mt-1">{{ $w->nama }}</h3>
                            <p class="text-xs text-slate-500">{{ $w->children->count() }} Kecamatan | {{ $w->sekolahs_count }} Sekolah | {{ $w->perusahaans_count }} Perusahaan</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $w->status === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-600' }}">
                            {{ $w->status }}
                        </span>
                    </div>

                    <!-- Cakupan Kecamatan Preview -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1.5">
                        <div class="flex items-center justify-between text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                            <span>Kecamatan di Wilayah Ini ({{ $w->children->count() }})</span>
                            <span class="text-indigo-600 font-semibold lowercase">dikelola oleh SPV</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @forelse($w->children as $child)
                                <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-medium shadow-2xs">
                                    {{ $child->nama }}
                                </span>
                            @empty
                                <span class="text-slate-400 text-xs italic">Belum ada kecamatan turunan yang terdaftar.</span>
                            @endforelse
                        </div>
                        <p class="text-[10px] text-slate-400 pt-0.5">
                            ℹ️ HM bertanggung jawab pada level Kota/Kabupaten dan menunjuk SPV penanggung jawab. Pembagian target dan penugasan Sales/CS per kecamatan dilakukan oleh SPV.
                        </p>
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
                        <button type="button" @click="openSpvModal({{ json_encode($w) }}, '{{ $assignedSpv ? $assignedSpv->id : '' }}')" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition cursor-pointer">
                            {{ $assignedSpv ? 'Ganti SPV' : '+ Penunjukan SPV' }}
                        </button>
                    </div>

                    <!-- Target Wilayah -->
                    <div class="p-4 rounded-xl bg-purple-50/50 border border-purple-100/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] uppercase font-bold text-purple-700 tracking-wider">Target Wilayah (HM &rarr; SPV)</span>
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

                        @if($targetWil)
                            <div class="text-[11px] text-slate-500 flex items-center justify-between px-1">
                                <span>Periode: <strong class="text-slate-700">{{ $targetWil->tipe_periode }}</strong></span>
                                <span>{{ \Carbon\Carbon::parse($targetWil->tanggal_mulai)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($targetWil->tanggal_selesai)->format('d/m/Y') }}</span>
                            </div>
                        @endif

                        <button type="button" @click="openTargetModal({{ json_encode($w) }}, {{ json_encode($targetWil) }}, '{{ $assignedSpv ? $assignedSpv->id : '' }}')" class="w-full py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold transition cursor-pointer">
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
                                @if($spvCandidates->isEmpty())
                                    <div class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs font-semibold">
                                        ⚠️ Belum ada user dengan role SPV terdaftar di sistem. Mohon buat user SPV di Master User Admin.
                                    </div>
                                @else
                                    <select name="spv_id" x-model="spvForm.spv_id" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                        <option value="">-- Pilih SPV --</option>
                                        @foreach($spvCandidates as $cand)
                                            <option value="{{ $cand->id }}">{{ $cand->name }} ({{ $cand->email }})</option>
                                        @endforeach
                                    </select>
                                    <template x-if="spvForm.spv_id">
                                        <div class="mt-1 text-[11px] text-purple-700 font-semibold flex items-center gap-1">
                                            <span>📧 Email SPV:</span>
                                            <span class="font-mono bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200" x-text="spvList.find(c => c.id === String(spvForm.spv_id))?.email || '-'"></span>
                                        </div>
                                    </template>
                                @endif
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
                    <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3" x-text="targetForm.target_id ? 'Edit & Lock Target Wilayah' : 'Tentukan Target Wilayah (HM)'"></h3>
                    <template x-if="selectedWil">
                        <form :action="'/hm/wilayah/' + selectedWil.id + '/target'" method="POST" class="mt-4 space-y-3 text-xs">
                            @csrf
                            <input type="hidden" name="target_id" :value="targetForm.target_id">

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Wilayah *</label>
                                <input type="text" readonly :value="selectedWil.nama" class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-100 border border-slate-200 font-bold text-slate-800">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">SPV Penanggung Jawab *</label>
                                @if($spvCandidates->isEmpty())
                                    <div class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs font-semibold">
                                        ⚠️ Belum ada user dengan role SPV terdaftar di sistem. Mohon buat user SPV di Master User Admin.
                                    </div>
                                @else
                                    <select name="spv_id" x-model="targetForm.spv_id" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                        <option value="">-- Pilih SPV --</option>
                                        @foreach($spvCandidates as $cand)
                                            <option value="{{ $cand->id }}">{{ $cand->name }} ({{ $cand->email }})</option>
                                        @endforeach
                                    </select>
                                    <template x-if="targetForm.spv_id">
                                        <div class="mt-1 text-[11px] text-purple-700 font-semibold flex items-center gap-1">
                                            <span>📧 Email SPV:</span>
                                            <span class="font-mono bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200" x-text="spvList.find(c => c.id === String(targetForm.spv_id))?.email || '-'"></span>
                                        </div>
                                    </template>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Periode *</label>
                                    <select name="tipe_periode" x-model="targetForm.tipe_periode" @change="calculateEndDate()" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white">
                                        <option value="Tahunan">Tahunan</option>
                                        <option value="Bulanan">Bulanan</option>
                                        <option value="Mingguan">Mingguan</option>
                                        <option value="Harian">Harian</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Tgl Mulai *</label>
                                    <input type="date" name="tanggal_mulai" x-model="targetForm.tanggal_mulai" @change="calculateEndDate()" required class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200">
                                </div>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tgl Selesai *</label>
                                <input type="date" name="tanggal_selesai" x-model="targetForm.tanggal_selesai" required class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200">
                                <p class="text-[10px] text-slate-400 mt-0.5">Otomatis dihitung sesuai periode & tgl mulai (dapat disesuaikan jika perlu).</p>
                            </div>
                            <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Target Kontak</label>
                                    <input type="number" name="target_kontak" x-model="targetForm.target_kontak" required min="0" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Target Formulir</label>
                                    <input type="number" name="target_formulir" x-model="targetForm.target_formulir" required min="0" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Target Lunas</label>
                                    <input type="number" name="target_lunas" x-model="targetForm.target_lunas" required min="0" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200">
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                                <button type="button" @click="modalTarget = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600">Batal</button>
                                <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 text-white" x-text="targetForm.target_id ? 'Update & Lock Target' : 'Simpan & Lock Target'"></button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>

        <!-- MODAL: TAMBAH SPV BARU -->
        <div x-show="modalAddSpv" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAddSpv = false"></div>
                <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah SPV Baru</h3>
                        <button @click="modalAddSpv = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form action="{{ route('hm.wilayah.storeSpv') }}" method="POST" class="mt-4 space-y-3 text-xs">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                            <input type="text" name="name" required placeholder="Masukkan Nama Lengkap ..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Email *</label>
                                <input type="email" name="email" required placeholder="Masukkan Email ..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor HP *</label>
                                <input type="tel" name="phone" required placeholder="Masukkan Nomor HP ..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Password *</label>
                            <input type="password" name="password" required placeholder="Masukkan Password (min. 8 karakter)" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-200 focus:border-purple-400 outline-none">
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex justify-end gap-2 mt-4">
                            <button type="button" @click="modalAddSpv = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white shadow-xs transition cursor-pointer">Simpan SPV</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
