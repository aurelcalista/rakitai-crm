<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Kontak Cepat - CRM UCIC</title>
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
<body class="font-sans antialiased text-slate-800 bg-[#F5F8FF] selection:bg-blue-600 selection:text-white" x-data="{ isLoading: false }">
    <!-- Ambient Backgrounds -->
    <div class="fixed -top-40 -left-40 w-96 h-96 bg-blue-300/30 rounded-full blur-3xl pointer-events-none -z-10"></div>
    <div class="fixed top-1/4 right-0 w-80 h-80 bg-emerald-300/20 rounded-full blur-3xl pointer-events-none -z-10"></div>

    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center items-center gap-3 mb-6">
                <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" class="h-12 w-auto object-contain">
                <div class="hidden sm:block">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-blue-700 bg-blue-100/70 border border-blue-200/90 px-2 py-0.5 rounded-full mb-0.5 w-max">
                        UCIC CAMPUS
                    </span>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight leading-none">CRM Inbound</h1>
                </div>
            </div>
            
            <div class="text-center mb-8">
                <h2 class="text-3xl font-black text-slate-900 mb-2">Tinggalkan Kontak Anda</h2>
                <p class="text-slate-600">Tim kami akan segera menghubungi Anda melalui WhatsApp.</p>
            </div>
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-xl">
            <div class="bg-white py-8 px-4 shadow-2xl shadow-blue-500/10 sm:rounded-[2rem] sm:px-10 border border-slate-100">
                <form action="{{ route('kontak-cepat.store') }}" method="POST" @submit="isLoading = true" class="space-y-6" x-data="{ prospectType: 'Sekolah' }">
                    @csrf
                    
                    @if ($errors->any())
                        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm font-medium">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Jenis Instansi -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Asal Instansi <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer text-sm font-bold text-center transition"
                                :class="prospectType === 'Sekolah' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-2 ring-blue-600/20 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white hover:border-slate-300'">
                                <input type="radio" name="jenis_instansi" value="Sekolah" x-model="prospectType" class="hidden">
                                <span>Sekolah (SMA/SMK)</span>
                            </label>
                            <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer text-sm font-bold text-center transition"
                                :class="prospectType === 'PT' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-2 ring-blue-600/20 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-white hover:border-slate-300'">
                                <input type="radio" name="jenis_instansi" value="PT" x-model="prospectType" class="hidden">
                                <span>Perguruan Tinggi (PT)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Sekolah Selector (jika Sekolah) -->
                    <div x-show="prospectType === 'Sekolah'" x-cloak class="space-y-1">
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Sekolah <span class="text-rose-500">*</span></label>
                        <x-searchable-select 
                            name="sekolah_id" 
                            :options="$sekolahs" 
                            placeholder="-- Ketik untuk mencari Sekolah... --" 
                            :value="old('sekolah_id')" 
                        />
                    </div>

                    <!-- Perusahaan/PT Selector (jika PT) -->
                    <div x-show="prospectType === 'PT'" x-cloak class="space-y-1">
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Perguruan Tinggi <span class="text-rose-500">*</span></label>
                        <x-searchable-select 
                            name="perusahaan_id" 
                            :options="$perusahaans" 
                            placeholder="-- Ketik untuk mencari Perguruan Tinggi... --" 
                            :value="old('perusahaan_id')" 
                        />
                    </div>

                    <!-- Nama Lengkap -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama" value="{{ old('nama') }}" required class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    </div>

                    <!-- No WhatsApp -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nomor WhatsApp <span class="text-rose-500">*</span></label>
                        <input type="tel" name="no_whatsapp" value="{{ old('no_whatsapp') }}" required class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition" placeholder="Contoh: 08123456789">
                    </div>

                    <!-- Prodi -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Pilihan Program Studi <span class="text-rose-500">*</span></label>
                        <select name="prodi_id" required class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition appearance-none">
                            <option value="">-- Pilih Program Studi --</option>
                            @foreach($prodis as $prodi)
                                <option value="{{ $prodi->id }}" {{ old('prodi_id') == $prodi->id ? 'selected' : '' }}>
                                    {{ $prodi->jenjang }} {{ $prodi->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Kelas -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Pilihan Kelas <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer text-sm font-bold text-center transition bg-slate-50 border-slate-200 text-slate-600 hover:bg-white hover:border-slate-300">
                                <input type="radio" name="kelas" value="Reguler" required class="mr-2 text-blue-600" {{ old('kelas') == 'Reguler' ? 'checked' : '' }}>
                                <span>Reguler (Pagi)</span>
                            </label>
                            <label class="flex items-center justify-center p-3 rounded-xl border cursor-pointer text-sm font-bold text-center transition bg-slate-50 border-slate-200 text-slate-600 hover:bg-white hover:border-slate-300">
                                <input type="radio" name="kelas" value="Karyawan" required class="mr-2 text-blue-600" {{ old('kelas') == 'Karyawan' ? 'checked' : '' }}>
                                <span>Karyawan (Sore)</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-4 mt-6 border-t border-slate-100">
                        <button type="submit" :disabled="isLoading" class="w-full flex justify-center items-center gap-2 py-4 px-4 border border-transparent rounded-xl shadow-lg shadow-blue-500/30 text-lg font-black text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-70 disabled:cursor-not-allowed">
                            <span x-show="!isLoading">Kirim Data Saya</span>
                            <span x-show="isLoading">Memproses...</span>
                            <svg x-show="isLoading" x-cloak class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="text-center mt-8">
                <a href="{{ url('/') }}" class="text-sm font-semibold text-slate-500 hover:text-blue-600 transition-colors flex justify-center items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</body>
</html>
