<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terima Kasih - CRM UCIC</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ucic.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-ucic.png') }}">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .font-sans { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-inter { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="font-sans antialiased text-slate-800 bg-[#F5F8FF] selection:bg-blue-600 selection:text-white">
    <!-- Ambient Backgrounds -->
    <div class="fixed top-0 left-0 w-full h-1/2 bg-gradient-to-b from-blue-600/10 to-transparent -z-10"></div>
    <div class="fixed top-1/4 left-1/4 w-96 h-96 bg-blue-300/30 rounded-full blur-3xl pointer-events-none -z-10"></div>
    <div class="fixed bottom-1/4 right-1/4 w-80 h-80 bg-emerald-300/20 rounded-full blur-3xl pointer-events-none -z-10"></div>

    <div class="min-h-screen flex flex-col justify-center py-10 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md mx-auto">
            <div class="bg-white py-10 px-6 sm:py-12 sm:px-8 shadow-2xl shadow-blue-500/10 rounded-3xl sm:rounded-[2.5rem] border border-slate-100 text-center relative overflow-hidden">
                <!-- Confetti/Celebration Element -->
                <div class="w-24 h-24 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                
                <h2 class="text-3xl font-black text-slate-900 mb-4 tracking-tight">Terima Kasih</h2>
                <p class="text-lg text-slate-600 mb-8 leading-relaxed">
                    Data kamu berhasil dikirim.<br>
                    Tim kami akan menghubungi kamu melalui WhatsApp untuk informasi selanjutnya.
                </p>

                <div class="flex flex-col gap-3 max-w-xs mx-auto">
                    <a href="{{ route('kontak-cepat') }}" class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg shadow-blue-500/20 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 hover:-translate-y-0.5 transition-all">
                        Isi Data Lagi
                    </a>
                    <a href="{{ url('/') }}" class="w-full flex justify-center items-center py-3.5 px-4 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 hover:border-slate-300 transition-all">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
