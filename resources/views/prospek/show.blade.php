@php
    $pageTitle = 'Detail Prospek';
    $pageSubtitle = $prospect['name'];
@endphp

<x-app-layout :title="'Detail Prospek - ' . $prospect['name']">

    <div class="space-y-6" x-data="{
        prospect: {{ json_encode($prospect) }},
        currentStatus: '{{ $prospect['status'] }}'
    }">

        <!-- Back Button & Breadcrumbs -->
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="{{ route((strtolower(auth()->user()->role) === 'sales' ? 'sales.' : '') . 'prospek.index') }}" class="hover:text-blue-600 flex items-center gap-1">
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

                    @can('transaction', $prospekModel)
                    <button 
                        type="button" 
                        @click="selectedProspect = prospect; modalTransaksi = true"
                        class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100 text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Input Transaksi</span>
                    </button>
                    @endcan

                    @can('takeover', $prospekModel)
                    <form action="{{ route(strtolower(auth()->user()->role) === 'sales' ? 'sales.prospek.takeover' : 'prospek.takeover', $prospect['id']) }}" method="POST" class="inline-block">
                        @csrf
                        <button 
                            type="submit" 
                            onclick="return confirm('{{ strtolower(auth()->user()->role) === 'cs' ? 'Yakin ingin melakukan pro-active takeover prospek ini?' : 'Yakin ingin menyerahkan prospek ini ke CS?' }}')"
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
                            <p class="font-medium text-slate-800 mt-0.5">{{ $prospect['source'] }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Potensi Calon Mahasiswa</span>
                            <p class="font-medium text-slate-800 mt-0.5 leading-relaxed">{{ $prospect['potential'] }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Training AI & Robotics</span>
                            <p class="font-semibold text-blue-600 mt-0.5">{{ $prospect['ai_training'] }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Catatan Internal</span>
                            <p class="text-slate-600 mt-0.5 bg-slate-50 p-2.5 rounded-lg border border-slate-100">{{ $prospect['notes'] }}</p>
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
                        + Tambah Catatan
                    </button>
                </div>

                <!-- Timeline Items -->
                <div class="space-y-6 relative before:absolute before:inset-0 before:left-3 before:w-0.5 before:bg-slate-200">
                    @foreach($prospect['timeline'] as $item)
                        <div class="relative flex items-start gap-4">
                            <!-- Timeline Dot / Avatar -->
                            <div class="w-6 h-6 rounded-full bg-white border-2 border-blue-600 text-blue-600 font-bold flex items-center justify-center text-[10px] shrink-0 z-10">
                                @if($item['role'] === 'Sales') S @elseif($item['role'] === 'CS') C @else ✓ @endif
                            </div>

                            <div class="flex-1 bg-slate-50/70 p-4 rounded-xl border border-slate-200/80 space-y-1">
                                <div class="flex flex-wrap items-center justify-between gap-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-900">{{ $item['title'] }}</span>
                                        <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-200 text-slate-700 font-medium">{{ $item['role'] }}</span>
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

                <form :action="'{{ route('prospek.transaksi', '') }}/' + selectedProspect.id" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Transaksi *</label>
                        <select name="jenis" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                            <option value="">-- Pilih Jenis --</option>
                            <option value="Beli Formulir">Beli Formulir</option>
                            <option value="Pembayaran Termin 1">Pembayaran Termin 1 (Closing)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Transaksi *</label>
                        <input type="date" name="tanggal" required value="{{ date('Y-m-d') }}" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal (Rp) *</label>
                        <input type="number" name="nominal" required min="0" placeholder="Contoh: 1500000" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                    </div>

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

</x-app-layout>
