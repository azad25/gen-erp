@props([
    'show' => false,
    'title' => null,
    'variant' => 'default', // default, danger
    'size' => 'md', // sm, md, lg
    'width' => '500px', // width of the modal
])

@php
    $sizes = [
        'sm' => ['padding' => '20px 24px', 'radius' => '16px'],
        'md' => ['padding' => '28px 32px', 'radius' => '20px'],
        'lg' => ['padding' => '32px 40px', 'radius' => '24px'],
    ];

    $sizeConfig = $sizes[$size] ?? $sizes['md'];

    $variants = [
        'default' => [
            'confirmBg' => 'linear-gradient(135deg, #0F766E 0%, #14B8A6 100%)',
            'confirmText' => '#FFFFFF',
            'confirmBorder' => 'none',
        ],
        'danger' => [
            'confirmBg' => 'linear-gradient(135deg, #B91C1C 0%, #DC2626 100%)',
            'confirmText' => '#FFFFFF',
            'confirmBorder' => 'none',
        ],
    ];

    $variantConfig = $variants[$variant] ?? $variants['default'];
@endphp

<div x-show="{{ $show }}"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:enter-start="scale-95"
     x-transition:enter-end="scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     x-transition:leave-start="scale-100"
     x-transition:leave-end="scale-95"
     style="
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
     ">
    <!-- Backdrop -->
    <div x-show="{{ $show }}"
         @click="$wire.set('show', false)"
         style="
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(8px);
         "></div>

    <!-- Modal Content -->
    <div style="
        position: relative;
        background: #FFFFFF;
        border-radius: {{ $sizeConfig['radius'] }};
        box-shadow: 0 16px 48px rgba(0,0,0,0.12);
        max-width: {{ $width }};
        width: 100%;
        max-height: 90vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
     ">
        <!-- Header -->
        @if($title)
        <div style="
            padding: {{ $sizeConfig['padding'] }};
            padding-bottom: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #E5E7EB;
         ">
            <h2 style="
                font-size: 20px;
                font-weight: 600;
                color: #1F2937;
                margin: 0;
                letter-spacing: -0.015em;
             ">
                {{ $title }}
            </h2>
            <button @click="$wire.set('show', false)"
                    style="
                        background: transparent;
                        border: none;
                        cursor: pointer;
                        padding: 8px;
                        color: #6B7280;
                        border-radius: 8px;
                        transition: all 0.15s;
                     "
                    onmouseover="this.style.background='#F3F4F6'; this.style.color='#1F2937'"
                    onmouseout="this.style.background='transparent'; this.style.color='#6B7280'">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>
        @endif

        <!-- Body -->
        <div style="
            padding: {{ $sizeConfig['padding'] }};
            overflow-y: auto;
         ">
            {{ $slot }}
        </div>

        <!-- Footer Actions -->
        @if(isset($actions))
        <div style="
            padding: {{ $sizeConfig['padding'] }};
            padding-top: 0;
            border-top: 1px solid #E5E7EB;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
         ">
            {{ $actions }}
        </div>
        @endif
    </div>
</div>
