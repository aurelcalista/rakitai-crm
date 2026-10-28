@extends('pmb.portal.layout')

@section('title', 'Pusat Bantuan & Kontak PMB UCIC')

@section('content')
<div class="space-y-6">

    <!-- Header Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
        <div>
            <h1 class="text-2xl font-black text-slate-900 font-display tracking-tight">Pusat Bantuan PMB</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Panduan lengkap pendaftaran, pembayaran, dan kontak resmi panitia PMB UCIC</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=Halo%20Admin%20PMB%20UCIC%2C%20saya%20butuh%20bantuan"
               target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-600 text-white shadow-xs transition">
                <span>💬 Chat WhatsApp CS</span>
            </a>
        </div>
    </div>

    <!-- Contact Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- WhatsApp -->
        <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-2xs flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg mb-3">
                    💬
                </div>
                <h3 class="text-base font-bold text-slate-900">WhatsApp CS PMB</h3>
                <p class="text-xs text-slate-500 mt-1">Konsultasi cepat, kendala pendaftaran, dan konfirmasi bukti bayar.</p>
                <span class="text-sm font-black text-slate-800 block mt-3 font-display">0812-1670-0519</span>
            </div>
            <a href="https://api.whatsapp.com/send/?phone=6281216700519&text=Halo%20Admin%20PMB%20UCIC"
               target="_blank"
               class="mt-4 text-xs font-bold text-emerald-600 hover:underline inline-block">
                Hubungi via WhatsApp &rarr;
            </a>
        </div>

        <!-- Telepon Kampus -->
        <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-2xs flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg mb-3">
                    📞
                </div>
                <h3 class="text-base font-bold text-slate-900">Telepon Kampus</h3>
                <p class="text-xs text-slate-500 mt-1">Jam layanan kantor: Senin - Jumat (08.00 - 16.00 WIB).</p>
                <span class="text-sm font-black text-slate-800 block mt-3 font-display">(0231) 200418</span>
            </div>
            <a href="tel:0231200418" class="mt-4 text-xs font-bold text-blue-600 hover:underline inline-block">
                Panggil Nomor &rarr;
            </a>
        </div>

        <!-- Email Resmi -->
        <div class="p-6 rounded-3xl bg-white border border-slate-200/80 shadow-2xs flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-lg mb-3">
                    ✉️
                </div>
                <h3 class="text-base font-bold text-slate-900">Email PMB UCIC</h3>
                <p class="text-xs text-slate-500 mt-1">Kirimkan berkas atau pertanyaan resmi ke email panitia.</p>
                <span class="text-sm font-black text-slate-800 block mt-3 font-display">pmb@cic.ac.id</span>
            </div>
            <a href="mailto:pmb@cic.ac.id" class="mt-4 text-xs font-bold text-indigo-600 hover:underline inline-block">
                Kirim Email &rarr;
            </a>
        </div>
    </div>

    <!-- FAQ Accordion -->
    <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-2xs">
        <h2 class="text-base font-bold text-slate-900 font-display pb-4 border-b border-slate-100 mb-6">
            Pertanyaan yang Sering Diajukan (FAQ)
        </h2>

        <div class="space-y-4 text-xs sm:text-sm text-slate-700">
            <!-- FAQ 1 -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1.5">
                <h4 class="font-bold text-slate-900 text-sm">Bagaimana cara membayar biaya formulir pendaftaran?</h4>
                <p class="text-slate-600 leading-relaxed">
                    Masuk ke menu <strong class="text-slate-800">Pembayaran</strong> pada portal ini, lalu klik tombol <strong class="text-slate-800">Bayar Sekarang</strong>. Anda dapat memilih metode bayar QRIS (GoPay, OVO, ShopeePay, DANA) atau Virtual Account bank BCA, BNI, BRI, Mandiri melalui integrasi Midtrans Snap yang terverifikasi otomatis.
                </p>
            </div>

            <!-- FAQ 2 -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1.5">
                <h4 class="font-bold text-slate-900 text-sm">Apakah saya bisa mengubah pilihan program studi setelah mendaftar?</h4>
                <p class="text-slate-600 leading-relaxed">
                    Ya, Anda dapat memperbarui pilihan Program Studi 1 dan 2 pada menu <strong class="text-slate-800">Profil Saya</strong> selama proses seleksi belum ditutup oleh panitia PMB.
                </p>
            </div>

            <!-- FAQ 3 -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1.5">
                <h4 class="font-bold text-slate-900 text-sm">Kapan ujian seleksi online dilaksanakan?</h4>
                <p class="text-slate-600 leading-relaxed">
                    Setelah pembayaran formulir pendaftaran terverifikasi, Anda akan mendapatkan informasi jadwal ujian seleksi online dari panitia PMB melalui notifikasi portal dan pesan WhatsApp.
                </p>
            </div>

            <!-- FAQ 4 -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1.5">
                <h4 class="font-bold text-slate-900 text-sm">Bagaimana jika saya melakukan pembayaran tunai atau transfer manual?</h4>
                <p class="text-slate-600 leading-relaxed">
                    Jika Anda membayar langsung di loket kasir kampus UCIC atau transfer manual ke rekening universitas, silakan kirimkan foto struk/bukti transfer ke nomor WhatsApp Customer Service PMB (0812-1670-0519) dengan menyertakan Nomor Registrasi Anda agar dapat diverifikasi secara manual oleh staf CS.
                </p>
            </div>
        </div>
    </div>

    <!-- Campus Location Card -->
    <div class="p-6 sm:p-8 rounded-3xl bg-slate-900 text-white shadow-md flex flex-col sm:flex-row items-center justify-between gap-6">
        <div class="space-y-1">
            <span class="text-xs font-bold text-blue-400 uppercase tracking-wider block">Lokasi Kampus PMB</span>
            <h3 class="text-lg font-bold font-display">Universitas Catur Insan Cendekia (UCIC)</h3>
            <p class="text-xs text-slate-300">
                Jl. Kesambi No. 202, Drajat, Kec. Kesambi, Kota Cirebon, Jawa Barat 45133
            </p>
        </div>
        <a href="https://maps.google.com/?q=Universitas+Catur+Insan+Cendekia+Cirebon"
           target="_blank"
           class="shrink-0 px-5 py-2.5 rounded-xl font-bold text-xs bg-white text-slate-900 hover:bg-slate-100 transition shadow-xs">
            Buka di Google Maps &rarr;
        </a>
    </div>

</div>
@endsection
