<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Pembayaran Pendaftaran PMB - {{ $pendaftar->name }} - UCIC</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ucic.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Midtrans Snap.js -->
    @php
        $snapJsUrl = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
        $clientKey = (string) config('services.midtrans.client_key');
    @endphp
    <script src="{{ $snapJsUrl }}" data-client-key="{{ $clientKey }}"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased selection:bg-blue-600 selection:text-white flex flex-col justify-between"
      x-data="camabaPayment({
          orderId: '{{ $transaksi->order_id }}',
          prospekId: {{ $pendaftar->id }},
          initialStatus: '{{ $transaksi->payment_status }}',
          initialSnapToken: '{{ $transaksi->snap_token }}',
          nominal: {{ $transaksi->nominal }},
          statusCheckUrl: '{{ route('pmb.pembayaran.status', $transaksi->order_id) }}',
          checkoutUrl: '{{ route('pmb.checkout', $pendaftar->id) }}'
      })"
      x-init="init()">

    <!-- Top Navigation Header -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-20 shadow-2xs">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo-no-text.png') }}" alt="Logo UCIC" class="h-10 w-auto object-contain shrink-0">
                <div>
                    <h1 class="text-sm font-bold text-slate-900 leading-tight font-display">PMB Universitas CIC</h1>
                    <p class="text-[11px] text-slate-500">Portal Pembayaran Online Resmi</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if(config('services.midtrans.is_production'))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Secure Midtrans Live
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Sandbox Mode (Uji Coba)
                    </span>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-3xl w-full mx-auto px-4 sm:px-6 py-8 flex-1">

        <!-- Error Alert Banner -->
        <div x-show="errorMessage" x-cloak class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-start gap-3 shadow-xs">
            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="flex-1">
                <span class="font-bold block">Pemberitahuan:</span>
                <span x-text="errorMessage"></span>
            </div>
            <button type="button" @click="errorMessage = ''" class="text-rose-500 hover:text-rose-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Success Alert Banner (Live Verification Update) -->
        <div x-show="isVerified" x-cloak class="mb-6 p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div>
                <h2 class="text-base font-bold text-emerald-900">Pembayaran Berhasil & Terverifikasi Sah!</h2>
                <p class="text-xs text-emerald-700 mt-0.5">
                    Biaya pendaftaran sebesar <strong class="font-semibold">Rp {{ number_format($transaksi->nominal, 0, ',', '.') }}</strong> telah diterima. Akun pendaftaran Anda telah aktif masuk ke tahap <span class="font-bold underline">FORMULIR</span>.
                </p>
            </div>
        </div>

        <!-- Invoice / Payment Card -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-200/40 overflow-hidden">

            <!-- Card Header -->
            <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 text-white p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-bold bg-white/20 text-white backdrop-blur-xs mb-2">
                            Tagihan PMB UCIC
                        </span>
                        <h2 class="text-xl sm:text-2xl font-black tracking-tight font-display">Biaya Pendaftaran Mahasiswa Baru</h2>
                        <p class="text-xs text-blue-100 mt-1 font-mono">Order ID: {{ $transaksi->order_id }}</p>
                    </div>

                    <!-- Status Pill -->
                    <div>
                        <template x-if="isVerified">
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-500 text-white shadow-xs">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>LUNAS / PAID</span>
                            </span>
                        </template>
                        <template x-if="isPending && !isVerified">
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-400 text-amber-950 shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-amber-900 animate-ping"></span>
                                <span>MENUNGGU PEMBAYARAN</span>
                            </span>
                        </template>
                        <template x-if="isRejected && !isVerified">
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-rose-600 text-white shadow-xs">
                                <span>GAGAL / BATAL</span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Details Section -->
            <div class="p-6 sm:p-8 space-y-6">

                <!-- Applicant Information Grid -->
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-3">Data Calon Mahasiswa</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-slate-50/80 p-4.5 rounded-2xl border border-slate-100">
                        <div>
                            <span class="text-slate-500 block">Nama Lengkap:</span>
                            <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $pendaftar->name }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">No. Registrasi:</span>
                            <span class="font-mono font-semibold text-slate-800 mt-0.5 block">{{ $pendaftar->no_registrasi ?: '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Program Studi Pilihan:</span>
                            <span class="font-medium text-slate-800 mt-0.5 block">{{ $pendaftar->prodi?->nama ?? '- Belum Dipilih' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">No. WhatsApp / HP:</span>
                            <span class="font-medium text-slate-800 mt-0.5 block">{{ $pendaftar->whatsapp }}</span>
                        </div>
                    </div>
                </div>

                <!-- Breakdown Item Tagihan -->
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-3">Rincian Pembayaran</h3>
                    <div class="border border-slate-200/80 rounded-2xl divide-y divide-slate-100 overflow-hidden text-xs">
                        <div class="p-4 flex items-center justify-between bg-white">
                            <div>
                                <span class="font-bold text-slate-800 block">Biaya Formulir & Pendaftaran PMB UCIC</span>
                                <span class="text-slate-500 text-[11px] mt-0.5 block">Akses formulir, tes seleksi masuk, dan pembuatan akun calon mahasiswa</span>
                            </div>
                            <span class="font-semibold text-slate-800 text-sm">
                                Rp {{ number_format($transaksi->nominal, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="p-4 bg-slate-50 flex items-center justify-between font-bold text-slate-900 text-sm">
                            <span>Total Tagihan:</span>
                            <span class="text-blue-700 text-base">
                                Rp {{ number_format($transaksi->nominal, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Button Area -->
                <div class="pt-2">
                    <template x-if="!isVerified">
                        <div class="space-y-3">
                            <button
                                type="button"
                                @click="payWithSnap()"
                                :disabled="isLoading"
                                class="w-full py-3.5 px-6 rounded-2xl bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-extrabold text-sm sm:text-base shadow-lg shadow-blue-500/25 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:pointer-events-none font-display"
                            >
                                <svg x-show="isLoading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg x-show="!isLoading" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <span x-text="isLoading ? 'Menghubungkan ke Midtrans...' : (currentSnapToken ? 'Lanjutkan Pembayaran (Midtrans Snap)' : 'Bayar Sekarang via Midtrans')"></span>
                            </button>

                            <p class="text-center text-[11px] text-slate-500">
                                Mendukung QRIS, GoPay, ShopeePay, DANA, Virtual Account BCA/BNI/BRI/Mandiri, dan Kartu Kredit.
                            </p>
                        </div>
                    </template>

                    <template x-if="isVerified">
                        <div class="text-center space-y-3">
                            <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Transaksi ini telah selesai diverifikasi. Anda tidak perlu membayar lagi.</span>
                            </div>
                        </div>
                    </template>
                </div>

            </div>

            <!-- Footer Notes -->
            <div class="p-6 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Butuh bantuan pembayaran? Hubungi Sekretariat PMB UCIC di <strong>pmb@cic.ac.id</strong></span>
                </div>
                <div>
                    <button type="button" @click="checkStatus(true)" class="font-semibold text-blue-600 hover:text-blue-800 transition flex items-center gap-1 cursor-pointer">
                        <svg class="w-3.5 h-3.5" :class="isChecking ? 'animate-spin text-blue-600' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Cek Status Terbaru</span>
                    </button>
                </div>
            </div>

        </div>

    </main>

    <!-- Page Footer -->
    <footer class="py-6 text-center text-xs text-slate-400 border-t border-slate-200/60 bg-white">
        <p>&copy; {{ date('Y') }} Universitas Catur Insan Cendekia (UCIC). Sistem PMB & Keuangan Terintegrasi Midtrans.</p>
    </footer>

    <!-- Alpine.js Client Logic for Midtrans Snap -->
    <script>
        function camabaPayment(config) {
            return {
                orderId: config.orderId,
                prospekId: config.prospekId,
                status: config.initialStatus,
                currentSnapToken: config.initialSnapToken,
                statusCheckUrl: config.statusCheckUrl,
                checkoutUrl: config.checkoutUrl,
                isLoading: false,
                isChecking: false,
                errorMessage: '',
                pollInterval: null,

                get isVerified() {
                    return ['verified', 'paid'].includes(this.status.toLowerCase());
                },
                get isPending() {
                    return ['pending'].includes(this.status.toLowerCase());
                },
                get isRejected() {
                    return ['rejected', 'failed', 'batal'].includes(this.status.toLowerCase());
                },

                init() {
                    // Jika masih pending, jalankan polling status otomatis berkala
                    if (this.isPending) {
                        this.startPolling();
                    }
                },

                startPolling() {
                    if (this.pollInterval) clearInterval(this.pollInterval);
                    this.pollInterval = setInterval(() => {
                        if (!this.isVerified) {
                            this.checkStatus(false);
                        } else {
                            clearInterval(this.pollInterval);
                        }
                    }, 5000);
                },

                async checkStatus(showFeedback = false) {
                    this.isChecking = true;
                    try {
                        const response = await fetch(this.statusCheckUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await response.json();
                        if (data && data.payment_status) {
                            this.status = data.payment_status;
                            if (this.isVerified && this.pollInterval) {
                                clearInterval(this.pollInterval);
                            }
                        }
                    } catch (e) {
                        console.error('Status check error:', e);
                    } finally {
                        this.isChecking = false;
                    }
                },

                async payWithSnap() {
                    this.isLoading = true;
                    this.errorMessage = '';

                    try {
                        // 1. Ambil atau buat Snap Token jika belum tersedia
                        let token = this.currentSnapToken;
                        if (!token) {
                            const response = await fetch(this.checkoutUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({
                                    order_id: this.orderId
                                })
                            });

                            const data = await response.json();
                            if (!data.success) {
                                throw new Error(data.message || 'Gagal memproses tagihan pembayaran.');
                            }

                            token = data.snap_token;
                            this.currentSnapToken = token;
                        }

                        if (!token) {
                            throw new Error('Snap Token tidak ditemukan.');
                        }

                        // 2. Buka Midtrans Snap Popup
                        if (typeof window.snap === 'undefined') {
                            throw new Error('Midtrans Snap SDK gagal dimuat. Pastikan koneksi internet aktif.');
                        }

                        const self = this;
                        window.snap.pay(token, {
                            onSuccess: function(result) {
                                self.checkStatus(true);
                                self.startPolling();
                            },
                            onPending: function(result) {
                                self.checkStatus(true);
                                self.startPolling();
                            },
                            onError: function(result) {
                                self.errorMessage = 'Pembayaran tidak berhasil diselesaikan. Silakan coba metode lain.';
                                self.checkStatus(true);
                            },
                            onClose: function() {
                                // User menutup popup, periksa status terkini
                                self.checkStatus(false);
                            }
                        });

                    } catch (error) {
                        this.errorMessage = error.message || 'Terjadi kesalahan saat membuka sistem pembayaran.';
                    } finally {
                        this.isLoading = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
