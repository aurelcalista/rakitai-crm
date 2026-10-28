@extends('pmb.portal.layout')

@section('title', 'Detail Biaya Kuliah - Portal PMB UCIC')

@section('content')
<div class="space-y-8">

    @php
        $isPaid = ($transaksiDaftar?->payment_status ?? '') === \App\Models\Transaksi::STATUS_VERIFIED;
    @endphp

    <!-- Top Blue Hero Banner (Matching Screenshot 3 & 4) -->
    <div class="relative overflow-hidden rounded-3xl bg-[#0b3d82] text-white py-12 px-6 sm:px-12 text-center shadow-lg">
        <!-- Background decorative translucent circles -->
        <div class="absolute -top-12 -left-12 w-48 h-48 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute top-1/2 -right-8 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute bottom-2 left-1/3 w-32 h-32 rounded-full bg-white/5 pointer-events-none"></div>

        <div class="relative z-10 max-w-2xl mx-auto space-y-3">
            <span class="inline-flex items-center gap-1 px-3.5 py-1 rounded-full text-xs font-semibold bg-white/15 text-white">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Informasi Pembayaran</span>
            </span>

            <h1 class="text-3xl sm:text-4xl font-black font-display tracking-tight text-white">
                Detail Biaya Kuliah
            </h1>

            <p class="text-xs sm:text-sm text-blue-100 max-w-xl mx-auto">
                Informasi lengkap mengenai biaya pendidikan dan cara pembayaran di Universitas Catur Insan Cendekia
            </p>

            <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
                <a href="#dokumen-biaya"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs bg-white text-[#0b3d82] hover:bg-slate-100 shadow-sm transition">
                    <svg class="w-4 h-4 text-[#0b3d82]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Lihat Detail</span>
                </a>

                <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=Halo%20Admin%20PMB%20UCIC%2C%20saya%20ingin%20konsultasi%20mengenai%20rincian%20biaya%20kuliah"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs bg-transparent border border-white text-white hover:bg-white/10 transition">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span>Konsultasi</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 3 Highlight Cards (Matching Screenshot 3) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- 1. Biaya Transparan -->
        <div class="bg-white rounded-3xl p-8 border border-slate-200/90 shadow-sm text-center flex flex-col items-center">
            <div class="w-16 h-16 rounded-full bg-blue-500 text-white flex items-center justify-center mb-5 shadow-xs">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 font-display">Biaya Transparan</h3>
            <p class="text-xs text-slate-500 mt-2 max-w-xs leading-relaxed">
                Tanpa biaya tersembunyi, semua sudah tertera jelas
            </p>
        </div>

        <!-- 2. Program Beasiswa -->
        <div class="bg-white rounded-3xl p-8 border border-slate-200/90 shadow-sm text-center flex flex-col items-center">
            <div class="w-16 h-16 rounded-full bg-blue-500 text-white flex items-center justify-center mb-5 shadow-xs">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 font-display">Program Beasiswa</h3>
            <p class="text-xs text-slate-500 mt-2 max-w-xs leading-relaxed">
                Berbagai program beasiswa untuk mahasiswa berprestasi
            </p>
        </div>

        <!-- 3. Konsultasi 24/7 -->
        <div class="bg-white rounded-3xl p-8 border border-slate-200/90 shadow-sm text-center flex flex-col items-center">
            <div class="w-16 h-16 rounded-full bg-blue-500 text-white flex items-center justify-center mb-5 shadow-xs">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 font-display">Konsultasi 24/7</h3>
            <p class="text-xs text-slate-500 mt-2 max-w-xs leading-relaxed">
                Tim kami siap membantu kapan saja Anda memerlukan
            </p>
        </div>
    </div>

    <!-- Document Section: Dokumen Biaya Kuliah (Matching Screenshot 3 & 4) -->
    <div id="dokumen-biaya" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-rose-500">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <h2 class="text-lg font-bold text-slate-900 font-display">
                        Dokumen Biaya Kuliah
                    </h2>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Informasi lengkap mengenai biaya kuliah semua program studi
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button"
                        onclick="window.location.reload()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Refresh</span>
                </button>

                <a href="{{ route('pmb.portal') }}#prodi"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    <span>Buka di Tab Baru</span>
                </a>
            </div>
        </div>

        <!-- Tuition Fee Table (Rincian Biaya PMB UCIC) -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#0b3d82] text-white font-semibold">
                    <tr>
                        <th class="px-4 py-3.5">Fakultas / Program Studi</th>
                        <th class="px-4 py-3.5">Jenjang</th>
                        <th class="px-4 py-3.5">Kelas</th>
                        <th class="px-4 py-3.5">Biaya Pendaftaran</th>
                        <th class="px-4 py-3.5">Estimasi Biaya / Semester</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-semibold text-slate-900">S1 Teknik Informatika</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                        <td class="px-4 py-3">Reguler Pagi</td>
                        <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                        <td class="px-4 py-3 font-bold text-slate-900">Rp 3.500.000</td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition bg-amber-50/20">
                        <td class="px-4 py-3 font-semibold text-slate-900">S1 Teknik Informatika (Kelas Sore/Karyawan)</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                        <td class="px-4 py-3">Kelas Sore</td>
                        <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                        <td class="px-4 py-3 font-bold text-slate-900">Rp 3.575.000</td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-semibold text-slate-900">S1 Sistem Informasi</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                        <td class="px-4 py-3">Reguler Pagi</td>
                        <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                        <td class="px-4 py-3 font-bold text-slate-900">Rp 3.200.000</td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition bg-amber-50/20">
                        <td class="px-4 py-3 font-semibold text-slate-900">S1 Sistem Informasi (Kelas Sore/Karyawan)</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                        <td class="px-4 py-3">Kelas Sore</td>
                        <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                        <td class="px-4 py-3 font-bold text-slate-900">Rp 3.350.000</td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-semibold text-slate-900">S1 Desain Komunikasi Visual (DKV)</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                        <td class="px-4 py-3">Reguler Pagi</td>
                        <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                        <td class="px-4 py-3 font-bold text-slate-900">Rp 3.400.000</td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-semibold text-slate-900">S1 Manajemen Bisnis</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                        <td class="px-4 py-3">Reguler Pagi / Sore</td>
                        <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                        <td class="px-4 py-3 font-bold text-slate-900">Rp 3.000.000</td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-semibold text-slate-900">S1 Akuntansi</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">S1</span></td>
                        <td class="px-4 py-3">Reguler Pagi / Sore</td>
                        <td class="px-4 py-3 font-semibold text-emerald-700">Rp 300.000</td>
                        <td class="px-4 py-3 font-bold text-slate-900">Rp 3.000.000</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Info Card: Pembayaran Biaya Pendaftaran -->
        <div class="p-5 rounded-2xl bg-blue-50 border border-blue-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h4 class="text-sm font-bold text-blue-900">Lanjutkan Pembayaran Biaya Formulir (Rp 300.000)</h4>
                <p class="text-xs text-blue-700 mt-0.5">Biaya formulir berlaku untuk seluruh pilihan prodi dan diproses aman via Midtrans Snap.</p>
            </div>
            @if($isPaid)
                <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white">
                    ✓ Sudah Lunas
                </span>
            @else
                <a href="{{ route('pmb.akun.bayar') }}"
                   class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-xs font-bold bg-[#0b3d82] hover:bg-[#082a5c] text-white shadow-xs transition shrink-0">
                    Bayar Sekarang &rarr;
                </a>
            @endif
        </div>
    </div>

</div>
@endsection
