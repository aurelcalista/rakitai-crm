@props([
    'title' => 'Belum ada data',
    'description' => 'Data untuk kategori ini belum tersedia atau belum ditambahkan.',
    'actionLabel' => null,
    'actionClick' => null,
    'icon' => null
])

<div class="crm-card p-8 sm:p-12 text-center flex flex-col items-center justify-center max-w-lg mx-auto my-6 border border-dashed border-slate-300 bg-slate-50/50">
    <div class="w-14 h-14 rounded-2xl bg-white shadow-xs border border-slate-200 flex items-center justify-center text-slate-400 mb-4">
        @if($icon)
            {!! $icon !!}
        @else
            <svg class="w-7 h-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
        @endif
    </div>
    <h3 class="text-base font-semibold text-slate-800 mb-1">{{ $title }}</h3>
    <p class="text-xs sm:text-sm text-slate-500 max-w-sm mb-5">{{ $description }}</p>

    @if($actionLabel)
        <button 
            type="button"
            @if($actionClick) @click="{{ $actionClick }}" @endif
            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-blue-600 text-white shadow-xs hover:bg-blue-700 transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ $actionLabel }}
        </button>
    @endif
</div>
