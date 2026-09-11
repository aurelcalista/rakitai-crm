@props(['type' => 'sales', 'name' => ''])

@php
    $isSales = strtolower($type) === 'sales' || str_contains(strtolower($type), 'sales');
@endphp

@if($isSales)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-semibold tracking-wide bg-blue-50 text-blue-700 border border-blue-200/80']) }}>
        <svg class="w-3 h-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        TAKEOVER SALES @if($name) <span class="font-normal text-blue-600">— {{ $name }}</span> @endif
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-semibold tracking-wide bg-teal-50 text-teal-800 border border-teal-200/80']) }}>
        <svg class="w-3 h-3 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        TAKEOVER CS @if($name) <span class="font-normal text-teal-700">— {{ $name }}</span> @endif
    </span>
@endif
