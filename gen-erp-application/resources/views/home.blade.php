<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('home.title') ?? 'GenERP BD - Intelligent Cloud ERP' }}</title>
    
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-home.css />
</head>
<body class="antialiased bg-base-bg text-base-text {{ app()->getLocale() === 'bn' ? 'font-bn' : 'font-sans' }}">

    <!-- Organic Ambient Background Blobs -->
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
        <div class="absolute -top-[10%] -left-[10%] w-[500px] h-[500px] rounded-full bg-primary/10 blur-[90px] animated-blob"></div>
        <div class="absolute top-[20%] -right-[10%] w-[600px] h-[600px] rounded-full bg-success/10 blur-[100px] animated-blob" style="animation-delay: -5s"></div>
    </div>

    <!-- Navigation -->
    <nav class="fixed w-full z-50 glass-panel border-b border-neutral-100 transition-all duration-300" 
         x-data="{ scrolled: false }" 
         @scroll.window="scrolled = (window.pageYOffset > 20)"
         :class="{ 'py-4 shadow-sm': scrolled, 'py-6': !scrolled }">
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                
                <a href="/" class="flex items-center gap-3 group">
                    <x-home.logo class="w-10 h-10 drop-shadow-md group-hover:scale-105 transition-transform" />
                    <span class="text-xl font-extrabold text-base-text tracking-tight">GenERP BD</span>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-base-text/70 hover:text-primary font-bold transition-colors">{{ __('home.nav.features') }}</a>
                    <a href="#modules" class="text-base-text/70 hover:text-primary font-bold transition-colors">{{ __('home.nav.modules') }}</a>
                    <a href="#pricing" class="text-base-text/70 hover:text-primary font-bold transition-colors">{{ __('home.nav.pricing') }}</a>
                    <a href="#testimonials" class="text-base-text/70 hover:text-primary font-bold transition-colors">{{ __('home.nav.testimonials') }}</a>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center bg-neutral-100 rounded-lg p-1">
                        <a href="{{ route('locale.set', 'bn') }}" class="px-3 py-1 rounded-md text-sm font-bold transition-all {{ app()->getLocale() === 'bn' ? 'bg-white text-primary shadow-sm' : 'text-neutral-500 hover:text-base-text' }}">BN</a>
                        <a href="{{ route('locale.set', 'en') }}" class="px-3 py-1 rounded-md text-sm font-bold transition-all {{ app()->getLocale() === 'en' ? 'bg-white text-primary shadow-sm' : 'text-neutral-500 hover:text-base-text' }}">EN</a>
                    </div>

                    @auth
                        <a href="{{ route('filament.app.pages.dashboard') }}" class="hidden md:inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-xl text-white bg-primary hover:bg-primary-dark shadow-sm hover:shadow-md transition-all">
                            {{ __('home.nav.dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-base-text/80 hover:text-primary font-bold px-4">{{ __('home.nav.sign_in') }}</a>
                        <a href="{{ route('register') }}" class="hidden md:inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-xl text-white bg-gradient-premium shadow-lg shadow-primary/25 hover:scale-105 transition-all">
                            {{ __('home.nav.start_trial') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative pt-32 pb-20 sm:pt-40 sm:pb-24 lg:pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="lg:grid lg:grid-cols-12 lg:gap-16 items-center">
                
                <div class="lg:col-span-6 text-center lg:text-left mb-16 lg:mb-0" data-aos="fade-right">
                    <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-primary/10 text-primary font-bold text-sm mb-8 border border-primary/20">
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-light opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                        </span>
                        {{ __('home.hero.badge') }}
                    </div>
                    
                    <h1 class="text-5xl sm:text-6xl font-extrabold tracking-tight text-base-text mb-6 leading-[1.1]">
                        {{ __('home.hero.title_part1') }} <br/>
                        <span class="text-gradient">{{ __('home.hero.title_part2') }}</span>
                    </h1>
                    
                    <p class="text-lg text-base-text/80 mb-8 max-w-2xl mx-auto lg:mx-0 font-medium">
                        {{ __('home.hero.description') }}
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 text-base font-extrabold rounded-xl text-white bg-gradient-premium shadow-xl shadow-primary/25 transition-all hover:-translate-y-1">
                            {{ __('home.hero.cta_primary') }}
                            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                        <a href="#features" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold rounded-xl text-base-text bg-white border border-neutral-100 shadow-sm transition-all hover:-translate-y-1">
                            {{ __('home.hero.cta_secondary') }}
                        </a>
                    </div>
                </div>
                
                <div class="lg:col-span-6 relative perspective-1000" data-aos="fade-left" data-aos-delay="200">
                    <x-home.hero-svg />

                    <div class="absolute -right-8 top-[15%] glass-panel rounded-2xl p-4 shadow-xl z-20 float-animation border-l-4 border-success">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center text-success">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-neutral-500 uppercase tracking-wide">Monthly Revenue</p>
                                <p class="text-lg font-extrabold text-base-text">৳ 2.4M <span class="text-sm text-success">+14%</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -left-12 bottom-[20%] glass-panel rounded-2xl p-4 shadow-xl z-20 float-animation-delayed border border-neutral-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-neutral-500 uppercase tracking-wide">Mushak 6.3</p>
                                <p class="text-lg font-extrabold text-success">Generated ✓</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trusted Companies Marquee -->
    <div class="py-12 bg-white border-y border-neutral-100 overflow-hidden relative group">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8 text-center relative z-20">
            <p class="text-sm font-bold text-neutral-500 uppercase tracking-widest">{{ __('home.companies.title') }}</p>
        </div>
        
        <!-- Fading Edges for smooth scroll disappearing effect -->
        <div class="absolute left-0 top-0 bottom-0 w-32 bg-gradient-to-r from-white to-transparent z-10 pointer-events-none"></div>
        <div class="absolute right-0 top-0 bottom-0 w-32 bg-gradient-to-l from-white to-transparent z-10 pointer-events-none"></div>
        
        <div class="flex w-max animate-marquee whitespace-nowrap items-center pt-2 pb-4">
            @for($i=0; $i<4; $i++)
                <!-- Partner Logo 1 -->
                <div class="flex items-center gap-3 px-12 opacity-50 grayscale hover:grayscale-0 hover:opacity-100 hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 22h20L12 2zm0 6l5.5 11h-11L12 8z"/></svg>
                    <span class="text-2xl font-extrabold text-neutral-400 hover:text-neutral-800 transition-colors tracking-tight">ApexCloud</span>
                </div>
                <!-- Partner Logo 2 -->
                <div class="flex items-center gap-3 px-12 opacity-50 grayscale hover:grayscale-0 hover:opacity-100 hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <svg class="w-8 h-8 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><circle cx="12" cy="12" r="8"/><path d="M12 8v8m-4-4h8"/></svg>
                    <span class="text-2xl font-black text-neutral-400 hover:text-neutral-800 transition-colors tracking-tighter">Nexus<span class="text-success opacity-50 hover:opacity-100">Flow</span></span>
                </div>
                <!-- Partner Logo 3 -->
                <div class="flex items-center gap-3 px-12 opacity-50 grayscale hover:grayscale-0 hover:opacity-100 hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <svg class="w-8 h-8 text-primary-light" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="12" cy="12" r="4" fill="white"/></svg>
                    <span class="text-2xl font-bold text-neutral-400 hover:text-neutral-800 transition-colors">BoxMatrix</span>
                </div>
                <!-- Partner Logo 4 -->
                <div class="flex items-center gap-3 px-12 opacity-50 grayscale hover:grayscale-0 hover:opacity-100 hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <svg class="w-8 h-8 text-warning" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <span class="text-2xl font-extrabold text-neutral-400 hover:text-neutral-800 transition-colors tracking-wide uppercase">Lumina</span>
                </div>
                <!-- Partner Logo 5 -->
                <div class="flex items-center gap-3 px-12 opacity-50 grayscale hover:grayscale-0 hover:opacity-100 hover:-translate-y-1 transition-all duration-300 cursor-pointer">
                    <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1v-7s-1 1-4 1-5-2-8-2-4 1-4 1v7z"/><path d="M4 22v-7"/></svg>
                    <span class="text-2xl font-bold text-neutral-400 hover:text-neutral-800 transition-colors">Wave<span class="font-normal italic">Data</span></span>
                </div>
            @endfor
        </div>
    </div>

    <div class="py-16 bg-primary text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CgkJPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMSIgZmlsbD0icmdiYSgyNTUsIDI1NSwgMjU1LCAwLjE1KSIvPgo8L3N2Zz4=')]"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center divide-x divide-primary-light/30">
                <div data-aos="fade-up" data-aos-delay="100">
                    <p class="text-4xl font-extrabold mb-2">500+</p>
                    <p class="text-neutral-100/80 font-medium">{{ __('home.stats.companies') }}</p>
                </div>
                <div data-aos="fade-up" data-aos-delay="200">
                    <p class="text-4xl font-extrabold mb-2 text-primary-light">99.9%</p>
                    <p class="text-neutral-100/80 font-medium">{{ __('home.stats.uptime') }}</p>
                </div>
                <div data-aos="fade-up" data-aos-delay="300">
                    <p class="text-4xl font-extrabold mb-2">24/7</p>
                    <p class="text-neutral-100/80 font-medium">{{ __('home.stats.support') }}</p>
                </div>
                <div data-aos="fade-up" data-aos-delay="400">
                    <p class="text-4xl font-extrabold mb-2">50+</p>
                    <p class="text-neutral-100/80 font-medium">{{ __('home.stats.integrations') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div id="features" class="py-24 bg-base-bg relative border-b border-neutral-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16" data-aos="fade-up">
                <h2 class="text-primary font-bold tracking-widest uppercase text-sm mb-3">{{ __('home.features.badge') }}</h2>
                <p class="text-4xl font-extrabold text-base-text mb-4">{{ __('home.features.title') }}</p>
                <p class="text-xl text-base-text/80 font-medium">{{ __('home.features.description') }}</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach(__('home.features.items') as $index => $feature)
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-neutral-100 hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 transition-all duration-300 hover:-translate-y-2 group" data-aos="fade-up" data-aos-delay="{{ $index * 100 }}">
                    <div class="w-16 h-16 rounded-2xl bg-neutral-100 flex items-center justify-center text-3xl mb-6 shadow-sm border border-white group-hover:scale-110 group-hover:bg-primary group-hover:text-white transition-all">
                        {{ $feature['icon'] }}
                    </div>
                    <h3 class="text-xl font-bold text-base-text mb-3">{{ $feature['title'] }}</h3>
                    <p class="text-base-text/70 font-medium leading-relaxed">{{ $feature['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Product Modules Cards -->
    <div id="modules" class="py-24 bg-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16" data-aos="fade-up">
                <h2 class="text-success font-bold tracking-widest uppercase text-sm mb-3">{{ __('home.modules.badge') }}</h2>
                <p class="text-4xl font-extrabold text-base-text mb-4">{{ __('home.modules.title') }}</p>
                <p class="text-xl text-base-text/80 font-medium">{{ __('home.modules.description') }}</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach(__('home.modules.items') as $index => $module)
                <div class="bg-base-bg border border-neutral-100 hover:border-success/40 rounded-3xl p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-1 cursor-pointer group" data-aos="zoom-in" data-aos-delay="{{ $index * 50 }}">
                    <div class="text-4xl mb-4 group-hover:scale-125 transition-transform duration-300">{{ $module['icon'] }}</div>
                    <h4 class="font-bold text-base-text">{{ $module['name'] }}</h4>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Parallax Showcase Sections -->
    <div class="py-32 bg-base-bg border-y border-neutral-100 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            @foreach(__('home.showcase.items') as $index => $item)
            <div class="lg:grid lg:grid-cols-2 lg:gap-16 items-center mb-32 last:mb-0 {{ $index % 2 !== 0 ? 'lg:grid-flow-col-dense' : '' }}">
                
                <div class="{{ $index % 2 !== 0 ? 'lg:col-start-2' : '' }}" data-aos="{{ $index % 2 === 0 ? 'fade-right' : 'fade-left' }}">
                    <div class="text-4xl mb-4 text-primary bg-primary/10 w-20 h-20 flex items-center justify-center rounded-full leading-none">{{ $item['icon'] }}</div>
                    <h2 class="text-3xl font-extrabold text-base-text mb-6">{{ $item['title'] }}</h2>
                    <p class="text-lg text-base-text/80 mb-8 font-medium leading-relaxed">{{ $item['description'] }}</p>
                    
                    <ul class="space-y-4 font-bold text-base-text/70">
                        @foreach($item['features'] as $feature)
                        <li class="flex items-center gap-3">
                            <div class="bg-success/10 text-success rounded-full p-1 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <span class="text-base-text">{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-16 lg:mt-0 glass-panel border border-white rounded-[2rem] p-6 shadow-xl relative {{ $index % 2 !== 0 ? 'lg:col-start-1' : '' }}" data-aos="zoom-in">
                    <div class="absolute -top-6 -right-6 w-32 h-32 {{ $index % 2 === 0 ? 'bg-primary' : 'bg-success' }} rounded-full blur-[60px] opacity-20 z-0"></div>
                    <div class="relative z-10 rounded-2xl overflow-hidden border border-neutral-100 bg-white aspect-video flex items-center justify-center">
                        <svg viewBox="0 0 400 250" class="w-full text-neutral-100" fill="none">
                            <rect width="400" height="40" fill="#F3F4F6"></rect>
                            <rect x="20" y="60" width="360" height="170" rx="12" fill="#FAFAFA"></rect>
                            <rect x="40" y="80" width="100" height="15" rx="8" fill="#0F766E" opacity="0.6"></rect>
                            <rect x="40" y="110" width="320" height="100" rx="8" fill="#D1D5DB" opacity="0.4"></rect>
                            <circle cx="90" cy="160" r="30" fill="#16A34A" opacity="0.5"></circle>
                            <rect x="140" y="140" width="180" height="10" rx="5" fill="#14B8A6"></rect>
                            <rect x="140" y="160" width="140" height="10" rx="5" fill="#CA8A04" opacity="0.5"></rect>
                        </svg>
                    </div>
                </div>

            </div>
            @endforeach
        </div>
    </div>

    <!-- Testimonials / Customer Reviews -->
    <div id="testimonials" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16" data-aos="fade-up">
                <h2 class="text-primary font-bold tracking-widest uppercase text-sm mb-3">{{ __('home.testimonials.badge') }}</h2>
                <p class="text-4xl font-extrabold text-base-text mb-4">{{ __('home.testimonials.title') }}</p>
                <p class="text-xl text-base-text/80 font-medium">{{ __('home.testimonials.description') }}</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                @foreach(__('home.testimonials.items') as $index => $review)
                <div class="bg-base-bg p-8 rounded-[2rem] border border-neutral-100 relative group hover:shadow-xl transition-all duration-300" data-aos="fade-up" data-aos-delay="{{ $index * 150 }}">
                    <div class="text-warning flex gap-1 mb-6 text-xl">
                        ★★★★★
                    </div>
                    <p class="text-base-text font-medium text-lg leading-relaxed mb-8">"{{ $review['text'] }}"</p>
                    <div class="flex items-center gap-4 mt-auto">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xl border border-primary/20">
                            {{ $review['avatar'] }}
                        </div>
                        <div>
                            <p class="font-bold text-base-text">{{ $review['name'] }}</p>
                            <p class="text-sm font-medium text-base-text/60">{{ $review['role'] }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Pricing Packages -->
    <div id="pricing" class="py-24 bg-[#111827] relative overflow-hidden">
        <div class="absolute inset-0">
             <div class="absolute top-[20%] left-1/2 -translate-x-1/2 w-[700px] h-[700px] bg-primary rounded-full blur-[150px] opacity-20 pointer-events-none"></div>
             <div class="absolute bottom-[0%] right-[10%] w-[500px] h-[500px] bg-success rounded-full blur-[150px] opacity-10 pointer-events-none"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto mb-16" data-aos="fade-up">
                <h2 class="text-primary-light font-bold tracking-widest uppercase text-sm mb-3">{{ __('home.pricing.badge') }}</h2>
                <p class="text-4xl font-extrabold text-white mb-4">{{ __('home.pricing.title') }}</p>
                <p class="text-xl text-neutral-100/70 font-medium">{{ __('home.pricing.description') }}</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 items-center max-w-6xl mx-auto">
                @foreach(__('home.pricing.plans') as $index => $plan)
                <div class="relative bg-[#1F2937]/80 backdrop-blur-xl border {{ $plan['featured'] ? 'border-primary shadow-2xl shadow-primary/20 scale-105 z-20' : 'border-neutral-500/30 z-10' }} rounded-[2rem] p-8" data-aos="zoom-in" data-aos-delay="{{ $index * 100 }}">
                    
                    @if($plan['featured'])
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-premium text-white px-5 py-1.5 rounded-full text-sm font-bold shadow-lg">
                        {{ __('home.pricing.popular') }}
                    </div>
                    @endif

                    <h3 class="text-2xl font-bold text-white mb-2">{{ $plan['name'] }}</h3>
                    <p class="text-neutral-100/70 font-medium mb-8">{{ $plan['description'] }}</p>
                    
                    <div class="mb-8">
                        @if($plan['price'] === 'Custom' || $plan['price'] === 'কাস্টম')
                            <span class="text-5xl font-extrabold text-white">{{ $plan['price'] }}</span>
                        @else
                            <span class="text-5xl font-extrabold text-white">{{ $plan['price'] }}</span><span class="text-neutral-100/70 font-bold text-xl">{{ $plan['currency'] }}</span>
                        @endif
                        <p class="text-neutral-100/50 text-sm mt-2 font-medium">{{ $plan['period'] }}</p>
                    </div>

                    <ul class="space-y-4 mb-8">
                        @foreach($plan['features'] as $feature)
                        <li class="flex items-start gap-3">
                            <div class="mt-1">
                                <svg class="w-5 h-5 {{ $plan['featured'] ? 'text-primary-light' : 'text-neutral-100/50' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <span class="text-neutral-100/80 font-medium">{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('register') }}" class="block w-full text-center py-4 rounded-xl font-bold text-lg transition-all {{ $plan['featured'] ? 'bg-primary text-white hover:bg-primary-dark shadow-lg shadow-primary/25' : 'bg-neutral-500/20 text-white hover:bg-neutral-500/40' }}">
                        {{ __('home.pricing.get_started') }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>


    <!-- CTA Section -->
    <div class="relative py-24 bg-white border-b border-neutral-100">
        <div class="max-w-5xl mx-auto px-4 relative z-10 text-center" data-aos="zoom-in">
            <h2 class="text-4xl sm:text-5xl font-extrabold text-base-text mb-6">{{ __('home.cta.title') }}</h2>
            <p class="text-xl text-base-text/80 mb-10 font-medium max-w-3xl mx-auto">{{ __('home.cta.description') }}</p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-10 py-5 text-lg font-bold rounded-2xl text-white bg-gradient-premium shadow-xl shadow-primary/25 transition-all hover:scale-105">
                    {{ __('home.cta.button') }}
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-10 py-5 text-lg font-bold rounded-2xl text-base-text bg-base-bg border border-neutral-100 hover:bg-neutral-100 transition-all">
                    {{ __('home.cta.sign_in') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-base-text pt-20 pb-10 border-t-8 border-primary">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-12 mb-16">
                
                <div class="md:col-span-2 pr-8">
                    <a href="/" class="flex items-center gap-3 mb-6">
                        <x-home.logo class="w-10 h-10" />
                        <span class="text-2xl font-extrabold text-white tracking-tight">GenERP BD</span>
                    </a>
                    <p class="text-neutral-100/70 text-base font-medium leading-relaxed mb-6">{{ __('home.footer.tagline') }}</p>
                    <div class="flex space-x-4">
                        <div class="w-10 h-10 rounded-full bg-neutral-500/20 flex items-center justify-center text-neutral-100/70 hover:bg-primary hover:text-white transition-all cursor-pointer">
                            <span>in</span>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-neutral-500/20 flex items-center justify-center text-neutral-100/70 hover:bg-primary hover:text-white transition-all cursor-pointer">
                            <span>fb</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-white font-bold text-lg mb-6">{{ __('home.footer.product.title') }}</h3>
                    <ul class="space-y-4 text-neutral-100/70 font-medium list-none p-0">
                        @foreach(__('home.footer.product.links') as $link)
                            <li><a href="#" class="hover:text-primary-light transition-colors">{{ $link }}</a></li>
                        @endforeach
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-white font-bold text-lg mb-6">{{ __('home.footer.company.title') }}</h3>
                    <ul class="space-y-4 text-neutral-100/70 font-medium list-none p-0">
                        @foreach(__('home.footer.company.links') as $link)
                            <li><a href="#" class="hover:text-primary-light transition-colors">{{ $link }}</a></li>
                        @endforeach
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-white font-bold text-lg mb-6">{{ __('home.footer.support.title') }}</h3>
                    <ul class="space-y-4 text-neutral-100/70 font-medium list-none p-0">
                        @foreach(__('home.footer.support.links') as $link)
                            <li><a href="#" class="hover:text-primary-light transition-colors">{{ $link }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
            
            <div class="pt-8 border-t border-neutral-500/20 flex flex-col md:flex-row justify-between items-center gap-4 text-neutral-100/60 font-medium">
                <div>{{ __('home.footer.copyright', ['year' => date('Y')]) }}</div>
                <div class="flex gap-6 text-sm">
                    <a href="#" class="hover:text-neutral-100/90">Privacy Policy</a>
                    <a href="#" class="hover:text-neutral-100/90">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({ once: true, offset: 50, duration: 800, easing: 'ease-out-cubic' });
        });
    </script>
</body>
</html>
