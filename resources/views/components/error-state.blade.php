@props([
    'title' => 'Data prospek belum dapat dimuat',
    'message' => 'Terjadi gangguan sementara saat memuat data. Silakan coba beberapa saat lagi.',
    'onRetry' => '$store.crm.setState("normal")'
])

<div class="crm-card p-8 sm:p-10 text-center flex flex-col items-center justify-center max-w-md mx-auto my-8 border border-rose-200 bg-rose-50/40">
    <div class="w-12 h-12 rounded-2xl bg-rose-100 border border-rose-200 flex items-center justify-center text-rose-600 mb-4 shadow-xs">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
    </div>
    <h3 class="text-base font-semibold text-slate-900 mb-1">{{ $title }}</h3>
    <p class="text-xs sm:text-sm text-slate-600 max-w-xs mb-5">{{ $message }}</p>

    <button 
        type="button" 
        @click="{{ $onRetry }}"
        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-white text-slate-700 border border-slate-300 shadow-xs hover:bg-slate-50 transition cursor-pointer"
    >
        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Coba Lagi
    </button>
</div>
