@props(['status'])

@php
    $normalized = strtolower(trim($status ?? ''));
    
    $classes = match(true) {
        str_contains($normalized, 'cold') || $normalized === 'baru' => 'bg-slate-100 text-slate-700 border-slate-200',
        str_contains($normalized, 'interested') || $normalized === 'kontak' => 'bg-blue-50 text-blue-700 border-blue-200',
        str_contains($normalized, 'hot') || $normalized === 'panas' => 'bg-orange-50 text-orange-700 border-orange-200',
        str_contains($normalized, 'prospek') || str_contains($normalized, 'hangat') || str_contains($normalized, 'follow') => 'bg-amber-50 text-amber-800 border-amber-200',
        str_contains($normalized, 'beli') || str_contains($normalized, 'formulir') => 'bg-purple-50 text-purple-700 border-purple-200',
        str_contains($normalized, 'termin') || str_contains($normalized, 'pembayaran') || str_contains($normalized, 'berkas') => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        str_contains($normalized, 'closing') || str_contains($normalized, 'lunas') => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        str_contains($normalized, 'no respon') || str_contains($normalized, 'dingin') || str_contains($normalized, 'lost') => 'bg-rose-50 text-rose-700 border-rose-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };

    $dotColor = match(true) {
        str_contains($normalized, 'cold') || $normalized === 'baru' => 'bg-slate-400',
        str_contains($normalized, 'interested') || $normalized === 'kontak' => 'bg-blue-500',
        str_contains($normalized, 'hot') || $normalized === 'panas' => 'bg-orange-500',
        str_contains($normalized, 'prospek') || str_contains($normalized, 'hangat') || str_contains($normalized, 'follow') => 'bg-amber-500',
        str_contains($normalized, 'beli') || str_contains($normalized, 'formulir') => 'bg-purple-500',
        str_contains($normalized, 'termin') || str_contains($normalized, 'pembayaran') || str_contains($normalized, 'berkas') => 'bg-indigo-500',
        str_contains($normalized, 'closing') || str_contains($normalized, 'lunas') => 'bg-emerald-500',
        str_contains($normalized, 'no respon') || str_contains($normalized, 'dingin') || str_contains($normalized, 'lost') => 'bg-rose-500',
        default => 'bg-slate-400',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border {$classes}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
    {{ $status }}
</span>
