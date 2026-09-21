@props([
    'type' => 'info', // success, error, warning, info
    'title' => null,
    'message' => null,
    'dismissible' => true,
])

@php
    $typeConfigs = [
        'success' => [
            'bg' => 'bg-emerald-50/90',
            'border' => 'border-emerald-200',
            'text' => 'text-emerald-900',
            'icon_bg' => 'bg-emerald-100 text-emerald-600',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
            'default_title' => 'Berhasil!',
        ],
        'error' => [
            'bg' => 'bg-rose-50/90',
            'border' => 'border-rose-200',
            'text' => 'text-rose-900',
            'icon_bg' => 'bg-rose-100 text-rose-600',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
            'default_title' => 'Terjadi Kesalahan!',
        ],
        'warning' => [
            'bg' => 'bg-amber-50/90',
            'border' => 'border-amber-200',
            'text' => 'text-amber-900',
            'icon_bg' => 'bg-amber-100 text-amber-600',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
            'default_title' => 'Perhatian!',
        ],
        'info' => [
            'bg' => 'bg-blue-50/90',
            'border' => 'border-blue-200',
            'text' => 'text-blue-900',
            'icon_bg' => 'bg-blue-100 text-blue-600',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'default_title' => 'Informasi',
        ],
    ];

    $cfg = $typeConfigs[$type] ?? $typeConfigs['info'];
    $finalTitle = $title ?? $cfg['default_title'];
@endphp

<div 
    x-data="{ show: true }" 
    x-show="show" 
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0 -translate-y-2"
    class="p-4 rounded-2xl border {{ $cfg['bg'] }} {{ $cfg['border'] }} shadow-xs relative flex items-start gap-3.5 my-3"
    role="alert"
>
    <!-- Icon -->
    <div class="w-8 h-8 rounded-xl {{ $cfg['icon_bg'] }} flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            {!! $cfg['icon'] !!}
        </svg>
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0 {{ $cfg['text'] }}">
        @if($finalTitle)
            <h4 class="font-bold text-xs uppercase tracking-wider mb-0.5">{{ $finalTitle }}</h4>
        @endif
        <div class="text-xs font-medium leading-relaxed">
            @if($message)
                {{ $message }}
            @else
                {{ $slot }}
            @endif
        </div>
    </div>

    <!-- Dismiss Button -->
    @if($dismissible)
        <button 
            type="button" 
            @click="show = false" 
            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-black/5 transition cursor-pointer shrink-0"
            title="Tutup"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    @endif
</div>
