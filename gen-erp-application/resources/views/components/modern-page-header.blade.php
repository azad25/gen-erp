@props([
    'title',
    'description' => null,
    'icon' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->merge(['class' => 'mb-8']) }}>
    {{-- Breadcrumbs --}}
    @if(count($breadcrumbs) > 0)
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="fi-breadcrumbs">
                @foreach($breadcrumbs as $index => $breadcrumb)
                    <li class="flex items-center">
                        @if($index > 0)
                            <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        @endif
                        @if(isset($breadcrumb['url']))
                            <a href="{{ $breadcrumb['url'] }}" class="fi-breadcrumb-item">
                                {{ $breadcrumb['label'] }}
                            </a>
                        @else
                            <span class="fi-breadcrumb-item font-medium text-gray-900 dark:text-white">
                                {{ $breadcrumb['label'] }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div class="flex items-start gap-4">
            @if($icon)
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/30">
                    <x-dynamic-component :component="$icon" class="w-7 h-7 text-white" />
                </div>
            @endif
            
            <div>
                <h1 class="text-3xl font-bold text-gradient">
                    {{ $title }}
                </h1>
                @if($description)
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        {{ $description }}
                    </p>
                @endif
            </div>
        </div>
        
        @if(isset($actions))
            <div class="flex gap-3">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
