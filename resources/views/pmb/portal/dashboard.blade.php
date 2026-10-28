@extends('pmb.portal.layout')

@section('title', 'Dashboard Calon Mahasiswa')

@section('content')
<div class="space-y-6">

    <!-- Welcome Hero Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-900 text-white p-6 sm:p-8 shadow-lg shadow-blue-900/10">
        <!-- Ambient Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-white/20 text-white mb-2">
                    🎓 Portal Resmi PMB UCIC
                </span>
                <h1 class="text-2xl sm:text-3xl font-black font-display tracking-tight">
                    Halo, {{ $user->name }}!
                </h1>
                <p class="text-xs sm:text-sm text-blue-100 mt-1 max-w-xl">
                    Selamat datang di portal pendaftaran mahasiswa baru Universitas Catur Insan Cendekia. Pantau status pendaftaran, pembayaran formulir, dan lengkapi berkas Anda di sini.
                </p>
            </div>

            <!-- Quick Action Button -->
            <div class="shrink-0 flex sm:flex-col gap-2">
                @if(($transaksiAktif?->payment_status ?? '') === \App\Models\Transaksi::STATUS_VERIFIED)
                    <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-500 text-white shadow-xs">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Formulir Terbayar</span>
                    </span>
                @else
                    <a href="{{ route('pmb.akun.bayar') }}"
                       class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold bg-amber-400 hover:bg-amber-300 text-slate-900 shadow-md transition active:scale-95">
                        <svg class="w-4 h-4 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Bayar Biaya Formulir</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Registration Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Nomor Registrasi -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">No. Registrasi</span>
                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <span class="text-lg font-black text-slate-900 font-display block">
                {{ $prospek->no_registrasi ?? '-' }}
            </span>
            <span class="text-[11px] text-slate-500 mt-1 block">
                {{ $prospek->gelombang ?? 'Gelombang 1' }}
            </span>
        </div>

        <!-- 2. Status Pembayaran -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Biaya Formulir</span>
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            @php
                $isPaid = ($transaksiAktif?->payment_status ?? '') === \App\Models\Transaksi::STATUS_VERIFIED;
            @endphp
            <div class="flex items-center gap-2">
                @if($isPaid)
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        LUNAS (Verified)
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        Belum Lunas (Rp 300.000)
                    </span>
                @endif
            </div>
            <a href="{{ route('pmb.akun.bayar') }}" class="text-[11px] text-blue-600 hover:underline mt-1.5 block font-semibold">
                {{ $isPaid ? 'Lihat Bukti Bayar &rarr;' : 'Bayar Sekarang &rarr;' }}
            </a>
        </div>

        <!-- 3. Kelengkapan Berkas -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Status Berkas</span>
                <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            @php
                $berkasStatus = $prospek->status_berkas ?? 'Belum Upload';
            @endphp
            <div>
                @if($berkasStatus === 'Sudah Upload')
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Sudah Lengkap
                    </span>
                @elseif($berkasStatus === 'Pending')
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        Sedang Diverifikasi
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        Belum Upload
                    </span>
                @endif
            </div>
            <span class="text-[11px] text-slate-500 mt-1.5 block">Dokumen Ijazah/Rapor</span>
        </div>

        <!-- 4. Status Ujian Seleksi -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Ujian Seleksi</span>
                <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            @php
                $ujianStatus = $prospek->status_ujian ?? 'Belum Mengerjakan';
            @endphp
            <div>
                @if($ujianStatus === 'Sudah Mengerjakan')
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Selesai (Skor: {{ $prospek->nilai_ujian ?? 'Lulus' }})
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                        Belum Ujian
                    </span>
                @endif
            </div>
            <span class="text-[11px] text-slate-500 mt-1.5 block">Tes Potensi Akademik Online</span>
        </div>
    </div>

    <!-- Main Grid: Registration Details & Next Action Checklist -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1 & 2: Informasi Detail Pendaftar -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Detail Pendaftaran Card -->
            <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-2xs">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 font-display">Ringkasan Data Pendaftaran</h2>
                        <p class="text-xs text-slate-500">Data pribadi dan program studi pilihan Anda</p>
                    </div>
                    <a href="{{ route('pmb.akun.profil') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline">
                        Edit Profil &rarr;
                    </a>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 font-semibold block text-[11px]">Nama Lengkap:</span>
                        <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $prospek->name ?? $user->name }}</span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 font-semibold block text-[11px]">Email Terdaftar:</span>
                        <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $user->email }}</span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 font-semibold block text-[11px]">Nomor WhatsApp:</span>
                        <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $prospek->whatsapp ?? '-' }}</span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 font-semibold block text-[11px]">Asal Sekolah:</span>
                        <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $prospek->sekolah?->nama ?? 'Belum ditentukan' }}</span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-blue-50/60 border border-blue-100">
                        <span class="text-blue-600 font-bold block text-[11px] uppercase">Pilihan Prodi 1:</span>
                        <span class="font-bold text-slate-900 text-sm mt-0.5 block">
                            {{ $prospek->prodi?->nama ?? 'Belum memilih prodi' }}
                        </span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 font-semibold block text-[11px] uppercase">Pilihan Prodi 2:</span>
                        <span class="font-bold text-slate-800 text-sm mt-0.5 block">
                            {{ $prospek->prodiPilihan2?->nama ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Riwayat Transaksi Card -->
            <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-2xs">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 font-display">Riwayat Tagihan & Pembayaran</h2>
                        <p class="text-xs text-slate-500">Semua transaksi PMB yang terhubung ke akun Anda</p>
                    </div>
                    <a href="{{ route('pmb.akun.bayar') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline">
                        Buka Portal Bayar &rarr;
                    </a>
                </div>

                <div class="mt-4 divide-y divide-slate-100">
                    @forelse($riwayatTransaksi as $tx)
                        <div class="py-3 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-800 block">{{ $tx->kategori ?? 'Biaya Formulir PMB' }}</span>
                                <span class="text-slate-400 text-[11px]">Order ID: {{ $tx->order_id ?? ('TRX-' . $tx->id) }} • {{ $tx->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-black text-slate-900 block font-display">Rp {{ number_format($tx->nominal, 0, ',', '.') }}</span>
                                @if($tx->payment_status === \App\Models\Transaksi::STATUS_VERIFIED)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Verified Lunas
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
                            Belum ada riwayat transaksi pembayaran.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Column 3: Checklist Langkah Berikutnya -->
        <div class="space-y-6">

            <!-- Next Steps Checklist Card -->
            <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-2xs">
                <h3 class="text-base font-bold text-slate-900 font-display mb-1">Langkah Pendaftaran Anda</h3>
                <p class="text-xs text-slate-500 mb-5">Panduan tahapan yang perlu Anda selesaikan</p>

                <div class="space-y-4 text-xs">
                    <!-- Step 1: Buat Akun (Done) -->
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <span class="font-bold text-slate-800 block">Registrasi Akun PMB</span>
                            <span class="text-emerald-600 text-[11px] font-medium">Selesai</span>
                        </div>
                    </div>

                    <!-- Step 2: Bayar Formulir -->
                    <div class="flex items-start gap-3">
                        @if($isPaid)
                            <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 block">Pembayaran Formulir Rp 300.000</span>
                                <span class="text-emerald-600 text-[11px] font-medium">Lunas terverifikasi</span>
                            </div>
                        @else
                            <div class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                2
                            </div>
                            <div>
                                <span class="font-bold text-slate-900 block">Pembayaran Formulir Rp 300.000</span>
                                <span class="text-amber-700 text-[11px] font-medium block">Segera selesaikan via Midtrans</span>
                                <a href="{{ route('pmb.akun.bayar') }}" class="text-blue-600 font-bold hover:underline inline-block mt-1">
                                    Bayar Sekarang &rarr;
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Step 3: Pilih Program Studi -->
                    <div class="flex items-start gap-3">
                        @if($prospek->prodi_id)
                            <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 block">Pilihan Program Studi</span>
                                <span class="text-emerald-600 text-[11px] font-medium">{{ $prospek->prodi->nama }}</span>
                            </div>
                        @else
                            <div class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                3
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 block">Pilihan Program Studi</span>
                                <a href="{{ route('pmb.akun.profil') }}" class="text-blue-600 font-semibold hover:underline block mt-0.5">
                                    Tentukan Program Studi &rarr;
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Step 4: Ujian & Seleksi -->
                    <div class="flex items-start gap-3">
                        @if(($prospek->status_ujian ?? '') === 'Sudah Mengerjakan')
                            <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 block">Ujian Seleksi Online</span>
                                <span class="text-emerald-600 text-[11px] font-medium">Selesai dikerjakan</span>
                            </div>
                        @else
                            <div class="w-6 h-6 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                4
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 block">Ujian Seleksi Online</span>
                                <span class="text-slate-400 text-[11px] block">Jadwal akan diumumkan setelah pembayaran</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- WhatsApp Direct Help Card -->
            <div class="p-6 rounded-3xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white shadow-md">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-100 block mb-1">Butuh Bantuan?</span>
                <h4 class="text-lg font-bold font-display leading-tight">Chat CS PMB UCIC</h4>
                <p class="text-xs text-emerald-100 mt-2 leading-relaxed">
                    Jika Anda memiliki kendala pembayaran, salah input data, atau pertanyaan beasiswa, tim kami siap membantu Anda.
                </p>
                <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=Halo%20Admin%20PMB%20UCIC%2C%20saya%20{{ urlencode($user->name) }}%20(No%20Reg%3A%20{{ $prospek->no_registrasi ?? '-' }})%20ingin%20bertanya"
                   target="_blank"
                   class="mt-4 inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-white text-emerald-800 hover:bg-emerald-50 transition shadow-xs">
                    <span>Chat CS PMB via WhatsApp</span>
                </a>
            </div>

        </div>
    </div>

</div>
@endsection
