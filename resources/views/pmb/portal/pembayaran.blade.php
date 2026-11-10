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
    <style>
        #snap-container {
            width: 100% !important;
            min-height: 560px !important;
            display: flex;
            justify-content: center;
        }
        #snap-container iframe {
            width: 100% !important;
            min-height: 560px !important;
            border-radius: 1rem;
        }
        iframe#snap-midtrans {
            z-index: 999999 !important;
        }
    </style>
@endpush

@php
    $prodi = $prospek?->prodi;
    $catalog = \App\Services\MidtransService::getBiayaCatalog($prospek ?? null);
    $pricePerlengkapan = (int) ($catalog['perlengkapan']['price'] ?? 600000);
    $pricePendaftaran  = (int) ($catalog['pendaftaran']['price'] ?? 300000);
    $priceDpp          = (int) ($catalog['dpp']['price'] ?? 500000);
    $priceUangGedung   = $prodi && isset($prodi->biaya_uang_gedung) ? (int) $prodi->biaya_uang_gedung : 0;

    $paidItems = $paidItems ?? (\App\Services\MidtransService::resolvePaidItems($prospek ?? null));
    $initialItems = $initialItems ?? ($transaksi ? \App\Services\MidtransService::resolveInitialItems($transaksi) : ['pendaftaran' => true, 'perlengkapan' => false, 'dpp' => false]);
    foreach ($paidItems as $_k => $_p) {
        if ($_p) {
            $initialItems[$_k] = false;
        }
    }
    $initialTotal = $initialTotal ?? ($transaksi ? ((int) $transaksi->nominal ?: $pricePendaftaran) : $pricePendaftaran);
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
         snapEmbedModalOpen: false,
         manualModalOpen: false,
         detailPendingModalOpen: false,
         selectedPending: {
             id: null,
             orderId: '',
             kategori: '',
             detail: '',
             nominal: 0,
             nominalFormatted: '',
             tanggal: '',
             metode: '',
             snapToken: ''
         },
         selectedBankId: {{ $bankAccounts->first()?->id ?? 0 }},
         paidItems: {
             perlengkapan: {{ !empty($paidItems['perlengkapan']) ? 'true' : 'false' }},
             pendaftaran: {{ !empty($paidItems['pendaftaran']) ? 'true' : 'false' }},
             dpp: {{ !empty($paidItems['dpp']) ? 'true' : 'false' }}
         },
         items: {
             perlengkapan: {{ (!empty($initialItems['perlengkapan']) && empty($paidItems['perlengkapan'])) ? 'true' : 'false' }},
             pendaftaran: {{ (!empty($initialItems['pendaftaran']) && empty($paidItems['pendaftaran'])) ? 'true' : 'false' }},
             dpp: {{ (!empty($initialItems['dpp']) && empty($paidItems['dpp'])) ? 'true' : 'false' }}
         },
         prices: {
             perlengkapan: {{ $pricePerlengkapan }},
             pendaftaran: {{ $pricePendaftaran }},
             dpp: {{ $priceDpp }}
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
                 if (this.items.perlengkapan && !this.paidItems.perlengkapan) sum += this.prices.perlengkapan;
                 if (this.items.pendaftaran && !this.paidItems.pendaftaran) sum += this.prices.pendaftaran;
                 if (this.items.dpp && !this.paidItems.dpp) sum += this.prices.dpp;
                 return sum;
             }
             return this.categoryNominals[this.activeCategory] || 0;
         },
         formatRupiah(num) {
             num = Number(num) || 0;
             return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
         },
         copiedId: null,
         copyText(text, id) {
             if (!text) return;
             if (navigator.clipboard && window.isSecureContext) {
                 navigator.clipboard.writeText(text);
             } else {
                 const textArea = document.createElement('textarea');
                 textArea.value = text;
                 textArea.style.position = 'fixed';
                 textArea.style.left = '-999999px';
                 textArea.style.top = '-999999px';
                 document.body.appendChild(textArea);
                 textArea.focus();
                 textArea.select();
                 document.execCommand('copy');
                 textArea.remove();
             }
             this.copiedId = id;
             setTimeout(() => {
                 if (this.copiedId === id) this.copiedId = null;
             }, 2000);
         },
         selectCategory(cat) {
             this.activeCategory = cat;
             this.snapToken = null;
         },
         onItemChange() {
             if (this.paidItems.perlengkapan) this.items.perlengkapan = false;
             if (this.paidItems.pendaftaran) this.items.pendaftaran = false;
             if (this.paidItems.dpp) this.items.dpp = false;
             this.snapToken = null;
         },

         openDetailPending(item) {
             this.selectedPending = item;
             this.detailPendingModalOpen = true;
         },

         openSnapPay(token, orderId) {
             const targetOrderId = orderId || this.orderId;
             const targetToken = token || (targetOrderId === this.orderId ? this.snapToken : '');

             if (targetOrderId) {
                 this.orderId = targetOrderId;
             }
             if (targetToken) {
                 this.snapToken = targetToken;
             }

             if (!targetToken && targetOrderId) {
                 window.location.href = '{{ url('/pmb/bayar') }}/' + targetOrderId;
                 return;
             }

             if (!targetToken) {
                 this.payCategoryNow(this.activeCategory);
                 return;
             }

             if (!window.snap) {
                 window.location.href = '{{ url('/pmb/bayar') }}/' + targetOrderId;
                 return;
             }

             this.showSnapModal(targetToken);
         },

         showSnapModal(token) {
             const targetToken = token || this.snapToken;
             if (!targetToken) return;

             this.snapToken = targetToken;
             this.snapEmbedModalOpen = true;

             this.$nextTick(() => {
                 const container = document.getElementById('snap-container');
                 if (container) {
                     container.innerHTML = '';
                 }

                 const self = this;
                 if (window.snap && typeof window.snap.embed === 'function') {
                     try {
                         window.snap.embed(targetToken, {
                             embedId: 'snap-container',
                             onSuccess: function(result) {
                                 self.snapEmbedModalOpen = false;
                                 self.status = 'verified';
                                 setTimeout(() => window.location.reload(), 1500);
                             },
                             onPending: function(result) {
                                 self.startPolling();
                             },
                             onError: function(result) {
                                 alert('Pembayaran belum berhasil diselesaikan. Silakan coba kembali.');
                             },
                             onClose: function() {
                                 self.closeSnapModal();
                             }
                         });
                         return;
                     } catch (err) {
                         console.warn('Snap embed fallback to pay:', err);
                     }
                 }

                 if (window.snap && typeof window.snap.pay === 'function') {
                     self.snapEmbedModalOpen = false;
                     window.snap.pay(targetToken, {
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
                 }
             });
         },

         closeSnapModal() {
             this.snapEmbedModalOpen = false;
             if (window.snap && typeof window.snap.hide === 'function') {
                 try { window.snap.hide(); } catch (e) {}
             }
             const container = document.getElementById('snap-container');
             if (container) {
                 container.innerHTML = '';
             }
             this.checkStatusOnce();
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

             await this.payCategoryNow(targetCat);
         },

         async payCategoryNow(cat) {
             this.activeCategory = cat;
             this.loading = true;

             const payload = {
                 category: cat,
                 kategori: cat,
                 payment_method: this.selectedMethod,
                 fresh: false
             };

             if (cat === 'Biaya Pendaftaran') {
                 const selectedKeys = Object.keys(this.items).filter(k => this.items[k] && !this.paidItems[k]);
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
                     if (res.status === 404) {
                         alert(data.message || 'Sesi data pendaftaran telah diperbarui. Halaman akan dimuat ulang.');
                         window.location.reload();
                         return;
                     }
                     throw new Error(data.message || 'Gagal memproses pembayaran.');
                 }

                 this.orderId = data.order_id;
                 this.status = 'pending';

                 if (this.selectedMethod === 'transfer_manual') {
                     this.loading = false;
                     this.manualModalOpen = true;
                     return;
                 }

                 this.snapToken = data.snap_token;
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

             this.showSnapModal(this.snapToken);
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

                            @if($statusCode === 'verified' || ($catKey === \App\Models\Transaksi::KATEGORI_PENDAFTARAN && $nominal <= 0))
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>LUNAS</span>
                                </span>
                            @elseif($statusCode === 'pending' && $catKey !== \App\Models\Transaksi::KATEGORI_PENDAFTARAN)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>MENUNGGU</span>
                                </span>
                            @elseif($statusCode === 'failed')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                    <span>GAGAL/EXPIRED</span>
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    <span>{{ strtoupper($statusLabel ?? 'BELUM LUNAS') }}</span>
                                </span>
                            @endif
                        </div>

                        <!-- Card Body -->
                        <div class="mt-3">
                            <h3 class="font-bold text-slate-900 font-display text-base">{{ $card['title'] }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $card['desc'] }}</p>

                            @if($catKey === \App\Models\Transaksi::KATEGORI_PENDAFTARAN)
                                <div class="mt-3.5 space-y-1.5 pt-2.5 border-t border-slate-100 text-xs">
                                    <div class="flex items-center justify-between text-slate-500">
                                        <span>Total Tagihan:</span>
                                        <span class="font-semibold text-slate-800">Rp {{ number_format($info['total_tagihan'] ?? 1400000, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-slate-500">
                                        <span>Total Sudah Dibayar:</span>
                                        <span class="font-semibold text-emerald-600">Rp {{ number_format($info['sudah_dibayar'] ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between pt-1.5 border-t border-slate-100">
                                        <span class="font-bold text-slate-700">Sisa Tagihan:</span>
                                        <span class="text-xl font-bold font-display tracking-tight text-[#0b3d82]">
                                            Rp {{ number_format($info['sisa_tagihan'] ?? $nominal, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <div class="mt-4">
                                    <span class="text-[11px] text-slate-400 block font-medium">Nominal Tagihan:</span>
                                    <span class="text-xl font-bold text-slate-900 font-display tracking-tight">
                                        Rp {{ number_format($nominal, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="mt-5 pt-3 border-t border-slate-100">
                        @if($statusCode === 'verified' || ($catKey === \App\Models\Transaksi::KATEGORI_PENDAFTARAN && $nominal <= 0))
                            <button type="button" disabled
                                    class="w-full py-2.5 px-3 rounded-xl text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 cursor-not-allowed flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Lunas Terverifikasi</span>
                            </button>
                        @elseif($statusCode === 'pending' && $catKey !== \App\Models\Transaksi::KATEGORI_PENDAFTARAN)
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
                                        @click="selectCategory('{{ $catKey }}'); document.getElementById('checkout-section')?.scrollIntoView({ behavior: 'smooth' });"
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
    <div id="checkout-section" class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200/90 shadow-xs space-y-6">
        
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
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 block mb-1">Rincian Komponen Biaya Pendaftaran:</span>
                <span class="text-[11px] text-slate-400">Pilih komponen yang ingin dibayar</span>
            </div>

            <!-- 1. Perlengkapan -->
            <label class="flex items-center justify-between p-3 rounded-2xl border transition select-none"
                   :class="paidItems.perlengkapan ? 'bg-slate-50/80 border-slate-200 cursor-not-allowed opacity-85' : 'border-slate-100 hover:bg-slate-50 cursor-pointer'"
                   @click="if (paidItems.perlengkapan) { $event.preventDefault(); return false; }">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="check-perlengkapan"
                           data-key="perlengkapan"
                           data-price="{{ $pricePerlengkapan }}"
                           x-model="items.perlengkapan"
                           @change="onItemChange()"
                           :disabled="paidItems.perlengkapan"
                           {{ !empty($paidItems['perlengkapan']) ? 'disabled' : '' }}
                           {{ (!empty($initialItems['perlengkapan']) && empty($paidItems['perlengkapan'])) ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:border-slate-300 disabled:text-slate-400">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-semibold text-slate-800 block">Biaya Perlengkapan Kuliah</span>
                            <template x-if="paidItems.perlengkapan">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Sudah Dibayar (Lunas)
                                </span>
                            </template>
                            @if(!empty($paidItems['perlengkapan']))
                                <span x-show="!$data" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    ✓ Sudah Dibayar (Lunas)
                                </span>
                            @endif
                        </div>
                        <span class="text-[11px] text-slate-400">Jas almamater, atribut, dan paket perlengkapan mahasiswa</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-sm font-semibold text-slate-700" :class="paidItems.perlengkapan ? 'line-through text-slate-400' : ''">
                        Rp {{ number_format($pricePerlengkapan, 0, ',', '.') }}
                    </span>
                </div>
            </label>

            <!-- 2. Pendaftaran (Official PMB Fee) -->
            <label class="flex items-center justify-between p-3 rounded-2xl border transition select-none"
                   :class="paidItems.pendaftaran ? 'bg-slate-50/80 border-slate-200 cursor-not-allowed opacity-85' : 'border-slate-100 hover:bg-slate-50 cursor-pointer'"
                   @click="if (paidItems.pendaftaran) { $event.preventDefault(); return false; }">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="check-pendaftaran"
                           data-key="pendaftaran"
                           data-price="{{ $pricePendaftaran }}"
                           x-model="items.pendaftaran"
                           @change="onItemChange()"
                           :disabled="paidItems.pendaftaran"
                           {{ !empty($paidItems['pendaftaran']) ? 'disabled' : '' }}
                           {{ (!empty($initialItems['pendaftaran']) && empty($paidItems['pendaftaran'])) ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:border-slate-300 disabled:text-slate-400">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-bold text-slate-900 block">Biaya Pendaftaran PMB Resmi</span>
                            <template x-if="paidItems.pendaftaran">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Sudah Dibayar (Lunas)
                                </span>
                            </template>
                            @if(!empty($paidItems['pendaftaran']))
                                <span x-show="!$data" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    ✓ Sudah Dibayar (Lunas)
                                </span>
                            @endif
                        </div>
                        <span class="text-[11px] text-slate-400">Formulir pendaftaran online & ujian seleksi</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-sm font-bold text-slate-900" :class="paidItems.pendaftaran ? 'line-through text-slate-400' : ''">
                        Rp {{ number_format($pricePendaftaran, 0, ',', '.') }}
                    </span>
                </div>
            </label>

            <!-- 3. DPP -->
            <label class="flex items-center justify-between p-3 rounded-2xl border transition select-none"
                   :class="paidItems.dpp ? 'bg-slate-50/80 border-slate-200 cursor-not-allowed opacity-85' : 'border-slate-100 hover:bg-slate-50 cursor-pointer'"
                   @click="if (paidItems.dpp) { $event.preventDefault(); return false; }">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="check-dpp"
                           data-key="dpp"
                           data-price="{{ $priceDpp }}"
                           x-model="items.dpp"
                           @change="onItemChange()"
                           :disabled="paidItems.dpp"
                           {{ !empty($paidItems['dpp']) ? 'disabled' : '' }}
                           {{ (!empty($initialItems['dpp']) && empty($paidItems['dpp'])) ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:border-slate-300 disabled:text-slate-400">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-semibold text-slate-800 block">DPP (Dana Pengembangan Pendidikan)</span>
                            <template x-if="paidItems.dpp">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Sudah Dibayar (Lunas)
                                </span>
                            </template>
                            @if(!empty($paidItems['dpp']))
                                <span x-show="!$data" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    ✓ Sudah Dibayar (Lunas)
                                </span>
                            @endif
                        </div>
                        <span class="text-[11px] text-slate-400">Termin awal pengembangan sarana prasarana</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-sm font-semibold text-slate-700" :class="paidItems.dpp ? 'line-through text-slate-400' : ''">
                        Rp {{ number_format($priceDpp, 0, ',', '.') }}
                    </span>
                </div>
            </label>
        </div>

        <!-- Kategori Down Payment (DP): Ringkasan Tagihan -->
        <div x-show="activeCategory === 'DP'" class="pt-2 border-t border-slate-100">
            <div class="p-4 rounded-2xl bg-blue-50/50 border border-blue-100 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-slate-800">Down Payment (DP)</h4>
                    <p class="text-xs text-slate-500 mt-0.5">Uang muka konfirmasi pendaftaran mahasiswa baru PMB UCIC</p>
                </div>
                <div class="text-right">
                    <span class="text-base font-bold text-[#0b3d82] font-display">Rp 500.000</span>
                </div>
            </div>
        </div>

        <!-- Kategori Pelunasan: Ringkasan Tagihan -->
        <div x-show="activeCategory === 'Pelunasan'" class="pt-2 border-t border-slate-100">
            <div class="p-4 rounded-2xl bg-blue-50/50 border border-blue-100 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-slate-800">Pelunasan Biaya Kuliah</h4>
                    <p class="text-xs text-slate-500 mt-0.5">Sisa tagihan perkuliahan semester 1</p>
                </div>
                <div class="text-right">
                    <span class="text-base font-bold text-[#0b3d82] font-display" x-text="formatRupiah(total)">
                        Rp {{ number_format($sisaPelunasan, 0, ',', '.') }}
                    </span>
                </div>
            </div>
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
                        <div class="space-y-1 w-full">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-900 block font-display">
                                    Bayar Online Otomatis (Midtrans Snap)
                                </span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-blue-50 text-[#0b3d82] border border-blue-200">
                                    Verifikasi Otomatis
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                @php
                                    $activeBankNames = $bankAccounts->map(function($b) {
                                        return preg_replace('/^(Bank|PT\.?\s*Bank)\s+/i', '', $b->bank_name);
                                    })->unique()->values();
                                    $vaListText = $activeBankNames->isNotEmpty() 
                                        ? $activeBankNames->implode(', ') 
                                        : 'Rekening Bank Resmi';
                                @endphp
                                Pembayaran terverifikasi otomatis via Virtual Account ({{ $vaListText }}).
                            </p>
                            <div class="pt-2 flex flex-wrap items-center gap-1.5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#0b3d82] text-white shadow-2xs flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    Midtrans Snap
                                </span>

                                @forelse($bankAccounts as $bank)
                                    @php
                                        $cleanBankName = preg_replace('/^(Bank|PT\.?\s*Bank)\s+/i', '', $bank->bank_name);
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-blue-50 text-[#0b3d82] border border-blue-200 flex items-center gap-1">
                                        <svg class="w-2.5 h-2.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                        {{ $cleanBankName }} VA
                                    </span>
                                @empty
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-500">
                                        Belum ada rekening aktif di Admin
                                    </span>
                                @endforelse
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

                            <!-- Daftar Rekening Aktif dari Database (UI Selaras Master Rekening Bank Admin) -->
                            <div x-show="selectedMethod === 'transfer_manual'" x-cloak class="pt-3 space-y-2 mt-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-bold text-slate-800">Daftar Rekening Resmi PMB UCIC (Aktif):</p>
                                    <span class="text-[10px] text-slate-500">{{ $bankAccounts->count() }} Rekening Terdaftar</span>
                                </div>
                                
                                @forelse($bankAccounts as $bank)
                                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs hover:border-blue-300 transition text-xs space-y-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-[11px] border border-purple-100 shrink-0">
                                                    {{ strtoupper(substr($bank->bank_name, 0, 3)) }}
                                                </div>
                                                <div>
                                                    <span class="font-extrabold text-slate-900 block text-xs">{{ $bank->bank_name }}</span>
                                                    <span class="text-slate-500 text-[11px]">A/N: <strong class="text-slate-800">{{ $bank->account_name }}</strong></span>
                                                </div>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                                Aktif
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between bg-slate-50/90 px-3 py-2 rounded-xl border border-slate-100">
                                            <span class="text-[#0b3d82] font-mono font-bold text-sm tracking-wide select-all">{{ $bank->account_number }}</span>
                                            <button 
                                                type="button" 
                                                @click="copyText('{{ $bank->account_number }}', 'opt2_{{ $bank->id }}')" 
                                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                                                :class="copiedId === 'opt2_{{ $bank->id }}' ? 'bg-emerald-600 text-white' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs'"
                                                title="Salin nomor rekening"
                                            >
                                                <template x-if="copiedId === 'opt2_{{ $bank->id }}'">
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                        Tersalin!
                                                    </span>
                                                </template>
                                                <template x-if="copiedId !== 'opt2_{{ $bank->id }}'">
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                        Salin
                                                    </span>
                                                </template>
                                            </button>
                                        </div>

                                        @if($bank->notes)
                                            <p class="text-[10px] text-slate-400 pl-1">{{ $bank->notes }}</p>
                                        @endif
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
                <div class="flex items-center gap-2 flex-wrap">
                    <span>Order ID Aktif: <strong class="text-slate-800" x-text="orderId || '-'">{{ $transaksi?->order_id ?? '-' }}</strong></span>
                    <template x-if="status !== 'verified' && snapToken">
                        <button type="button"
                                @click="openSnapPay(snapToken, orderId)"
                                class="text-xs font-bold text-[#0b3d82] hover:underline cursor-pointer inline-flex items-center gap-1 bg-blue-50 px-2 py-0.5 rounded-md border border-blue-200">
                            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>Buka Form Bayar</span>
                        </button>
                    </template>
                </div>
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
                                <span>Metode: <strong class="text-slate-700">{{ $tx->metode_detail }}</strong></span>
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

                            @if($tx->payment_status !== \App\Models\Transaksi::STATUS_VERIFIED && $tx->payment_status !== \App\Models\Transaksi::STATUS_REJECTED)
                                <div class="mt-2.5 flex flex-wrap sm:justify-end items-center gap-1.5">
                                    <button type="button"
                                            @click="payCategoryNow('{{ $tx->kategori ?: $tx->jenis }}')"
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-[#0b3d82] hover:bg-[#082a5c] transition cursor-pointer flex items-center gap-1 shadow-2xs">
                                        <svg class="w-3.5 h-3.5 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                        <span>Bayar Sekarang</span>
                                    </button>

                                    <button type="button"
                                            @click="openDetailPending({
                                                id: {{ $tx->id }},
                                                orderId: '{{ $tx->order_id ?: ('TRX-' . $tx->id) }}',
                                                kategori: '{{ $tx->kategori ?? 'Pendaftaran' }}',
                                                detail: '{{ addslashes($tx->detail_pembayaran ?: ($tx->kategori ?: 'Biaya PMB')) }}',
                                                nominal: {{ (int) $tx->nominal }},
                                                nominalFormatted: 'Rp {{ number_format($tx->nominal, 0, ',', '.') }}',
                                                tanggal: '{{ $tx->created_at->format('d M Y, H:i') }} WIB',
                                                metode: '{{ $txMetodeLabel }}',
                                                snapToken: '{{ $tx->snap_token ?? '' }}'
                                            })"
                                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition cursor-pointer flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>Lihat Rekening</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Modal Snap Embed: Form Pembayaran Proporsional, Luas & Rapi (Anti-Memanjang) -->
    <div x-show="snapEmbedModalOpen" x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-900/60 backdrop-blur-xs transition-opacity"
         style="z-index: 99999;">
        <div class="bg-white rounded-3xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col my-auto transition-all"
             style="max-height: 92vh;"
             @click.away="closeSnapModal()">
            <!-- Top Bar Dialog -->
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0b3d82] flex items-center justify-center font-bold text-xs">
                        PMB
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 font-display">Pilih Metode Pembayaran UCIC</h4>
                        <p class="text-[10px] text-slate-500">Virtual Account (BCA, Mandiri, BNI, BRI), QRIS, GoPay & Kartu Kredit</p>
                    </div>
                </div>
                <button type="button" 
                        @click="closeSnapModal()" 
                        class="w-8 h-8 rounded-full bg-slate-200/70 hover:bg-slate-300 text-slate-600 hover:text-slate-900 flex items-center justify-center text-sm font-bold transition cursor-pointer"
                        title="Tutup Form">
                    ✕
                </button>
            </div>

            <!-- Snap Embed Container -->
            <div class="p-2 sm:p-4 bg-white flex justify-center overflow-y-auto" style="min-height: 560px;">
                <div id="snap-container" class="w-full max-w-lg min-h-[560px]"></div>
            </div>
        </div>
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

            <div class="space-y-2.5 max-h-64 overflow-y-auto pr-1">
                @forelse($bankAccounts as $bank)
                    <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs space-y-2 text-xs">
                        <div class="flex items-center justify-between gap-2 pb-1.5 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-[10px] border border-purple-100 shrink-0">
                                    {{ strtoupper(substr($bank->bank_name, 0, 3)) }}
                                </div>
                                <strong class="text-slate-900 font-bold text-xs">{{ $bank->bank_name }}</strong>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Rekening Aktif
                            </span>
                        </div>

                        <div class="flex items-center justify-between bg-slate-50 px-3 py-2 rounded-xl border border-slate-100">
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase tracking-wider font-semibold">Nomor Rekening</span>
                                <strong class="text-[#0b3d82] font-mono font-bold text-sm select-all">{{ $bank->account_number }}</strong>
                            </div>
                            <button 
                                type="button" 
                                @click="copyText('{{ $bank->account_number }}', 'modal_{{ $bank->id }}')" 
                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                                :class="copiedId === 'modal_{{ $bank->id }}' ? 'bg-emerald-600 text-white' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs'"
                                title="Salin nomor rekening"
                            >
                                <template x-if="copiedId === 'modal_{{ $bank->id }}'">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Tersalin!
                                    </span>
                                </template>
                                <template x-if="copiedId !== 'modal_{{ $bank->id }}'">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        Salin
                                    </span>
                                </template>
                            </button>
                        </div>

                        <div class="flex justify-between items-center text-[11px] pt-0.5">
                            <span class="text-slate-500">Atas Nama:</span>
                            <strong class="text-slate-800 font-semibold">{{ $bank->account_name }}</strong>
                        </div>
                        @if($bank->notes)
                            <p class="text-[10px] text-slate-400 pt-1 border-t border-slate-100">{{ $bank->notes }}</p>
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
                <button type="button" @click="manualModalOpen = false; window.location.reload();" class="flex-1 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer">
                    Tutup
                </button>
                <a :href="'https://api.whatsapp.com/send/?phone=6281216700519&text=Halo%20Admin%20PMB%20UCIC%2C%20saya%20sudah%20transfer%20manual%20sebesar%20' + formatRupiah(total) + '%20untuk%20' + activeCategory + '%20(No%20Reg%3A%20{{ $prospek->no_registrasi ?? '-' }}%2C%20Order%20ID%3A%20' + (orderId || '-') + ').%20Berikut%20bukti%20transfernya%3A'"
                   target="_blank"
                   @click="setTimeout(() => window.location.reload(), 1200)"
                   class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white text-center shadow-xs cursor-pointer">
                    Konfirmasi WhatsApp
                </a>
            </div>
        </div>
    </div>

    <!-- Modal Detail Tagihan Belum Dibayar (Fitur Seperti Shopee 'Belum Dibayar') -->
    <div x-show="detailPendingModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl space-y-4" @click.away="detailPendingModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 font-display">Tagihan Belum Dibayar</h3>
                        <p class="text-xs text-slate-500">Rincian tagihan & nomor rekening resmi UCIC</p>
                    </div>
                </div>
                <button type="button" @click="detailPendingModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Info Order & Status -->
            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80 space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Order ID:</span>
                    <span class="font-mono font-bold text-slate-900" x-text="selectedPending.orderId || '-'"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Kategori / Rincian:</span>
                    <span class="font-semibold text-slate-800" x-text="selectedPending.detail || selectedPending.kategori || '-'"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Waktu Dibuat:</span>
                    <span class="text-slate-700" x-text="selectedPending.tanggal || '-'"></span>
                </div>
                <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between text-sm">
                    <span class="font-bold text-slate-800">Total Pembayaran:</span>
                    <span class="font-extrabold text-[#0b3d82] font-display text-base" x-text="selectedPending.nominalFormatted || '-'"></span>
                </div>
            </div>

            <!-- Action Buka Form Midtrans Snap (Jika ada token) -->
            <template x-if="selectedPending.snapToken">
                <div class="p-3.5 bg-blue-50/70 rounded-2xl border border-blue-200 flex items-center justify-between gap-3">
                    <div class="text-xs">
                        <span class="font-bold text-slate-900 block">Bayar Otomatis (Midtrans Snap)</span>
                        <span class="text-[11px] text-slate-500">Buka kembali popup pembayaran resmi UCIC</span>
                    </div>
                    <button type="button"
                            @click="payCategoryNow(selectedPending.kategori || activeCategory); detailPendingModalOpen = false;"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#0b3d82] hover:bg-[#082a5c] transition cursor-pointer flex items-center gap-1.5 shadow-xs shrink-0">
                        <svg class="w-3.5 h-3.5 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span>Buka Form Bayar</span>
                    </button>
                </div>
            </template>

            <!-- Daftar Rekening Bank Kampus UCIC (Dinamis dari Admin) -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800">Daftar Rekening Resmi Kampus UCIC:</span>
                    <span class="text-[10px] text-slate-400">Salin rekening jika transfer manual</span>
                </div>
                <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                    @forelse($bankAccounts as $bank)
                        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-1.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">{{ $bank->bank_name }}</span>
                                <span class="text-[11px] text-slate-500">A/N: <strong class="text-slate-800">{{ $bank->account_name }}</strong></span>
                            </div>
                            <div class="flex items-center justify-between bg-slate-50 px-2.5 py-1.5 rounded-lg border border-slate-100">
                                <span class="font-mono font-bold text-sm text-[#0b3d82] select-all">{{ $bank->account_number }}</span>
                                <button type="button"
                                        @click="copyText('{{ $bank->account_number }}', 'pending_{{ $bank->id }}')"
                                        class="px-2 py-0.5 rounded text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                                        :class="copiedId === 'pending_{{ $bank->id }}' ? 'bg-emerald-600 text-white' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'">
                                    <template x-if="copiedId === 'pending_{{ $bank->id }}'">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Tersalin!
                                        </span>
                                    </template>
                                    <template x-if="copiedId !== 'pending_{{ $bank->id }}'">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            Salin
                                        </span>
                                    </template>
                                </button>
                            </div>
                            @if($bank->notes)
                                <p class="text-[10px] text-slate-400">{{ $bank->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="p-3 bg-slate-50 rounded-xl text-center text-xs text-slate-500">
                            Tidak ada rekening aktif yang terdaftar.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button type="button"
                        @click="detailPendingModalOpen = false"
                        class="w-full py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer">
                    Tutup
                </button>
            </div>
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
                if (cb.checked && !cb.disabled) {
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
