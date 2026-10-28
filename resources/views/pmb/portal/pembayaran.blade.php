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

@section('content')
<div class="space-y-6"
     x-data="{
         orderId: '{{ $transaksi->order_id }}',
         prospekId: {{ $prospek->id }},
         status: '{{ $transaksi->payment_status }}',
         snapToken: '{{ $transaksi->snap_token }}',
         nominal: {{ $transaksi->nominal }},
         loading: false,
         pollingInterval: null,

         async payNow() {
             if (this.status === 'verified') {
                 alert('Tagihan ini sudah lunas diverifikasi!');
                 return;
             }

             if (!this.snapToken) {
                 this.loading = true;
                 try {
                     const res = await fetch('{{ route('pmb.checkout', $prospek->id) }}', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                             'Accept': 'application/json'
                         }
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

    <!-- Header Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
        <div>
            <h1 class="text-2xl font-black text-slate-900 font-display tracking-tight">Pembayaran Pendaftaran</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Pembayaran online resmi terintegrasi otomatis dengan Midtrans Snap</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Midtrans Secure Snap</span>
            </span>
        </div>
    </div>

    <!-- Active Invoice Card -->
    <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-2xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-slate-100">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                    Tagihan Pendaftaran Aktif
                </span>
                <h2 class="text-xl font-bold text-slate-900 font-display">
                    {{ $transaksi->detail_pembayaran ?: 'Biaya Formulir & Pendaftaran PMB UCIC' }}
                </h2>
                <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                    <span>Order ID: <strong class="text-slate-800" x-text="orderId">{{ $transaksi->order_id }}</strong></span>
                    <span>•</span>
                    <span>Pendaftar: <strong class="text-slate-800">{{ $prospek->name }}</strong></span>
                    <span>•</span>
                    <span>No. Registrasi: <strong class="text-slate-800">{{ $prospek->no_registrasi ?? '-' }}</strong></span>
                </div>
            </div>

            <!-- Total Amount & Status Badge -->
            <div class="lg:text-right shrink-0">
                <span class="text-xs text-slate-400 block font-semibold">Total Tagihan:</span>
                <span class="text-3xl font-black text-slate-900 font-display block leading-none my-1">
                    Rp {{ number_format($transaksi->nominal, 0, ',', '.') }}
                </span>
                <div class="mt-2">
                    <template x-if="status === 'verified'">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Lunas Terverifikasi</span>
                        </span>
                    </template>
                    <template x-if="status !== 'verified'">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                            <span>Menunggu Pembayaran</span>
                        </span>
                    </template>
                </div>
            </div>
        </div>

        <!-- Payment Actions & Methods -->
        <div class="mt-6 pt-2">
            <template x-if="status === 'verified'">
                <div class="p-6 rounded-2xl bg-emerald-50 border border-emerald-200 text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500 text-white flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-emerald-900">Pembayaran Berhasil Diverifikasi!</h3>
                        <p class="text-xs text-emerald-700 mt-1 max-w-md mx-auto">
                            Terima kasih, pembayaran pendaftaran Anda telah resmi diverifikasi oleh sistem. Anda dapat melanjutkan ke tahapan pengisian data formulir dan pemilihan program studi.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a href="{{ route('pmb.akun.status') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs">
                            Cek Status Pendaftaran &rarr;
                        </a>
                    </div>
                </div>
            </template>

            <template x-if="status !== 'verified'">
                <div class="space-y-6">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Metode Pembayaran Online</h4>
                            <p class="text-xs text-slate-500 mt-0.5">Dukung QRIS (GoPay, OVO, ShopeePay, DANA) & Virtual Account Bank (BCA, BNI, BRI, Mandiri, Permata)</p>
                        </div>
                        <button type="button"
                                @click="payNow()"
                                :disabled="loading"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl font-bold text-sm text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/25 transition active:scale-95 disabled:opacity-75">
                            <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="loading ? 'Menyiapkan Midtrans...' : 'Bayar Sekarang (Rp 300.000)'"></span>
                        </button>
                    </div>

                    <div class="text-xs text-slate-500 space-y-1">
                        <p>• Transaksi ini aman dan diverifikasi secara langsung melalui notifikasi resmi Midtrans Webhook.</p>
                        <p>• Jika Anda mengalami kendala pada jendela popup Snap, Anda juga dapat membuka langsung halaman pembayaran mandiri di:
                            <a :href="'{{ url('/pmb/bayar') }}/' + orderId" target="_blank" class="text-blue-600 font-semibold underline">
                                {{ url('/pmb/bayar') }}/<span x-text="orderId"></span>
                            </a>
                        </p>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Riwayat Transaksi Card -->
    <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-2xs">
        <h3 class="text-base font-bold text-slate-900 font-display pb-3 border-b border-slate-100">
            Riwayat Seluruh Transaksi PMB
        </h3>

        <div class="mt-4 divide-y divide-slate-100">
            @forelse($riwayatTransaksi as $tx)
                <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                    <div>
                        <span class="font-bold text-slate-800 text-sm block">{{ $tx->kategori ?? 'Biaya Pendaftaran' }}</span>
                        <span class="text-slate-400 text-[11px]">
                            Order ID: <strong>{{ $tx->order_id ?: ('TRX-' . $tx->id) }}</strong> •
                            {{ $tx->created_at->format('d F Y, H:i') }} WIB •
                            Metode: {{ $tx->payment_type ?: ($tx->metode_pembayaran ?: 'Midtrans Snap') }}
                        </span>
                    </div>

                    <div class="sm:text-right">
                        <span class="font-black text-slate-900 block font-display text-sm">
                            Rp {{ number_format($tx->nominal, 0, ',', '.') }}
                        </span>
                        @if($tx->payment_status === \App\Models\Transaksi::STATUS_VERIFIED)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                Lunas (Verified)
                            </span>
                        @elseif($tx->payment_status === \App\Models\Transaksi::STATUS_REJECTED)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                                Ditolak
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                Menunggu Pembayaran
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-6 text-center text-slate-400 text-xs">
                    Belum ada riwayat transaksi.
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
