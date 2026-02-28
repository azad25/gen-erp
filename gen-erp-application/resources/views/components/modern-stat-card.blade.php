@props([
    'title',
    'value',
    'icon' => null,
    'color' => 'primary',
    'trend' => null,
    'trendUp' => true,
])

<div {{ $attributes->merge(['class' => 'stats-card bg-white dark:bg-gray-800']) }}>
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                {{ $title }}
            </p>
            <p class="stats-card-value">
                {{ $value }}
            </p>
            @if($trend)
                <div class="flex items-center gap-1 mt-2">
                    @if($trendUp)
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $trend }}</span>
                    @else
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        </svg>
                        <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ $trend }}</span>
                    @endif
                </div>
            @endif
        </div>
        
        @if($icon)
            <div class="stats-card-icon">
                <x-dynamic-component :component="$icon" class="w-6 h-6 text-white" />
            </div>
        @endif
    </div>
</div>
