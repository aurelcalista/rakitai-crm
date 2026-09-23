@php $pageTitle = 'Kelola Target'; @endphp

<x-app-layout :title="'Kelola Target - CRM UCIC'">

    <div class="space-y-6" x-data="{
        modalAdd: false,
        modalEdit: false,
        selectedTarget: null,
        filterStatus: 'all',
        filterRole: 'all',
        selectedRoleAdd: '',
        targets: {{ json_encode($targets) }},
        salesList: {{ json_encode($salesList) }},
        get filtered() {
            return this.targets.filter(t => 
                (this.filterStatus === 'all' || t.status.toLowerCase() === this.filterStatus.toLowerCase()) &&
                (this.filterRole === 'all' || t.role.toLowerCase() === this.filterRole.toLowerCase())
            );
        },
        getColorClass(realisasi, target) {
            if (target <= 0) return 'bg-emerald-500';
            let pct = (realisasi / target) * 100;
            if (pct < 50) return 'bg-red-500';
            if (pct <= 80) return 'bg-yellow-500';
            return 'bg-emerald-500';
        }
    }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Kelola Target Sales</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Atur target harian/bulanan Sales, pantau realisasi, dan akumulasi kekurangan.</p>
            </div>
            <button type="button" @click="modalAdd = true" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>+ Tambah Target</span>
            </button>
        </div>

        <!-- Info Box: Aturan Akumulasi -->
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-bold text-amber-800 mb-1">Aturan Akumulasi Target</p>
                    <p class="text-amber-700 leading-relaxed">Kekurangan target hari ini (kontak baru & follow up) <strong>secara otomatis dibawa ke hari berikutnya</strong>. Target besok = Target Harian + Akumulasi Kekurangan. Contoh: Target 10 kontak, realisasi 7 → besok jadi 13 kontak.</p>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Status Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.away="open = false" type="button" class="flex items-center justify-between gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-xl shadow-xs text-xs font-semibold text-slate-700 hover:bg-slate-50 transition min-w-[150px]">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400">Status:</span>
                        <span x-text="filterStatus === 'all' ? 'Semua' : (filterStatus.charAt(0).toUpperCase() + filterStatus.slice(1))"></span>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute left-0 mt-2 w-full min-w-[150px] bg-white rounded-xl shadow-xl border border-slate-100 py-1.5 z-20" style="display: none;">
                    @foreach([['val'=>'all','label'=>'Semua'],['val'=>'aktif','label'=>'Aktif'],['val'=>'selesai','label'=>'Selesai'],['val'=>'nonaktif','label'=>'Nonaktif']] as $sf)
                    <button type="button" @click="filterStatus = '{{ $sf['val'] }}'; open = false" class="w-full text-left px-4 py-2 text-xs font-medium transition-colors flex items-center justify-between" :class="filterStatus === '{{ $sf['val'] }}' ? 'bg-purple-50 text-purple-700' : 'text-slate-600 hover:bg-slate-50'">
                        <span>{{ $sf['label'] }}</span>
                        <svg x-show="filterStatus === '{{ $sf['val'] }}'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Role Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.away="open = false" type="button" class="flex items-center justify-between gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-xl shadow-xs text-xs font-semibold text-slate-700 hover:bg-slate-50 transition min-w-[150px]">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-400">Role:</span>
                        <span x-text="filterRole === 'all' ? 'Semua Role' : filterRole.toUpperCase()"></span>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute left-0 mt-2 w-full min-w-[150px] bg-white rounded-xl shadow-xl border border-slate-100 py-1.5 z-20" style="display: none;">
                    @foreach([['val'=>'all','label'=>'Semua'],['val'=>'spv','label'=>'SPV'],['val'=>'sales','label'=>'Sales'],['val'=>'cs','label'=>'CS']] as $rf)
                    <button type="button" @click="filterRole = '{{ $rf['val'] }}'; open = false" class="w-full text-left px-4 py-2 text-xs font-medium transition-colors flex items-center justify-between" :class="filterRole === '{{ $rf['val'] }}' ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50'">
                        <span>{{ $rf['label'] }}</span>
                        <svg x-show="filterRole === '{{ $rf['val'] }}'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Target Cards -->
        <div class="space-y-4">
            <template x-for="t in filtered" :key="t.id">
                <div class="crm-card bg-white p-5 space-y-4"
                    :class="(t.kekurangan_kontak > 0 || t.kekurangan_followup > 0) && t.status === 'Aktif' ? 'border-red-200' : ''">

                    <!-- Header Card -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-600 to-blue-600 text-white font-bold flex items-center justify-center text-sm shrink-0" x-text="t.avatar"></div>
                            <div>
                                <div class="font-extrabold text-slate-900 text-sm" x-text="t.sales"></div>
                                <div class="text-[11px] text-slate-400" x-text="t.periode_type + ' — ' + t.tanggal_mulai + ' s/d ' + t.tanggal_selesai"></div>
                            </div>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 border border-emerald-200': t.status === 'Aktif',
                                    'bg-slate-100 text-slate-600 border border-slate-200': t.status === 'Selesai',
                                    'bg-red-50 text-red-600 border border-red-200': t.status === 'Nonaktif'
                                }"
                                x-text="t.status"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="selectedTarget = t; modalEdit = true" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer">Edit</button>
                            <form :action="'/admin/target/' + t.id" method="POST" class="inline" @submit="if(!confirm('Hapus target ini?')) $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-50 hover:bg-red-100 text-red-600 transition cursor-pointer">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Metric Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">

                        <!-- Maba Lunas (Closing) -->
                        <div class="p-3.5 rounded-xl border bg-emerald-50/40 border-emerald-200">
                            <p class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider mb-2">Maba Lunas (Closing)</p>
                            <div class="flex items-end justify-between">
                                <div>
                                    <div class="text-2xl font-extrabold text-slate-900" x-text="(t.realisasi_lunas || 0) + ' / ' + (t.target_lunas || 0)"></div>
                                    <div class="text-[11px] mt-0.5" x-text="(t.kekurangan_lunas > 0 ? 'Kurang: ' + t.kekurangan_lunas : 'Tercapai ✓')" :class="t.kekurangan_lunas > 0 ? 'text-red-600 font-bold' : 'text-emerald-700 font-semibold'"></div>
                                </div>
                            </div>
                            <div class="mt-2 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-full rounded-full transition-all bg-emerald-500"
                                    :style="'width:' + (t.target_lunas > 0 ? Math.min(100, Math.round((t.realisasi_lunas||0)/t.target_lunas*100)) : 100) + '%'"></div>
                            </div>
                        </div>

                        <!-- Kontak Baru -->
                        <div class="p-3.5 rounded-xl border"
                            :class="t.kekurangan_kontak > 0 ? 'bg-red-50/50 border-red-200' : 'bg-slate-50/50 border-slate-200'">
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Kontak Baru</p>
                            <div class="flex items-end justify-between">
                                <div>
                                    <div class="text-2xl font-extrabold text-slate-900" x-text="t.realisasi_kontak + ' / ' + t.target_kontak"></div>
                                    <div class="text-[11px] mt-0.5" x-text="t.kekurangan_kontak > 0 ? 'Kurang: ' + t.kekurangan_kontak : 'Tercapai ✓'" :class="t.kekurangan_kontak > 0 ? 'text-red-600 font-bold' : 'text-emerald-600 font-semibold'"></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[11px] text-slate-400">Akumulasi</div>
                                    <div class="font-bold text-slate-700" x-text="t.akum_kontak > 0 ? '+' + t.akum_kontak : '0'" :class="t.akum_kontak > 0 ? 'text-red-600' : 'text-slate-500'"></div>
                                </div>
                            </div>
                            <!-- Progress Bar -->
                            <div class="mt-2 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                    :class="getColorClass(t.realisasi_kontak, t.target_kontak)"
                                    :style="'width:' + (t.target_kontak > 0 ? Math.min(100, Math.round(t.realisasi_kontak/t.target_kontak*100)) : 100) + '%'"></div>
                            </div>
                        </div>

                        <!-- Menghubungi (CS Only) -->
                        <template x-if="t.role === 'CS' || t.target_menghubungi > 0">
                            <div class="p-3.5 rounded-xl border"
                                :class="t.kekurangan_menghubungi > 0 ? 'bg-red-50/50 border-red-200' : 'bg-slate-50/50 border-slate-200'">
                                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Menghubungi</p>
                                <div class="flex items-end justify-between">
                                    <div>
                                        <div class="text-2xl font-extrabold text-slate-900" x-text="t.realisasi_menghubungi + ' / ' + t.target_menghubungi"></div>
                                        <div class="text-[11px] mt-0.5" x-text="t.kekurangan_menghubungi > 0 ? 'Kurang: ' + t.kekurangan_menghubungi : 'Tercapai ✓'" :class="t.kekurangan_menghubungi > 0 ? 'text-red-600 font-bold' : 'text-emerald-600 font-semibold'"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[11px] text-slate-400">Akumulasi</div>
                                        <div class="font-bold" x-text="t.akum_menghubungi > 0 ? '+' + t.akum_menghubungi : '0'" :class="t.akum_menghubungi > 0 ? 'text-red-600' : 'text-slate-500'"></div>
                                    </div>
                                </div>
                                <div class="mt-2 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                    <div class="h-full rounded-full transition-all"
                                        :class="getColorClass(t.realisasi_menghubungi, t.target_menghubungi)"
                                        :style="'width:' + (t.target_menghubungi > 0 ? Math.min(100, Math.round(t.realisasi_menghubungi/t.target_menghubungi*100)) : 100) + '%'"></div>
                                </div>
                            </div>
                        </template>

                        <!-- Follow Up -->
                        <div class="p-3.5 rounded-xl border"
                            :class="t.kekurangan_followup > 0 ? 'bg-red-50/50 border-red-200' : 'bg-slate-50/50 border-slate-200'">
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Follow Up</p>
                            <div class="flex items-end justify-between">
                                <div>
                                    <div class="text-2xl font-extrabold text-slate-900" x-text="t.realisasi_followup + ' / ' + t.target_followup"></div>
                                    <div class="text-[11px] mt-0.5" x-text="t.kekurangan_followup > 0 ? 'Kurang: ' + t.kekurangan_followup : 'Tercapai ✓'" :class="t.kekurangan_followup > 0 ? 'text-red-600 font-bold' : 'text-emerald-600 font-semibold'"></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[11px] text-slate-400">Akumulasi</div>
                                    <div class="font-bold" x-text="t.akum_followup > 0 ? '+' + t.akum_followup : '0'" :class="t.akum_followup > 0 ? 'text-red-600' : 'text-slate-500'"></div>
                                </div>
                            </div>
                            <div class="mt-2 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                    :class="getColorClass(t.realisasi_followup, t.target_followup)"
                                    :style="'width:' + (t.target_followup > 0 ? Math.min(100, Math.round(t.realisasi_followup/t.target_followup*100)) : 100) + '%'"></div>
                            </div>
                        </div>

                        <!-- Target Besok (Carry-Over) -->
                        <div class="p-3.5 rounded-xl border"
                            :class="(t.kekurangan_kontak > 0 || t.kekurangan_followup > 0) ? 'bg-amber-50/50 border-amber-200' : 'bg-emerald-50/50 border-emerald-200'">
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Target Besok</p>
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Kontak Baru</span>
                                    <span class="font-extrabold text-slate-900" x-text="(t.target_besok_kontak || 0) + ' kontak'"></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Follow Up</span>
                                    <span class="font-extrabold text-slate-900" x-text="(t.target_besok_followup || 0) + ' FU'"></span>
                                </div>
                                <div class="pt-2 border-t border-slate-200 text-[11px]"
                                    :class="(t.kekurangan_kontak > 0 || t.kekurangan_followup > 0) ? 'text-amber-700' : 'text-emerald-600'">
                                    <span x-text="(t.kekurangan_kontak > 0 || t.kekurangan_followup > 0) ? '⚠ Termasuk akumulasi kekurangan' : '✓ Target normal, tidak ada carry-over'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- MODAL TAMBAH TARGET -->
        <div x-show="modalAdd" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalAdd = false"></div>
                <div class="inline-block w-full max-w-lg p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Tambah Target Baru</h3>
                        <button @click="modalAdd = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form action="{{ route('admin.target.store') }}" method="POST" class="mt-4 space-y-3 text-xs">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Pilih Penerima Target (SPV / Sales / CS) *</label>
                                <select name="sales_id" @change="selectedRoleAdd = $event.target.options[$event.target.selectedIndex].dataset.role" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                    <option value="" data-role="">Pilih Penerima Target</option>
                                    @foreach($salesList as $s)
                                        <option value="{{ $s->id }}" data-role="{{ $s->role }}">{{ $s->name }} ({{ $s->role }}{{ $s->wilayah ? ' - ' . $s->wilayah->nama : '' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tipe Periode *</label>
                                <select name="tipe_periode" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                    <option value="Harian">Harian</option><option value="Mingguan">Mingguan</option><option value="Bulanan" selected>Bulanan</option>
                                </select>
                            </div>
                        </div>

                        <!-- Target Maba Lunas & Formulir -->
                        <div class="grid grid-cols-2 gap-3 bg-purple-50/60 p-3 rounded-xl border border-purple-100">
                            <div>
                                <label class="block font-bold text-purple-950 mb-1">Target Maba Lunas (Closing)</label>
                                <input type="number" name="target_lunas" min="0" value="35" placeholder="Contoh: 35" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-purple-200 bg-white outline-none focus:ring-2 focus:ring-purple-300 font-bold">
                                <span class="text-[10px] text-purple-700 mt-0.5 block">Wajib jika alokasi ke SPV</span>
                            </div>
                            <div>
                                <label class="block font-bold text-purple-950 mb-1">Target Beli Formulir</label>
                                <input type="number" name="target_formulir" min="0" value="60" placeholder="Contoh: 60" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-purple-200 bg-white outline-none focus:ring-2 focus:ring-purple-300 font-bold">
                                <span class="text-[10px] text-purple-700 mt-0.5 block">Tahap pipeline Formulir</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal Mulai *</label>
                                <input type="date" name="tanggal_mulai" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal Selesai *</label>
                                <input type="date" name="tanggal_selesai" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Target Kontak</label>
                                <input type="number" name="target_kontak" min="0" required placeholder="10" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div x-show="selectedRoleAdd === 'CS'">
                                <label class="block font-semibold text-slate-700 mb-1">Target Menghubungi</label>
                                <input type="number" name="target_menghubungi" min="0" required placeholder="0" value="0" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Target Follow Up</label>
                                <input type="number" name="target_followup" min="0" required placeholder="20" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div x-show="selectedRoleAdd !== 'CS'">
                                <label class="block font-semibold text-slate-700 mb-1">Target Kunjungan</label>
                                <input type="number" name="target_kunjungan" min="0" required placeholder="2" value="0" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Status Target</label>
                            <select name="status" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                <option value="Aktif">Aktif</option><option value="Selesai">Selesai</option><option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="modalAdd = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white">Simpan Target</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT TARGET -->
        <div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="modalEdit = false"></div>
                <div class="inline-block w-full max-w-lg p-6 my-8 bg-white shadow-2xl rounded-2xl relative z-10">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">Edit Target</h3>
                        <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 cursor-pointer"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <form :action="selectedTarget ? '/admin/target/' + selectedTarget.id : ''" method="POST" class="mt-4 space-y-3 text-xs">
                        @csrf
                        @method('PUT')
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700" x-text="selectedTarget ? selectedTarget.sales + ' — ' + selectedTarget.periode : ''"></div>
                        
                        <input type="hidden" name="sales_id" :value="selectedTarget ? selectedTarget.sales_id : ''">
                        <input type="hidden" name="tipe_periode" :value="selectedTarget ? selectedTarget.tipe_periode : ''">
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" required :value="selectedTarget ? new Date(selectedTarget.raw_tanggal_mulai).toISOString().split('T')[0] : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" required :value="selectedTarget ? new Date(selectedTarget.raw_tanggal_selesai).toISOString().split('T')[0] : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Target Kontak</label>
                                <input type="number" name="target_kontak" required :value="selectedTarget ? selectedTarget.target_kontak : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div x-show="selectedTarget && selectedTarget.role === 'CS'">
                                <label class="block font-semibold text-slate-700 mb-1">Target Menghubungi</label>
                                <input type="number" name="target_menghubungi" required :value="selectedTarget ? selectedTarget.target_menghubungi : '0'" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Target Follow Up</label>
                                <input type="number" name="target_followup" required :value="selectedTarget ? selectedTarget.target_followup : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                            <div x-show="selectedTarget && selectedTarget.role !== 'CS'">
                                <label class="block font-semibold text-slate-700 mb-1">Target Kunjungan</label>
                                <input type="number" name="target_kunjungan" required :value="selectedTarget ? selectedTarget.target_kunjungan : '0'" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 outline-none focus:ring-2 focus:ring-purple-200">
                            </div>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Status</label>
                            <select name="status" required :value="selectedTarget ? selectedTarget.status : ''" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white outline-none focus:ring-2 focus:ring-purple-200">
                                <option value="Aktif">Aktif</option><option value="Selesai">Selesai</option><option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" @click="modalEdit = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-purple-600 hover:bg-purple-700 text-white">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>
