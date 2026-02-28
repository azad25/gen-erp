<x-filament-panels::page>
    {{-- Modern List Page Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gradient">
                    {{ $this->getHeading() }}
                </h1>
                @if($description = $this->getSubheading())
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        {{ $description }}
                    </p>
                @endif
            </div>
            
            @if($this->hasHeaderActions())
                <div class="flex gap-3">
                    {{ $this->getHeaderActions() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Stats Overview (if available) --}}
    @if(method_exists($this, 'getStats'))
        <div class="grid gap-6 mb-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($this->getStats() as $stat)
                <div class="stats-card">
                    {{ $stat }}
                </div>
            @endforeach
        </div>
    @endif

    {{-- Main Content --}}
    <div class="modern-table-container">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
