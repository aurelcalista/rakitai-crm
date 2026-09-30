@php
    $pageTitle = 'Detail Prospek (SPV)';
    $pageSubtitle = $prospect['name'];
@endphp

<x-app-layout :title="'Detail Prospek - ' . $prospect['name']">

    <div class="space-y-6" x-data="{
        modalReassign: false,
        modalStatus: false,
        modalClosing: false,
        selectedStatus: '{{ $prospect['status'] }}'
    }">

        <!-- Back Button & Breadcrumbs -->
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('spv.prospek.index') }}" class="hover:text-blue-600 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                Kembali ke Daftar Prospek Tim
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
                        @if(!empty($prospect['sekolah_nama']))
                            <span class="px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-semibold">{{ $prospect['sekolah_nama'] }}</span>
                        @endif
                        @if(!empty($prospect['prodi_nama']) && $prospect['prodi_nama'] !== '-')
                            <span class="px-2.5 py-0.5 rounded-md bg-blue-50 text-blue-700 text-xs font-bold border border-blue-200">
                                Prodi: {{ $prospect['prodi_nama'] }} ({{ $prospect['kelas'] ?? 'Reguler' }})
                            </span>
                        @endif
                        <span class="px-2.5 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold">Handler: {{ $prospect['takeover_sales'] ?? ($prospect['takeover_cs'] ?? 'Belum Ditugaskan') }}</span>
                        <span class="px-2.5 py-0.5 rounded-md bg-blue-50 text-blue-700 text-xs font-bold border border-blue-200">{{ $prospect['follow_up_count'] ?? 0 }}x Follow Up</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-500 font-medium">
                        <span>PIC: <strong class="text-slate-800">{{ $prospect['pic'] }}</strong></span>
                        <span>&bull;</span>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $prospect['whatsapp']) }}" target="_blank" class="text-emerald-600 hover:text-emerald-700 flex items-center gap-1 font-semibold">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            <span>WhatsApp ({{ $prospect['whatsapp'] }})</span>
                        </a>
                        <span>&bull;</span>
                        <span>Dibuat: <strong class="text-slate-800">{{ $prospect['created_at'] }}</strong></span>
                    </div>
                </div>

                <!-- SPV Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Bantu Closing Button (SPV closing power with sales credit intact) -->
                    @if($prospect['status'] !== 'LUNAS')
                    <button 
                        type="button" 
                        @click="modalClosing = true"
                        class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>Bantu Closing (Maba Lunas)</span>
                    </button>
                    @endif

                    <!-- Reassign Handler Button -->
                    <button 
                        type="button" 
                        @click="modalReassign = true"
                        class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        <span>Re-assign Handler</span>
                    </button>

                    <!-- Update Status Button -->
                    <button 
                        type="button" 
                        @click="modalStatus = true"
                        class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        <span>Ubah Status</span>
                    </button>

                    <!-- Delete Prospect (SPV power) -->
                    <form action="{{ route('spv.prospek.destroy', $prospect['id']) }}" method="POST" class="inline-block" data-confirm="Apakah Anda yakin ingin menghapus prospek ini secara permanen? Seluruh histori timeline dan follow up terkait juga akan dihapus.">
                        @csrf
                        @method('DELETE')
                        <button 
                            type="submit" 
                            class="px-3.5 py-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            <span>Hapus</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Pipeline Horizontal Stepper -->
            <div class="pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Tahapan Pipeline Prospek</h3>
                </div>

                <div class="hidden md:flex items-center justify-between relative">
                    <div class="absolute top-1/2 left-4 right-4 h-1 bg-slate-100 -translate-y-1/2 z-0"></div>
                    
                    @foreach($allStages as $stage)
                        @php
                            $isReached = ($prospect['stage_number'] ?? 1) >= $stage['number'];
                            $isCurrent = ($prospect['stage_number'] ?? 1) === $stage['number'];
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
            </div>
        </div>

        <!-- 2-COLUMN MAIN CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT COLUMN: DETAIL PROSPEK -->
            <div class="space-y-6">
                <div class="crm-card bg-white p-5 space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100">Informasi Prospek</h3>
                    
                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Program Studi Diminati</span>
                            <p class="font-bold text-blue-900 mt-0.5 text-sm">{{ $prospect['prodi_nama'] ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Pilihan Kelas</span>
                            <p class="font-semibold text-slate-800 mt-0.5 inline-block px-2 py-0.5 bg-slate-100 rounded text-xs">{{ $prospect['kelas'] ?? 'Reguler' }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Pemilik Lead & Handler</span>
                            <p class="font-bold text-slate-900 mt-0.5">{{ $prospect['takeover_sales'] ? $prospect['takeover_sales'] . ' (Sales)' : ($prospect['takeover_cs'] ? $prospect['takeover_cs'] . ' (CS)' : 'Belum Ditugaskan') }}</p>
                            <span class="text-[10px] text-slate-400">Owner Lead: {{ $prospect['owner'] ?? '-' }}</span>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Wilayah / Teritori</span>
                            <p class="font-medium text-slate-800 mt-0.5">{{ $prospect['wilayah_nama'] ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Sumber Prospek</span>
                            <p class="font-medium text-slate-800 mt-0.5">{{ $prospect['source'] ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Potensi Calon Mahasiswa</span>
                            <p class="font-medium text-slate-800 mt-0.5 leading-relaxed">{{ $prospect['potential'] ?? '-' }}</p>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Catatan Internal</span>
                            <p class="text-slate-600 mt-0.5 bg-slate-50 p-2.5 rounded-lg border border-slate-100">{{ $prospect['notes'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: TRANSACTIONS & TIMELINE -->
            <div class="lg:col-span-2 space-y-6">

                <!-- KARTU STATUS PEMBAYARAN & TRANSAKSI -->
                <div class="crm-card bg-white p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Status Pembayaran & Transaksi</h3>
                            <p class="text-xs text-slate-500">Pencatatan pembayaran Formulir, Termin 1, dan verifikasi CS</p>
                        </div>
                    </div>

                    @php 
                        $pendingList = ($transaksis ?? collect())->where('payment_status', 'pending');
                    @endphp
                    @if($pendingList->isNotEmpty())
                        @foreach($pendingList as $pTrx)
                            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 space-y-1 text-xs">
                                <div class="flex items-center justify-between font-bold">
                                    <span>Menunggu Verifikasi CS</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-200 text-amber-900">PENDING</span>
                                </div>
                                <p class="text-amber-800">
                                    Transaksi <strong>{{ $pTrx->jenis }}</strong> sebesar <strong>Rp {{ number_format($pTrx->nominal, 0, ',', '.') }}</strong> sedang diproses. Closing akan resmi aktif setelah diverifikasi CS.
                                </p>
                            </div>
                        @endforeach
                    @endif

                    @if(($transaksis ?? collect())->isEmpty())
                        <div class="py-6 text-center bg-slate-50 rounded-xl text-slate-400 text-xs">
                            Belum ada transaksi tercatat untuk prospek ini.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Tanggal</th>
                                        <th class="px-3 py-2 text-left">Jenis</th>
                                        <th class="px-3 py-2 text-right">Nominal</th>
                                        <th class="px-3 py-2 text-left">Metode</th>
                                        <th class="px-3 py-2 text-center">Status</th>
                                        <th class="px-3 py-2 text-left">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($transaksis as $trx)
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="px-3 py-2.5 font-medium text-slate-700 whitespace-nowrap">{{ $trx->tanggal ? $trx->tanggal->format('d/m/Y') : '-' }}</td>
                                            <td class="px-3 py-2.5 font-bold text-slate-800 whitespace-nowrap">{{ $trx->jenis }}</td>
                                            <td class="px-3 py-2.5 text-right font-extrabold text-slate-900 whitespace-nowrap">Rp {{ number_format($trx->nominal, 0, ',', '.') }}</td>
                                            <td class="px-3 py-2.5 whitespace-nowrap">
                                                <span class="px-2 py-0.5 rounded-md border font-semibold text-[10px] bg-slate-50 text-slate-700">
                                                    {{ $trx->metode_label }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                                @if($trx->payment_status === 'pending')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200">Pending</span>
                                                @elseif($trx->payment_status === 'verified')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">Terverifikasi</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200">Ditolak</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2.5 text-slate-600 text-[11px]">
                                                {{ $trx->notes ?? '-' }}
                                                @if($trx->verifier)
                                                    <div class="text-[10px] text-emerald-700">✓ CS: {{ $trx->verifier->name }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- RIWAYAT AKTIVITAS & TIMELINE TIM -->
                <div class="crm-card bg-white p-6 space-y-5">
                    <div class="pb-3 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Riwayat Aktivitas & Timeline Tim</h3>
                        <p class="text-xs text-slate-500">Log perubahan status, catatan follow up, dan aktivitas Sales pada prospek ini</p>
                    </div>

                <!-- Timeline Items -->
                <div class="space-y-6 relative before:absolute before:inset-0 before:left-3 before:w-0.5 before:bg-slate-200">
                    @forelse($prospect['timeline'] ?? [] as $item)
                        <div class="relative flex items-start gap-4">
                            <div class="w-6 h-6 rounded-full bg-white border-2 border-blue-600 text-blue-600 font-bold flex items-center justify-center text-[10px] shrink-0 z-10">
                                @if(($item['role'] ?? '') === 'Sales') S @elseif(($item['role'] ?? '') === 'CS') C @else ✓ @endif
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
                    @empty
                        <div class="text-center py-6 text-slate-400 text-xs">Belum ada riwayat aktivitas yang tercatat.</div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- MODAL REASSIGN HANDLER -->
        <div x-show="modalReassign" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="modalReassign = false">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Re-assign Prospek ke Handler Baru</h3>
                    <button @click="modalReassign = false" class="text-slate-400 hover:text-slate-600"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <div class="p-3 bg-amber-50/80 rounded-xl border border-amber-200/70 text-[11px] text-amber-800 leading-relaxed flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <strong class="font-bold">Ketentuan Re-alokasi:</strong> Hitungan follow up (<code class="bg-amber-100 px-1 py-0.5 rounded text-[10px] font-mono">follow_up_count</code>) akan direset ke 0 untuk handler baru. Riwayat aktivitas sebelumnya tetap aman di timeline.
                    </div>
                </div>

                <form action="{{ route('spv.prospek.reassign', $prospect['id']) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Sales Handler</label>
                        <select name="sales_id" class="w-full text-xs px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium">
                            <option value="">-- Tetap / Kosongkan --</option>
                            @foreach($teamSales as $s)
                                <option value="{{ $s->id }}" {{ ($prospect['sales_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }} (Sales)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Atau Tugaskan CS Handler</label>
                        <select name="cs_id" class="w-full text-xs px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium">
                            <option value="">-- Tidak ada CS / Tetap --</option>
                            @foreach($teamCs as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} (CS)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Pengalihan</label>
                        <input type="text" name="reason" placeholder="Contoh: Pergantian wilayah atau percepatan closing" class="w-full text-xs px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="modalReassign = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold">Simpan Pengalihan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL UPDATE STATUS -->
        <div x-show="modalStatus" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="modalStatus = false">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Ubah Status Pipeline Prospek</h3>
                    <button @click="modalStatus = false" class="text-slate-400 hover:text-slate-600"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                
                <form action="{{ route('spv.prospek.updateStatus', $prospect['id']) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Status Baru (8 Tahapan PMB TA 2027/2028)</label>
                        <select name="status" x-model="selectedStatus" class="w-full text-xs px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold">
                            @foreach($allStages as $stage)
                                <option value="{{ $stage['name'] }}">{{ $stage['name'] }} (Stage {{ $stage['number'] }})</option>
                            @endforeach
                            <option value="Lost">Lost (Arsip)</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="modalStatus = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold">Update Status</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL BANTU CLOSING (MABA LUNAS: FORMULIR + TERMIN 1) -->
        <div x-show="modalClosing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 border border-slate-200" @click.away="modalClosing = false">
                <!-- Header Modal -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900">Bantu Closing Prospek (Maba Lunas)</h3>
                            <p class="text-xs text-slate-500">Pencatatan Transaksi Formulir + Pembayaran Termin 1 oleh SPV</p>
                        </div>
                    </div>
                    <button @click="modalClosing = false" class="text-slate-400 hover:text-slate-600 p-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>

                <!-- Business Rule Warning Alert: Attribution Guarantee -->
                <div class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-200 text-xs text-emerald-900 leading-relaxed space-y-1">
                    <div class="flex items-center gap-1.5 font-bold text-emerald-800">
                        <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Jaminan Kepemilikan Lead:</span>
                    </div>
                    <p class="text-[11px] text-emerald-700">
                        SPV berwenang membantu closing, namun <strong>kredit dan kepemilikan lead (<code class="bg-emerald-100 px-1 py-0.5 rounded font-mono">sales_id</code>) tetap menjadi hak milik {{ $prospect['takeover_sales'] ?? 'Sales Pemilik Awal' }}</strong>.
                    </p>
                </div>

                <form action="{{ route('spv.prospek.closing', $prospect['id']) }}" method="POST" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Biaya Beli Formulir (Rp)
                            </label>
                            <input 
                                type="number" 
                                name="nominal_formulir" 
                                value="250000" 
                                min="0" 
                                max="9999999999"
                                step="1000"
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-semibold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                placeholder="250000"
                            >
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Kosongkan/0 jika sudah bayar sebelumnya</span>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Nominal Termin 1 (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="nominal_termin1" 
                                value="1500000" 
                                min="10000" 
                                max="9999999999"
                                step="1000" 
                                required
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-bold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                placeholder="1500000"
                            >
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Wajib ada untuk syarat Maba Lunas</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Tanggal Transaksi Closing <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="tanggal" 
                            value="{{ date('Y-m-d') }}" 
                            required
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                        >
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Bantuan Closing</label>
                        <textarea 
                            name="notes" 
                            rows="2" 
                            placeholder="Catatan SPV (misal: closing via bantuan presentasi di sekolah/kampus)..." 
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 resize-none"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="modalClosing = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span>Konfirmasi Closing Maba Lunas</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</x-app-layout>
