@extends('pmb.portal.layout')

@section('title', 'Pembayaran Biaya PMB UCIC')

@push('styles')
    @php
        $snapJsUrl = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
        $clientKey = (string) config('services.midtrans.client_key');
    @endphp
    <script src="{{ $snapJsUrl }}" data-client-key="{{ $clientKey }}"></script>
@endpush

@php
    $initialItems = $initialItems ?? ($transaksi ? \App\Services\MidtransService::resolveInitialItems($transaksi) : ['pendaftaran' => true, 'perlengkapan' => false, 'dpp' => false]);
    $initialTotal = $initialTotal ?? ($transaksi ? ((int) $transaksi->nominal ?: 300000) : 300000);
    $activeCategory = $transaksi ? \App\Models\Transaksi::normalizeCategory($transaksi->kategori) : \App\Models\Transaksi::KATEGORI_PENDAFTARAN;
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-8"
     id="pmb-pembayaran-container"
     x-init="if (status !== 'verified' && orderId) { checkStatusOnce(); }"
     x-data="{
         orderId: '{{ $transaksi?->order_id ?? '' }}',
         prospekId: {{ $prospek->id }},
         status: '{{ $transaksi?->payment_status ?? 'unpaid' }}',
         snapToken: '{{ $transaksi?->snap_token ?? '' }}',
         activeCategory: '{{ $activeCategory }}',
         loading: false,
         pollingInterval: null,
         selectedMethod: 'online',
         manualModalOpen: false,
         offlineModalOpen: false,
         selectedBankId: {{ $bankAccounts->first()?->id ?? 0 }},
         items: {
             perlengkapan: {{ !empty($initialItems['perlengkapan']) ? 'true' : 'false' }},
             pendaftaran: {{ !empty($initialItems['pendaftaran']) ? 'true' : 'false' }},
             dpp: {{ !empty($initialItems['dpp']) ? 'true' : 'false' }}
         },
         prices: {
             perlengkapan: 600000,
             pendaftaran: 300000,
             dpp: 500000
         },
         categoryNominals: {
             'Biaya Pendaftaran': {{ (int) ($categoriesStatus['Biaya Pendaftaran']['nominal'] ?? 300000) }},
             'DP': {{ (int) ($categoriesStatus['DP']['nominal'] ?? 500000) }},
             'Pelunasan': {{ (int) ($categoriesStatus['Pelunasan']['nominal'] ?? 0) }}
         },
         categoryStatuses: {
             'Biaya Pendaftaran': '{{ $categoriesStatus['Biaya Pendaftaran']['status_code'] ?? 'unpaid' }}',
             'DP': '{{ $categoriesStatus['DP']['status_code'] ?? 'unpaid' }}',
             'Pelunasan': '{{ $categoriesStatus['Pelunasan']['status_code'] ?? 'unpaid' }}'
         },
         get total() {
             if (this.activeCategory === 'Biaya Pendaftaran') {
                 let sum = 0;
                 if (this.items.perlengkapan) sum += this.prices.perlengkapan;
                 if (this.items.pendaftaran) sum += this.prices.pendaftaran;
                 if (this.items.dpp) sum += this.prices.dpp;
                 return sum;
             }
             return this.categoryNominals[this.activeCategory] || 0;
         },
         formatRupiah(num) {
             num = Number(num) || 0;
             return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
         },
         selectCategory(cat) {
             this.activeCategory = cat;
             this.snapToken = null;
         },
         onItemChange() {
             this.snapToken = null;
         },

         async handleAction(categoryOverride = null) {
             const targetCat = categoryOverride || this.activeCategory;
             const catStatus = this.categoryStatuses[targetCat];

             if (catStatus === 'verified') {
                 alert('Kategori pembayaran ' + targetCat + ' sudah lunas terverifikasi sah!');
                 return;
             }

             if (this.total === 0 && targetCat === 'Biaya Pendaftaran') {
                 alert('Silakan pilih minimal satu item tagihan pembayaran.');
                 return;
             }

             if (targetCat === 'Pelunasan' && this.categoryNominals['Pelunasan'] <= 0) {
                 alert('Tidak ada sisa tagihan pelunasan yang harus dibayar.');
                 return;
             }

             if (this.selectedMethod === 'online') {
                 await this.payCategoryNow(targetCat);
             } else if (this.selectedMethod === 'transfer_manual') {
                 this.manualModalOpen = true;
             } else if (this.selectedMethod === 'offline') {
                 this.offlineModalOpen = true;
             }
         },

         async payCategoryNow(cat) {
             this.activeCategory = cat;
             this.loading = true;

             const payload = {
                 category: cat,
                 kategori: cat,
                 fresh: true
             };

             if (cat === 'Biaya Pendaftaran') {
                 const selectedKeys = Object.keys(this.items).filter(k => this.items[k]);
                 if (selectedKeys.length > 0) {
                     payload.items = selectedKeys;
                 }
             }

             try {
                 const res = await fetch('{{ route('pmb.checkout', $prospek->id) }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                         'Accept': 'application/json'
                     },
                     body: JSON.stringify(payload)
                 });
                 const data = await res.json();
                 if (!res.ok || !data.success) {
                     throw new Error(data.message || 'Gagal memproses pembayaran.');
                 }

                 this.snapToken = data.snap_token;
                 this.orderId = data.order_id;
                 this.status = 'pending';
             } catch (e) {
                 alert(e.message || 'Terjadi kesalahan sistem saat memproses checkout.');
                 this.loading = false;
                 return;
             }

             this.loading = false;

             if (!window.snap) {
                 window.location.href = '{{ url('/pmb/bayar') }}/' + this.orderId;
                 return;
             }

             const self = this;
             window.snap.pay(this.snapToken, {
                 onSuccess: function(result) {
                     self.status = 'verified';
                     setTimeout(() => window.location.reload(), 1500);
                 },
                 onPending: function(result) {
                     self.startPolling();
                 },
                 onError: function(result) {
                     alert('Pembayaran tidak berhasil diselesaikan. Silakan coba kembali.');
                 },
                 onClose: function() {
                     self.checkStatusOnce();
                 }
             });
         },

         startPolling() {
             if (this.pollingInterval) return;
             this.pollingInterval = setInterval(async () => {
                 await this.checkStatusOnce();
             }, 3500);
         },

         async checkStatusOnce() {
             if (!this.orderId) return;
             try {
                 const res = await fetch('{{ url('/pmb/pembayaran/status') }}/' + this.orderId, {
                     headers: { 'Accept': 'application/json' }
                 });
                 if (res.ok) {
                     const data = await res.json();
                     if (data.payment_status === 'verified') {
                         this.status = 'verified';
                         if (this.pollingInterval) clearInterval(this.pollingInterval);
                         window.location.reload();
                     }
                 }
             } catch (e) {}
         }
     }">

    <!-- =================================================================== -->
    <!-- HEADER PORTAL PEMBAYARAN                                           -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-xs">
        <div class="text-center pb-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-[#0b3d82] font-display tracking-tight">
                Pembayaran Pendaftaran PMB UCIC
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-xl mx-auto">
                Kelola Pembayaran Pendaftaran, Down Payment (DP), dan Pelunasan biaya perkuliahan secara resmi, aman, dan terintegrasi.
            </p>
            <div class="w-28 h-1 bg-[#0b3d82] mx-auto rounded-full mt-3"></div>
        </div>

        <!-- Deadline PMB Box (Icon SVG profesional, tanpa emoji) -->
        <div class="bg-sky-50 rounded-2xl p-4 sm:p-5 border border-sky-200 mt-4">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-sky-100 text-[#0b3d82] flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="space-y-1 text-xs">
                    <h3 class="font-bold text-slate-900 font-display text-sm">
                        Periode Pendaftaran & Pembayaran PMB
                    </h3>
                    <p class="text-slate-700 leading-relaxed">
                        Selesaikan pembayaran tagihan sebelum: <strong class="font-semibold text-slate-900">30 Oktober 2026, 23:59 WIB</strong>
                    </p>
                    <p class="text-slate-500">
                        Pendaftar: <strong class="text-slate-800">{{ $prospek->name }}</strong> | No. Registrasi: <strong class="text-[#0b3d82] font-mono">{{ $prospek->no_registrasi ?? '-' }}</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 1. KARTU TERPISAH UNTUK 3 KATEGORI PEMBAYARAN                      -->
    <!-- (DP, Biaya Pendaftaran, Pelunasan)                                 -->
    <!-- =================================================================== -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900 font-display">Kategori Pembayaran Mahasiswa</h2>
                <p class="text-xs text-slate-500">Setiap kategori pembayaran hanya dapat dibayar satu kali.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-blue-50 text-[#0b3d82]">
                Total Tagihan: Rp {{ number_format($totalTagihan, 0, ',', '.') }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @php
                $catCards = [
                    [
                        'key'         => \App\Models\Transaksi::KATEGORI_PENDAFTARAN,
                        'title'       => 'Biaya Pendaftaran',
                        'desc'        => 'Formulir & seleksi PMB',
                        'icon'        => 'doc',
                        'info'        => $categoriesStatus[\App\Models\Transaksi::KATEGORI_PENDAFTARAN] ?? [],
                    ],
                    [
                        'key'         => \App\Models\Transaksi::KATEGORI_DP,
                        'title'       => 'Down Payment (DP)',
                        'desc'        => 'Uang muka konfirmasi PMB',
                        'icon'        => 'card',
                        'info'        => $categoriesStatus[\App\Models\Transaksi::KATEGORI_DP] ?? [],
                    ],
                    [
                        'key'         => \App\Models\Transaksi::KATEGORI_PELUNASAN,
                        'title'       => 'Pelunasan',
                        'desc'        => 'Sisa tagihan kuliah semester 1',
                        'icon'        => 'check',
                        'info'        => $categoriesStatus[\App\Models\Transaksi::KATEGORI_PELUNASAN] ?? [],
                    ],
                ];
            @endphp

            @foreach($catCards as $card)
                @php
                    $catKey = $card['key'];
                    $info = $card['info'];
                    $statusCode = $info['status_code'] ?? 'unpaid';
                    $statusLabel = $info['status'] ?? 'BELUM DIBAYAR';
                    $nominal = (int) ($info['nominal'] ?? 0);
                    $canPay = (bool) ($info['can_pay'] ?? false);
                @endphp
                <div class="bg-white rounded-3xl p-5 sm:p-6 border transition shadow-xs flex flex-col justify-between"
                     :class="activeCategory === '{{ $catKey }}' ? 'border-[#0b3d82] ring-2 ring-[#0b3d82]/20' : 'border-slate-200/90 hover:border-slate-300'">
                    
                    <div>
                        <!-- Top Bar: Icon + Status Badge -->
                        <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 {{ $statusCode === 'verified' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-[#0b3d82]' }}">
                                @if($card['icon'] === 'doc')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                @elseif($card['icon'] === 'card')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                @endif
                            </div>

                            @if($statusCode === 'verified')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>LUNAS</span>
                                </span>
                            @elseif($statusCode === 'pending')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>MENUNGGU</span>
                                </span>
                            @elseif($statusCode === 'failed')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                    <span>GAGAL/EXPIRED</span>
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                    <span>BELUM DIBAYAR</span>
                                </span>
                            @endif
                        </div>

                        <!-- Card Body -->
                        <div class="mt-3">
                            <h3 class="font-bold text-slate-900 font-display text-base">{{ $card['title'] }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $card['desc'] }}</p>

                            <div class="mt-4">
                                <span class="text-[11px] text-slate-400 block font-medium">Nominal Tagihan:</span>
                                <span class="text-xl font-bold text-slate-900 font-display tracking-tight">
                                    Rp {{ number_format($nominal, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="mt-5 pt-3 border-t border-slate-100">
                        @if($statusCode === 'verified')
                            <button type="button" disabled
                                    class="w-full py-2.5 px-3 rounded-xl text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 cursor-not-allowed flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Lunas Terverifikasi</span>
                            </button>
                        @elseif($statusCode === 'pending')
                            <button type="button"
                                    @click="selectCategory('{{ $catKey }}'); handleAction('{{ $catKey }}');"
                                    class="w-full py-2.5 px-3 rounded-xl text-xs font-bold text-amber-900 bg-amber-100 hover:bg-amber-200 border border-amber-300 transition cursor-pointer flex items-center justify-center gap-1.5 shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-amber-600 animate-pulse"></span>
                                <span>Selesaikan Pembayaran</span>
                            </button>
                        @elseif($statusCode === 'failed')
                            <button type="button"
                                    @click="selectCategory('{{ $catKey }}'); handleAction('{{ $catKey }}');"
                                    class="w-full py-2.5 px-3 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition cursor-pointer flex items-center justify-center gap-1.5 shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span>Coba Bayar Ulang</span>
                            </button>
                        @else
                            @if($catKey === \App\Models\Transaksi::KATEGORI_PELUNASAN && $nominal <= 0)
                                <button type="button" disabled
                                        class="w-full py-2.5 px-3 rounded-xl text-xs font-bold text-slate-400 bg-slate-100 cursor-not-allowed flex items-center justify-center">
                                    <span>Tagihan Nihil (Rp 0)</span>
                                </button>
                            @else
                                <button type="button"
                                        @click="selectCategory('{{ $catKey }}'); handleAction('{{ $catKey }}');"
                                        class="w-full py-2.5 px-3 rounded-xl text-xs font-bold text-white bg-[#0b3d82] hover:bg-[#082a5c] transition cursor-pointer flex items-center justify-center gap-1.5 shadow-xs">
                                    <span>Pilih & Bayar &rarr;</span>
                                </button>
                            @endif
                        @endif
                    </div>

                </div>
            @endforeach
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 2. DETAIL CHECKOUT & KOMPONEN BIAYA                                -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200/90 shadow-xs space-y-6">
        
        <div>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 font-display">
                        Checkout Pembayaran: <span class="text-[#0b3d82]" x-text="activeCategory">Biaya Pendaftaran</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Konfirmasi komponen biaya dan metode transaksi pilihan Anda.</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block font-medium">Total Pembayaran:</span>
                    <span id="total-pembayaran-display"
                          class="text-xl font-bold text-slate-900 font-display text-[#0b3d82]"
                          x-text="formatRupiah(total)">
                        Rp {{ number_format($initialTotal, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Khusus Kategori Biaya Pendaftaran: Opsi Checklist Komponen Biaya -->
        <div x-show="activeCategory === 'Biaya Pendaftaran'" class="space-y-2.5 pt-2 border-t border-slate-100">
            <span class="text-xs font-bold text-slate-700 block mb-1">Rincian Komponen Biaya Pendaftaran:</span>

            <!-- 1. Perlengkapan -->
            <label class="flex items-center justify-between p-3 rounded-2xl border border-slate-100 hover:bg-slate-50 transition cursor-pointer select-none">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="check-perlengkapan"
                           data-key="perlengkapan"
                           data-price="600000"
                           x-model="items.perlengkapan"
                           @change="onItemChange()"
                           {{ !empty($initialItems['perlengkapan']) ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] cursor-pointer">
                    <div>
                        <span class="text-sm font-semibold text-slate-800 block">Biaya Perlengkapan Kuliah</span>
                        <span class="text-[11px] text-slate-400">Jas almamater, atribut, dan paket perlengkapan mahasiswa</span>
                    </div>
                </div>
                <span class="text-sm font-semibold text-slate-700">Rp 600.000</span>
            </label>

            <!-- 2. Pendaftaran (Official PMB Fee - Rp 300.000) -->
            <label class="flex items-center justify-between p-3 rounded-2xl border border-slate-100 hover:bg-slate-50 transition cursor-pointer select-none">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="check-pendaftaran"
                           data-key="pendaftaran"
                           data-price="300000"
                           x-model="items.pendaftaran"
                           @change="onItemChange()"
                           {{ !empty($initialItems['pendaftaran']) ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] cursor-pointer">
                    <div>
                        <span class="text-sm font-bold text-slate-900 block">Biaya Pendaftaran PMB Resmi</span>
                        <span class="text-[11px] text-slate-400">Formulir pendaftaran online & ujian seleksi</span>
                    </div>
                </div>
                <span class="text-sm font-bold text-slate-900">Rp 300.000</span>
            </label>

            <!-- 3. DPP -->
            <label class="flex items-center justify-between p-3 rounded-2xl border border-slate-100 hover:bg-slate-50 transition cursor-pointer select-none">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="check-dpp"
                           data-key="dpp"
                           data-price="500000"
                           x-model="items.dpp"
                           @change="onItemChange()"
                           {{ !empty($initialItems['dpp']) ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] cursor-pointer">
                    <div>
                        <span class="text-sm font-semibold text-slate-800 block">DPP (Dana Pengembangan Pendidikan)</span>
                        <span class="text-[11px] text-slate-400">Termin awal pengembangan sarana prasarana</span>
                    </div>
                </div>
                <span class="text-sm font-semibold text-slate-700">Rp 500.000</span>
            </label>
        </div>

        <!-- Section: Pilih Metode Pembayaran -->
        <div class="pt-4 border-t border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 font-display mb-3">
                Pilih Metode Pembayaran
            </h3>

            <div class="space-y-3">
                <!-- Option 1: Bayar Online (Midtrans Snap) -->
                <label class="block p-4 sm:p-5 rounded-2xl border transition cursor-pointer"
                       :class="selectedMethod === 'online' ? 'border-[#0b3d82] bg-blue-50/20 ring-1 ring-[#0b3d82]' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <div class="flex items-start gap-3.5">
                        <input type="radio"
                               name="payment_method"
                               value="online"
                               x-model="selectedMethod"
                               class="w-4 h-4 mt-0.5 text-[#0b3d82] focus:ring-[#0b3d82] border-slate-300 cursor-pointer">
                        <div class="space-y-1">
                            <span class="text-sm font-bold text-slate-900 block font-display">
                                Bayar Online Otomatis (Midtrans Snap)
                            </span>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Pembayaran terverifikasi otomatis via Virtual Account (BCA, Mandiri, BRI, BNI), QRIS, GoPay, ShopeePay, dan Kartu Kredit.
                            </p>
                            <div class="pt-2 flex flex-wrap items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-[#0b3d82]">Midtrans Snap</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">QRIS</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">BCA VA</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">BRI VA</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">Mandiri VA</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">GoPay</span>
                            </div>
                        </div>
                    </div>
                </label>

                <!-- Option 2: Transfer Manual Rekening Kampus (Dynamic dari Database) -->
                <label class="block p-4 sm:p-5 rounded-2xl border transition cursor-pointer"
                       :class="selectedMethod === 'transfer_manual' ? 'border-[#0b3d82] bg-blue-50/20 ring-1 ring-[#0b3d82]' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <div class="flex items-start gap-3.5">
                        <input type="radio"
                               name="payment_method"
                               value="transfer_manual"
                               x-model="selectedMethod"
                               class="w-4 h-4 mt-0.5 text-[#0b3d82] focus:ring-[#0b3d82] border-slate-300 cursor-pointer">
                        <div class="space-y-1 w-full">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-900 block font-display">
                                    Transfer Manual ke Rekening Bank Kampus
                                </span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700">Verifikasi CS</span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Transfer manual via ATM/M-Banking langsung ke rekening resmi yayasan/universitas.
                            </p>

                            <!-- Daftar Rekening Aktif dari Database -->
                            <div x-show="selectedMethod === 'transfer_manual'" x-cloak class="pt-3 space-y-2 mt-2">
                                <p class="text-xs font-semibold text-slate-800">Daftar Rekening Resmi PMB UCIC (Aktif):</p>
                                
                                @forelse($bankAccounts as $bank)
                                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div>
                                            <span class="font-bold text-slate-900 block text-sm">{{ $bank->bank_name }}</span>
                                            <span class="text-slate-500 text-[11px]">A/N: <strong class="text-slate-700">{{ $bank->account_name }}</strong></span>
                                            @if($bank->notes)
                                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $bank->notes }}</p>
                                            @endif
                                        </div>
                                        <div class="sm:text-right">
                                            <span class="text-[#0b3d82] font-mono font-bold text-sm select-all">{{ $bank->account_number }}</span>
                                            <span class="text-[10px] text-slate-400 block">Salin nomor rekening</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800">
                                        Belum ada rekening bank yang diaktifkan oleh Admin PMB. Silakan gunakan metode online atau hubungi helpdesk PMB.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </label>

                <!-- Option 3: Bayar Offline (Datang Langsung) -->
                <label class="block p-4 sm:p-5 rounded-2xl border transition cursor-pointer"
                       :class="selectedMethod === 'offline' ? 'border-[#0b3d82] bg-blue-50/20 ring-1 ring-[#0b3d82]' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <div class="flex items-start gap-3.5">
                        <input type="radio"
                               name="payment_method"
                               value="offline"
                               x-model="selectedMethod"
                               class="w-4 h-4 mt-0.5 text-[#0b3d82] focus:ring-[#0b3d82] border-slate-300 cursor-pointer">
                        <div class="space-y-1">
                            <span class="text-sm font-bold text-slate-900 block font-display">
                                Bayar Offline di Kasir PMB UCIC
                            </span>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Bayar langsung secara tunai atau debit di Ruang Customer Service PMB Gedung Utama Lantai 1, Jl. Kesambi No. 202 Kota Cirebon.
                            </p>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Tombol Aksi Pembayaran -->
        <div class="pt-4 border-t border-slate-100">
            <template x-if="categoryStatuses[activeCategory] === 'verified'">
                <button type="button" disabled
                        class="w-full py-4 px-6 rounded-2xl font-bold text-sm text-white bg-emerald-600 transition flex items-center justify-center gap-2 cursor-not-allowed shadow-xs">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Kategori Ini Sudah Lunas Terverifikasi &rarr;</span>
                </button>
            </template>

            <template x-if="categoryStatuses[activeCategory] !== 'verified'">
                <button type="button"
                        id="btn-lanjut-bayar"
                        @click="handleAction()"
                        :disabled="loading || total === 0"
                        class="w-full py-4 px-6 rounded-2xl font-bold text-sm transition flex items-center justify-center gap-2 cursor-pointer shadow-sm"
                        :class="total === 0 ? 'bg-slate-300 text-slate-500 cursor-not-allowed' : 'bg-[#0b3d82] hover:bg-[#082a5c] text-white active:scale-[0.99]'">
                    <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span id="btn-lanjut-bayar-text" x-text="loading ? 'Menghubungkan Midtrans...' : (total === 0 ? 'Pilih minimal 1 komponen biaya' : ('Lanjutkan Pembayaran ' + activeCategory + ' - ' + formatRupiah(total)))">
                        Lanjutkan Pembayaran - Rp {{ number_format($initialTotal, 0, ',', '.') }}
                    </span>
                </button>
            </template>

            <!-- Order info & security notice -->
            <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between text-xs text-slate-500 gap-2">
                <span>Order ID Aktif: <strong class="text-slate-800" x-text="orderId || '-'">{{ $transaksi?->order_id ?? '-' }}</strong></span>
                <span class="inline-flex items-center gap-1.5 text-blue-700 font-semibold">
                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Terhubung Resmi dengan Gerbang Pembayaran Midtrans Snap</span>
                </span>
            </div>
        </div>

    </div>

    <!-- =================================================================== -->
    <!-- 3. KARTU RIWAYAT TRANSAKSI LENGKAP                                 -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200/90 shadow-xs">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h2 class="text-xl font-bold text-[#0b3d82] font-display tracking-tight">
                    Riwayat Pembayaran PMB
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Catatan seluruh transaksi yang terhubung dengan akun Anda</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                {{ $riwayatTransaksi->count() }} Transaksi Tercatat
            </span>
        </div>

        @if($riwayatTransaksi->isEmpty())
            <div class="py-12 text-center text-slate-500 text-sm">
                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Belum ada riwayat pembayaran yang tercatat.
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($riwayatTransaksi as $tx)
                    <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900 text-sm block font-display">
                                    {{ $tx->detail_pembayaran ?: ($tx->kategori ?: 'Biaya PMB') }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-[#0b3d82]">
                                    {{ $tx->kategori ?? 'Pendaftaran' }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-slate-500 text-[11px]">
                                <span>Order ID: <strong class="text-slate-800 font-mono">{{ $tx->order_id ?: ('TRX-' . $tx->id) }}</strong></span>
                                <span>•</span>
                                <span>{{ $tx->created_at->format('d M Y, H:i') }} WIB</span>
                                <span>•</span>
                                <span>Metode: <strong class="text-slate-700">{{ $tx->payment_type ?: ($tx->metode_pembayaran ?: 'Midtrans Snap') }}</strong></span>
                            </div>
                        </div>

                        <div class="sm:text-right shrink-0">
                            <span class="font-bold text-slate-900 block font-display text-base">
                                Rp {{ number_format($tx->nominal, 0, ',', '.') }}
                            </span>
                            <div class="mt-1">
                                @if($tx->payment_status === \App\Models\Transaksi::STATUS_VERIFIED)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span>Lunas (Verified)</span>
                                    </span>
                                @elseif($tx->payment_status === \App\Models\Transaksi::STATUS_REJECTED)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                        Ditolak / Gagal
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span>Menunggu Pembayaran</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Modal Konfirmasi Transfer Manual (Dynamic Bank Accounts dari Database) -->
    <div x-show="manualModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-xl space-y-4" @click.away="manualModalOpen = false">
            <div class="text-center">
                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-[#0b3d82] flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 font-display">Instruksi Transfer Manual</h3>
                <p class="text-xs text-slate-500 mt-1">Silakan transfer nominal tagihan ke salah satu rekening resmi kampus UCIC berikut:</p>
            </div>

            <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                @forelse($bankAccounts as $bank)
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-1.5 text-xs">
                        <div class="flex justify-between items-center pb-1.5 border-b border-slate-200">
                            <span class="text-slate-500">Bank Tujuan</span>
                            <strong class="text-slate-900 font-bold">{{ $bank->bank_name }}</strong>
                        </div>
                        <div class="flex justify-between items-center pb-1.5 border-b border-slate-200">
                            <span class="text-slate-500">Nomor Rekening</span>
                            <strong class="text-[#0b3d82] font-mono font-bold text-sm select-all">{{ $bank->account_number }}</strong>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Atas Nama</span>
                            <strong class="text-slate-900 font-bold">{{ $bank->account_name }}</strong>
                        </div>
                        @if($bank->notes)
                            <p class="text-[10px] text-slate-500 pt-1 border-t border-slate-200">{{ $bank->notes }}</p>
                        @endif
                    </div>
                @empty
                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-800 text-center">
                        Belum ada data rekening bank resmi yang aktif. Silakan hubungi admin PMB.
                    </div>
                @endforelse
            </div>

            <div class="bg-blue-50 p-3 rounded-xl border border-blue-200 text-xs text-[#0b3d82] flex justify-between items-center">
                <span>Nominal yang Harus Ditransfer:</span>
                <strong class="text-base font-bold" x-text="formatRupiah(total)"></strong>
            </div>

            <p class="text-[11px] text-slate-500 text-center">
                Setelah transfer berhasil, kirimkan bukti transfer ke WhatsApp resmi Admin PMB untuk diverifikasi oleh staf CS.
            </p>

            <div class="flex items-center gap-2 pt-2">
                <button type="button" @click="manualModalOpen = false" class="flex-1 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer">
                    Tutup
                </button>
                <a :href="'https://api.whatsapp.com/send/?phone=6281216700519&text=Halo%20Admin%20PMB%20UCIC%2C%20saya%20sudah%20transfer%20manual%20sebesar%20' + formatRupiah(total) + '%20untuk%20' + activeCategory + '%20(No%20Reg%3A%20{{ $prospek->no_registrasi ?? '-' }}%2C%20Order%20ID%3A%20' + (orderId || '-') + ').%20Berikut%20bukti%20transfernya%3A'"
                   target="_blank"
                   class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white text-center shadow-xs cursor-pointer">
                    Konfirmasi WhatsApp
                </a>
            </div>
        </div>
    </div>

    <!-- Modal Panduan Bayar Offline -->
    <div x-show="offlineModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-xl space-y-4" @click.away="offlineModalOpen = false">
            <div class="text-center">
                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-[#0b3d82] flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 font-display">Panduan Bayar di Kasir PMB</h3>
                <p class="text-xs text-slate-500 mt-1">Datang langsung ke gedung pendaftaran kampus UCIC:</p>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-2 text-xs">
                <div>
                    <span class="text-slate-500 block">Alamat Kampus:</span>
                    <strong class="text-slate-900 font-bold block mt-0.5">Jl. Kesambi No. 202, Drajat, Kec. Kesambi, Kota Cirebon, Jawa Barat 45133</strong>
                </div>
                <div class="pt-2 border-t border-slate-200">
                    <span class="text-slate-500 block">Lokasi Loket:</span>
                    <strong class="text-slate-900 block mt-0.5">Ruang Customer Service PMB, Gedung Utama Lantai 1</strong>
                </div>
                <div class="pt-2 border-t border-slate-200">
                    <span class="text-slate-500 block">Jam Pelayanan:</span>
                    <strong class="text-slate-900 block mt-0.5">Senin - Jumat (08:00 - 17:00 WIB), Sabtu (08:00 - 14:00 WIB)</strong>
                </div>
                <div class="pt-2 border-t border-slate-200">
                    <span class="text-slate-500 block">Persyaratan:</span>
                    <span class="text-slate-700 block mt-0.5">Sebutkan No. Registrasi: <strong class="text-[#0b3d82]">{{ $prospek->no_registrasi ?? '-' }}</strong> atau Order ID: <strong class="text-slate-900" x-text="orderId || '-'"></strong></span>
                </div>
            </div>

            <button type="button" @click="offlineModalOpen = false" class="w-full py-2.5 rounded-xl bg-[#0b3d82] text-xs font-bold text-white hover:bg-[#082a5c] cursor-pointer">
                Saya Mengerti
            </button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Fallback kalkulasi total pendaftaran instan saat checkbox diklik
    (function() {
        function calculateAndSync() {
            var checkboxes = document.querySelectorAll('.fee-checkbox');
            var totalDisplay = document.getElementById('total-pembayaran-display');
            var btnText = document.getElementById('btn-lanjut-bayar-text');
            var btnBayar = document.getElementById('btn-lanjut-bayar');

            var sum = 0;
            checkboxes.forEach(function(cb) {
                if (cb.checked) {
                    sum += parseInt(cb.getAttribute('data-price') || '0', 10);
                }
            });

            var formatted = 'Rp ' + sum.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            if (totalDisplay) {
                totalDisplay.textContent = formatted;
            }
            if (btnText && !btnText.textContent.includes('Menghubungkan')) {
                btnText.textContent = sum === 0 ? 'Pilih minimal 1 komponen biaya' : ('Lanjutkan Pembayaran - ' + formatted);
            }
            if (btnBayar) {
                if (sum === 0) {
                    btnBayar.disabled = true;
                    btnBayar.classList.add('bg-slate-300', 'text-slate-500', 'cursor-not-allowed');
                    btnBayar.classList.remove('bg-[#0b3d82]', 'hover:bg-[#082a5c]', 'text-white');
                } else {
                    btnBayar.disabled = false;
                    btnBayar.classList.remove('bg-slate-300', 'text-slate-500', 'cursor-not-allowed');
                    btnBayar.classList.add('bg-[#0b3d82]', 'hover:bg-[#082a5c]', 'text-white');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var checkboxes = document.querySelectorAll('.fee-checkbox');
            checkboxes.forEach(function(cb) {
                cb.addEventListener('change', calculateAndSync);
            });
        });
    })();
</script>
@endpush
