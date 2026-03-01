@props([
    'show' => false,
    'message' => null,
    'variant' => 'info', // info, success, warning, danger
    'icon' => null,
    'position' => 'top-right', // top-right, top-left, bottom-right, bottom-left
])

@php
    $variants = [
        'info' => [
            'bg' => '#F0FDFA',
            'border' => '#0F766E',
            'text' => '#0F766E',
            'icon' => 'heroicon-o-information-circle',
        ],
        'success' => [
            'bg' => '#DCFCE7',
            'border' => '#16A34A',
            'text' => '#15803D',
            'icon' => 'heroicon-o-check-circle',
        ],
        'warning' => [
            'bg' => '#FEF9C3',
            'border' => '#CA8A04',
            'text' => '#A16207',
            'icon' => 'heroicon-o-exclamation-triangle',
        ],
        'danger' => [
            'bg' => '#FEE2E2',
            'border' => '#B91C1C',
            'text' => '#991B1B',
            'icon' => 'heroicon-o-x-circle',
        ],
    ];

    $config = $variants[$variant] ?? $variants['info'];
    $icon = $icon ?? $config['icon'];

    $positions = [
        'top-right' => ['top' => '24px', 'right' => '24px', 'bottom' => 'auto', 'left' => 'auto'],
        'top-left' => ['top' => '24px', 'right' => 'auto', 'bottom' => 'auto', 'left' => '24px'],
        'bottom-right' => ['top' => 'auto', 'right' => '24px', 'bottom' => '24px', 'left' => 'auto'],
        'bottom-left' => ['top' => 'auto', 'right' => 'auto', 'bottom' => '24px', 'left' => '24px'],
    ];

    $positionConfig = $positions[$position] ?? $positions['top-right'];
@endphp

<div x-show="{{ $show }}"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-2"
     style="
        position: fixed;
        top: {{ $positionConfig['top'] }};
        right: {{ $positionConfig['right'] }};
        bottom: {{ $positionConfig['bottom'] }};
        left: {{ $positionConfig['left'] }};
        z-index: 9999;
        background-color: {{ $config['bg'] }};
        border-left: 4px solid {{ $config['border'] }};
        padding: 12px 16px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        min-width: 280px;
        max-width: 380px;
     ">
    <div style="color: {{ $config['text'] }}; flex-shrink: 0;">
        <x-heroicon-o-information-circle x-show="{{ $icon === 'heroicon-o-information-circle' }}" class="w-5 h-5" />
        <x-heroicon-o-check-circle x-show="{{ $icon === 'heroicon-o-check-circle' }}" class="w-5 h-5" />
        <x-heroicon-o-exclamation-triangle x-show="{{ $icon === 'heroicon-o-exclamation-triangle' }}" class="w-5 h-5" />
        <x-heroicon-o-x-circle x-show="{{ $icon === 'heroicon-o-x-circle' }}" class="w-5 h-5" />
    </div>

    <div style="flex: 1;">
        <div style="color: {{ $config['text'] }}; font-size: 14px; line-height: 1.4;">
            {{ $message ?? $slot }}
        </div>
    </div>

    <button @click="$wire.set('show', false)"
            style="background: transparent; border: none; cursor: pointer; padding: 4px; color: {{ $config['text'] }}; opacity: 0.6; transition: opacity 0.15s;"
            onmouseover="this.style.opacity='1'"
            onmouseout="this.style.opacity='0.6'">
        <x-heroicon-o-x-mark class="w-4 h-4" />
    </button>
</div>
