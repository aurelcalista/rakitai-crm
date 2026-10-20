@props([
    'name',
    'id' => null,
    'value' => null,
    'placeholder' => '81234567890',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'class' => '',
    'inputClass' => '',
    'model' => null,
    'bindValue' => null,
    'bindRequired' => null,
    'bindDisabled' => null,
])

@php
    $inputId = $id ?? ($name . '_' . \Illuminate\Support\Str::random(6));
    $cleanInitial = \App\Support\WhatsAppHelper::normalizeForDisplay(old($name, $value));
@endphp

<div 
    class="relative flex items-center rounded-xl border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 transition overflow-hidden shadow-2xs {{ $class }}"
    x-data="{
        cleanInput(e) {
            let el = e.target;
            let val = el.value.replace(/[^0-9]/g, '');
            // Strip leading 62 or 0 repeatedly so user only enters digits after +62
            while (val.startsWith('62') || val.startsWith('0')) {
                if (val.startsWith('62')) val = val.substring(2);
                else if (val.startsWith('0')) val = val.substring(1);
            }
            if (val.length > 13) val = val.substring(0, 13);
            el.value = val;
            @if($model)
                if (typeof {{ $model }} !== 'undefined') {
                    {{ $model }} = val;
                }
            @endif
        }
    }"
    @if($model)
    x-init="$watch('{{ $model }}', value => {
        if (value && typeof value === 'string') {
            let cleaned = value.replace(/[^0-9]/g, '').replace(/^(62|0)+/, '');
            if (cleaned !== value) {
                {{ $model }} = cleaned;
            }
        }
    })"
    @endif
>
    <span class="inline-flex items-center px-3.5 py-2.5 bg-slate-100/90 text-slate-700 font-bold text-xs sm:text-sm select-none border-r border-slate-200 shrink-0 font-mono tracking-tight">
        +62
    </span>
    <input 
        type="tel" 
        inputmode="numeric"
        name="{{ $name }}" 
        id="{{ $inputId }}"
        value="{{ $cleanInitial }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        @if($model) x-model="{{ $model }}" @endif
        @if($bindValue) :value="{{ $bindValue }} ? (String({{ $bindValue }}).replace(/[^0-9]/g, '').replace(/^(62|0)+/, '')) : ''" @endif
        @if($bindRequired) :required="{{ $bindRequired }}" @endif
        @if($bindDisabled) :disabled="{{ $bindDisabled }}" @endif
        x-on:input="cleanInput($event)"
        class="w-full text-xs sm:text-sm px-3.5 py-2.5 bg-transparent border-0 text-slate-800 font-semibold focus:outline-none focus:ring-0 placeholder:text-slate-400 placeholder:font-normal {{ $inputClass }}"
    >
</div>
