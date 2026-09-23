<x-mobile-form-layout :title="$title" :pageHeader="'Pengiriman Sukses'">
    <div class="bg-white rounded-3xl border border-slate-200/90 p-6 shadow-xs text-center space-y-4 my-auto">
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-2">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <div>
            <h2 class="text-lg font-extrabold text-slate-900">{{ $title }}</h2>
            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">{{ $message }}</p>
        </div>

        <div class="pt-4 space-y-2.5">
            @if($type === 'prospek')
                <a 
                    href="{{ route('mobile.prospek.create') }}" 
                    class="block w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-xs rounded-2xl shadow-xs transition"
                >
                    + Input Prospek Baru Lainnya
                </a>
            @else
                <a 
                    href="{{ route('mobile.kunjungan.create') }}" 
                    class="block w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-bold text-xs rounded-2xl shadow-xs transition"
                >
                    + Input Kunjungan Baru Lainnya
                </a>
            @endif

            <button 
                type="button" 
                onclick="
                    if (window.sendToFlutter) { window.sendToFlutter({ action: 'close', status: 'success' }); }
                    if (window.history.length > 2) { window.history.go(-2); } else { window.close(); }
                "
                class="block w-full py-3 px-4 bg-slate-100 hover:bg-slate-200 active:scale-[0.99] text-slate-700 font-bold text-xs rounded-2xl transition cursor-pointer"
            >
                Kembali ke Aplikasi Flutter
            </button>
        </div>
    </div>
</x-mobile-form-layout>
