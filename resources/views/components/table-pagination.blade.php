@props([
    'total' => 'filtered.length',
    'page' => 'currentPage',
    'perPage' => 'perPage',
    'totalPages' => 'totalPages',
    'color' => 'blue' // 'blue', 'purple', 'emerald', 'indigo'
])

@php
    $activeColorClasses = match($color) {
        'purple' => 'bg-purple-600 text-white shadow-xs scale-105 ring-2 ring-purple-600/30',
        'emerald' => 'bg-emerald-600 text-white shadow-xs scale-105 ring-2 ring-emerald-600/30',
        'indigo' => 'bg-indigo-600 text-white shadow-xs scale-105 ring-2 ring-indigo-600/30',
        default => 'bg-blue-600 text-white shadow-xs scale-105 ring-2 ring-blue-600/30',
    };
@endphp

<div x-show="{{ $total }} > {{ $perPage }}" class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/60 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
    <div class="text-slate-500 font-medium">
        Menampilkan <strong class="text-slate-800" x-text="({{ $page }} - 1) * {{ $perPage }} + 1"></strong> - <strong class="text-slate-800" x-text="Math.min({{ $page }} * {{ $perPage }}, {{ $total }})"></strong> dari <strong class="text-slate-800" x-text="{{ $total }}"></strong> data
    </div>
    
    <div class="flex items-center gap-1.5 flex-wrap justify-center">
        <!-- Tombol Sebelumnya -->
        <button 
            type="button" 
            @click="if({{ $page }} > 1) { {{ $page }}--; $el.closest('.crm-card, .overflow-hidden')?.querySelector('tbody')?.classList.remove('crm-table-slide'); void $el.closest('.crm-card, .overflow-hidden')?.querySelector('tbody')?.offsetWidth; $el.closest('.crm-card, .overflow-hidden')?.querySelector('tbody')?.classList.add('crm-table-slide'); }" 
            :disabled="{{ $page }} === 1"
            class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 bg-white font-semibold hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-1 cursor-pointer shadow-2xs"
            title="Halaman Sebelumnya"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span>Sebelumnya</span>
        </button>

        <!-- Angka Halaman -->
        <div class="flex items-center gap-1">
            <template x-for="p in {{ $totalPages }}" :key="p">
                <button 
                    type="button"
                    x-show="p === 1 || p === {{ $totalPages }} || (p >= {{ $page }} - 2 && p <= {{ $page }} + 2)"
                    @click="{{ $page }} = p; $el.closest('.crm-card, .overflow-hidden')?.querySelector('tbody')?.classList.remove('crm-table-slide'); void $el.closest('.crm-card, .overflow-hidden')?.querySelector('tbody')?.offsetWidth; $el.closest('.crm-card, .overflow-hidden')?.querySelector('tbody')?.classList.add('crm-table-slide');"
                    class="min-w-[32px] h-8 px-2 rounded-xl font-bold text-xs transition-all duration-200 flex items-center justify-center cursor-pointer"
                    :class="{{ $page }} === p ? '{{ $activeColorClasses }}' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 shadow-2xs'"
                    x-text="p"
                ></button>
            </template>
        </div>

        <!-- Tombol Berikutnya -->
        <button 
            type="button" 
            @click="if({{ $page }} < {{ $totalPages }}) { {{ $page }}++; $el.closest('.crm-card, .overflow-hidden')?.querySelector('tbody')?.classList.remove('crm-table-slide'); void $el.closest('.crm-card, .overflow-hidden')?.querySelector('tbody')?.offsetWidth; $el.closest('.crm-card, .overflow-hidden')?.querySelector('tbody')?.classList.add('crm-table-slide'); }" 
            :disabled="{{ $page }} === {{ $totalPages }}"
            class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 bg-white font-semibold hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-1 cursor-pointer shadow-2xs"
            title="Halaman Berikutnya"
        >
            <span>Berikutnya</span>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>
</div>
