@extends('layouts.app', ['title' => 'Simulasi Pembayaran ' . $payment->transaction_id])

@section('content')
<div class="max-w-2xl mx-auto space-y-6" 
     x-data="qrisTimer({
        remainingSeconds: {{ $remainingSeconds }},
        status: '{{ $payment->status }}',
        expireUrl: '{{ route('payments.simulate.action', $payment->transaction_id) }}'
     })">

    <!-- Top Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('payments.show', $invoice->id) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Tagihan {{ $invoice->invoice_number }}
        </a>
        <span class="text-[11px] font-mono text-slate-400">
            ID: {{ $payment->transaction_id }}
        </span>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif
    @if(session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    <!-- Main Payment Simulator Box -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
        
        <!-- Simulation Notice Banner -->
        <div class="bg-amber-500/10 border-b border-amber-200/80 px-6 py-2.5 flex items-center justify-between">
            <span class="inline-flex items-center gap-2 text-xs font-bold text-amber-800 tracking-wide">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                SIMULASI GATEWAY PEMBAYARAN LOKAL
            </span>
            <span class="text-[10px] font-semibold text-amber-700 bg-amber-100/80 px-2 py-0.5 rounded">
                Tidak Menggunakan Uang Nyata
            </span>
        </div>

        <div class="p-8 text-center space-y-6">

            <!-- Nominal & Tagihan -->
            <div>
                <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold block mb-1">Total Jumlah Tagihan</span>
                <div class="text-3xl font-extrabold text-slate-900 tracking-tight font-sans">
                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                </div>
                <div class="mt-2 text-xs text-slate-500">
                    Pelanggan: <strong class="text-slate-800">{{ $invoice->customer_name }}</strong> &bull; Invoice: <span class="font-mono">{{ $invoice->invoice_number }}</span>
                </div>
            </div>

            <!-- STATE 1: PAID (LUNAS) -->
            @if($payment->status === 'paid')
                <div class="p-8 rounded-2xl bg-emerald-50 border border-emerald-200 space-y-4">
                    <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center border-2 border-emerald-200">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h2 class="text-xl font-extrabold text-emerald-950">Pembayaran Berhasil</h2>
                        <p class="text-xs text-emerald-800">
                            Transaksi telah divalidasi dan tagihan terkait resmi berstatus <strong>LUNAS (PAID)</strong>.
                        </p>
                    </div>
                    <div class="p-3 bg-white/90 rounded-xl border border-emerald-200/80 text-xs font-mono text-slate-700 max-w-sm mx-auto">
                        Transaction ID: <strong>{{ $payment->transaction_id }}</strong><br>
                        Waktu Bayar: {{ $payment->paid_at?->format('d M Y, H:i:s') }} WIB
                    </div>
                </div>

            <!-- STATE 2: EXPIRED (KEDALUWARSA) -->
            @elseif($payment->status === 'expired')
                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
                    <div class="w-16 h-16 mx-auto rounded-full bg-slate-200 text-slate-500 flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h2 class="text-xl font-extrabold text-slate-800">QRIS Kedaluwarsa</h2>
                        <p class="text-xs text-slate-500">
                            Waktu pembayaran 5 menit telah habis. Kode QR tidak dapat digunakan lagi.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a href="{{ route('payments.show', $invoice->id) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition shadow-xs">
                            Buat Pembayaran Baru
                        </a>
                    </div>
                </div>

            <!-- STATE 3: FAILED (GAGAL) -->
            @elseif($payment->status === 'failed')
                <div class="p-8 rounded-2xl bg-rose-50 border border-rose-200 space-y-4">
                    <div class="w-16 h-16 mx-auto rounded-full bg-rose-100 text-rose-600 flex items-center justify-center border border-rose-200">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h2 class="text-xl font-extrabold text-rose-950">Pembayaran Gagal</h2>
                        <p class="text-xs text-rose-800">
                            Transaksi pembayaran ditandai gagal atau dibatalkan oleh pengguna.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a href="{{ route('payments.show', $invoice->id) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition shadow-xs">
                            Kembali & Buat Transaksi Baru
                        </a>
                    </div>
                </div>

            <!-- STATE 4: PENDING (ACTIVE SIMULATION) -->
            @else
                <!-- Countdown Realtime Timer (Derived from expired_at) -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1 max-w-sm mx-auto">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Sisa Waktu Pembayaran</span>
                    <div class="text-3xl font-extrabold font-mono text-slate-800 tracking-wider" x-text="timerDisplay">
                        {{ sprintf('%02d:%02d', floor($remainingSeconds / 60), $remainingSeconds % 60) }}
                    </div>
                    <span class="text-[11px] text-slate-400 block">
                        Berlaku sampai: <strong>{{ $payment->expired_at?->format('H:i') }} WIB</strong> (5 menit sejak dibuat)
                    </span>
                </div>

                <!-- Visual QR Code Simulasi -->
                <div class="inline-block p-5 bg-white border-2 border-slate-200 rounded-2xl shadow-xs">
                    <div class="text-center font-bold text-xs tracking-wider text-slate-900 pb-2 border-b border-slate-100 uppercase">
                        QRIS SIMULASI
                    </div>

                    <!-- Clean SVG Mockup for QRIS -->
                    <div class="w-56 h-56 mx-auto my-3 p-3 bg-slate-50 rounded-xl border border-slate-200 flex flex-col items-center justify-center relative overflow-hidden">
                        <svg class="w-full h-full text-slate-900" viewBox="0 0 100 100" fill="currentColor">
                            <!-- Corner Position Detectors -->
                            <rect x="5" y="5" width="28" height="28" rx="4" fill="none" stroke="currentColor" stroke-width="5" />
                            <rect x="12" y="12" width="14" height="14" fill="currentColor" />
                            
                            <rect x="67" y="5" width="28" height="28" rx="4" fill="none" stroke="currentColor" stroke-width="5" />
                            <rect x="74" y="12" width="14" height="14" fill="currentColor" />
                            
                            <rect x="5" y="67" width="28" height="28" rx="4" fill="none" stroke="currentColor" stroke-width="5" />
                            <rect x="12" y="74" width="14" height="14" fill="currentColor" />

                            <!-- Data Pattern Grid Mockup -->
                            <rect x="40" y="8" width="6" height="6" />
                            <rect x="50" y="8" width="6" height="14" />
                            <rect x="40" y="20" width="6" height="6" />
                            <rect x="8" y="40" width="6" height="6" />
                            <rect x="20" y="40" width="14" height="6" />
                            <rect x="40" y="40" width="20" height="20" rx="3" fill="#2563eb" />
                            <rect x="67" y="40" width="10" height="6" />
                            <rect x="84" y="40" width="8" height="8" />
                            <rect x="40" y="67" width="8" height="8" />
                            <rect x="54" y="67" width="6" height="14" />
                            <rect x="67" y="67" width="14" height="6" />
                            <rect x="67" y="80" width="6" height="12" />
                            <rect x="80" y="74" width="12" height="12" />
                        </svg>

                        <!-- Centered Badge in QR -->
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <span class="px-2 py-0.5 rounded bg-white text-[9px] font-black text-blue-700 shadow-md border border-blue-200">
                                SIMULASI
                            </span>
                        </div>
                    </div>

                    <div class="text-[10px] text-slate-400 font-mono tracking-tight">
                        REF: {{ $payment->transaction_id }}
                    </div>
                </div>

                <div class="text-xs text-slate-500 font-medium">
                    Status: <span class="text-amber-600 font-bold uppercase">Menunggu Pembayaran...</span>
                </div>

                <!-- Action Simulation Buttons -->
                <div class="pt-4 border-t border-slate-100 space-y-3">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                        Pilih Aksi Pengujian Simulasi
                    </span>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                        <!-- Simulasikan Berhasil -->
                        <form action="{{ route('payments.simulate.action', $payment->transaction_id) }}" method="POST" class="w-full sm:w-auto">
                            @csrf
                            <input type="hidden" name="action" value="success">
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 shadow-xs transition cursor-pointer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                Simulasikan Berhasil
                            </button>
                        </form>

                        <!-- Simulasikan Gagal -->
                        <form action="{{ route('payments.simulate.action', $payment->transaction_id) }}" method="POST" class="w-full sm:w-auto">
                            @csrf
                            <input type="hidden" name="action" value="failed">
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 shadow-xs transition cursor-pointer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Simulasikan Gagal
                            </button>
                        </form>

                        <!-- Simulasikan Expired -->
                        <form action="{{ route('payments.simulate.action', $payment->transaction_id) }}" method="POST" class="w-full sm:w-auto">
                            @csrf
                            <input type="hidden" name="action" value="expired">
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-700 text-white text-xs font-bold hover:bg-slate-800 shadow-xs transition cursor-pointer">
                                Simulasikan Expired
                            </button>
                        </form>
                    </div>
                </div>
            @endif

        </div>

        <!-- Footer Info -->
        <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 text-[11px] text-slate-400 flex items-center justify-between">
            <span>Metode: <strong class="uppercase text-slate-700">{{ $payment->payment_method }}</strong></span>
            <span>Gateway: <strong class="text-slate-700">{{ ucfirst($payment->payment_gateway) }}</strong></span>
        </div>
    </div>

</div>

<script>
function qrisTimer(config) {
    return {
        seconds: config.remainingSeconds || 0,
        status: config.status,
        timerDisplay: '00:00',
        interval: null,

        init() {
            this.updateDisplay();
            if (this.status === 'pending' && this.seconds > 0) {
                this.interval = setInterval(() => {
                    this.seconds--;
                    this.updateDisplay();

                    if (this.seconds <= 0) {
                        clearInterval(this.interval);
                        // Refresh page so backend validates and turns state into expired
                        window.location.reload();
                    }
                }, 1000);
            }
        },

        updateDisplay() {
            if (this.seconds <= 0) {
                this.timerDisplay = '00:00';
                return;
            }
            const mins = Math.floor(this.seconds / 60);
            const secs = this.seconds % 60;
            this.timerDisplay = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        }
    };
}
</script>
@endsection
