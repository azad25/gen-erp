@props([
    'navigation',
])

<header class="fi-topbar sticky top-0 z-20 overflow-x-clip">
    <nav class="flex h-16 items-center gap-x-4 bg-white px-4 shadow-sm ring-1 ring-gray-950/5 md:px-6 lg:px-8">
        {{-- Sidebar Toggle --}}
        <button
            type="button"
            class="fi-topbar-item flex items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-50 hover:text-gray-700 lg:hidden"
            x-data="{}"
            x-on:click="$store.sidebar.isOpen = ! $store.sidebar.isOpen"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Breadcrumbs / Page Title --}}
        <div class="flex flex-1 items-center gap-x-4">
            <h1 class="text-xl font-bold text-gray-900">
                {{ filament()->getCurrentPanel()->getId() === 'app' ? __('Dashboard') : '' }}
            </h1>
        </div>

        {{-- Right Side Actions --}}
        <div class="flex items-center gap-x-4">
            {{-- Global Search --}}
            <button
                type="button"
                class="fi-topbar-item flex items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-50 hover:text-gray-700"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>

            {{-- Language Selector --}}
            <div x-data="{ open: false }" class="relative">
                <button
                    @click="open = !open"
                    type="button"
                    class="fi-topbar-item flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                    <span class="uppercase">{{ app()->getLocale() }}</span>
                </button>

                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition
                    class="absolute right-0 mt-2 w-48 rounded-lg bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                    style="display: none;"
                >
                    <a href="{{ route('locale.set', 'en') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ app()->getLocale() === 'en' ? 'bg-teal-50 text-teal-700 font-semibold' : '' }}">
                        <span class="text-lg">🇬🇧</span>
                        <span>English</span>
                        @if(app()->getLocale() === 'en')
                            <svg class="ml-auto h-4 w-4 text-teal-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </a>
                    <a href="{{ route('locale.set', 'bn') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ app()->getLocale() === 'bn' ? 'bg-teal-50 text-teal-700 font-semibold' : '' }}">
                        <span class="text-lg">🇧🇩</span>
                        <span>বাংলা</span>
                        @if(app()->getLocale() === 'bn')
                            <svg class="ml-auto h-4 w-4 text-teal-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </a>
                </div>
            </div>

            {{-- Notifications --}}
            <button
                type="button"
                class="fi-topbar-item relative flex items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-50 hover:text-gray-700"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-red-500"></span>
            </button>

            {{-- User Menu --}}
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_BEFORE) }}
            
            @if (filament()->auth()->check())
                <x-filament-panels::user-menu />
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_AFTER) }}
        </div>
    </nav>
</header>
