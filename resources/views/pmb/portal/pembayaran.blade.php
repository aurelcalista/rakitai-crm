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
    $catalog = $catalog ?? \App\Services\MidtransService::getBiayaCatalog($prospek ?? null);
    $activeUktOptions = $activeUktOptions ?? \App\Services\MidtransService::getActiveUktCatalogOptions($prospek ?? null);
    $initialUktPeriod = $initialUktPeriod ?? ($catalog['ukt']['period'] ?? (array_key_first($activeUktOptions) ?: ''));

    $pricePendaftaran  = (int) ($catalog['pendaftaran']['price'] ?? 300000);
    $pricePerlengkapan = (int) ($catalog['perlengkapan']['price'] ?? 600000);
    $priceDpp          = (int) ($catalog['dpp']['price'] ?? 500000);
    $dppBasePrice      = (int) ($catalog['dpp']['base_price'] ?? $priceDpp);
    $dppPaidPartial    = (int) ($catalog['dpp']['paid_partial'] ?? 0);
    $dppBeforeDiscount = (int) ($catalog['dpp']['remaining_before_discount'] ?? $priceDpp);
    $dppDiscountAmount = (int) ($catalog['dpp']['discount_amount'] ?? 0);
    $dppSummary        = $dppSummary ?? \App\Services\VoucherDiscountService::getDppSummary($prospek ?? null);
    $appliedVoucher    = $appliedVoucher ?? \App\Services\VoucherDiscountService::formatUsageForDisplay($dppSummary['active_usage'] ?? null);
    $priceUangGedung   = (int) ($catalog['uang_gedung']['price'] ?? ($prodi && isset($prodi->biaya_uang_gedung) ? (int) $prodi->biaya_uang_gedung : 0));
    $priceUkt          = (int) ($catalog['ukt']['price'] ?? ($initialUktPeriod && isset($activeUktOptions[$initialUktPeriod]) ? $activeUktOptions[$initialUktPeriod]['nominal'] : 0));

    $paidItems = $paidItems ?? (\App\Services\MidtransService::resolvePaidItems($prospek ?? null));
    $paymentGate = $paymentGate ?? \App\Services\MidtransService::getSequentialGateState($prospek ?? null, $initialUktPeriod);
    $unlocked = $paymentGate['unlocked'] ?? ['pendaftaran' => true, 'perlengkapan' => true, 'dpp' => false, 'uang_gedung' => false, 'ukt' => false];
    $lockReasons = $paymentGate['lock_reasons'] ?? [];
    $canUseDppVoucher = !empty($unlocked['dpp']) && empty($paidItems['dpp']) && $dppBeforeDiscount > 0;

    $initialItems = $initialItems ?? ($transaksi ? \App\Services\MidtransService::resolveInitialItems($transaksi) : ['pendaftaran' => true, 'perlengkapan' => false, 'dpp' => false, 'uang_gedung' => false, 'ukt' => false]);
    foreach (['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'] as $_k) {
        if (!empty($paidItems[$_k]) || empty($unlocked[$_k])) {
            $initialItems[$_k] = false;
        }
    }
    if ($pricePendaftaran <= 0)  $initialItems['pendaftaran'] = false;
    if ($pricePerlengkapan <= 0) $initialItems['perlengkapan'] = false;
    if ($dppBeforeDiscount <= 0) $initialItems['dpp'] = false;
    if ($priceUangGedung <= 0)   $initialItems['uang_gedung'] = false;
    if ($priceUkt <= 0)          $initialItems['ukt'] = false;

    $initialTotal = $initialTotal ?? ($transaksi ? ((int) $transaksi->nominal ?: $pricePendaftaran) : $pricePendaftaran);
    if (empty($unlocked['pendaftaran']) && $initialTotal === $pricePendaftaran && empty($initialItems['pendaftaran'])) {
        $initialTotal = 0;
    }
    $activeCategory = \App\Models\Transaksi::KATEGORI_PENDAFTARAN;
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6"
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
         uktOptions: {{ json_encode($activeUktOptions) }},
         selectedUktPeriod: '{{ $initialUktPeriod }}',
         paidItems: {
             pendaftaran: {{ !empty($paidItems['pendaftaran']) ? 'true' : 'false' }},
             perlengkapan: {{ !empty($paidItems['perlengkapan']) ? 'true' : 'false' }},
             dpp: {{ !empty($paidItems['dpp']) ? 'true' : 'false' }},
             uang_gedung: {{ !empty($paidItems['uang_gedung']) ? 'true' : 'false' }},
             ukt: {{ !empty($paidItems['ukt']) ? 'true' : 'false' }}
         },
         unlocked: {
             pendaftaran: {{ !empty($unlocked['pendaftaran']) ? 'true' : 'false' }},
             perlengkapan: {{ !empty($unlocked['perlengkapan']) ? 'true' : 'false' }},
             dpp: {{ !empty($unlocked['dpp']) ? 'true' : 'false' }},
             uang_gedung: {{ !empty($unlocked['uang_gedung']) ? 'true' : 'false' }},
             ukt: {{ !empty($unlocked['ukt']) ? 'true' : 'false' }}
         },
         items: {
             pendaftaran: {{ (!empty($initialItems['pendaftaran']) && empty($paidItems['pendaftaran']) && !empty($unlocked['pendaftaran']) && $pricePendaftaran > 0) ? 'true' : 'false' }},
             perlengkapan: {{ (!empty($initialItems['perlengkapan']) && empty($paidItems['perlengkapan']) && !empty($unlocked['perlengkapan']) && $pricePerlengkapan > 0) ? 'true' : 'false' }},
             dpp: {{ (!empty($initialItems['dpp']) && empty($paidItems['dpp']) && !empty($unlocked['dpp']) && $dppBeforeDiscount > 0) ? 'true' : 'false' }},
             uang_gedung: {{ (!empty($initialItems['uang_gedung']) && empty($paidItems['uang_gedung']) && !empty($unlocked['uang_gedung']) && $priceUangGedung > 0) ? 'true' : 'false' }},
             ukt: {{ (!empty($initialItems['ukt']) && empty($paidItems['ukt']) && !empty($unlocked['ukt']) && $priceUkt > 0) ? 'true' : 'false' }}
         },
         prices: {
             pendaftaran: {{ $pricePendaftaran }},
             perlengkapan: {{ $pricePerlengkapan }},
             dpp: {{ $priceDpp }},
             uang_gedung: {{ $priceUangGedung }},
             ukt: {{ $priceUkt }}
         },
         dppBeforeDiscount: {{ $dppBeforeDiscount }},
         dppPaidPartial: {{ $dppPaidPartial }},
         appliedVoucher: {{ json_encode($appliedVoucher) }},
         voucherCodeInput: '{{ $appliedVoucher['voucher_code'] ?? '' }}',
         voucherLoading: false,
         voucherMessage: '',
         voucherMessageType: 'success',
         get canZeroVoucherCheckout() {
             return Boolean(this.items.dpp && !this.paidItems.dpp && this.unlocked.dpp && this.prices.dpp === 0 && this.appliedVoucher && this.appliedVoucher.discount_amount > 0);
         },
         get hasPayableSelection() {
             return this.total > 0 || this.canZeroVoucherCheckout;
         },
         get allItemsPaid() {
             const keys = ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'];
             return keys.every(k => {
                 if (k === 'dpp') {
                     return this.paidItems.dpp || this.dppBeforeDiscount <= 0;
                 }
                 return this.paidItems[k] || (this.prices[k] || 0) <= 0;
             });
         },
         get total() {
             let sum = 0;
             if (this.items.pendaftaran && !this.paidItems.pendaftaran && this.unlocked.pendaftaran && this.prices.pendaftaran > 0) sum += this.prices.pendaftaran;
             if (this.items.perlengkapan && !this.paidItems.perlengkapan && this.unlocked.perlengkapan && this.prices.perlengkapan > 0) sum += this.prices.perlengkapan;
             if (this.items.dpp && !this.paidItems.dpp && this.unlocked.dpp && this.prices.dpp > 0) sum += this.prices.dpp;
             if (this.items.uang_gedung && !this.paidItems.uang_gedung && this.unlocked.uang_gedung && this.prices.uang_gedung > 0) sum += this.prices.uang_gedung;
             if (this.items.ukt && !this.paidItems.ukt && this.unlocked.ukt && this.prices.ukt > 0) sum += this.prices.ukt;
             return sum;
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
         onUktPeriodChange() {
             const opt = this.uktOptions[this.selectedUktPeriod];
             this.prices.ukt = opt ? Number(opt.nominal || 0) : 0;
             if (this.paidItems.ukt || !this.unlocked.ukt || this.prices.ukt <= 0) {
                 this.items.ukt = false;
             }
             this.snapToken = null;
             this.$nextTick(() => {
                 if (typeof window.syncFeeFallback === 'function') {
                     window.syncFeeFallback();
                 }
             });
         },
         onItemChange() {
             if (this.paidItems.pendaftaran || !this.unlocked.pendaftaran || this.prices.pendaftaran <= 0) this.items.pendaftaran = false;
             if (this.paidItems.perlengkapan || !this.unlocked.perlengkapan || this.prices.perlengkapan <= 0) this.items.perlengkapan = false;
             if (this.paidItems.dpp || !this.unlocked.dpp || this.dppBeforeDiscount <= 0) this.items.dpp = false;
             if (this.paidItems.uang_gedung || !this.unlocked.uang_gedung || this.prices.uang_gedung <= 0) this.items.uang_gedung = false;
             if (this.paidItems.ukt || !this.unlocked.ukt || this.prices.ukt <= 0) this.items.ukt = false;
             this.snapToken = null;
         },

         async applyVoucher() {
             const code = (this.voucherCodeInput || '').trim().toUpperCase();
             if (!code) {
                 this.voucherMessageType = 'error';
                 this.voucherMessage = 'Silakan masukkan kode voucher terlebih dahulu.';
                 return;
             }
             this.voucherLoading = true;
             this.voucherMessage = '';
             try {
                 const res = await fetch('{{ route('pmb.akun.voucher.apply') }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                         'Accept': 'application/json'
                     },
                     body: JSON.stringify({ code })
                 });
                 const data = await res.json();
                 if (!res.ok || !data.success) {
                     throw new Error(data.message || 'Kode voucher tidak dapat digunakan.');
                 }
                 this.appliedVoucher = data.voucher;
                 this.voucherCodeInput = data.voucher?.voucher_code || code;
                 if (data.dpp_summary) {
                     this.prices.dpp = Number(data.dpp_summary.remaining_payable ?? 0);
                     this.dppBeforeDiscount = Number(data.dpp_summary.remaining_before_discount ?? this.dppBeforeDiscount);
                 }
                 if (!this.paidItems.dpp && this.unlocked.dpp) {
                     this.items.dpp = true;
                 }
                 this.snapToken = null;
                 this.voucherMessageType = 'success';
                 this.voucherMessage = data.message || 'Voucher berhasil diterapkan!';
                 this.$nextTick(() => {
                     if (typeof window.syncFeeFallback === 'function') window.syncFeeFallback();
                 });
             } catch (e) {
                 this.voucherMessageType = 'error';
                 this.voucherMessage = e.message || 'Gagal memvalidasi kode voucher.';
             } finally {
                 this.voucherLoading = false;
             }
         },

         async removeAppliedVoucher() {
             this.voucherLoading = true;
             this.voucherMessage = '';
             try {
                 const res = await fetch('{{ route('pmb.akun.voucher.remove') }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                         'Accept': 'application/json'
                     },
                     body: JSON.stringify({})
                 });
                 const data = await res.json();
                 if (!res.ok || !data.success) {
                     throw new Error(data.message || 'Gagal membatalkan voucher.');
                 }
                 this.appliedVoucher = null;
                 this.voucherCodeInput = '';
                 if (data.dpp_summary) {
                     this.prices.dpp = Number(data.dpp_summary.remaining_payable ?? this.dppBeforeDiscount);
                 }
                 this.snapToken = null;
                 this.voucherMessageType = 'success';
                 this.voucherMessage = data.message || 'Voucher dibatalkan.';
                 this.$nextTick(() => {
                     if (typeof window.syncFeeFallback === 'function') window.syncFeeFallback();
                 });
             } catch (e) {
                 this.voucherMessageType = 'error';
                 this.voucherMessage = e.message || 'Gagal membatalkan voucher.';
             } finally {
                 this.voucherLoading = false;
             }
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
             if (this.allItemsPaid) {
                 alert('Seluruh komponen biaya pembayaran sudah lunas terverifikasi sah!');
                 return;
             }

             if (!this.hasPayableSelection) {
                 alert('Silakan pilih minimal satu item tagihan pembayaran.');
                 return;
             }

             await this.payCategoryNow(categoryOverride || '{{ \App\Models\Transaksi::KATEGORI_PENDAFTARAN }}');
         },

         async payCategoryNow(cat = null) {
             const targetCat = cat || '{{ \App\Models\Transaksi::KATEGORI_PENDAFTARAN }}';
             this.activeCategory = targetCat;
             this.loading = true;

             const payload = {
                 category: targetCat,
                 kategori: targetCat,
                 payment_method: this.selectedMethod,
                 fresh: false
             };

             if (targetCat === '{{ \App\Models\Transaksi::KATEGORI_PENDAFTARAN }}') {
                 const orderedKeys = ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'];
                 const selectedKeys = orderedKeys.filter(k => {
                     if (!this.items[k] || this.paidItems[k] || !this.unlocked[k]) return false;
                     if (k === 'dpp' && this.appliedVoucher && this.appliedVoucher.discount_amount > 0) return true;
                     return (this.prices[k] || 0) > 0;
                 });
                 if (selectedKeys.length > 0) {
                     payload.items = selectedKeys;
                 }
                 if (selectedKeys.includes('ukt') && this.selectedUktPeriod) {
                     payload.ukt_period = this.selectedUktPeriod;
                 }
                 if (selectedKeys.includes('dpp') && this.appliedVoucher && this.appliedVoucher.voucher_code) {
                     payload.voucher_code = this.appliedVoucher.voucher_code;
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
                 if (data.voucher_settled) {
                     this.status = 'verified';
                     this.loading = false;
                     window.location.reload();
                     return;
                 }
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
                Kelola pembayaran Biaya Pendaftaran, Biaya Perlengkapan, DPP, Uang Gedung, dan UKT secara resmi, aman, dan terintegrasi.
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
                    <p class="text-slate-500">
                        Pendaftar: <strong class="text-slate-800">{{ $prospek->name }}</strong> | No. Registrasi: <strong class="text-[#0b3d82] font-mono">{{ $prospek->no_registrasi ?? '-' }}</strong> | Program Studi: <strong class="text-slate-800">{{ $prodi ? (($prodi->jenjang ? $prodi->jenjang . ' ' : '') . $prodi->nama) : '-' }}</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- DETAIL CHECKOUT & KOMPONEN BIAYA                                    -->
    <!-- =================================================================== -->
    <div id="checkout-section" class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200/90 shadow-xs space-y-6">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-900 font-display">
                    Checkout Pembayaran
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Konfirmasi komponen biaya dan metode transaksi pilihan Anda.</p>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-400 block font-medium">Total Pembayaran:</span>
                <span id="total-pembayaran-display"
                      class="text-xl font-bold font-display text-[#0b3d82]"
                      x-text="formatRupiah(total)">
                    Rp {{ number_format($initialTotal, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Checklist 5 Komponen Biaya (Urutan: Pendaftaran, Perlengkapan, DPP, Uang Gedung, UKT) -->
        <div class="space-y-2.5 pt-2 border-t border-slate-100">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-xs font-bold text-slate-700 block mb-1">Rincian Komponen Biaya Pembayaran:</span>
                <span class="text-[11px] text-slate-400">Pilih komponen yang ingin dibayar</span>
            </div>

            <!-- 1. Biaya Pendaftaran -->
            <label class="flex items-center justify-between p-3 rounded-2xl border transition select-none"
                   :class="(paidItems.pendaftaran || prices.pendaftaran <= 0) ? 'bg-slate-50/80 border-slate-200 cursor-not-allowed opacity-85' : 'border-slate-100 hover:bg-slate-50 cursor-pointer'"
                   @click="if (paidItems.pendaftaran || prices.pendaftaran <= 0) { $event.preventDefault(); return false; }">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="check-pendaftaran"
                           data-key="pendaftaran"
                           data-price="{{ $pricePendaftaran }}"
                           x-model="items.pendaftaran"
                           @change="onItemChange()"
                           :disabled="paidItems.pendaftaran || prices.pendaftaran <= 0"
                           {{ (!empty($paidItems['pendaftaran']) || $pricePendaftaran <= 0) ? 'disabled' : '' }}
                           {{ (!empty($initialItems['pendaftaran']) && empty($paidItems['pendaftaran']) && $pricePendaftaran > 0) ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:border-slate-300 disabled:text-slate-400">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-bold text-slate-900 block">Biaya Pendaftaran</span>
                            @if($pricePendaftaran <= 0)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    Gratis (Rp 0)
                                </span>
                            @else
                                <template x-if="paidItems.pendaftaran">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Sudah Dibayar (Lunas)
                                    </span>
                                </template>
                            @endif
                            @if(!empty($paidItems['pendaftaran']) && $pricePendaftaran > 0)
                                <span x-show="!$data" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    ✓ Sudah Dibayar (Lunas)
                                </span>
                            @endif
                        </div>
                        <span class="text-[11px] text-slate-400">Formulir pendaftaran online & ujian seleksi (1x bayar)</span>
                    </div>
                </div>
                <div class="text-right shrink-0 ml-3">
                    <span class="text-sm font-bold text-slate-900" :class="paidItems.pendaftaran ? 'line-through text-slate-400' : ''">
                        {{ $pricePendaftaran <= 0 ? 'Rp 0 (Gratis)' : 'Rp ' . number_format($pricePendaftaran, 0, ',', '.') }}
                    </span>
                </div>
            </label>

            <!-- 2. Biaya Perlengkapan -->
            <label class="flex items-center justify-between p-3 rounded-2xl border transition select-none"
                   :class="(paidItems.perlengkapan || prices.perlengkapan <= 0) ? 'bg-slate-50/80 border-slate-200 cursor-not-allowed opacity-85' : 'border-slate-100 hover:bg-slate-50 cursor-pointer'"
                   @click="if (paidItems.perlengkapan || prices.perlengkapan <= 0) { $event.preventDefault(); return false; }">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="check-perlengkapan"
                           data-key="perlengkapan"
                           data-price="{{ $pricePerlengkapan }}"
                           x-model="items.perlengkapan"
                           @change="onItemChange()"
                           :disabled="paidItems.perlengkapan || prices.perlengkapan <= 0"
                           {{ (!empty($paidItems['perlengkapan']) || $pricePerlengkapan <= 0) ? 'disabled' : '' }}
                           {{ (!empty($initialItems['perlengkapan']) && empty($paidItems['perlengkapan']) && $pricePerlengkapan > 0) ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:border-slate-300 disabled:text-slate-400">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-semibold text-slate-800 block">Biaya Perlengkapan</span>
                            @if($pricePerlengkapan <= 0)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    Gratis (Rp 0)
                                </span>
                            @else
                                <template x-if="paidItems.perlengkapan">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Sudah Dibayar (Lunas)
                                    </span>
                                </template>
                            @endif
                            @if(!empty($paidItems['perlengkapan']) && $pricePerlengkapan > 0)
                                <span x-show="!$data" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    ✓ Sudah Dibayar (Lunas)
                                </span>
                            @endif
                        </div>
                        <span class="text-[11px] text-slate-400">Jas almamater, atribut, dan paket perlengkapan mahasiswa (1x bayar)</span>
                    </div>
                </div>
                <div class="text-right shrink-0 ml-3">
                    <span class="text-sm font-semibold text-slate-700" :class="paidItems.perlengkapan ? 'line-through text-slate-400' : ''">
                        {{ $pricePerlengkapan <= 0 ? 'Rp 0 (Gratis)' : 'Rp ' . number_format($pricePerlengkapan, 0, ',', '.') }}
                    </span>
                </div>
            </label>

            <!-- 3. DPP -->
            <div class="space-y-2">
                <label class="flex items-center justify-between p-3 rounded-2xl border transition select-none"
                       :class="(paidItems.dpp || dppBeforeDiscount <= 0 || !unlocked.dpp) ? 'bg-slate-50/80 border-slate-200 cursor-not-allowed opacity-75' : 'border-slate-100 hover:bg-slate-50 cursor-pointer'"
                       @click="if (paidItems.dpp || dppBeforeDiscount <= 0 || !unlocked.dpp) { $event.preventDefault(); return false; }">
                    <div class="flex items-center gap-3">
                        <input type="checkbox"
                               id="check-dpp"
                               data-key="dpp"
                               :data-price="prices.dpp"
                               data-price="{{ $priceDpp }}"
                               :data-voucher-applied="(appliedVoucher && appliedVoucher.discount_amount > 0) ? '1' : '0'"
                               x-model="items.dpp"
                               @change="onItemChange()"
                               :disabled="paidItems.dpp || dppBeforeDiscount <= 0 || !unlocked.dpp"
                               {{ (!empty($paidItems['dpp']) || $dppBeforeDiscount <= 0 || empty($unlocked['dpp'])) ? 'disabled' : '' }}
                               {{ (!empty($initialItems['dpp']) && empty($paidItems['dpp']) && !empty($unlocked['dpp']) && $dppBeforeDiscount > 0) ? 'checked' : '' }}
                               class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:border-slate-300 disabled:text-slate-400">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-semibold text-slate-800 block">DPP</span>
                                @if($dppBasePrice <= 0)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        Gratis (Rp 0)
                                    </span>
                                @elseif(!empty($paidItems['dpp']))
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Sudah Dibayar (Lunas)
                                    </span>
                                    @if(!empty($appliedVoucher))
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">
                                            Voucher {{ $appliedVoucher['voucher_code'] }} (-{{ $appliedVoucher['percentage_formatted'] }}% • Hemat Rp {{ number_format($appliedVoucher['discount_amount'], 0, ',', '.') }})
                                        </span>
                                    @endif
                                @elseif(empty($unlocked['dpp']))
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                        Terkunci
                                    </span>
                                @else
                                    <template x-if="appliedVoucher">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                            <span x-text="'Voucher ' + appliedVoucher.voucher_code + ' (-' + appliedVoucher.percentage_formatted + '%)'"></span>
                                        </span>
                                    </template>
                                @endif
                            </div>
                            <span class="text-[11px] {{ empty($unlocked['dpp']) && $dppBeforeDiscount > 0 && empty($paidItems['dpp']) ? 'text-amber-700 font-medium' : 'text-slate-400' }}">
                                @if(empty($unlocked['dpp']) && $dppBeforeDiscount > 0 && empty($paidItems['dpp']))
                                    {{ $lockReasons['dpp'] ?? 'Selesaikan Biaya Pendaftaran dan Perlengkapan terlebih dahulu' }}
                                @elseif($dppPaidPartial > 0 && empty($paidItems['dpp']))
                                    Dana Pengembangan Pendidikan • Sudah dibayar sebagian Rp {{ number_format($dppPaidPartial, 0, ',', '.') }} (Sisa sebelum diskon: Rp {{ number_format($dppBeforeDiscount, 0, ',', '.') }})
                                @else
                                    Dana Pengembangan Pendidikan (1x bayar)
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="text-right shrink-0 ml-3">
                        <template x-if="appliedVoucher && !paidItems.dpp">
                            <div>
                                <span class="text-[11px] text-slate-400 line-through block" x-text="formatRupiah(dppBeforeDiscount)"></span>
                                <span class="text-sm font-bold text-emerald-700" x-text="prices.dpp <= 0 ? 'Rp 0 (Diskon 100%)' : formatRupiah(prices.dpp)"></span>
                            </div>
                        </template>
                        <template x-if="!appliedVoucher || paidItems.dpp">
                            <span class="text-sm font-semibold text-slate-700" :class="paidItems.dpp ? 'line-through text-slate-400' : ''" x-text="dppBasePrice <= 0 ? 'Rp 0 (Gratis)' : formatRupiah(paidItems.dpp ? dppBasePrice : prices.dpp)">
                                {{ $dppBasePrice <= 0 ? 'Rp 0 (Gratis)' : 'Rp ' . number_format(!empty($paidItems['dpp']) ? $dppBasePrice : $priceDpp, 0, ',', '.') }}
                            </span>
                        </template>
                    </div>
                </label>

                {{-- Form Voucher Diskon DPP: HANYA muncul ketika CAMABA sudah mencapai tahap pembayaran DPP --}}
                @if($canUseDppVoucher)
                    <div id="dpp-voucher-box" class="ml-0 sm:ml-7 p-3.5 sm:p-4 rounded-2xl border border-blue-200/80 bg-gradient-to-br from-blue-50/60 via-white to-emerald-50/30 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-xl bg-blue-100 text-[#0b3d82] flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-extrabold text-slate-900 font-display">Voucher Diskon DPP</h4>
                                    <p class="text-[11px] text-slate-500">Masukkan kode voucher resmi dari Admin PMB UCIC untuk potongan biaya DPP.</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <input type="text"
                                   id="voucher-code-input"
                                   x-model="voucherCodeInput"
                                   @keydown.enter.prevent="applyVoucher()"
                                   placeholder="Masukkan kode voucher (contoh: UCIC20)"
                                   class="flex-1 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-bold uppercase tracking-wider text-slate-800 placeholder-slate-400 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 outline-none transition">
                            <button type="button"
                                    id="btn-gunakan-voucher"
                                    @click="applyVoucher()"
                                    :disabled="voucherLoading"
                                    class="px-4 py-2 rounded-xl text-xs font-bold bg-[#0b3d82] hover:bg-[#082a5c] text-white shadow-2xs transition cursor-pointer disabled:opacity-60 shrink-0">
                                <span x-text="voucherLoading ? 'Memeriksa...' : 'Gunakan Voucher'">Gunakan Voucher</span>
                            </button>
                        </div>

                        {{-- Notifikasi Validasi Voucher --}}
                        <template x-if="voucherMessage">
                            <div class="px-3 py-2 rounded-xl border text-xs font-semibold flex items-center gap-2"
                                 :class="voucherMessageType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-700'">
                                <span x-text="voucherMessage"></span>
                            </div>
                        </template>

                        {{-- Rincian Potongan Voucher Aktif --}}
                        <template x-if="appliedVoucher">
                            <div id="dpp-voucher-applied-summary" class="p-3 rounded-xl bg-white border border-emerald-200 shadow-2xs space-y-2 text-xs">
                                <div class="flex items-center justify-between gap-2 pb-2 border-b border-slate-100">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 rounded-md font-mono font-extrabold text-[11px] bg-emerald-100 text-emerald-800 border border-emerald-200" x-text="appliedVoucher.voucher_code"></span>
                                        <template x-if="appliedVoucher.voucher_name">
                                            <span class="text-[11px] font-bold text-slate-700" x-text="appliedVoucher.voucher_name"></span>
                                        </template>
                                        <span class="text-[11px] font-bold text-emerald-700" x-text="'Diskon ' + appliedVoucher.percentage_formatted + '%'"></span>
                                    </div>
                                    <template x-if="appliedVoucher.status === 'applied'">
                                        <button type="button"
                                                @click="removeAppliedVoucher()"
                                                :disabled="voucherLoading"
                                                class="text-[11px] font-bold text-rose-600 hover:text-rose-800 hover:underline cursor-pointer">
                                            Batalkan Voucher
                                        </button>
                                    </template>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-0.5 text-[11px]">
                                    <div>
                                        <span class="text-slate-400 block">Kode Voucher</span>
                                        <strong class="text-slate-800 font-mono" x-text="appliedVoucher.voucher_code"></strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block">Persentase Diskon</span>
                                        <strong class="text-emerald-700" x-text="appliedVoucher.percentage_formatted + '%'"></strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block">Nominal Potongan</span>
                                        <strong class="text-emerald-700" x-text="'- ' + formatRupiah(appliedVoucher.discount_amount)"></strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block">DPP Setelah Diskon</span>
                                        <strong class="text-[#0b3d82]" x-text="formatRupiah(appliedVoucher.dpp_after_discount)"></strong>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                @endif
            </div>

            <!-- 4. Uang Gedung -->
            <label class="flex items-center justify-between p-3 rounded-2xl border transition select-none"
                   :class="(paidItems.uang_gedung || prices.uang_gedung <= 0 || !unlocked.uang_gedung) ? 'bg-slate-50/80 border-slate-200 cursor-not-allowed opacity-75' : 'border-slate-100 hover:bg-slate-50 cursor-pointer'"
                   @click="if (paidItems.uang_gedung || prices.uang_gedung <= 0 || !unlocked.uang_gedung) { $event.preventDefault(); return false; }">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="check-uang-gedung"
                           data-key="uang_gedung"
                           data-price="{{ $priceUangGedung }}"
                           x-model="items.uang_gedung"
                           @change="onItemChange()"
                           :disabled="paidItems.uang_gedung || prices.uang_gedung <= 0 || !unlocked.uang_gedung"
                           {{ (!empty($paidItems['uang_gedung']) || $priceUangGedung <= 0 || empty($unlocked['uang_gedung'])) ? 'disabled' : '' }}
                           {{ (!empty($initialItems['uang_gedung']) && empty($paidItems['uang_gedung']) && !empty($unlocked['uang_gedung']) && $priceUangGedung > 0) ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:border-slate-300 disabled:text-slate-400">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-semibold text-slate-800 block">Uang Gedung</span>
                            @if($priceUangGedung <= 0)
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    Gratis (Rp 0)
                                </span>
                            @elseif(!empty($paidItems['uang_gedung']))
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Sudah Dibayar (Lunas)
                                </span>
                            @elseif(empty($unlocked['uang_gedung']))
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                    Terkunci
                                </span>
                            @endif
                        </div>
                        <span class="text-[11px] {{ empty($unlocked['uang_gedung']) && $priceUangGedung > 0 && empty($paidItems['uang_gedung']) ? 'text-amber-700 font-medium' : 'text-slate-400' }}">
                            {{ (empty($unlocked['uang_gedung']) && $priceUangGedung > 0 && empty($paidItems['uang_gedung'])) ? ($lockReasons['uang_gedung'] ?? 'Bayar DPP terlebih dahulu') : 'Kontribusi pengembangan gedung & fasilitas kampus (1x bayar)' }}
                        </span>
                    </div>
                </div>
                <div class="text-right shrink-0 ml-3">
                    <span class="text-sm font-semibold text-slate-700" :class="paidItems.uang_gedung ? 'line-through text-slate-400' : ''">
                        {{ $priceUangGedung <= 0 ? 'Rp 0 (Gratis)' : 'Rp ' . number_format($priceUangGedung, 0, ',', '.') }}
                    </span>
                </div>
            </label>

            <!-- 5. UKT -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded-2xl border transition select-none"
                 :class="(paidItems.ukt || prices.ukt <= 0 || !unlocked.ukt) ? 'bg-slate-50/80 border-slate-200 opacity-75' : 'border-slate-100 hover:bg-slate-50'">
                <div class="flex items-start sm:items-center gap-3 flex-1 min-w-0">
                    <input type="checkbox"
                           id="check-ukt"
                           data-key="ukt"
                           :data-price="prices.ukt"
                           data-price="{{ $priceUkt }}"
                           x-model="items.ukt"
                           @change="onItemChange()"
                           :disabled="paidItems.ukt || prices.ukt <= 0 || !unlocked.ukt"
                           {{ (!empty($paidItems['ukt']) || $priceUkt <= 0 || empty($unlocked['ukt'])) ? 'disabled' : '' }}
                           {{ (!empty($initialItems['ukt']) && empty($paidItems['ukt']) && !empty($unlocked['ukt']) && $priceUkt > 0) ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 mt-0.5 sm:mt-0 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] disabled:cursor-not-allowed disabled:bg-slate-200 disabled:border-slate-300 disabled:text-slate-400 shrink-0 cursor-pointer">
                    <div class="flex-1 min-w-0 space-y-1.5">
                        <label for="check-ukt"
                               :class="(paidItems.ukt || prices.ukt <= 0 || !unlocked.ukt) ? 'cursor-not-allowed' : 'cursor-pointer'"
                               class="block"
                               @click="if (paidItems.ukt || prices.ukt <= 0 || !unlocked.ukt) { $event.preventDefault(); return false; }">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-semibold text-slate-800 block">UKT</span>
                                @if($priceUkt <= 0)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        Tidak Aktif / Rp 0
                                    </span>
                                @elseif(!empty($paidItems['ukt']))
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Sudah Dibayar (Lunas)
                                    </span>
                                @elseif(empty($unlocked['ukt']))
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                        Terkunci
                                    </span>
                                @endif
                            </div>
                            <span class="text-[11px] block {{ empty($unlocked['ukt']) && $priceUkt > 0 && empty($paidItems['ukt']) ? 'text-amber-700 font-medium' : 'text-slate-400' }}">
                                {{ (empty($unlocked['ukt']) && $priceUkt > 0 && empty($paidItems['ukt'])) ? ($lockReasons['ukt'] ?? 'Selesaikan pembayaran tahap sebelumnya terlebih dahulu') : 'Uang Kuliah Tunggal sesuai periode yang dipilih' }}
                            </span>
                        </label>

                        @if(!empty($activeUktOptions))
                            <div class="max-w-xs">
                                <select id="select-ukt-period"
                                        x-model="selectedUktPeriod"
                                        @change="onUktPeriodChange()"
                                        :disabled="paidItems.ukt || !unlocked.ukt"
                                        {{ (!empty($paidItems['ukt']) || empty($unlocked['ukt'])) ? 'disabled' : '' }}
                                        class="w-full px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 focus:border-[#0b3d82] focus:ring-2 focus:ring-blue-100 transition disabled:bg-slate-100 disabled:cursor-not-allowed">
                                    @foreach($activeUktOptions as $uktKey => $uktOpt)
                                        <option value="{{ $uktKey }}" {{ $initialUktPeriod === $uktKey ? 'selected' : '' }}>
                                            {{ $uktOpt['label'] }} — Rp {{ number_format($uktOpt['nominal'], 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="text-right shrink-0 ml-3">
                    <span class="text-sm font-semibold text-slate-700"
                          :class="paidItems.ukt ? 'line-through text-slate-400' : ''"
                          x-text="prices.ukt <= 0 ? 'Rp 0 (Gratis)' : formatRupiah(prices.ukt)">
                        {{ $priceUkt <= 0 ? 'Rp 0 (Gratis)' : 'Rp ' . number_format($priceUkt, 0, ',', '.') }}
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
            <template x-if="allItemsPaid">
                <button type="button" disabled
                        class="w-full py-4 px-6 rounded-2xl font-bold text-sm text-white bg-emerald-600 transition flex items-center justify-center gap-2 cursor-not-allowed shadow-xs">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Seluruh Komponen Biaya Sudah Lunas Terverifikasi &rarr;</span>
                </button>
            </template>

            <template x-if="!allItemsPaid">
                <button type="button"
                        id="btn-lanjut-bayar"
                        @click="handleAction()"
                        :disabled="loading || !hasPayableSelection"
                        class="w-full py-4 px-6 rounded-2xl font-bold text-sm transition flex items-center justify-center gap-2 cursor-pointer shadow-sm"
                        :class="!hasPayableSelection ? 'bg-slate-300 text-slate-500 cursor-not-allowed' : 'bg-[#0b3d82] hover:bg-[#082a5c] text-white active:scale-[0.99]'">
                    <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span id="btn-lanjut-bayar-text" x-text="loading ? 'Menghubungkan Midtrans...' : (!hasPayableSelection ? 'Pilih minimal 1 komponen biaya' : (canZeroVoucherCheckout && total === 0 ? 'Konfirmasi Pembayaran DPP (Voucher 100%)' : ('Lanjutkan Pembayaran - ' + formatRupiah(total))))">
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
                            @click="openSnapPay(selectedPending.snapToken, selectedPending.orderId); detailPendingModalOpen = false;"
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
            var hasZeroVoucherDpp = false;
            checkboxes.forEach(function(cb) {
                if (cb.checked && !cb.disabled) {
                    var price = parseInt(cb.getAttribute('data-price') || '0', 10);
                    sum += price;
                    if (cb.getAttribute('data-key') === 'dpp' && cb.getAttribute('data-voucher-applied') === '1' && price === 0) {
                        hasZeroVoucherDpp = true;
                    }
                }
            });

            var formatted = 'Rp ' + sum.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            if (totalDisplay) {
                totalDisplay.textContent = formatted;
            }
            if (btnText && !btnText.textContent.includes('Menghubungkan')) {
                if (sum === 0 && hasZeroVoucherDpp) {
                    btnText.textContent = 'Konfirmasi Pembayaran DPP (Voucher 100%)';
                } else {
                    btnText.textContent = sum === 0 ? 'Pilih minimal 1 komponen biaya' : ('Lanjutkan Pembayaran - ' + formatted);
                }
            }
            if (btnBayar) {
                if (sum === 0 && !hasZeroVoucherDpp) {
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

        window.syncFeeFallback = calculateAndSync;

        document.addEventListener('DOMContentLoaded', function() {
            var checkboxes = document.querySelectorAll('.fee-checkbox');
            checkboxes.forEach(function(cb) {
                cb.addEventListener('change', calculateAndSync);
            });
            var uktSelect = document.getElementById('select-ukt-period');
            if (uktSelect) {
                uktSelect.addEventListener('change', calculateAndSync);
            }
        });
    })();
</script>
@endpush
