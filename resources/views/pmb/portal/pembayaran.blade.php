@extends('pmb.portal.layout')

@section('title', 'Pembayaran Biaya Pendaftaran PMB')

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
    $initialItems = $initialItems ?? \App\Services\MidtransService::resolveInitialItems($transaksi);
    $initialTotal = $initialTotal ?? ((int) $transaksi->nominal ?: 300000);
@endphp

@section('content')
<div class="max-w-3xl mx-auto space-y-8"
     id="pmb-pembayaran-container"
     x-init="if (status !== 'verified') { checkStatusOnce(); }"
     x-data="{
         orderId: '{{ $transaksi->order_id }}',
         prospekId: {{ $prospek->id }},
         status: '{{ $transaksi->payment_status }}',
         snapToken: '{{ $transaksi->snap_token }}',
         loading: false,
         pollingInterval: null,
         selectedMethod: 'online',
         manualModalOpen: false,
         offlineModalOpen: false,
         items: {
             perlengkapan: {{ $initialItems['perlengkapan'] ? 'true' : 'false' }},
             pendaftaran: {{ $initialItems['pendaftaran'] ? 'true' : 'false' }},
             dpp: {{ $initialItems['dpp'] ? 'true' : 'false' }}
         },
         prices: {
             perlengkapan: 600000,
             pendaftaran: 300000,
             dpp: 500000
         },
         get total() {
             let sum = 0;
             if (this.items.perlengkapan) sum += this.prices.perlengkapan;
             if (this.items.pendaftaran) sum += this.prices.pendaftaran;
             if (this.items.dpp) sum += this.prices.dpp;
             return sum;
         },
         formatRupiah(num) {
             num = Number(num) || 0;
             return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
         },
         onItemChange() {
             // Reset token Snap ketika komponen biaya diubah agar checkout memicu pembuatan token baru
             this.snapToken = null;
         },

         async handleAction() {
             if (this.status === 'verified') {
                 window.location.href = '{{ route('pmb.akun.status') }}';
                 return;
             }

             if (this.total === 0) {
                 alert('Silakan pilih minimal satu item tagihan pembayaran.');
                 return;
             }

             if (this.selectedMethod === 'online') {
                 await this.payNow();
             } else if (this.selectedMethod === 'transfer_manual') {
                 this.manualModalOpen = true;
             } else if (this.selectedMethod === 'offline') {
                 this.offlineModalOpen = true;
             }
         },

         async payNow() {
             if (this.status === 'verified') {
                 alert('Tagihan pendaftaran ini sudah lunas diverifikasi!');
                 return;
             }

             if (this.total === 0) {
                 alert('Silakan pilih minimal satu item tagihan pembayaran.');
                 return;
             }

             const selectedKeys = Object.keys(this.items).filter(k => this.items[k]);

             // Jika belum ada snap token atau komponen biaya berubah, buat order/token baru di backend
             if (!this.snapToken) {
                 this.loading = true;
                 try {
                     const res = await fetch('{{ route('pmb.checkout', $prospek->id) }}', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                             'Accept': 'application/json'
                         },
                         body: JSON.stringify({
                             items: selectedKeys,
                             fresh: true
                         })
                     });
                     const data = await res.json();
                     if (!res.ok || !data.success) {
                         throw new Error(data.message || 'Gagal menyiapkan pembayaran.');
                     }
                     this.snapToken = data.snap_token;
                     this.orderId = data.order_id;
                 } catch (e) {
                     alert(e.message || 'Terjadi kesalahan sistem.');
                     this.loading = false;
                     return;
                 }
                 this.loading = false;
             }

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
    <!-- 1. KARTU UTAMA: PEMBAYARAN BIAYA PENDAFTARAN (Matching Screenshot 1 & 2) -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200/90 shadow-sm">
        
        <!-- Header Title matching Screenshot 1 -->
        <div class="text-center pb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-[#0b3d82] font-display tracking-tight">
                Pembayaran Biaya Pendaftaran
            </h1>
            <p class="text-xs text-slate-500 mt-1">Pembayaran Pendaftaran Online Resmi PMB UCIC</p>
            <div class="w-32 h-1 bg-[#0b3d82] mx-auto rounded-full mt-3"></div>
        </div>

        <!-- Verified Status Banner (If payment is already verified) -->
        <template x-if="status === 'verified'">
            <div class="mb-6 p-6 rounded-2xl bg-emerald-50 border border-emerald-200 text-center space-y-3">
                <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500 text-white flex items-center justify-center shadow-xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-emerald-900">Pembayaran Berhasil Diverifikasi!</h3>
                    <p class="text-xs text-emerald-700 mt-1 max-w-md mx-auto">
                        Terima kasih, pembayaran pendaftaran Anda sebesar <strong>Rp {{ number_format($transaksi->nominal, 0, ',', '.') }}</strong> telah resmi terverifikasi dan sah.
                    </p>
                </div>
                <div class="pt-1">
                    <a href="{{ route('pmb.akun.status') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-xs">
                        Cek Status Pendaftaran &rarr;
                    </a>
                </div>
            </div>
        </template>

        <!-- Deadline PMB Box matching Screenshot 1 -->
        <div class="bg-[#e0f2fe] rounded-2xl p-4 sm:p-5 border border-[#bae6fd] mb-6">
            <div class="flex items-start gap-2.5">
                <span class="text-xl shrink-0 leading-none mt-0.5">⏰</span>
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-slate-900 font-display">
                        Deadline PMB
                    </h3>
                    <p class="text-xs text-slate-700 leading-relaxed">
                        Pembayaran dan pendaftaran harus diselesaikan sebelum: <strong class="font-semibold text-slate-900">30 Oct 2026, 23:59 WIB</strong>
                    </p>
                    <p class="text-xs text-slate-600">
                        Sisa waktu: 7 hari 13 jam | Gelombang 1 Beasiswa Rektor
                    </p>
                </div>
            </div>
        </div>

        <!-- Checklist Rincian Biaya matching Screenshot 1 -->
        <div class="space-y-3 pb-4">
            <!-- 1. Perlengkapan -->
            <label class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition cursor-pointer select-none">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="check-perlengkapan"
                           data-key="perlengkapan"
                           data-price="600000"
                           x-model="items.perlengkapan"
                           @change="onItemChange()"
                           {{ $initialItems['perlengkapan'] ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] cursor-pointer">
                    <span class="text-sm font-medium text-slate-700">Perlengkapan</span>
                </div>
                <span class="text-sm font-medium text-slate-700">Rp 600.000</span>
            </label>

            <!-- 2. Pendaftaran (Official PMB Fee - Rp 300.000) -->
            <label class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition cursor-pointer select-none">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="check-pendaftaran"
                           data-key="pendaftaran"
                           data-price="300000"
                           x-model="items.pendaftaran"
                           @change="onItemChange()"
                           {{ $initialItems['pendaftaran'] ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] cursor-pointer">
                    <span class="text-sm font-medium text-slate-900 font-semibold">Pendaftaran</span>
                </div>
                <span class="text-sm font-medium text-slate-900 font-semibold">Rp 300.000</span>
            </label>

            <!-- 3. DPP -->
            <label class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition cursor-pointer select-none">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           id="check-dpp"
                           data-key="dpp"
                           data-price="500000"
                           x-model="items.dpp"
                           @change="onItemChange()"
                           {{ $initialItems['dpp'] ? 'checked' : '' }}
                           class="fee-checkbox w-4 h-4 rounded border-slate-300 text-[#0b3d82] focus:ring-[#0b3d82] cursor-pointer">
                    <span class="text-sm font-medium text-slate-700">DPP</span>
                </div>
                <span class="text-sm font-medium text-slate-700">Rp 500.000</span>
            </label>

            <!-- Divider Line matching Screenshot 1 -->
            <div class="border-t border-slate-200 pt-3 flex items-center justify-between">
                <span class="text-sm sm:text-base font-bold text-slate-900 font-display">Total Pembayaran</span>
                <span id="total-pembayaran-display"
                      class="text-sm sm:text-base font-bold text-slate-900 font-display"
                      x-text="formatRupiah(total)">
                    Rp {{ number_format($initialTotal, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Section: Pilih Metode Pembayaran matching Screenshot 2 -->
        <div class="mt-8 pt-6 border-t border-slate-100">
            <h2 class="text-base font-bold text-slate-900 font-display mb-4">
                Pilih Metode Pembayaran
            </h2>

            <div class="space-y-3.5">
                <!-- Option 1: Bayar Online (Midtrans Snap) -->
                <label class="block p-4 sm:p-5 rounded-2xl border transition cursor-pointer"
                       :class="selectedMethod === 'online' ? 'border-[#0b3d82] bg-blue-50/30 ring-1 ring-[#0b3d82]' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <div class="flex items-start gap-3.5">
                        <input type="radio"
                               name="payment_method"
                               value="online"
                               x-model="selectedMethod"
                               class="w-4 h-4 mt-0.5 text-[#0b3d82] focus:ring-[#0b3d82] border-slate-300 cursor-pointer">
                        <div class="space-y-1">
                            <span class="text-sm font-bold text-slate-900 block font-display">
                                Bayar Online
                            </span>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Pembayaran online menggunakan Virtual Account, E-Wallet (GoPay, ShopeePay), atau kartu kredit/debit
                            </p>
                            <div class="pt-2 flex flex-wrap items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-[#0b3d82]">Midtrans Snap</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">QRIS</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">BCA</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">BRI</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">BNI</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">Mandiri</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">GoPay</span>
                            </div>
                        </div>
                    </div>
                </label>

                <!-- Option 2: Transfer Manual (BRI) -->
                <label class="block p-4 sm:p-5 rounded-2xl border transition cursor-pointer"
                       :class="selectedMethod === 'transfer_manual' ? 'border-[#0b3d82] bg-blue-50/30 ring-1 ring-[#0b3d82]' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <div class="flex items-start gap-3.5">
                        <input type="radio"
                               name="payment_method"
                               value="transfer_manual"
                               x-model="selectedMethod"
                               class="w-4 h-4 mt-0.5 text-[#0b3d82] focus:ring-[#0b3d82] border-slate-300 cursor-pointer">
                        <div class="space-y-1">
                            <span class="text-sm font-bold text-slate-900 block font-display">
                                Transfer Manual (BRI)
                            </span>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Transfer langsung ke rekening kampus melalui ATM, internet banking, atau mobile banking
                            </p>
                            <div x-show="selectedMethod === 'transfer_manual'" x-cloak class="pt-2 text-xs text-slate-700 bg-white p-3 rounded-xl border border-slate-200/80 mt-2">
                                <p class="font-semibold text-slate-900">Rekening Resmi PMB UCIC:</p>
                                <p class="mt-0.5">Bank BRI: <strong class="text-[#0b3d82] font-mono text-sm">0107-01-001234-56-7</strong></p>
                                <p class="text-[11px] text-slate-500">a.n. Universitas Catur Insan Cendekia</p>
                                <p class="mt-1 text-[11px] text-slate-600">Simpan bukti transfer untuk konfirmasi ke admin PMB.</p>
                            </div>
                        </div>
                    </div>
                </label>

                <!-- Option 3: Bayar Offline (Datang Langsung) -->
                <label class="block p-4 sm:p-5 rounded-2xl border transition cursor-pointer"
                       :class="selectedMethod === 'offline' ? 'border-[#0b3d82] bg-blue-50/30 ring-1 ring-[#0b3d82]' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <div class="flex items-start gap-3.5">
                        <input type="radio"
                               name="payment_method"
                               value="offline"
                               x-model="selectedMethod"
                               class="w-4 h-4 mt-0.5 text-[#0b3d82] focus:ring-[#0b3d82] border-slate-300 cursor-pointer">
                        <div class="space-y-1">
                            <span class="text-sm font-bold text-slate-900 block font-display">
                                Bayar Offline (Datang Langsung)
                            </span>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Bayar langsung di gedung pendaftaran UCIC pada jam kerja (08:00 - 17:00 WIB)
                            </p>
                            <div x-show="selectedMethod === 'offline'" x-cloak class="pt-2 text-xs text-slate-700 bg-white p-3 rounded-xl border border-slate-200/80 mt-2">
                                <p class="font-semibold text-slate-900">Lokasi Front Desk PMB UCIC:</p>
                                <p class="mt-0.5">Gedung Rektorat Lantai 1, Jl. Kesambi No. 202 Kota Cirebon</p>
                                <p class="text-[11px] text-slate-500">Bawa No. Registrasi pendaftaran Anda: <strong class="text-slate-800">{{ $prospek->no_registrasi ?? '-' }}</strong></p>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Tombol Aksi Pembayaran matching Screenshot 2 -->
        <div class="mt-8">
            <template x-if="status === 'verified'">
                <button type="button"
                        @click="handleAction()"
                        class="w-full py-4 px-6 rounded-2xl font-bold text-sm text-white bg-emerald-600 hover:bg-emerald-700 transition flex items-center justify-center gap-2 cursor-pointer shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Pembayaran Sudah Lunas Terverifikasi &rarr;</span>
                </button>
            </template>

            <template x-if="status !== 'verified'">
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
                    <span id="btn-lanjut-bayar-text" x-text="loading ? 'Menghubungkan Midtrans...' : (total === 0 ? 'Pilih minimal 1 komponen biaya' : ('Lanjutkan Pembayaran - ' + formatRupiah(total)))">
                        Lanjutkan Pembayaran - Rp {{ number_format($initialTotal, 0, ',', '.') }}
                    </span>
                </button>
            </template>

            <!-- Order info & security notice -->
            <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between text-xs text-slate-500 gap-2">
                <span>Order ID: <strong class="text-slate-800" x-text="orderId">{{ $transaksi->order_id }}</strong></span>
                <span class="inline-flex items-center gap-1.5 text-blue-700 font-semibold">
                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Terhubung Resmi dengan Midtrans Snap API</span>
                </span>
            </div>
        </div>

    </div>

    <!-- =================================================================== -->
    <!-- 2. KARTU HISTORY PEMBAYARAN (Matching Screenshot 3)                -->
    <!-- =================================================================== -->
    <div class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200/90 shadow-sm">
        <div class="text-center pb-6">
            <h2 class="text-2xl sm:text-3xl font-bold text-[#0b3d82] font-display tracking-tight">
                History Pembayaran
            </h2>
            <div class="w-32 h-1 bg-[#0b3d82] mx-auto rounded-full mt-3"></div>
        </div>

        @if($riwayatTransaksi->isEmpty())
            <!-- Tampilan kosong persis Screenshot 3 -->
            <div class="py-12 text-center text-slate-500 text-sm">
                Belum ada riwayat pembayaran.
            </div>
        @else
            <!-- Tampilan daftar transaksi yang rapi dan responsif -->
            <div class="divide-y divide-slate-100">
                @foreach($riwayatTransaksi as $tx)
                    <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div class="space-y-1">
                            <span class="font-bold text-slate-900 text-sm block font-display">
                                {{ $tx->detail_pembayaran ?: ($tx->kategori ?: 'Biaya Formulir & Pendaftaran PMB') }}
                            </span>
                            <div class="flex flex-wrap items-center gap-2 text-slate-500 text-[11px]">
                                <span>Order ID: <strong class="text-slate-800">{{ $tx->order_id ?: ('TRX-' . $tx->id) }}</strong></span>
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

    <!-- Modal Konfirmasi Transfer Manual BRI -->
    <div x-show="manualModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-xl space-y-4" @click.away="manualModalOpen = false">
            <div class="text-center">
                <div class="w-12 h-12 rounded-full bg-blue-100 text-[#0b3d82] flex items-center justify-center mx-auto mb-3 font-bold text-xl">
                    🏦
                </div>
                <h3 class="text-lg font-bold text-slate-900 font-display">Instruksi Transfer Manual (BRI)</h3>
                <p class="text-xs text-slate-500 mt-1">Silakan transfer nominal tagihan ke rekening resmi kampus UCIC berikut:</p>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-2 text-xs">
                <div class="flex justify-between items-center pb-2 border-b border-slate-200">
                    <span class="text-slate-500">Bank Tujuan</span>
                    <strong class="text-slate-900 font-bold">Bank BRI</strong>
                </div>
                <div class="flex justify-between items-center pb-2 border-b border-slate-200">
                    <span class="text-slate-500">Nomor Rekening</span>
                    <strong class="text-[#0b3d82] font-mono font-bold text-sm">0107-01-001234-56-7</strong>
                </div>
                <div class="flex justify-between items-center pb-2 border-b border-slate-200">
                    <span class="text-slate-500">Atas Nama</span>
                    <strong class="text-slate-900 font-bold">Universitas Catur Insan Cendekia</strong>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">Total Transfer</span>
                    <strong class="text-slate-900 font-bold text-sm" x-text="formatRupiah(total)"></strong>
                </div>
            </div>

            <p class="text-[11px] text-slate-500 text-center">
                Setelah melakukan transfer, silakan kirimkan foto struk / tangkapan layar bukti transfer ke WhatsApp resmi Admin PMB untuk verifikasi.
            </p>

            <div class="flex items-center gap-2 pt-2">
                <button type="button" @click="manualModalOpen = false" class="flex-1 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50">
                    Tutup
                </button>
                <a :href="'https://api.whatsapp.com/send/?phone=6281216700519&text=Halo%20Admin%20PMB%20UCIC%2C%20saya%20sudah%20transfer%20manual%20sebesar%20' + formatRupiah(total) + '%20untuk%20No%20Reg%20{{ $prospek->no_registrasi ?? '-' }}%20(Order%20ID%3A%20' + orderId + ').%20Berikut%20bukti%20transfernya%3A'"
                   target="_blank"
                   class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white text-center shadow-xs">
                    Konfirmasi WhatsApp
                </a>
            </div>
        </div>
    </div>

    <!-- Modal Panduan Bayar Offline -->
    <div x-show="offlineModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-xl space-y-4" @click.away="offlineModalOpen = false">
            <div class="text-center">
                <div class="w-12 h-12 rounded-full bg-blue-100 text-[#0b3d82] flex items-center justify-center mx-auto mb-3 font-bold text-xl">
                    🏢
                </div>
                <h3 class="text-lg font-bold text-slate-900 font-display">Panduan Bayar Offline</h3>
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
                    <span class="text-slate-700 block mt-0.5">Sebutkan No. Registrasi: <strong class="text-[#0b3d82]">{{ $prospek->no_registrasi ?? '-' }}</strong> atau Order ID: <strong class="text-slate-900" x-text="orderId"></strong></span>
                </div>
            </div>

            <button type="button" @click="offlineModalOpen = false" class="w-full py-2.5 rounded-xl bg-[#0b3d82] text-xs font-bold text-white hover:bg-[#082a5c]">
                Saya Mengerti
            </button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Fallback instan: Menjamin kalkulasi total dan tombol checkout langsung sinkron saat checkbox diklik
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
