@php
    $brandName = filament()->getBrandName();
    $brandLogo = filament()->getBrandLogo();
@endphp

<div class="flex items-center gap-3 px-4 py-4">
    @if ($brandLogo)
        <div class="flex-shrink-0">
            @if(is_string($brandLogo))
                <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-10 w-10">
            @else
                <div class="h-10 w-10">
                    {!! $brandLogo !!}
                </div>
            @endif
        </div>
    @endif
    
    <div class="flex flex-col">
        <span class="text-xl font-extrabold text-gray-900 tracking-tight leading-none">
            {{ $brandName }}
        </span>
        <span class="text-xs font-semibold text-teal-600 tracking-wide">
            Enterprise ERP
        </span>
    </div>
</div>
