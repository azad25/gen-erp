<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Section --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-500 via-primary-600 to-purple-700 p-8 text-white shadow-2xl">
            <div class="relative z-10">
                <h1 class="text-3xl font-bold mb-2">
                    {{ __('Welcome back, :name!', ['name' => auth()->user()->name]) }}
                </h1>
                <p class="text-primary-100 text-lg">
                    {{ __('Here\'s what\'s happening with your business today.') }}
                </p>
            </div>
            
            {{-- Decorative Elements --}}
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-purple-500/20 rounded-full blur-2xl"></div>
        </div>

        {{-- Stats Grid --}}
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->getWidgets() as $widget)
                @if ($widget === \App\Filament\Widgets\ModernStatsOverview::class)
                    @livewire($widget)
                @endif
            @endforeach
        </div>

        {{-- Charts Section --}}
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($this->getWidgets() as $widget)
                @if ($widget === \App\Filament\Widgets\RevenueChart::class)
                    <div class="chart-container">
                        @livewire($widget)
                    </div>
                @endif
            @endforeach
            
            {{-- Quick Actions Card --}}
            <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold mb-4 text-gradient">
                    {{ __('Quick Actions') }}
                </h3>
                
                <div class="space-y-3">
                    <a href="{{ route('filament.app.resources.invoices.create') }}" 
                       class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 hover:shadow-lg transition-all duration-200 group">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/30 group-hover:scale-110 transition-transform">
                            <x-heroicon-o-document-plus class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ __('New Invoice') }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Create a new sales invoice') }}</div>
                        </div>
                    </a>

                    <a href="{{ route('filament.app.resources.products.create') }}" 
                       class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 hover:shadow-lg transition-all duration-200 group">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center shadow-lg shadow-green-500/30 group-hover:scale-110 transition-transform">
                            <x-heroicon-o-cube class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ __('Add Product') }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Add new product to inventory') }}</div>
                        </div>
                    </a>

                    <a href="{{ route('filament.app.resources.customers.create') }}" 
                       class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 hover:shadow-lg transition-all duration-200 group">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform">
                            <x-heroicon-o-user-plus class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ __('New Customer') }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Add a new customer') }}</div>
                        </div>
                    </a>

                    <a href="{{ route('filament.app.resources.expenses.create') }}" 
                       class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 hover:shadow-lg transition-all duration-200 group">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg shadow-orange-500/30 group-hover:scale-110 transition-transform">
                            <x-heroicon-o-banknotes class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ __('Record Expense') }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('Add a new expense entry') }}</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            @foreach ($this->getWidgets() as $widget)
                @if ($widget === \App\Filament\Widgets\RecentActivity::class)
                    @livewire($widget)
                @endif
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
