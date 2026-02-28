<x-filament-panels::page class="modern-view-page">
    <div class="modern-page-header">
        <div class="modern-page-header-content">
            <div>
                <h1 class="modern-page-title">
                    {{ $this->getHeading() }}
                </h1>
                @if ($subheading = $this->getSubheading())
                    <p class="modern-page-subtitle">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="modern-page-content">
        {{ \Filament\Support\Facades\FilamentView::renderHook('panels::page.start', scopes: $this->getRenderHookScopes()) }}

        <div class="modern-view-container">
            {{ $this->infolist }}
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook('panels::page.end', scopes: $this->getRenderHookScopes()) }}
    </div>
</x-filament-panels::page>
