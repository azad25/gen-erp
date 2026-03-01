@props([
    'show' => false,
    'title' => null,
    'message' => null,
    'variant' => 'default', // default, danger
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'icon' => null,
])

@php
    $variants = [
        'default' => [
            'icon' => 'heroicon-o-exclamation-circle',
            'iconColor' => '#0F766E',
            'confirmBg' => 'linear-gradient(135deg, #0F766E 0%, #14B8A6 100%)',
            'confirmText' => '#FFFFFF',
        ],
        'danger' => [
            'icon' => 'heroicon-o-exclamation-triangle',
            'iconColor' => '#B91C1C',
            'confirmBg' => 'linear-gradient(135deg, #B91C1C 0%, #DC2626 100%)',
            'confirmText' => '#FFFFFF',
        ],
    ];

    $config = $variants[$variant] ?? $variants['default'];
    $icon = $icon ?? $config['icon'];
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
        border-radius: 20px;
        box-shadow: 0 16px 48px rgba(0,0,0,0.12);
        max-width: 480px;
        width: 100%;
        padding: 32px;
        text-align: center;
     ">
        <!-- Icon -->
        <div style="
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            background: {{ $config['iconColor'] }}15;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
         ">
            <x-heroicon-o-exclamation-circle x-show="{{ $icon === 'heroicon-o-exclamation-circle' }}" 
                                          class="w-8 h-8" 
                                          style="color: {{ $config['iconColor'] }};" />
            <x-heroicon-o-exclamation-triangle x-show="{{ $icon === 'heroicon-o-exclamation-triangle' }}" 
                                              class="w-8 h-8" 
                                              style="color: {{ $config['iconColor'] }};" />
        </div>

        <!-- Title -->
        @if($title)
        <h2 style="
            font-size: 20px;
            font-weight: 600;
            color: #1F2937;
            margin: 0 0 12px;
            letter-spacing: -0.015em;
         ">
            {{ $title }}
        </h2>
        @endif

        <!-- Message -->
        <p style="
            font-size: 15px;
            color: #6B7280;
            margin: 0 0 28px;
            line-height: 1.6;
         ">
            {{ $message ?? $slot }}
        </p>

        <!-- Actions -->
        <div style="
            display: flex;
            gap: 10px;
            justify-content: center;
         ">
            <button @click="$wire.set('show', false)"
                    style="
                        background: #FFFFFF;
                        border: 1.5px solid #D1D5DB;
                        color: #374151;
                        font-weight: 500;
                        font-size: 14px;
                        padding: 11px 22px;
                        border-radius: 10px;
                        cursor: pointer;
                        transition: all 0.15s;
                     "
                    onmouseover="this.style.background='#F3F4F6'"
                    onmouseout="this.style.background='#FFFFFF'">
                {{ $cancelText }}
            </button>
            <button @click="$wire.confirm()"
                    style="
                        background: {{ $config['confirmBg'] }};
                        border: none;
                        color: {{ $config['confirmText'] }};
                        font-weight: 600;
                        font-size: 14px;
                        padding: 11px 28px;
                        border-radius: 10px;
                        cursor: pointer;
                        transition: all 0.15s;
                     "
                    onmouseover="this.style.filter='brightness(108%)'; this.style.transform='translateY(-1px)'"
                    onmouseout="this.style.filter='none'; this.style.transform='translateY(0)'">
                {{ $confirmText }}
            </button>
        </div>
    </div>
</div>
