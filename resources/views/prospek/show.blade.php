@php
    $pageTitle = 'Detail Prospek';
    $pageSubtitle = $prospect['name'];
    $prospekModel = $prospekModel ?? \App\Models\Prospek::find($prospect['id']);
    $transaksis = $transaksis ?? ($prospekModel ? $prospekModel->transaksis()->with(['user', 'verifier', 'rejecter'])->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->get() : collect());
    $pendingList = $transaksis->where('payment_status', 'pending');
    $hasPendingTrx = $pendingList->isNotEmpty();
    $hasVerifiedTermin1 = $transaksis->where('jenis', 'Pembayaran Termin 1')->where('payment_status', 'verified')->isNotEmpty();
    $hasVerifiedFormulir = $transaksis->where('jenis', 'Beli Formulir')->where('payment_status', 'verified')->isNotEmpty();
    $totalPaid = $transaksis->where('payment_status', 'verified')->sum('nominal');
    $totalPending = $transaksis->where('payment_status', 'pending')->sum('nominal');
@endphp

<x-app-layout :title="'Detail Prospek - ' . $prospect['name']">

    <div class="space-y-6" x-data="{
        prospect: {{ json_encode($prospect) }},
        currentStatus: '{{ $prospect['status'] }}',
        modalRealokasi: false,
        modalEditProspek: false,
        modalTransaksi: false,
        transaksiStep: 1,
        selectedJenis: 'Pembayaran Termin 1',
        nominalVal: 1500000,
        tanggalVal: '{{ date('Y-m-d') }}',
        metodePembayaran: 'bank_transfer',
        selectedBankId: '{{ \App\Models\BankAccount::where('is_active', true)->value('id') ?? '' }}',
        selectedBankVA: 'Mandiri Virtual Account',
        notesVal: '',
        copied: false,
        copyText(text) {
            if (!text) return;
            navigator.clipboard.writeText(text);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
        bankAccounts: {{ json_encode(\App\Models\BankAccount::where('is_active', true)->orderBy('bank_name')->get(['id','bank_name','account_number','account_name','notes'])) }},
        get selectedBank() {
            if (this.selectedBankId) {
                return this.bankAccounts.find(b => String(b.id) === String(this.selectedBankId)) || null;
            }
            return this.bankAccounts.length > 0 ? this.bankAccounts[0] : null;
        },
        get cleanPhone() {
            let p = this.prospect.whatsapp || this.prospect.pic_phone || '';
            return p.replace(/[^0-9]/g, '');
        },
        get vaNumber() {
            let prefix = '88019';
            if (this.selectedBankVA.includes('BCA')) prefix = '3901';
            else if (this.selectedBankVA.includes('BNI')) prefix = '9881';
            else if (this.selectedBankVA.includes('BRI')) prefix = '12899';
            else if (this.selectedBankVA.includes('Permata')) prefix = '8455';
            return prefix + (this.cleanPhone || '08123456789');
        },
        get ewalletNumber() {
            return '0821-2800-5599';
        },
        formatRupiah(num) {
            if (!num) return '0';
            return new Intl.NumberFormat('id-ID').format(num);
        },
        openModalTransaksi(jenis = 'Pembayaran Termin 1', nominal = 1500000) {
            this.selectedJenis = jenis;
            this.nominalVal = nominal;
            this.transaksiStep = 1;
            this.modalTransaksi = true;
        },
        resetTransaksi() {
            this.modalTransaksi = false;
            this.transaksiStep = 1;
        }
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
                        
                        @if($hasPendingTrx)
                            <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-900 border border-amber-300 text-xs font-bold inline-flex items-center gap-1.5 shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                Menunggu Verifikasi CS (Rp {{ number_format($totalPending, 0, ',', '.') }})
                            </span>
                        @elseif($hasVerifiedTermin1 || $prospect['status'] === 'LUNAS')
                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300 text-xs font-bold inline-flex items-center gap-1.5 shadow-xs">
                                Lunas (Closing Target Sales)
                            </span>
                        @endif

                        <span class="px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-semibold">{{ $prospect['type'] }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-500 font-medium">
                        @if(!empty($prospect['pic']) && $prospect['pic'] !== '-')
                            <span>Nama <strong class="text-slate-800">{{ $prospect['pic'] }}</strong></span>
                            <span>&bull;</span>
                        @endif
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
                        @click="modalTransaksi = true"
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
                    @if($prospect['status'] === 'Lost' || $prospect['status'] === 'DINGIN')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            Status Khusus: DINGIN (Lost)
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
                    
                    <div class="space-y-3.5 text-xs">
                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Status Pipeline</span>
                            <div class="mt-1">
                                <x-status-badge :status="$prospect['status']" />
                            </div>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Status Pembayaran</span>
                            <div class="mt-1">
                                @if($hasPendingTrx)
                                    <span class="px-2 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 font-bold text-[11px] inline-flex items-center gap-1">
                                        Menunggu Verifikasi CS (Rp {{ number_format($totalPending, 0, ',', '.') }})
                                    </span>
                                @elseif($hasVerifiedTermin1 || $prospect['status'] === 'LUNAS')
                                    <span class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold text-[11px] inline-flex items-center gap-1">
                                        Lunas (Rp {{ number_format($totalPaid, 0, ',', '.') }})
                                    </span>
                                @elseif($hasVerifiedFormulir)
                                    <span class="px-2 py-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200 font-bold text-[11px] inline-flex items-center gap-1">
                                        Formulir Lunas (Rp {{ number_format($totalPaid, 0, ',', '.') }})
                                    </span>
                                @else
                                    <span class="text-slate-500 font-medium italic">Belum ada transaksi</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Sumber Prospek</span>
                            <p class="font-medium text-slate-800 mt-0.5">{{ $prospect['source'] ?? '-' }}</p>
                        </div>

                        @if($prospekModel->asal_kelas)
                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Kelas / Jabatan</span>
                            <p class="font-medium text-slate-800 mt-0.5">{{ $prospekModel->asal_kelas }}</p>
                        </div>
                        @endif

                        @if($prospekModel->wa_ortu)
                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">WA Perwakilan </span>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $prospekModel->wa_ortu) }}" target="_blank" class="font-medium text-emerald-600 hover:text-emerald-700 mt-0.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                {{ $prospekModel->wa_ortu }}
                            </a>
                        </div>
                        @endif

                        @if($prospekModel->prodi_id || $prospekModel->prodi_lainnya)
                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Prodi Diminati</span>
                            <p class="font-medium text-slate-800 mt-0.5">{{ $prospekModel->prodi?->nama ?? $prospekModel->prodi_lainnya }}</p>
                        </div>
                        @endif

                        <div>
                            <span class="text-slate-400 block font-semibold text-[10px] uppercase">Catatan Internal</span>
                            <p class="text-slate-600 mt-0.5 bg-slate-50 p-2.5 rounded-lg border border-slate-100">{{ $prospect['notes'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: TRANSACTIONS & ACTIVITY TIMELINE -->
            <div class="lg:col-span-2 space-y-6">

                <!-- KARTU STATUS PEMBAYARAN & RIWAYAT TRANSAKSI -->
                <div class="crm-card bg-white p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Status Pembayaran & Transaksi</h3>
                            <p class="text-xs text-slate-500">Pencatatan pembayaran formulir, termin, dan verifikasi CS</p>
                        </div>

                        @can('transaction', $prospekModel ?? null)
                        <button 
                            type="button" 
                            @click="openModalTransaksi('Pembayaran Termin 1', 1500000)"
                            class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer self-start sm:self-auto"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            <span>Input Transaksi</span>
                        </button>
                        @endcan
                    </div>

                    {{-- Financial Metric Badges --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Terverifikasi (Lunas)</div>
                            <div class="text-base font-extrabold text-emerald-700 mt-0.5">
                                Rp {{ number_format($totalPaid, 0, ',', '.') }}
                            </div>
                        </div>

                        <div class="p-3 rounded-xl {{ $hasPendingTrx ? 'bg-amber-50 border border-amber-300' : 'bg-slate-50 border border-slate-200' }}">
                            <div class="text-[10px] font-bold {{ $hasPendingTrx ? 'text-amber-800' : 'text-slate-500' }} uppercase tracking-wider">Menunggu CS (Pending)</div>
                            <div class="text-base font-extrabold {{ $hasPendingTrx ? 'text-amber-900' : 'text-slate-600' }} mt-0.5">
                                Rp {{ number_format($totalPending, 0, ',', '.') }}
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 col-span-2 sm:col-span-1">
                            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status Closing Sales</div>
                            <div class="text-xs font-extrabold mt-1">
                                @if($hasVerifiedTermin1 || $prospect['status'] === 'LUNAS')
                                    <span class="text-emerald-700">Target Tercatat</span>
                                @elseif($hasPendingTrx)
                                    <span class="text-amber-700">Menunggu Verifikasi CS</span>
                                @else
                                    <span class="text-slate-500">Belum Closing</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Highlight Banner Transaksi Pending (Menunggu Verifikasi CS) --}}
                    @if($hasPendingTrx)
                        @foreach($pendingList as $pTrx)
                            <div class="p-4 rounded-2xl bg-amber-50/95 border border-amber-300 text-amber-950 space-y-2 shadow-xs">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 font-bold text-xs text-amber-900">
                                        <span class="relative flex h-3 w-3">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                                        </span>
                                        <span>Transaksi Sedang Menunggu Verifikasi CS</span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-200 text-amber-900 border border-amber-300">
                                        PENDING CS
                                    </span>
                                </div>
                                <p class="text-xs text-amber-900 leading-relaxed">
                                    Pembayaran <strong>{{ $pTrx->jenis }}</strong> sebesar <strong class="text-amber-950">Rp {{ number_format($pTrx->nominal, 0, ',', '.') }}</strong> (Metode: {{ $pTrx->metode_label }}) telah dicatat oleh <strong>{{ $pTrx->user?->name ?? 'Sales' }}</strong> pada {{ $pTrx->created_at ? $pTrx->created_at->format('d M Y H:i') : '-' }} dan diteruskan ke Tim CS. Begitu CS menyetujui, prospek otomatis menjadi <strong>LUNAS (Closing)</strong> dan target tercatat ke Sales.
                                </p>
                                @if($pTrx->notes)
                                    <div class="text-[11px] bg-white/90 p-2.5 rounded-xl border border-amber-200 text-amber-950 font-medium">
                                        <strong>Catatan / No. Rekening:</strong> {{ $pTrx->notes }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif

                    {{-- Daftar Riwayat Transaksi --}}
                    @if(($transaksis ?? collect())->isEmpty())
                        <div class="py-8 text-center bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                            <p class="text-xs font-bold text-slate-700">Belum ada transaksi pembayaran</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Catat pembayaran Beli Formulir atau Termin 1 untuk memproses pipeline closing.</p>
                            @can('transaction', $prospekModel ?? null)
                            <button 
                                type="button" 
                                @click="openModalTransaksi('Beli Formulir', 250000)"
                                class="mt-3 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-[11px] shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer"
                            >
                                <span>Input Transaksi Sekarang</span>
                            </button>
                            @endcan
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase">
                                    <tr>
                                        <th class="px-3.5 py-2.5 text-left">Tanggal</th>
                                        <th class="px-3.5 py-2.5 text-left">Jenis Transaksi</th>
                                        <th class="px-3.5 py-2.5 text-right">Nominal</th>
                                        <th class="px-3.5 py-2.5 text-left">Metode Bayar</th>
                                        <th class="px-3.5 py-2.5 text-center">Status</th>
                                        <th class="px-3.5 py-2.5 text-left">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($transaksis as $trx)
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="px-3.5 py-3 font-medium text-slate-700 whitespace-nowrap">
                                                {{ $trx->tanggal ? $trx->tanggal->format('d/m/Y') : '-' }}
                                            </td>
                                            <td class="px-3.5 py-3 font-bold text-slate-800 whitespace-nowrap">
                                                {{ $trx->jenis }}
                                            </td>
                                            <td class="px-3.5 py-3 text-right font-extrabold text-slate-900 whitespace-nowrap">
                                                Rp {{ number_format($trx->nominal, 0, ',', '.') }}
                                            </td>
                                            <td class="px-3.5 py-3 whitespace-nowrap">
                                                @php
                                                    $mMap = [
                                                        'bank_transfer'   => ['label' => 'Transfer Bank', 'class' => 'bg-amber-50 text-amber-800 border-amber-200'],
                                                        'virtual_account' => ['label' => 'Virtual Account', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
                                                        'gopay'           => ['label' => 'GoPay', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                                        'dana'            => ['label' => 'DANA', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
                                                        'shopeepay'       => ['label' => 'ShopeePay', 'class' => 'bg-orange-50 text-orange-700 border-orange-200'],
                                                        'tunai'           => ['label' => 'Kasir / Tunai', 'class' => 'bg-slate-50 text-slate-700 border-slate-200'],
                                                    ];
                                                    $mInfo = $mMap[$trx->metode_pembayaran] ?? ['label' => $trx->metode_pembayaran ?: 'Transfer Bank', 'class' => 'bg-slate-50 text-slate-700 border-slate-200'];
                                                @endphp
                                                <span class="px-2 py-0.5 rounded-md border font-semibold text-[10px] inline-flex items-center gap-1 {{ $mInfo['class'] }}">
                                                    {{ $mInfo['label'] }}
                                                </span>
                                            </td>
                                            <td class="px-3.5 py-3 text-center whitespace-nowrap">
                                                @if($trx->payment_status === 'pending')
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center gap-1">
                                                        Menunggu CS
                                                    </span>
                                                @elseif($trx->payment_status === 'verified')
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                                                        Diverifikasi CS
                                                    </span>
                                                @elseif($trx->payment_status === 'rejected')
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200 inline-flex items-center gap-1">
                                                        Ditolak CS
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                                        {{ $trx->payment_status ?? 'verified' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-3.5 py-3 text-slate-600 text-[11px]">
                                                @if($trx->notes)
                                                    <div class="font-medium text-slate-800">{{ $trx->notes }}</div>
                                                @endif
                                                @if($trx->verified_by && $trx->verifier)
                                                    <div class="text-[10px] text-emerald-700 mt-0.5">
                                                        CS: {{ $trx->verifier->name }} ({{ $trx->verified_at?->format('d/m/Y H:i') }})
                                                    </div>
                                                @elseif($trx->rejected_by && $trx->rejecter)
                                                    <div class="text-[10px] text-rose-700 mt-0.5">
                                                        Ditolak: {{ $trx->rejection_reason ?? '-' }} (CS: {{ $trx->rejecter->name }})
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- RIWAYAT AKTIVITAS & TIMELINE -->
                <div class="crm-card bg-white p-6 space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Riwayat Aktivitas & Timeline</h3>
                            <p class="text-xs text-slate-500">Log lengkap interaksi, perubahan status, dan catatan tim</p>
                        </div>
                        <button 
                            @click="selectedProspect = prospect; modalFollowUp = true"
                            class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-xs transition cursor-pointer"
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
                        <p class="text-xs text-slate-500">Perbarui data prospek, informasi sekolah/perusahaan, dan kontak.</p>
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


                            <div x-data="{ selectedProdi: '{{ $prospect['prodi_id'] ?? (empty($prospect['prodi_id']) && !empty($prospect['prodi_lainnya']) ? 'lainnya' : '') }}' }">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Program Studi Diminati</label>
                                <select name="prodi_id" x-model="selectedProdi" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white mb-2">
                                    <option value="">-- Pilih Program Studi --</option>
                                    @foreach($prodisList ?? [] as $prd)
                                        <option value="{{ $prd->id }}" {{ ($prospect['prodi_id'] ?? '') == $prd->id ? 'selected' : '' }}>{{ $prd->nama }} ({{ $prd->jenjang }})</option>
                                    @endforeach
                                    <option value="lainnya" {{ empty($prospect['prodi_id']) && !empty($prospect['prodi_lainnya']) ? 'selected' : '' }}>Lainnya</option>
                                </select>
                                <div x-show="selectedProdi === 'lainnya'" style="display: none;">
                                    <input type="text" name="prodi_lainnya" value="{{ $prospect['prodi_lainnya'] ?? '' }}" placeholder="Ketik program studi pilihan..." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Pipeline *</label>
                                <select name="status" required class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white">
                                    @foreach($allStages as $stage)
                                        <option value="{{ $stage['name'] }}" {{ $prospect['status'] === $stage['name'] ? 'selected' : '' }}>{{ $stage['name'] }}</option>
                                    @endforeach
                                    <option value="Lost" {{ $prospect['status'] === 'Lost' ? 'selected' : '' }}>Lost (Arsip)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Prospek <span class="text-rose-500">*</span></label>
                                <input type="text" name="pic" required value="{{ $prospect['pic'] !== '-' ? $prospect['pic'] : ($prospect['name'] !== '-' ? $prospect['name'] : '') }}" placeholder="Contoh: Budi Santoso" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>

                            <div x-show="prospekType === 'Sekolah' || prospekType === 'Corporate'">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    <span x-text="prospekType === 'Corporate' ? 'Jabatan' : 'Kelas / Tingkat Sekolah'"></span>
                                </label>
                                <input type="text" name="asal_kelas" value="{{ $prospect['asal_kelas'] ?? '' }}" :placeholder="prospekType === 'Corporate' ? 'Contoh: HR Manager' : 'Contoh: 12 RPL 1'" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp *</label>
                                <input type="tel" name="whatsapp" required value="{{ $prospect['whatsapp'] }}" placeholder="Masukkan nomor .." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    <span x-text="prospekType === 'Corporate' ? 'Nomor Perusahaan' : 'Nomor WA Orang Tua/Wali'"></span>
                                    <span class="text-sm font-normal text-slate-500">(Opsional)</span>
                                </label>
                                <input type="tel" name="wa_ortu" value="{{ $prospect['wa_ortu'] ?? '' }}" placeholder="Masukkan nomor .." class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
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

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- MODAL: INPUT TRANSAKSI (2-STEP PAYMENT WIZARD)              --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div
        x-show="modalTransaksi"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalTransaksi" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="resetTransaksi()"></div>

            <div x-show="modalTransaksi" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl relative z-10 border border-slate-100">
                {{-- Header Modal & Step Indicator --}}
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Input Transaksi Pembayaran</h3>
                        <p class="text-xs text-slate-500">Prospek: <strong class="text-slate-800" x-text="prospect.name"></strong></p>
                    </div>
                    <button @click="resetTransaksi()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Stepper Progress Bar --}}
                <div class="grid grid-cols-2 gap-2 my-4">
                    <button type="button" @click="transaksiStep = 1" class="flex items-center gap-2 p-2 rounded-xl text-left transition" :class="transaksiStep === 1 ? 'bg-blue-50 border border-blue-200' : 'bg-slate-50 opacity-70'">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold" :class="transaksiStep === 1 ? 'bg-blue-600 text-white' : 'bg-slate-300 text-slate-700'">1</span>
                        <div>
                            <div class="text-[11px] font-bold" :class="transaksiStep === 1 ? 'text-blue-900' : 'text-slate-600'">Rincian Tagihan</div>
                            <div class="text-[10px] text-slate-400">Jenis & Nominal</div>
                        </div>
                    </button>

                    <button type="button" @click="if(nominalVal > 0) transaksiStep = 2" class="flex items-center gap-2 p-2 rounded-xl text-left transition" :class="transaksiStep === 2 ? 'bg-emerald-50 border border-emerald-200' : 'bg-slate-50 opacity-70'">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold" :class="transaksiStep === 2 ? 'bg-emerald-600 text-white' : 'bg-slate-300 text-slate-700'">2</span>
                        <div>
                            <div class="text-[11px] font-bold" :class="transaksiStep === 2 ? 'text-emerald-900' : 'text-slate-600'">Nomor Bayar & Kirim</div>
                            <div class="text-[10px] text-slate-400">Salin Rekening / CS</div>
                        </div>
                    </button>
                </div>

                <form :action="'{{ url('prospek') }}/' + prospect.id + '/transaksi'" method="POST" class="space-y-4">
                    @csrf

                    {{-- ─────────────────────────────────────────────────── --}}
                    {{-- LANGKAH 1: RINCIAN TAGIHAN & PILIH METODE PEMBAYARAN --}}
                    {{-- ─────────────────────────────────────────────────── --}}
                    <div x-show="transaksiStep === 1" class="space-y-4">

                        {{-- Quick Presets --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="selectedJenis = 'Beli Formulir'; nominalVal = 250000" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition cursor-pointer" :class="selectedJenis === 'Beli Formulir' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'">
                                Formulir (Rp 250.000)
                            </button>
                            <button type="button" @click="selectedJenis = 'Pembayaran Termin 1'; nominalVal = 1500000" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition cursor-pointer" :class="selectedJenis === 'Pembayaran Termin 1' && nominalVal == 1500000 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'">
                                Termin 1 (Rp 1.500.000)
                            </button>
                            <button type="button" @click="selectedJenis = 'Pembayaran Termin 1'; nominalVal = 3000000" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition cursor-pointer" :class="selectedJenis === 'Pembayaran Termin 1' && nominalVal == 3000000 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'">
                                Termin 1 (Rp 3.000.000)
                            </button>
                        </div>

                        {{-- Jenis Transaksi --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Transaksi <span class="text-rose-500">*</span></label>
                            <select name="jenis" x-model="selectedJenis" required
                                class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition bg-white font-medium">
                                <option value="Beli Formulir">Beli Formulir</option>
                                <option value="Pembayaran Termin 1">Pembayaran Termin 1 (Syarat Closing)</option>
                            </select>
                        </div>

                        {{-- Nominal --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Transaksi (Rp) <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs font-bold">Rp</span>
                                <input type="number" name="nominal" x-model="nominalVal" required min="0" max="9999999999" step="1000" placeholder="1500000"
                                    class="w-full text-xs sm:text-sm pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition font-bold text-slate-900 text-base">
                            </div>
                            <div class="text-[11px] text-slate-500 mt-1 font-medium" x-show="nominalVal > 0">
                                Terbilang: <span class="text-blue-700 font-bold" x-text="'Rp ' + formatRupiah(nominalVal)"></span>
                            </div>
                        </div>

                        {{-- Tanggal --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Transaksi <span class="text-rose-500">*</span></label>
                            <input type="date" name="tanggal" x-model="tanggalVal" required
                                class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition font-medium">
                        </div>

                        {{-- Pilihan Metode Pembayaran --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Metode Pembayaran PMB <span class="text-rose-500">*</span>
                            </label>
                            <select name="metode_pembayaran" x-model="metodePembayaran" required
                                class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition bg-white font-medium">
                                <option value="bank_transfer">Transfer Bank (Rekening Kampus UCIC)</option>
                                <option value="virtual_account">Virtual Account (VA PMB)</option>
                                <option value="gopay">GoPay PMB UCIC</option>
                                <option value="dana">DANA PMB UCIC</option>
                                <option value="shopeepay">ShopeePay PMB UCIC</option>
                                <option value="tunai">Kasir / Tunai Kampus</option>
                            </select>
                        </div>

                        {{-- Dropdown Bank Dinamis (Jika Transfer Bank) --}}
                        <div x-show="metodePembayaran === 'bank_transfer'" class="space-y-1 bg-amber-50/70 p-3 rounded-xl border border-amber-200">
                            <label class="block text-[11px] font-bold text-amber-900 uppercase">Pilih Bank Institusi (Admin)</label>
                            <select name="bank_account_id" x-model="selectedBankId" :required="metodePembayaran === 'bank_transfer'"
                                class="w-full text-xs sm:text-sm px-3 py-2 rounded-xl border border-amber-300 bg-white font-medium">
                                <template x-for="b in bankAccounts" :key="b.id">
                                    <option :value="b.id" x-text="b.bank_name + ' - ' + b.account_number + ' (A/N: ' + b.account_name + ')'"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Dropdown Bank VA (Jika Virtual Account) --}}
                        <div x-show="metodePembayaran === 'virtual_account'" class="space-y-1 bg-blue-50/70 p-3 rounded-xl border border-blue-200">
                            <label class="block text-[11px] font-bold text-blue-950 uppercase">Pilih Bank Virtual Account</label>
                            <select name="bank_va" x-model="selectedBankVA"
                                class="w-full text-xs sm:text-sm px-3 py-2 rounded-xl border border-blue-300 bg-white font-medium">
                                <option value="Mandiri Virtual Account">Mandiri Virtual Account (88019)</option>
                                <option value="BCA Virtual Account">BCA Virtual Account (3901)</option>
                                <option value="BNI Virtual Account">BNI Virtual Account (9881)</option>
                                <option value="BRI Virtual Account">BRI Virtual Account / BRIVA (12899)</option>
                                <option value="Permata Virtual Account">Permata Virtual Account (8455)</option>
                            </select>
                        </div>

                        {{-- Tombol Step 1 --}}
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button type="button" @click="resetTransaksi()"
                                class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="button" @click="if(!nominalVal || nominalVal <= 0) { alert('Harap isi nominal transaksi.'); return; } transaksiStep = 2"
                                class="px-5 py-2.5 text-xs font-bold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition cursor-pointer flex items-center gap-1.5">
                                <span>Lihat Nomor Pembayaran & Lanjut</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>

                    </div>

                    {{-- ─────────────────────────────────────────────────── --}}
                    {{-- LANGKAH 2: TAMPILAN NOMOR BAYAR & KONFIRMASI KE CS --}}
                    {{-- ─────────────────────────────────────────────────── --}}
                    <div x-show="transaksiStep === 2" class="space-y-4">

                        {{-- Kotak Preview Nomor Pembayaran (Besar & Jelas dengan Tombol Salin) --}}
                        
                        {{-- 1. KOTAK TRANSFER BANK --}}
                        <template x-if="metodePembayaran === 'bank_transfer' && selectedBank">
                            <div class="bg-amber-500/10 border-2 border-amber-400 p-4 rounded-2xl space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-500 text-white font-extrabold text-xs" x-text="selectedBank.bank_name"></span>
                                    <span class="text-xs font-bold text-amber-900">Rekening Resmi Kampus UCIC</span>
                                </div>

                                <div class="bg-white p-3.5 rounded-xl border border-amber-200 shadow-xs space-y-2">
                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Nomor Rekening Tujuan:</div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-lg sm:text-xl font-black font-mono tracking-wider text-slate-900 select-all" x-text="selectedBank.account_number"></span>
                                        <button type="button" @click="copyText(selectedBank.account_number)"
                                            class="px-3 py-1.5 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold text-xs transition flex items-center gap-1 cursor-pointer">
                                            <span x-show="!copied">Salin No. Rek</span>
                                            <span x-show="copied" class="text-emerald-700">Tersalin!</span>
                                        </button>
                                    </div>

                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                                        <span class="text-slate-500 font-medium">Atas Nama:</span>
                                        <span class="font-bold text-slate-900" x-text="selectedBank.account_name"></span>
                                    </div>

                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-500 font-medium">Total Transfer:</span>
                                        <span class="font-extrabold text-emerald-700" x-text="'Rp ' + formatRupiah(nominalVal)"></span>
                                    </div>
                                </div>

                                <template x-if="selectedBank.notes">
                                    <p class="text-[11px] text-amber-800 italic" x-text="selectedBank.notes"></p>
                                </template>
                            </div>
                        </template>

                        {{-- 2. KOTAK VIRTUAL ACCOUNT --}}
                        <template x-if="metodePembayaran === 'virtual_account'">
                            <div class="bg-blue-500/10 border-2 border-blue-400 p-4 rounded-2xl space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="px-2.5 py-1 rounded-lg bg-blue-600 text-white font-extrabold text-xs" x-text="selectedBankVA"></span>
                                    <span class="text-xs font-bold text-blue-900">Virtual Account PMB</span>
                                </div>

                                <div class="bg-white p-3.5 rounded-xl border border-blue-200 shadow-xs space-y-2">
                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Kode Bayar Virtual Account:</div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-lg sm:text-xl font-black font-mono tracking-wider text-blue-950 select-all" x-text="vaNumber"></span>
                                        <button type="button" @click="copyText(vaNumber)"
                                            class="px-3 py-1.5 rounded-lg bg-blue-100 hover:bg-blue-200 text-blue-900 font-bold text-xs transition flex items-center gap-1 cursor-pointer">
                                            <span x-show="!copied">Salin Kode VA</span>
                                            <span x-show="copied" class="text-emerald-700">Tersalin!</span>
                                        </button>
                                    </div>

                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                                        <span class="text-slate-500 font-medium">Atas Nama:</span>
                                        <span class="font-bold text-slate-900" x-text="'PMB UCIC - ' + prospect.name"></span>
                                    </div>

                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-500 font-medium">Total Bayar:</span>
                                        <span class="font-extrabold text-emerald-700" x-text="'Rp ' + formatRupiah(nominalVal)"></span>
                                    </div>
                                </div>
                                <p class="text-[10px] text-blue-800">Kode bayar VA dapat langsung dibayarkan melalui ATM / M-Banking calon mahasiswa.</p>
                            </div>
                        </template>

                        {{-- 3. KOTAK E-WALLET (GOPAY, DANA, SHOPEEPAY) --}}
                        <template x-if="metodePembayaran === 'gopay' || metodePembayaran === 'dana' || metodePembayaran === 'shopeepay'">
                            <div class="bg-emerald-500/10 border-2 border-emerald-400 p-4 rounded-2xl space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-extrabold text-xs uppercase" x-text="metodePembayaran + ' PMB UCIC'"></span>
                                    <span class="text-xs font-bold text-emerald-900">E-Wallet Resmi UCIC</span>
                                </div>

                                <div class="bg-white p-3.5 rounded-xl border border-emerald-200 shadow-xs space-y-2">
                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Nomor E-Wallet PMB:</div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-lg sm:text-xl font-black font-mono tracking-wider text-slate-900 select-all" x-text="ewalletNumber"></span>
                                        <button type="button" @click="copyText(ewalletNumber)"
                                            class="px-3 py-1.5 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-900 font-bold text-xs transition flex items-center gap-1 cursor-pointer">
                                            <span x-show="!copied">Salin Nomor</span>
                                            <span x-show="copied" class="text-emerald-700">Tersalin!</span>
                                        </button>
                                    </div>

                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                                        <span class="text-slate-500 font-medium">Atas Nama Akun:</span>
                                        <span class="font-bold text-slate-900">PMB Universitas Catur Insan Cendekia</span>
                                    </div>

                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-500 font-medium">Total Nominal:</span>
                                        <span class="font-extrabold text-emerald-700" x-text="'Rp ' + formatRupiah(nominalVal)"></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- 4. KOTAK KASIR TUNAI --}}
                        <template x-if="metodePembayaran === 'tunai'">
                            <div class="bg-slate-100 border border-slate-300 p-4 rounded-2xl space-y-2">
                                <div class="font-bold text-xs text-slate-900 flex items-center gap-1.5">
                                    <span>Pembayaran Kasir Kampus UCIC</span>
                                </div>
                                <p class="text-xs text-slate-700">Pembayaran tunai langsung di Kasir Keuangan PMB Kampus UCIC (Jl. Kesambi No. 202, Cirebon).</p>
                                <div class="text-xs font-bold text-emerald-800">Total: <span x-text="'Rp ' + formatRupiah(nominalVal)"></span></div>
                            </div>
                        </template>

                        {{-- Catatan / Bukti Ref --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Nomor Referensi / Nama Pengirim / Catatan Bukti Transfer
                            </label>
                            <textarea name="notes" rows="2" placeholder="Contoh: Transfer dari BCA a.n Hendra Wijaya (Ayah) - No. Ref 817290..."
                                class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition resize-none"></textarea>
                        </div>

                        {{-- Alert CS Verification Flow --}}
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                            <div class="text-[11px] leading-relaxed">
                                Transaksi ini akan berstatus <strong>PENDING</strong> dan otomatis masuk ke antrean notifikasi <strong>CS</strong> untuk dicek dan disetujui. Target Closing akan masuk ke akun Anda setelah pembayaran diverifikasi oleh CS.
                            </div>
                        </div>

                        {{-- Tombol Step 2 --}}
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                            <button type="button" @click="transaksiStep = 1"
                                class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <span>Ganti Rincian</span>
                            </button>
                            <button type="submit"
                                class="px-5 py-2.5 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition cursor-pointer flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Simpan & Kirim ke CS</span>
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
</x-app-layout>

