@props(['type' => 'cards'])

@if($type === 'cards')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-pulse">
        @for($i = 0; $i < 4; $i++)
            <div class="crm-card p-5 bg-white space-y-3">
                <div class="flex justify-between items-center">
                    <div class="h-3 w-24 bg-slate-200 rounded"></div>
                    <div class="w-8 h-8 bg-slate-200 rounded-lg"></div>
                </div>
                <div class="h-8 w-16 bg-slate-200 rounded"></div>
                <div class="h-3 w-32 bg-slate-100 rounded"></div>
            </div>
        @endfor
    </div>
@elseif($type === 'table')
    <div class="crm-card p-4 bg-white animate-pulse space-y-4">
        <div class="h-6 w-48 bg-slate-200 rounded"></div>
        <div class="space-y-3">
            @for($i = 0; $i < 5; $i++)
                <div class="flex items-center justify-between py-3 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-200"></div>
                        <div class="space-y-1.5">
                            <div class="h-4 w-36 bg-slate-200 rounded"></div>
                            <div class="h-3 w-24 bg-slate-100 rounded"></div>
                        </div>
                    </div>
                    <div class="h-6 w-20 bg-slate-200 rounded-full"></div>
                    <div class="h-4 w-28 bg-slate-100 rounded hidden sm:block"></div>
                </div>
            @endfor
        </div>
    </div>
@endif
