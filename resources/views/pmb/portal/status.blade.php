@extends('pmb.portal.layout')

@section('title', 'Status Pendaftaran CAMABA')

@section('content')
<div class="space-y-6">

    <!-- Header Breadcrumb / Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
        <div>
            <h1 class="text-2xl font-black text-slate-900 font-display tracking-tight">Status Pendaftaran</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau perkembangan tahapan pendaftaran mahasiswa baru Anda</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                No. Reg: {{ $prospek->no_registrasi ?? '-' }}
            </span>
            <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                {{ $prospek->gelombang ?? 'Gelombang 1' }}
            </span>
        </div>
    </div>

    <!-- Stepper Timeline Visualizer -->
    <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-2xs">
        <h2 class="text-base font-bold text-slate-900 font-display mb-6">Tahapan Pendaftaran PMB</h2>

        @php
            $isPaid = ($transaksiDaftar?->payment_status ?? '') === \App\Models\Transaksi::STATUS_VERIFIED;
            $hasProdi = !empty($prospek->prodi_id);
            $berkasStatus = $prospek->status_berkas ?? 'Belum Upload';
            $ujianStatus = $prospek->status_ujian ?? 'Belum Mengerjakan';
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 relative">
            <!-- Stage 1: Akun Terdaftar -->
            <div class="p-4 rounded-2xl border-2 {{ $prospek ? 'border-emerald-500 bg-emerald-50/40' : 'border-slate-200 bg-slate-50' }}">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-6 h-6 rounded-full bg-emerald-600 text-white text-xs font-bold flex items-center justify-center">
                        ✓
                    </span>
                    <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Tahap 1</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Registrasi Akun</h3>
                <p class="text-xs text-slate-500 mt-1">Akun portal aktif dan terhubung.</p>
                <span class="inline-block mt-3 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                    Selesai
                </span>
            </div>

            <!-- Stage 2: Pembayaran Formulir -->
            <div class="p-4 rounded-2xl border-2 {{ $isPaid ? 'border-emerald-500 bg-emerald-50/40' : 'border-amber-400 bg-amber-50/40' }}">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-6 h-6 rounded-full {{ $isPaid ? 'bg-emerald-600' : 'bg-amber-500' }} text-white text-xs font-bold flex items-center justify-center">
                        {{ $isPaid ? '✓' : '2' }}
                    </span>
                    <span class="text-xs font-bold {{ $isPaid ? 'text-emerald-800' : 'text-amber-800' }} uppercase tracking-wider">Tahap 2</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Biaya Formulir</h3>
                <p class="text-xs text-slate-500 mt-1">Nominal Rp 300.000 via Midtrans Snap.</p>
                <div class="mt-3">
                    @if($isPaid)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            Lunas (Verified)
                        </span>
                    @else
                        <a href="{{ route('pmb.akun.bayar') }}" class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-2xs">
                            Bayar Sekarang &rarr;
                        </a>
                    @endif
                </div>
            </div>

            <!-- Stage 3: Kelengkapan Berkas & Data -->
            <div class="p-4 rounded-2xl border-2 {{ $berkasStatus === 'Sudah Upload' ? 'border-emerald-500 bg-emerald-50/40' : ($berkasStatus === 'Pending' ? 'border-amber-400 bg-amber-50/40' : 'border-slate-200 bg-white') }}">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-6 h-6 rounded-full {{ $berkasStatus === 'Sudah Upload' ? 'bg-emerald-600' : 'bg-slate-300 text-slate-700' }} text-white text-xs font-bold flex items-center justify-center">
                        {{ $berkasStatus === 'Sudah Upload' ? '✓' : '3' }}
                    </span>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tahap 3</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Verifikasi Berkas</h3>
                <p class="text-xs text-slate-500 mt-1">Status formulir & dokumen ijazah/rapor.</p>
                <span class="inline-block mt-3 px-2 py-0.5 rounded text-[10px] font-bold {{ $berkasStatus === 'Sudah Upload' ? 'bg-emerald-100 text-emerald-800' : ($berkasStatus === 'Pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                    {{ $berkasStatus }}
                </span>
            </div>

            <!-- Stage 4: Ujian & Hasil Seleksi -->
            <div class="p-4 rounded-2xl border-2 {{ $ujianStatus === 'Sudah Mengerjakan' ? 'border-emerald-500 bg-emerald-50/40' : 'border-slate-200 bg-white' }}">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-6 h-6 rounded-full {{ $ujianStatus === 'Sudah Mengerjakan' ? 'bg-emerald-600' : 'bg-slate-300 text-slate-700' }} text-white text-xs font-bold flex items-center justify-center">
                        {{ $ujianStatus === 'Sudah Mengerjakan' ? '✓' : '4' }}
                    </span>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tahap 4</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Ujian & Kelulusan</h3>
                <p class="text-xs text-slate-500 mt-1">Tes TPA Online / Hasil seleksi PMB.</p>
                <span class="inline-block mt-3 px-2 py-0.5 rounded text-[10px] font-bold {{ $ujianStatus === 'Sudah Mengerjakan' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700') }}">
                    {{ $ujianStatus === 'Sudah Mengerjakan' ? ('Nilai: ' . ($prospek->nilai_ujian ?? 'Lulus')) : 'Menunggu Jadwal' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Detailed Status Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Card: Status Data Lapangan CRM -->
        <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-2xs space-y-4">
            <h3 class="text-base font-bold text-slate-900 font-display pb-3 border-b border-slate-100">
                Detail Status di Sistem PMB
            </h3>

            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                    <span class="text-slate-500">Status Pendaftar (CRM):</span>
                    <span class="font-bold text-slate-800 uppercase px-2 py-0.5 rounded bg-white border border-slate-200">
                        {{ $prospek->status ?? 'BARU' }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                    <span class="text-slate-500">Status Pengisian Formulir:</span>
                    <span class="font-bold {{ ($prospek->status_formulir ?? '') === 'Sudah Isi' ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $prospek->status_formulir ?? 'Belum Isi' }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                    <span class="text-slate-500">Status Upload Berkas:</span>
                    <span class="font-bold text-slate-800">
                        {{ $prospek->status_berkas ?? 'Belum Upload' }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                    <span class="text-slate-500">Status Ujian Masuk:</span>
                    <span class="font-bold text-slate-800">
                        {{ $prospek->status_ujian ?? 'Belum Mengerjakan' }}
                    </span>
                </div>

                @if($prospek->tanggal_ujian)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                        <span class="text-slate-500">Tanggal Pelaksanaan Ujian:</span>
                        <span class="font-bold text-slate-800">
                            {{ $prospek->tanggal_ujian->format('d F Y H:i') }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Card: Instruksi Langkah Berikutnya -->
        <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-2xs space-y-4">
            <h3 class="text-base font-bold text-slate-900 font-display pb-3 border-b border-slate-100">
                Petunjuk & Instruksi Selanjutnya
            </h3>

            <div class="text-xs text-slate-600 space-y-3 leading-relaxed">
                @if(!$isPaid)
                    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900">
                        <h4 class="font-bold text-amber-900 mb-1">Perhatian: Biaya Formulir Belum Dibayar</h4>
                        <p>Silakan selesaikan pembayaran biaya formulir pendaftaran Rp 300.000 agar proses seleksi dan verifikasi berkas Anda dapat dilanjutkan oleh panitia PMB.</p>
                        <a href="{{ route('pmb.akun.bayar') }}" class="mt-3 inline-flex items-center gap-1 font-bold text-amber-900 underline hover:text-amber-700">
                            Menuju Halaman Pembayaran &rarr;
                        </a>
                    </div>
                @elseif(!$hasProdi)
                    <div class="p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900">
                        <h4 class="font-bold text-blue-900 mb-1">Pilihan Program Studi Belum Ditentukan</h4>
                        <p>Pembayaran Anda telah diverifikasi! Langkah selanjutnya adalah memilih program studi yang Anda minati di halaman profil.</p>
                        <a href="{{ route('pmb.akun.profil') }}" class="mt-3 inline-flex items-center gap-1 font-bold text-blue-900 underline hover:text-blue-700">
                            Tentukan Program Studi &rarr;
                        </a>
                    </div>
                @else
                    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900">
                        <h4 class="font-bold text-emerald-900 mb-1">Data Pendaftaran Anda Lengkap!</h4>
                        <p>Data diri dan pilihan program studi Anda telah tercatat dengan baik. Tim panitia PMB UCIC akan segera menghubungi Anda melalui WhatsApp untuk jadwal ujian seleksi online atau verifikasi berkas.</p>
                    </div>
                @endif

                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                    <span class="font-bold text-slate-800 block mb-1">Kontak Konfirmasi Panitia:</span>
                    <p class="text-slate-500">Jika Anda memerlukan bantuan khusus atau telah melakukan transfer manual ke rekening kampus, kirimkan bukti pembayaran ke Sekretariat PMB via WhatsApp:</p>
                    <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=Halo%20Admin%20PMB%20UCIC%2C%20saya%20{{ urlencode($user->name) }}%20(No%20Reg%3A%20{{ $prospek->no_registrasi ?? '-' }})%20ingin%20konfirmasi%20pendaftaran"
                       target="_blank"
                       class="mt-2 inline-flex items-center gap-1.5 font-bold text-emerald-600 hover:text-emerald-700">
                        <span>💬 Chat WhatsApp CS (0812-1670-0519)</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
