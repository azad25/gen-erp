<x-layouts.guest :title="__('Login')">
    <div class="flex min-h-screen bg-[#0f172a]">
        <!-- Left Panel: Form -->
        <div class="flex flex-col justify-center w-full px-8 py-12 lg:w-1/2 sm:px-12 lg:px-24">
            <div class="w-full max-w-md mx-auto">
                <div class="flex items-center gap-3 mb-8">
                    <x-home.logo class="w-10 h-10" />
                    <span class="text-2xl font-bold tracking-tight text-white">GenERP</span>
                </div>
                
                <h1 class="mb-2 text-3xl font-bold text-white tracking-tight">আপনার অ্যাকাউন্টে সাইন ইন করুন</h1>
                <p class="mb-8 text-sm text-slate-400">Welcome back! Select method to log in:</p>
                
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <button type="button" class="flex items-center justify-center w-full gap-2 px-4 py-2.5 text-sm font-medium text-white transition-colors border border-slate-700 rounded-lg bg-slate-800 hover:bg-slate-700">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Google
                    </button>
                    <button type="button" class="flex items-center justify-center w-full gap-2 px-4 py-2.5 text-sm font-medium text-white transition-colors border border-slate-700 rounded-lg bg-slate-800 hover:bg-slate-700">
                        <svg class="w-5 h-5 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"></path>
                        </svg>
                        Facebook
                    </button>
                </div>

                <div class="relative flex items-center py-5">
                    <div class="flex-grow border-t border-slate-700"></div>
                    <span class="flex-shrink-0 px-4 text-xs text-slate-500">or continue with email</span>
                    <div class="flex-grow border-t border-slate-700"></div>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf
                    
                    <div>
                        <label for="email" class="block mb-2 text-sm font-medium text-slate-300">ইমেইল এড্রেস *</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="block w-full px-4 py-3 text-white transition-colors bg-[#1e293b] border border-slate-700 rounded-lg focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary-light)] sm:text-sm" placeholder="name@company.com">
                        @error('email') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="block mb-2 text-sm font-medium text-slate-300">পাসওয়ার্ড *</label>
                        <div class="relative">
                            <input id="password" type="password" name="password" required class="block w-full px-4 py-3 text-white transition-colors bg-[#1e293b] border border-slate-700 rounded-lg focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary-light)] sm:text-sm" placeholder="••••••••">
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-sm text-slate-400 hover:text-white" onclick="const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password';">
                                Show password
                            </button>
                        </div>
                        @error('password') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember_me" name="remember" type="checkbox" class="w-4 h-4 text-[var(--color-primary)] bg-[#1e293b] border-slate-700 rounded focus:ring-[var(--color-primary-light)] focus:ring-offset-slate-900" {{ old('remember') ? 'checked' : '' }}>
                            <label for="remember_me" class="block ml-2 text-sm text-slate-300">মনে রাখুন</label>
                        </div>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-[var(--color-primary-light)] hover:underline">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="w-full px-4 py-3 text-sm font-medium text-white transition-all bg-[var(--color-primary)] hover:bg-[#115e59] rounded-lg shadow-lg hover:shadow-[var(--color-primary)]/30">
                        লগইন করুন
                    </button>
                    
                    <p class="mt-6 text-sm text-center text-slate-400">
                        Don't have an account? 
                        <a href="{{ route('register') }}" class="font-medium text-[var(--color-primary-light)] hover:underline">Create an account</a>
                    </p>
                </form>
            </div>
        </div>

        <!-- Right Panel: Branding/Image -->
        <div class="hidden lg:flex lg:w-1/2 bg-[var(--color-primary)] relative overflow-hidden items-center justify-center p-12">
            <!-- Background Dot Pattern -->
            <div class="absolute inset-0 z-0 opacity-20" style="background-image: radial-gradient(#ffffff 2px, transparent 2px); background-size: 32px 32px;"></div>
            
            <div class="relative z-10 w-full max-w-lg text-center">
                <div class="flex justify-center gap-6 mb-12">
                    <div class="flex items-center justify-center w-16 h-16 shadow-2xl bg-white/10 backdrop-blur-md rounded-2xl border border-white/20">
                        <span class="text-3xl">📊</span>
                    </div>
                    <div class="flex items-center justify-center w-16 h-16 shadow-2xl bg-white/10 backdrop-blur-md rounded-2xl border border-white/20">
                        <span class="text-3xl">🔒</span>
                    </div>
                    <div class="flex items-center justify-center w-16 h-16 shadow-2xl bg-white/10 backdrop-blur-md rounded-2xl border border-white/20">
                        <span class="text-3xl">⚡️</span>
                    </div>
                </div>
                
                <h2 class="mb-6 text-4xl font-extrabold leading-tight text-white">
                    Connect with every<br/>application.
                </h2>
                <p class="text-lg leading-relaxed text-emerald-100">
                    Everything you need in an easily customizable dashboard. Manage your business operations efficiently with GenERP.
                </p>
            </div>
        </div>
    </div>
</x-layouts.guest>
