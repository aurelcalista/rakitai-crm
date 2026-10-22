@php
    $pageTitle = 'Detail Pendaftar';
    $pageSubtitle = 'Informasi Lengkap Pendaftar PMB UCIC';
    $userRole = strtolower(auth()->user()->role ?? '');
@endphp

<x-app-layout :title="'Detail Pendaftar - ' . $pendaftar->name . ' - PMB UCIC'">
    <div class="space-y-6" x-data="{
        editModalOpen: false,
        resetModalOpen: false,
    }">

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between text-sm shadow-xs">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl flex items-center justify-between text-sm shadow-xs">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-800">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <!-- Applicant Header Card (Matching Screenshot 4) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight">{{ $pendaftar->name }}</h1>
                    <p class="text-slate-500 text-sm mt-0.5">{{ $pendaftar->email ?: ($pendaftar->whatsapp . '@pmb.cic.ac.id') }}</p>
                    
                    <div class="mt-2.5">
                        @if(($pendaftar->status_formulir ?? 'Belum Isi') === 'Sudah Isi')
                            <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold bg-emerald-600 text-white shadow-xs">
                                Sudah Mengisi Form
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold bg-amber-500 text-white shadow-xs">
                                Belum Mengisi Form
                            </span>
                        @endif

                        @if($pendaftar->no_registrasi)
                            <span class="ml-2 inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-medium bg-slate-100 text-slate-700">
                                Reg: {{ $pendaftar->no_registrasi }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Edit Button -->
                    <button 
                        type="button" 
                        @click="editModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                        title="Edit Data Pendaftar"
                    >
                        <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span>Edit Data</span>
                    </button>

                    <!-- Reset Password Button (Red as in PMB) -->
                    <button 
                        type="button" 
                        @click="resetModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition"
                        title="Reset password akun calon mahasiswa"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        <span>Reset Password</span>
                    </button>

                    <!-- Back Button -->
                    <a 
                        href="{{ route('pmb.pendaftar.index') }}" 
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold border border-blue-400 text-blue-600 hover:bg-blue-50 transition"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 2 Main Cards Grid (Matching Screenshot 4) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Card 1: Status Pendaftaran -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs flex flex-col">
                <!-- Header with Blue Icon -->
                <div class="p-6 pb-4 border-b border-slate-100 flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800">Status Pendaftaran</h2>
                        <p class="text-xs text-slate-500">Kelengkapan formulir dan profil pendaftar</p>
                    </div>
                </div>

                <!-- Body Content -->
                <div class="p-6 flex-1 flex flex-col justify-between">
                    @if(($pendaftar->status_formulir ?? 'Belum Isi') === 'Belum Isi')
                        <!-- State 1: Belum Mengisi Form Pendaftaran (Matches Screenshot 4) -->
                        <div class="py-8 flex flex-col items-center justify-center text-center">
                            <!-- Yellow Alert User Icon -->
                            <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center mb-3">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>

                            <h3 class="text-lg font-bold text-slate-800 tracking-tight">Belum Mengisi Form Pendaftaran</h3>
                            <p class="text-xs text-slate-500 max-w-md mt-1.5 leading-relaxed">
                                User belum mengisi form pendaftaran. Silakan gunakan tombol <span class="text-rose-600 font-semibold">Reset Password</span> untuk memberikan akses login kepada user.
                            </p>

                            <!-- Alert Box with Password -->
                            <div class="w-full mt-6 bg-sky-50 border border-sky-200 text-sky-900 rounded-xl p-3.5 text-xs sm:text-sm font-medium flex items-center justify-center gap-2 shadow-xs">
                                <svg class="w-5 h-5 text-sky-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Password setelah reset: <strong class="font-mono text-sky-950">{{ $pendaftar->email ?: ($pendaftar->whatsapp . '@pmb.cic.ac.id') }}</strong></span>
                            </div>
                        </div>
                    @else
                        <!-- State 2: Sudah Mengisi Form Pendaftaran -->
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <span class="text-slate-500 block text-[11px] font-bold uppercase">Program Studi Pilihan 1</span>
                                    <span class="font-semibold text-slate-800 mt-0.5 block">{{ $pendaftar->prodi?->nama ?? '- Belum Pilih' }}</span>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <span class="text-slate-500 block text-[11px] font-bold uppercase">Program Studi Pilihan 2</span>
                                    <span class="font-semibold text-slate-800 mt-0.5 block">{{ $pendaftar->prodiPilihan2?->nama ?? '-' }}</span>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <span class="text-slate-500 block text-[11px] font-bold uppercase">Gelombang Pendaftaran</span>
                                    <span class="font-semibold text-slate-800 mt-0.5 block">{{ $pendaftar->gelombang ?? '- Data Lama' }}</span>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    <span class="text-slate-500 block text-[11px] font-bold uppercase">Status Berkas</span>
                                    <div class="mt-1">
                                        @php
                                            $berkas = $pendaftar->status_berkas ?? 'Belum Upload';
                                        @endphp
                                        @if($berkas === 'Sudah Upload')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-600 text-white">Sudah Upload</span>
                                        @elseif($berkas === 'Pending')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-500 text-white">Pending</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-600 text-white">Belum Upload</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($pendaftar->notes)
                                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100 text-xs">
                                    <span class="text-slate-500 font-bold uppercase text-[10px] block mb-1">Catatan Tambahan</span>
                                    <p class="text-slate-700 leading-relaxed">{{ $pendaftar->notes }}</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Common Basic Information Footer -->
                    <div class="mt-6 pt-4 border-t border-slate-100 grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[11px]">No. WhatsApp / HP:</span>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $pendaftar->whatsapp) }}" target="_blank" class="font-medium text-blue-600 hover:underline">
                                {{ $pendaftar->whatsapp }}
                            </a>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Asal Sekolah:</span>
                            <span class="font-medium text-slate-700">{{ $pendaftar->sekolah?->nama ?? $pendaftar->school_name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Minat Kuliah:</span>
                            <span class="font-medium text-slate-700 capitalize">{{ $pendaftar->minat_kuliah ?? 'ya' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Minat Miniclass:</span>
                            <span class="font-medium text-slate-700 capitalize">{{ $pendaftar->minat_miniclass ?? 'tidak' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Hasil Ujian (Matches Screenshot 4) -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <!-- Header with Blue Icon -->
                    <div class="p-6 pb-4 border-b border-slate-100 flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Hasil Ujian</h2>
                            <p class="text-xs text-slate-500">Nilai tes seleksi masuk calon mahasiswa</p>
                        </div>
                    </div>

                    <!-- Exam Detail Rows (Matching Screenshot 4) -->
                    <div class="divide-y divide-slate-100">
                        <!-- Row 1: Status Ujian -->
                        <div class="p-4 sm:px-6 flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-medium">Status Ujian</span>
                            <div>
                                @if(($pendaftar->status_ujian ?? 'Belum Mengerjakan') === 'Sudah Mengerjakan')
                                    <span class="inline-flex items-center px-3 py-1 rounded text-xs font-semibold bg-emerald-600 text-white">
                                        Sudah Mengerjakan
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded text-xs font-semibold bg-slate-600 text-white">
                                        Belum Mengerjakan
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Row 2: Nilai Total -->
                        <div class="p-4 sm:px-6 flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-medium">Nilai Total</span>
                            <span class="font-bold text-slate-800 text-sm">
                                @if($pendaftar->nilai_ujian !== null)
                                    {{ number_format($pendaftar->nilai_ujian, 2) }}
                                @else
                                    --
                                @endif
                            </span>
                        </div>

                        <!-- Row 3: Tanggal Ujian -->
                        <div class="p-4 sm:px-6 flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-medium">Tanggal Ujian</span>
                            <span class="text-slate-700 font-medium">
                                @if($pendaftar->tanggal_ujian)
                                    {{ $pendaftar->tanggal_ujian->format('d M Y, H:i') }}
                                @else
                                    --
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Riwayat Transaksi / Pembayaran PMB Terkait -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="p-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800">Riwayat Pembayaran PMB</h3>
                        </div>
                        <a href="{{ route('pmb.pembayaran.index', ['search' => $pendaftar->name]) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                            Lihat di Pembayaran &rarr;
                        </a>
                    </div>

                    <div class="p-4 sm:p-6">
                        @if($pendaftar->transaksis && $pendaftar->transaksis->count() > 0)
                            <div class="space-y-3">
                                @foreach($pendaftar->transaksis as $trx)
                                    @php
                                        $statusClass = match(strtolower($trx->payment_status ?? 'pending')) {
                                            'verified', 'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'rejected', 'failed', 'batal' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            default => 'bg-amber-50 text-amber-700 border-amber-200'
                                        };
                                        $statusBadge = match(strtolower($trx->payment_status ?? 'pending')) {
                                            'verified', 'paid' => 'Paid',
                                            'rejected', 'failed', 'batal' => 'Failed',
                                            default => 'Pending'
                                        };
                                    @endphp
                                    <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono font-semibold text-slate-800">{{ $trx->order_id ?: ('TRX-' . $trx->id) }}</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $statusClass }}">
                                                    {{ $statusBadge }}
                                                </span>
                                            </div>
                                            <p class="text-slate-500 text-[11px] mt-0.5">
                                                {{ $trx->detail_pembayaran ?: ($trx->jenis ?? 'Pendaftaran PMB') }} • {{ $trx->metode_pembayaran ?? 'OFFLINE' }}
                                            </p>
                                        </div>
                                        <div class="text-right sm:shrink-0">
                                            <span class="font-bold text-slate-900 text-sm">Rp {{ number_format($trx->nominal, 0, ',', '.') }}</span>
                                            <span class="text-slate-400 text-[10px] block">{{ $trx->tanggal ? \Carbon\Carbon::parse($trx->tanggal)->format('d M Y, H:i') : '-' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6 text-slate-400 text-xs">
                                <svg class="w-8 h-8 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                </svg>
                                Belum ada riwayat transaksi pembayaran tercatat untuk pendaftar ini.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal: Reset Password Confirmation -->
        <div 
            x-show="resetModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="resetModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="resetModalOpen = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div 
                    x-show="resetModalOpen" 
                    x-transition.scale.origin.bottom
                    class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100"
                >
                    <form action="{{ route('pmb.pendaftar.reset-password', $pendaftar->id) }}" method="POST">
                        @csrf
                        <div class="p-6">
                            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-4">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                            </div>

                            <h3 class="text-lg font-bold text-slate-800">Reset Password Akun PMB?</h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                Tindakan ini akan me-reset password akun pendaftar <strong>{{ $pendaftar->name }}</strong>. Password sementara akan disetel sesuai email/nomor kontak pendaftar:
                            </p>

                            <div class="bg-rose-50 border border-rose-200 text-rose-900 rounded-xl p-3 font-mono text-xs font-semibold mt-3 text-center">
                                {{ $pendaftar->email ?: ($pendaftar->whatsapp . '@pmb.cic.ac.id') }}
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="resetModalOpen = false" 
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs transition"
                            >
                                Ya, Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal: Quick Edit Data Pendaftar -->
        <div 
            x-show="editModalOpen" 
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="editModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="editModalOpen = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div 
                    x-show="editModalOpen" 
                    x-transition.scale.origin.bottom
                    class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-100"
                >
                    <form action="{{ route('pmb.pendaftar.update', $pendaftar->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="p-6 border-b border-slate-100">
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-bold text-slate-800">Edit Data Pendaftar</h3>
                                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Perbarui informasi calon mahasiswa baru</p>
                        </div>

                        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                            <!-- Nama Lengkap -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                                <input type="text" name="name" value="{{ $pendaftar->name }}" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>

                            <!-- Email & No HP -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                                    <input type="email" name="email" value="{{ $pendaftar->email }}" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">No. WhatsApp / HP</label>
                                    <input type="text" name="whatsapp" value="{{ $pendaftar->whatsapp }}" required class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>

                            <!-- Status Formulir & Status Berkas -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status Formulir</label>
                                    <select name="status_formulir" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                        <option value="Belum Isi" {{ ($pendaftar->status_formulir ?? 'Belum Isi') === 'Belum Isi' ? 'selected' : '' }}>Belum Isi</option>
                                        <option value="Sudah Isi" {{ $pendaftar->status_formulir === 'Sudah Isi' ? 'selected' : '' }}>Sudah Isi</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status Berkas</label>
                                    <select name="status_berkas" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                        <option value="Belum Upload" {{ ($pendaftar->status_berkas ?? 'Belum Upload') === 'Belum Upload' ? 'selected' : '' }}>Belum Upload</option>
                                        <option value="Pending" {{ $pendaftar->status_berkas === 'Pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="Sudah Upload" {{ $pendaftar->status_berkas === 'Sudah Upload' ? 'selected' : '' }}>Sudah Upload</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Status Ujian & Nilai Ujian -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status Ujian</label>
                                    <select name="status_ujian" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                        <option value="Belum Mengerjakan" {{ ($pendaftar->status_ujian ?? 'Belum Mengerjakan') === 'Belum Mengerjakan' ? 'selected' : '' }}>Belum Mengerjakan</option>
                                        <option value="Sudah Mengerjakan" {{ $pendaftar->status_ujian === 'Sudah Mengerjakan' ? 'selected' : '' }}>Sudah Mengerjakan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nilai Ujian (0 - 100)</label>
                                    <input type="number" step="0.01" min="0" max="100" name="nilai_ujian" value="{{ $pendaftar->nilai_ujian }}" placeholder="Contoh: 85.50" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                </div>
                            </div>

                            <!-- Gelombang -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Gelombang</label>
                                <input type="text" name="gelombang" value="{{ $pendaftar->gelombang ?? '- Data Lama' }}" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            </div>

                            <!-- Minat Kuliah & Miniclass -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Minat Kuliah</label>
                                    <select name="minat_kuliah" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5">
                                        <option value="ya" {{ ($pendaftar->minat_kuliah ?? 'ya') === 'ya' ? 'selected' : '' }}>Ya</option>
                                        <option value="tidak" {{ ($pendaftar->minat_kuliah ?? '') === 'tidak' ? 'selected' : '' }}>Tidak</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Minat Miniclass</label>
                                    <select name="minat_miniclass" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5">
                                        <option value="tidak" {{ ($pendaftar->minat_miniclass ?? 'tidak') === 'tidak' ? 'selected' : '' }}>Tidak</option>
                                        <option value="ya" {{ ($pendaftar->minat_miniclass ?? '') === 'ya' ? 'selected' : '' }}>Ya</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Catatan -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan</label>
                                <textarea name="notes" rows="2" class="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5">{{ $pendaftar->notes }}</textarea>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-2.5 border-t border-slate-100">
                            <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200/60 rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-xs transition">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
