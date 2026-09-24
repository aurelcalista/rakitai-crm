<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Sales — CRM UCIC</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4 font-sans text-slate-100">
    <div class="w-full max-w-md bg-slate-800 border border-slate-700 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-400 font-black text-xl mb-2">
                UCIC
            </div>
            <h1 class="text-xl font-bold text-white tracking-tight">Pendaftaran Sales CRM</h1>
            <p class="text-xs text-slate-400">Daftarkan akun Sales mandiri Anda. Akun memerlukan ACC Admin sebelum dapat digunakan.</p>
        </div>

        @if ($errors->any())
            <div class="bg-rose-500/10 border border-rose-500/30 rounded-2xl p-4 text-xs text-rose-300 space-y-1">
                <div class="font-bold">Gagal Mendaftar:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Lengkap <span class="text-rose-400">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Budi Santoso" class="w-full px-3.5 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Email Domain @cic.ac.id <span class="text-rose-400">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="Contoh: sales.budi@cic.ac.id" class="w-full px-3.5 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">No. Telepon / WhatsApp</label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Contoh: 08123456789" class="w-full px-3.5 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Password <span class="text-rose-400">*</span></label>
                <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Konfirmasi Password <span class="text-rose-400">*</span></label>
                <input type="password" name="password_confirmation" required placeholder="Ulangi password" class="w-full px-3.5 py-2.5 bg-slate-900/80 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500">
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow-lg transition duration-200">
                Daftar Akun Sales
            </button>
        </form>

        <div class="text-center text-xs text-slate-400">
            Sudah memiliki akun? <a href="{{ route('login') }}" class="text-purple-400 font-bold hover:underline">Masuk Ke Sistem</a>
        </div>
    </div>
</body>
</html>
