@props([
    'title',
    'value',
    'subtitle' => null,
    'trend' => null,
    'trendUp' => true,
    'icon' => null,
    'color' => 'blue'
])

@php
    $iconBg = match($color) {
        'blue' => 'bg-blue-50 text-blue-600 border-blue-100',
        'indigo' => 'bg-indigo-50 text-indigo-600 border-indigo-100',
        'emerald' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'amber' => 'bg-amber-50 text-amber-600 border-amber-100',
        'purple' => 'bg-purple-50 text-purple-600 border-purple-100',
        'rose' => 'bg-rose-50 text-rose-600 border-rose-100',
        default => 'bg-slate-50 text-slate-600 border-slate-100',
    };
@endphp

<div class="crm-card crm-card-hover p-5 relative overflow-hidden bg-white">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">{{ $title }}</p>
            <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">{{ $value }}</h3>
            @if($subtitle)
                <p class="text-xs text-slate-500 mt-1.5 font-medium">{{ $subtitle }}</p>
            @endif
        </div>
        
        @if($icon)
            <div class="w-10 h-10 rounded-xl flex items-center justify-center border {{ $iconBg }} shrink-0">
                {!! $icon !!}
            </div>
        @endif
    </div>

    @if($trend)
        <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center text-xs">
            @if($trendUp)
                <span class="inline-flex items-center text-emerald-600 font-semibold gap-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                    {{ $trend }}
                </span>
            @else
                <span class="inline-flex items-center text-rose-600 font-semibold gap-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                    {{ $trend }}
                </span>
            @endif
            <span class="text-slate-500 ml-1.5 font-normal">dari periode lalu</span>
        </div>
    @endif
</div>
