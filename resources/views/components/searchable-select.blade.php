@props([
    'name',
    'id' => null,
    'options' => [],
    'value' => '',
    'placeholder' => 'Pilih atau cari...',
    'required' => false,
    'disabled' => false,
    'allowClear' => true,
])

@php
    $inputName = $name;
    $inputId = $id ?? $name . '_' . uniqid();

    $extractSub = function ($item) {
        if (is_array($item)) {
            if (!empty($item['sub']) && is_string($item['sub']) && !str_starts_with(trim($item['sub']), '{')) {
                return $item['sub'];
            }
            if (!empty($item['wilayah'])) {
                $w = $item['wilayah'];
                return is_object($w) ? ($w->nama ?? '') : (is_array($w) ? ($w['nama'] ?? '') : (is_string($w) && !str_starts_with(trim($w), '{') ? $w : ''));
            }
            if (!empty($item['kategori'])) {
                $k = $item['kategori'];
                return is_object($k) ? ($k->nama ?? '') : (is_array($k) ? ($k['nama'] ?? '') : (is_string($k) && !str_starts_with(trim($k), '{') ? $k : ''));
            }
            if (!empty($item['role'])) {
                return 'Role: ' . $item['role'];
            }
            if (!empty($item['jenjang'])) {
                return $item['jenjang'] . (!empty($item['kode']) ? ' (' . $item['kode'] . ')' : '');
            }
            return '';
        } elseif (is_object($item)) {
            if (isset($item->sub) && is_string($item->sub) && !str_starts_with(trim($item->sub), '{')) {
                return $item->sub;
            }
            // If model Sekolah or has wilayah
            if ($item->relationLoaded('wilayah') && $item->wilayah) {
                return $item->wilayah->nama ?? '';
            }
            if (isset($item->wilayah) && is_object($item->wilayah)) {
                return $item->wilayah->nama ?? '';
            }
            // If model Sekolah or has kategori
            if ($item->relationLoaded('kategori') && $item->kategori) {
                return $item->kategori->nama ?? '';
            }
            if (isset($item->kategori) && is_object($item->kategori)) {
                return $item->kategori->nama ?? '';
            }
            if (!empty($item->role)) {
                return 'Role: ' . $item->role;
            }
            if (!empty($item->jenjang)) {
                return $item->jenjang . (!empty($item->kode) ? ' (' . $item->kode . ')' : '');
            }
            if (!empty($item->kode)) {
                return 'Kode: ' . $item->kode;
            }
            return '';
        }
        return '';
    };

    // Normalize options if simple key-value or array of objects
    $formattedOptions = collect($options)->map(function ($opt, $key) use ($extractSub) {
        if (is_array($opt)) {
            return [
                'value' => (string)($opt['value'] ?? $opt['id'] ?? $key),
                'label' => (string)($opt['label'] ?? $opt['nama'] ?? $opt['name'] ?? ''),
                'sub'   => (string)$extractSub($opt),
            ];
        } elseif (is_object($opt)) {
            return [
                'value' => (string)($opt->id ?? $opt->value ?? $key),
                'label' => (string)($opt->nama ?? $opt->name ?? $opt->label ?? ''),
                'sub'   => (string)$extractSub($opt),
            ];
        }
        return [
            'value' => (string)$key,
            'label' => (string)$opt,
            'sub'   => '',
        ];
    })->values()->toArray();

    $initialSelected = collect($formattedOptions)->firstWhere('value', (string)$value);
@endphp

<div 
    x-data="{
        open: false,
        search: '',
        selectedValue: '{{ (string)$value }}',
        selectedLabel: '{{ $initialSelected ? addslashes($initialSelected['label']) : '' }}',
        options: {{ json_encode($formattedOptions) }},
        
        get filteredOptions() {
            if (!this.search.trim()) {
                return this.options;
            }
            const query = this.search.toLowerCase();
            return this.options.filter(item => 
                item.label.toLowerCase().includes(query) || 
                (item.sub && item.sub.toLowerCase().includes(query)) ||
                item.value.toLowerCase().includes(query)
            );
        },
        
        selectOption(opt) {
            this.selectedValue = opt.value;
            this.selectedLabel = opt.label;
            this.open = false;
            this.search = '';
            $dispatch('change', { name: '{{ $inputName }}', value: opt.value });
        },
        
        clearSelection() {
            this.selectedValue = '';
            this.selectedLabel = '';
            this.open = false;
            this.search = '';
            $dispatch('change', { name: '{{ $inputName }}', value: '' });
        }
    }" 
    class="relative w-full text-left"
    @click.away="open = false"
