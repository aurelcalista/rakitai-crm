@php
    $pageTitle = 'Detail Prospek';
    $pageSubtitle = $prospect['name'];
@endphp

<x-app-layout :title="'Detail Prospek - ' . $prospect['name']">

    <div class="space-y-6" x-data="{
        prospect: {{ json_encode($prospect) }},
        currentStatus: '{{ $prospect['status'] }}',
        modalRealokasi: false,
        modalEditProspek: false,
        modalTransaksi: false,
        selectedJenis: '',
        metodePembayaran: '',
        bankAccounts: {{ json_encode(\App\Models\BankAccount::where('bank_name', 'Mandiri')->where('is_active', true)->get(['account_number','account_name'])) }}
    }">

        <!-- Back Button & Breadcrumbs -->
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="{{ route((strtolower(auth()->user()->role ?? '') === 'spv' ? 'spv.' : '') . 'prospek.index') }}" class="hover:text-blue-600 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                Kembali ke Daftar Prospek
            </a>
            <span>/</span>
            <span class="text-slate-900">{{ $prospect['name'] }}</span>
        </div>

        <!-- Detail Header Card -->
        <div class="crm-card bg-white p-5 sm:p-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">{{ $prospect['name'] }}</h2>
                        <x-status-badge :status="$prospect['status']" />
                        <span class="px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-semibold">{{ $prospect['type'] }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-500 font-medium">
                        <span>PIC: <strong class="text-slate-800">{{ $prospect['pic'] }}</strong></span>
                        <span>&bull;</span>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $prospect['whatsapp']) }}" target="_blank" class="text-emerald-600 hover:text-emerald-700 flex items-center gap-1 font-semibold">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            <span>WhatsApp ({{ $prospect['whatsapp'] }})</span>
                        </a>
                        <span>&bull;</span>
                        <span>Created: <strong class="text-slate-800">{{ $prospect['created_at'] }}</strong></span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    @php 
                        $prospekModel = \App\Models\Prospek::find($prospect['id']); 
                    @endphp

                    @can('followUp', $prospekModel)
                    <button 
                        type="button" 
                        @click="selectedProspect = prospect; modalFollowUp = true"
                        class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        <span>Follow Up</span>
                    </button>
                    @endcan

                    @can('updateStatus', $prospekModel)
                    <button 
                        type="button" 
                        @click="selectedProspect = prospect; modalUpdateStatus = true"
                        class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        <span>Update Status</span>
                    </button>
                    @endcan

                    @can('update', $prospekModel)
                    <button 
                        type="button" 
                        @click="modalEditProspek = true"
                        class="px-3.5 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100 text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Edit Data Prospek</span>
                    </button>
                    @endcan

                    @can('transaction', $prospekModel ?? null)
                    <button 
                        type="button" 
                        @click="selectedProspect = prospect; modalTransaksi = true"
                        class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100 text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Input Transaksi</span>
                    </button>
                    @endcan

                    @can('reallocate', $prospekModel ?? null)
                    <button 
                        type="button" 
                        @click="modalRealokasi = true"
                        class="px-3.5 py-2 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 hover:bg-purple-100 text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        <span>Re-alokasi Sales</span>
                    </button>
                    @endcan

                    @can('takeover', $prospekModel ?? null)
                    <form action="{{ route(strtolower(auth()->user()->role) === 'sales' ? 'sales.prospek.takeover' : 'prospek.takeover', $prospect['id']) }}" method="POST" class="inline-block" data-confirm="{{ strtolower(auth()->user()->role) === 'cs' ? 'Yakin ingin melakukan pro-active takeover prospek ini?' : 'Yakin ingin menyerahkan prospek ini ke CS? Penanganan selanjutnya akan dialihkan ke tim CS.' }}">
                        @csrf
                        <button 
                            type="submit"
                            class="px-3.5 py-2 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-100 text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                            <span>{{ strtolower(auth()->user()->role) === 'cs' ? 'Takeover (Ambil Alih)' : 'Serahkan ke CS' }}</span>
                        </button>
                    </form>
                    @endcan
                </div>
            </div>

            <!-- PIPELINE HORIZONTAL STEPPER -->
            <div class="pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Tahapan Pipeline Saat Ini</h3>
                    @if($prospect['status'] === 'Lost')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            Status Khusus: Lost (Arsip Prospek)
                        </span>
                    @endif
                </div>

                <!-- Desktop Stepper -->
                <div class="hidden md:flex items-center justify-between relative">
                    <div class="absolute top-1/2 left-4 right-4 h-1 bg-slate-100 -translate-y-1/2 z-0"></div>
                    
                    @foreach($allStages as $index => $stage)
                        @php
                            $isReached = $prospect['stage_number'] >= $stage['number'];
                            $isCurrent = $prospect['stage_number'] === $stage['number'];
                        @endphp
                        <div class="relative z-10 flex flex-col items-center group">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold transition {{ $isCurrent ? 'bg-blue-600 text-white ring-4 ring-blue-100 shadow-md' : ($isReached ? 'bg-blue-100 text-blue-700 border border-blue-300' : 'bg-white text-slate-400 border border-slate-200') }}">
                                @if($isReached && !$isCurrent)
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                @else
                                    {{ $stage['number'] }}
                                @endif
                            </div>
                            <span class="text-[11px] mt-2 font-semibold text-center {{ $isCurrent ? 'text-blue-700 font-bold' : ($isReached ? 'text-slate-800' : 'text-slate-400') }}">
                                {{ $stage['name'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <!-- Mobile Stepper (Scrollable) -->
                <div class="md:hidden overflow-x-auto pb-2 flex items-center gap-3">
                    @foreach($allStages as $stage)
                        @php
                            $isReached = $prospect['stage_number'] >= $stage['number'];
                            $isCurrent = $prospect['stage_number'] === $stage['number'];
                        @endphp
                        <div class="shrink-0 flex items-center gap-2 p-2.5 rounded-xl border {{ $isCurrent ? 'bg-blue-50 border-blue-400 text-blue-700 font-bold' : ($isReached ? 'bg-slate-50 border-slate-200 text-slate-800' : 'opacity-50 border-dashed border-slate-200') }}">
                            <span class="w-5 h-5 rounded-full {{ $isCurrent ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700' }} flex items-center justify-center text-[10px] font-bold">
                                {{ $stage['number'] }}
                            </span>
                            <span class="text-xs whitespace-nowrap">{{ $stage['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- 2-COLUMN MAIN CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT COLUMN: DETAIL & TAKEOVER -->
            <div class="space-y-6">


                <!-- INFORMASI LENGKAP PROSPEK -->
                <div class="crm-card bg-white p-5 space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">Informasi Prospek</h3>
                    
                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Sumber Prospek</span>
                            <p class="font-medium text-slate-800 mt-0.5">{{ $prospect['source'] ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Potensi Calon Mahasiswa</span>
                            <p class="font-medium text-slate-800 mt-0.5 leading-relaxed">{{ $prospect['potential'] ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Training AI & Robotics</span>
                            <p class="font-semibold text-blue-600 mt-0.5">{{ $prospect['ai_training'] ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Catatan Internal</span>
                            <p class="text-slate-600 mt-0.5 bg-slate-50 p-2.5 rounded-lg border border-slate-100">{{ $prospect['notes'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: ACTIVITY TIMELINE -->
            <div class="crm-card bg-white p-6 lg:col-span-2 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Riwayat Aktivitas & Timeline</h3>
                        <p class="text-xs text-slate-500">Log lengkap interaksi, perubahan status, dan catatan tim</p>
                    </div>
                    <button 
                        @click="selectedProspect = prospect; modalFollowUp = true"
                        class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-xs transition"
                    >
                        Tambah Catatan
                    </button>
                </div>

                <!-- Timeline Items -->
                <div class="space-y-6 relative before:absolute before:inset-0 before:left-3 before:w-0.5 before:bg-slate-200">
                    @foreach($prospect['timeline'] as $item)
                        <div class="relative flex items-start gap-4">
                            <!-- Timeline Dot / Avatar -->
                            <div class="w-6 h-6 rounded-full bg-white border-2 {{ str_contains($item['title'], 'Re-alokasi') ? 'border-purple-600 text-purple-600' : 'border-blue-600 text-blue-600' }} font-bold flex items-center justify-center text-[10px] shrink-0 z-10">
                                @if(str_contains($item['title'], 'Re-alokasi')) ⇄ @elseif($item['role'] === 'Sales') S @elseif($item['role'] === 'CS') C @else ✓ @endif
                            </div>

                            <div class="flex-1 {{ str_contains($item['title'], 'Re-alokasi') ? 'bg-purple-50/60 border-purple-200' : 'bg-slate-50/70 border-slate-200/80' }} p-4 rounded-xl border space-y-1">
                                <div class="flex flex-wrap items-center justify-between gap-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-900">{{ $item['title'] }}</span>
                                        <span class="text-[10px] px-1.5 py-0.2 rounded {{ str_contains($item['title'], 'Re-alokasi') ? 'bg-purple-100 text-purple-700' : 'bg-slate-200 text-slate-700' }} font-medium">{{ $item['role'] }}</span>
                                    </div>
                                    <span class="text-[11px] text-slate-400">{{ $item['time'] }}</span>
                                </div>
                                
                                <p class="text-xs text-slate-600 pt-1 leading-relaxed">{{ $item['notes'] }}</p>
                                <div class="text-[10px] text-slate-400 pt-1 font-medium">Oleh: {{ $item['user'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- MODAL RE-ALOKASI PROSPEK (SPV / MANAGEMENT) -->
        <div 
            x-show="modalRealokasi" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto"
            role="dialog" 
            aria-modal="true"
        >
            <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div x-show="modalRealokasi" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalRealokasi = false"></div>

                <div 
                    x-show="modalRealokasi" 
                    class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10"
                >
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Re-alokasi Prospek ke Sales Lain</h3>
                            <p class="text-xs text-slate-500">Pindahkan penugasan prospek ke personil sales lain dalam tim Anda.</p>
                        </div>
                        <button @click="modalRealokasi = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form action="{{ route('prospek.realokasi', $prospect['id']) }}" method="POST" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Sales Saat Ini</label>
                            <input type="text" readonly value="{{ $prospect['takeover_sales'] ?? 'Belum Ditugaskan' }}" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-600 font-semibold cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Sales Baru (Penerima Tugas) *</label>
                            <select name="sales_id" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition bg-white font-medium">
                                <option value="">-- Pilih Sales Anggota Tim --</option>
                                @foreach($salesTeam as $st)
                                    <option value="{{ $st->id }}" {{ (isset($prospekModel) && $prospekModel->sales_id == $st->id) ? 'disabled' : '' }}>
                                        {{ $st->name }} ({{ $st->email }}) {{ (isset($prospekModel) && $prospekModel->sales_id == $st->id) ? '- (Saat ini)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan / Catatan Re-alokasi</label>
                            <textarea name="alasan" rows="3" placeholder="Contoh: Pembagian beban wilayah / percepatan follow-up..." class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-600 transition"></textarea>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="modalRealokasi = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition cursor-pointer">
                                Simpan Re-alokasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- MODAL: INPUT TRANSAKSI -->
    <div 
        x-show="modalTransaksi" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" 
        aria-modal="true"
    >
        <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalTransaksi" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalTransaksi = false"></div>

            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Input Transaksi Manual</h3>
                        <p class="text-xs text-slate-500" x-text="selectedProspect.name"></p>
                    </div>
                    <button @click="modalTransaksi = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form :action="'{{ url('prospek') }}/' + selectedProspect.id + '/transaksi'" method="POST" class="mt-4 space-y-4">
                    @csrf

                    {{-- Jenis Transaksi --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Transaksi *</label>
                        <select name="jenis" x-model="selectedJenis" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                            <option value="">-- Pilih Jenis --</option>
                            <option value="Beli Formulir">Beli Formulir</option>
                            <option value="Pembayaran Termin 1">Pembayaran Termin 1 (Closing)</option>
                        </select>
                    </div>

                    {{-- Tanggal --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Transaksi *</label>
                        <input type="date" name="tanggal" required value="{{ date('Y-m-d') }}" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>

                    {{-- Nominal --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal (Rp) *</label>
                        <input type="number" name="nominal" required min="0" placeholder="Contoh: 1500000" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>

                    {{-- Metode Pembayaran (hanya muncul jika Pembayaran Termin 1) --}}
                    <div x-show="selectedJenis === 'Pembayaran Termin 1'" x-transition>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Metode Pembayaran *</label>
                        <select name="metode_pembayaran" x-model="metodePembayaran"
                            :required="selectedJenis === 'Pembayaran Termin 1'"
                            class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition bg-white">
                            <option value="">-- Pilih Metode Pembayaran --</option>
                            <option value="virtual_account">🏦 Virtual Account</option>
                            <option value="gopay">💚 GoPay</option>
                            <option value="dana">🔵 DANA</option>
                            <option value="bank_transfer">🏛️ Transfer Bank Mandiri</option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">⏳ Pembayaran Termin 1 perlu diverifikasi oleh CS sebelum dinyatakan Closing.</p>
                    </div>

                    {{-- Info Rekening Mandiri (otomatis muncul) --}}
                    <div x-show="selectedJenis === 'Pembayaran Termin 1' && metodePembayaran === 'bank_transfer'" x-transition>
                        <template x-if="bankAccounts.length > 0">
                            <div class="p-3 rounded-xl border border-amber-200 bg-amber-50 space-y-1.5">
                                <p class="text-[10px] font-bold text-amber-800 uppercase tracking-wider">🏛️ Info Rekening Tujuan Transfer</p>
                                <template x-for="rek in bankAccounts" :key="rek.account_number">
                                    <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                                        <div>
                                            <span class="text-amber-600 text-[10px] font-semibold block">Bank</span>
                                            <span class="font-bold text-slate-800">Mandiri</span>
                                        </div>
                                        <div>
                                            <span class="text-amber-600 text-[10px] font-semibold block">No. Rekening</span>
                                            <span class="font-bold text-slate-800 font-mono" x-text="rek.account_number"></span>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-amber-600 text-[10px] font-semibold block">Atas Nama</span>
                                            <span class="font-bold text-slate-800" x-text="rek.account_name"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="bankAccounts.length === 0">
                            <div class="p-3 rounded-xl border border-rose-200 bg-rose-50">
                                <p class="text-xs text-rose-700 font-semibold">⚠️ Rekening Mandiri belum dikonfigurasi. Hubungi Admin.</p>
                            </div>
                        </template>
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                        <textarea name="notes" rows="2" placeholder="Catatan opsional..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition"></textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="modalTransaksi = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs">Simpan Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT DATA PROSPEK -->
    <div 
        x-show="modalEditProspek" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" 
        aria-modal="true"
    >
        <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalEditProspek" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="modalEditProspek = false"></div>

            <div 
                x-show="modalEditProspek" 
                class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl relative z-10 max-h-[90vh] overflow-y-auto"
            >
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Edit Data Prospek</h3>
                        <p class="text-xs text-slate-500">Perbarui data prospek, informasi sekolah/perusahaan, dan kontak PIC.</p>
                    </div>
                    <button @click="modalEditProspek = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form action="{{ route((strtolower(auth()->user()->role ?? '') === 'sales' ? 'sales.' : '') . 'prospek.update', $prospect['id']) }}" method="POST" class="mt-5 space-y-6" x-data="{ prospekType: '{{ $prospect['type'] }}', modeManualSekolah: {{ empty($prospect['sekolah_id']) && ($prospect['type'] ?? '') === 'Sekolah' ? 'true' : 'false' }}, modeManualCorp: {{ empty($prospect['perusahaan_id']) && ($prospect['type'] ?? '') === 'Corporate' ? 'true' : 'false' }} }">
                    @csrf
                    @method('PUT')

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-700 bg-blue-50 px-2.5 py-1 rounded-md inline-block mb-3">1. Informasi Dasar</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 h-5 flex items-center">Tipe Prospek <span class="text-rose-500 ml-0.5">*</span></label>
                                <select name="type" x-model="prospekType" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    <option value="Sekolah">Sekolah (SMA/SMK/MA)</option>
                                    <option value="Corporate">Corporate / Perusahaan</option>
                                    <option value="Individu">Individu / Siswa Langsung</option>
                                </select>
                            </div>

                            <!-- Input Sekolah (Search & Manual Mode) -->
                            <div x-show="prospekType === 'Sekolah'" x-on:switch-manual.stop="if ($event.detail.name === 'sekolah_id') { modeManualSekolah = true; $nextTick(function() { const inp = $el.querySelector('input[name=sekolah_manual]'); if(inp) { inp.value = $event.detail.search; inp.focus(); } }); }" class="space-y-1">
                                <div class="flex items-center justify-between mb-1.5 h-5">
                                    <template x-if="!modeManualSekolah">
                                        <div class="flex items-center justify-between w-full">
                                            <label class="block text-xs font-semibold text-slate-700">Nama Sekolah <span class="text-rose-500">*</span></label>
                                            <button 
                                                type="button" 
                                                @click="modeManualSekolah = true; const sel = $el.closest('.space-y-1').querySelector('input[name=sekolah_id]'); if(sel) sel.value = '';" 
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200/60 transition cursor-pointer group"
                                            >
                                                <svg class="w-3 h-3 text-blue-500 group-hover:text-blue-700 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                <span>Input Manual</span>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="modeManualSekolah">
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-1.5">
                                                <label class="block text-xs font-semibold text-slate-700">Nama Sekolah <span class="text-rose-500">*</span></label>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80">
                                                    Manual
                                                </span>
                                            </div>
                                            <button 
                                                type="button" 
                                                @click="modeManualSekolah = false;" 
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 transition cursor-pointer"
                                            >
                                                <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                <span>Cari Database</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="!modeManualSekolah">
                                    <x-searchable-select 
                                        name="sekolah_id" 
                                        :options="$sekolahsList ?? []" 
                                        :value="$prospect['sekolah_id'] ?? ''"
                                        placeholder="-- Ketik untuk mencari Sekolah... --" 
                                    />
                                </div>

                                <div x-show="modeManualSekolah" style="display: none;" class="space-y-1.5">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-blue-500">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="sekolah_manual" 
                                            value="{{ old('sekolah_manual', empty($prospect['sekolah_id']) ? (($prospect['sekolah_name'] ?? '-') !== '-' ? $prospect['sekolah_name'] : ($prospect['name'] ?? '')) : '') }}"
                                            placeholder="Ketik nama sekolah (misal: SMAN 1 Cirebon)..." 
                                            class="w-full text-xs sm:text-sm pl-10 pr-3.5 py-2.5 rounded-xl border border-blue-200 bg-blue-50/20 text-slate-800 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition shadow-2xs"
                                        >
                                    </div>
                                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50/70 border border-amber-200/50 text-[10px] text-amber-800 font-medium">
                                        <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Sekolah baru akan otomatis tersimpan ke master data.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Corporate (Search & Manual Mode) -->
                            <div x-show="prospekType === 'Corporate'" x-on:switch-manual.stop="if ($event.detail.name === 'perusahaan_id') { modeManualCorp = true; $nextTick(function() { const inp = $el.querySelector('input[name=perusahaan_manual]'); if(inp) { inp.value = $event.detail.search; inp.focus(); } }); }" style="display: none;" class="space-y-1">
                                <div class="flex items-center justify-between mb-1.5 h-5">
                                    <template x-if="!modeManualCorp">
                                        <div class="flex items-center justify-between w-full">
                                            <label class="block text-xs font-semibold text-slate-700">Nama Perusahaan <span class="text-rose-500">*</span></label>
                                            <button 
                                                type="button" 
                                                @click="modeManualCorp = true; const sel = $el.closest('.space-y-1').querySelector('input[name=perusahaan_id]'); if(sel) sel.value = '';" 
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200/60 transition cursor-pointer group"
                                            >
                                                <svg class="w-3 h-3 text-blue-500 group-hover:text-blue-700 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                <span>Input Manual</span>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="modeManualCorp">
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-1.5">
                                                <label class="block text-xs font-semibold text-slate-700">Nama Perusahaan <span class="text-rose-500">*</span></label>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80">
                                                    Manual
                                                </span>
                                            </div>
                                            <button 
                                                type="button" 
                                                @click="modeManualCorp = false;" 
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 transition cursor-pointer"
                                            >
                                                <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                <span>Cari Database</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="!modeManualCorp">
                                    <x-searchable-select 
                                        name="perusahaan_id" 
                                        :options="$perusahaansList ?? []" 
                                        :value="$prospect['perusahaan_id'] ?? ''"
                                        placeholder="-- Ketik untuk mencari Perusahaan... --" 
                                    />
                                </div>

                                <div x-show="modeManualCorp" style="display: none;" class="space-y-1.5">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-blue-500">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="perusahaan_manual" 
                                            value="{{ old('perusahaan_manual', empty($prospect['perusahaan_id']) ? (($prospect['sekolah_name'] ?? '-') !== '-' ? $prospect['sekolah_name'] : ($prospect['name'] ?? '')) : '') }}"
                                            placeholder="Ketik nama perusahaan (misal: PT Telkom Cirebon)..." 
                                            class="w-full text-xs sm:text-sm pl-10 pr-3.5 py-2.5 rounded-xl border border-blue-200 bg-blue-50/20 text-slate-800 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition shadow-2xs"
                                        >
                                    </div>
                                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50/70 border border-amber-200/50 text-[10px] text-amber-800 font-medium">
                                        <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Perusahaan baru akan otomatis tersimpan ke master data.</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Calon Mahasiswa / Prospek</label>
                                <input type="text" name="name" value="{{ $prospect['name'] }}" placeholder="Nama prospek..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Program Studi Diminati</label>
                                <select name="prodi_id" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    <option value="">-- Pilih Program Studi --</option>
                                    @foreach($prodisList ?? [] as $prd)
                                        <option value="{{ $prd->id }}" {{ ($prospect['prodi_id'] ?? '') == $prd->id ? 'selected' : '' }}>{{ $prd->nama }} ({{ $prd->jenjang }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Pipeline *</label>
                                <input type="text" readonly name="status" value="{{ $prospect['status'] }}" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-600 font-bold cursor-not-allowed">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama</label>
                                <input type="text" name="pic" required value="{{ $prospect['pic'] }}" placeholder="Contoh: Budi Santoso" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp *</label>
                                <input type="tel" name="whatsapp" required value="{{ $prospect['whatsapp'] }}" placeholder="Masukkan nomor .." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Sumber Prospek *</label>
                                <select name="source" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    <option value="">-- Pilih Sumber --</option>
                                    @foreach($sumberProspekList ?? [] as $sumber)
                                        <option value="{{ $sumber->nama }}" {{ ($prospect['source'] ?? '') == $sumber->nama ? 'selected' : '' }}>{{ $sumber->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Prospek</label>
                                <select name="category" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($kategoriProspekList ?? [] as $kategori)
                                        <option value="{{ $kategori->nama }}" {{ ($prospect['category'] ?? '') == $kategori->nama ? 'selected' : '' }}>{{ $kategori->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md inline-block mb-3">2. Detail & Potensi</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                                <textarea name="notes" rows="2" placeholder="Catatan hasil perbincangan..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">{{ $prospect['notes'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" @click="modalEditProspek = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition cursor-pointer">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-app-layout>
