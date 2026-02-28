<x-filament-widgets::widget>
    @php
        $activeCompany = $this->getActiveCompany();
        $companies = $this->getCompanies();
    @endphp

    @if ($companies->count() > 1)
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <span style="font-weight: 600; font-size: 0.875rem; color: var(--filament-gray-600);">
                    {{ __('Active Company:') }}
                </span>

                @foreach ($companies as $company)
                    @if ($company->id === $activeCompany?->id)
                        <span style="padding: 0.375rem 0.75rem; background: var(--primary-500); color: white; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500;">
                            {{ $company->name }}
                        </span>
                    @else
                        <form method="POST" action="{{ url('/app/switch-company/' . $company->id) }}" style="display: inline;">
                            @csrf
                            <button type="submit" style="padding: 0.375rem 0.75rem; background: var(--filament-gray-100); border: 1px solid var(--filament-gray-300); border-radius: 0.375rem; font-size: 0.875rem; cursor: pointer;">
                                {{ $company->name }}
                            </button>
                        </form>
                    @endif
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