>
    <!-- Hidden input to submit with regular form -->
    <input 
        type="hidden" 
        name="{{ $inputName }}" 
        id="{{ $inputId }}" 
        :value="selectedValue" 
        @if($required) required @endif
    >

    <!-- Trigger Button -->
    <button 
        type="button"
        @click="if(!{{ $disabled ? 'true' : 'false' }}) { open = !open; if(open) { $nextTick(() => $refs.searchInput.focus()); } }"
        class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl border transition text-xs font-medium bg-slate-50 border-slate-200 text-slate-800 hover:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 {{ $disabled ? 'opacity-50 cursor-not-allowed bg-slate-100' : 'cursor-pointer' }}"
        :class="open ? 'ring-2 ring-blue-500/20 border-blue-500 bg-white' : ''"
    >
        <span class="truncate block pr-2" x-text="selectedLabel ? selectedLabel : '{{ $placeholder }}'" :class="!selectedLabel ? 'text-slate-400' : 'text-slate-800 font-semibold'"></span>
        
        <div class="flex items-center gap-1 shrink-0">
            @if($allowClear)
            <template x-if="selectedValue">
                <span 
                    @click.stop="clearSelection()"
                    class="p-0.5 rounded-full hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition cursor-pointer"
                    title="Hapus pilihan"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </span>
            </template>
            @endif
            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180 text-blue-600' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </button>

    <!-- Dropdown Menu with Search Box -->
    <div 
        x-show="open" 
        x-cloak 
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-1.5 w-full rounded-xl bg-white shadow-xl border border-slate-200/80 overflow-hidden text-xs"
        style="max-height: 280px;"
    >
        <!-- Search Input with proper padding & styling -->
        <div class="p-2 border-b border-slate-100 bg-slate-50/80">
            <div class="relative flex items-center">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    x-ref="searchInput"
                    type="text" 
                    x-model="search" 
                    placeholder="Ketik untuk mencari..." 
                    style="padding-left: 2.25rem !important;"
                    class="w-full pr-3 py-2 rounded-lg text-xs bg-white border border-slate-200 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs"
                    @keydown.escape="open = false"
                >
            </div>
        </div>

        <!-- Options List -->
        <div class="max-h-52 overflow-y-auto divide-y divide-slate-50 p-1">
            <template x-for="opt in filteredOptions" :key="opt.value">
                <div 
                    @click="selectOption(opt)"
                    class="px-3 py-2 rounded-lg cursor-pointer flex items-center justify-between transition hover:bg-blue-50/80 group"
                    :class="selectedValue === opt.value ? 'bg-blue-50 font-bold text-blue-700' : 'text-slate-700'"
                >
                    <div class="min-w-0 pr-2">
                        <div class="truncate font-medium text-slate-800 group-hover:text-blue-700" x-text="opt.label"></div>
                        <template x-if="opt.sub">
                            <div class="text-[10px] text-slate-400 truncate" x-text="opt.sub"></div>
                        </template>
                    </div>
                    <template x-if="selectedValue === opt.value">
                        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                </div>
            </template>

            <!-- Empty Search Result -->
            <template x-if="filteredOptions.length === 0">
                <div class="p-4 text-center space-y-2.5">
                    <div class="w-8 h-8 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-700">Tidak ada hasil "<span x-text="search" class="text-slate-900 font-bold"></span>"</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Belum terdaftar di sistem? Anda bisa menginputkannya secara manual.</p>
                    </div>
                    <button 
                        type="button" 
                        @click="$dispatch('switch-manual', { search: search, name: '{{ $inputName }}' }); open = false;"
                        class="w-full py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-xs inline-flex items-center justify-center gap-1.5 transition shadow-xs cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        <span>Gunakan "<span x-text="search" class="max-w-[140px] truncate inline-block align-bottom"></span>" sebagai Manual</span>
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
