<x-filament-panels::page.simple>
    @push('styles')
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    @endpush
    
    <style>
        /* Override Filament constraints */
        .fi-simple-page,
        .fi-simple-main,
        .fi-simple-main > div {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        body {
            font-family: 'Noto Sans Bengali', 'Inter', sans-serif !important;
        }
        
        /* Force light background in light mode */
        html:not(.dark) {
            background: #ffffff !important;
        }
        
        html:not(.dark) body {
            background: #ffffff !important;
        }
        
        /* Override all Filament backgrounds */
        .fi-body,
        .fi-layout,
        [x-cloak] {
            background: transparent !important;
        }
        
        .modern-auth-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            background: #ffffff !important;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
        }
        
        .dark .modern-auth-container {
            background: #0f172a !important;
        }
        
        /* Left Side - Form */
        .auth-form-side {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: #ffffff;
            overflow-y: auto;
        }
        
        .dark .auth-form-side {
            background: #0f172a;
        }
        
        .auth-form-wrapper {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 10;
        }
        
        /* Hide Filament's default card styling */
        .fi-simple-main .fi-section-content-ctn {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
        
        .auth-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
        }
        
        .auth-logo svg {
            width: 40px;
            height: 40px;
            color: #1e40af;
        }
        
        .auth-logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e40af;
        }
        
        .auth-title {
            font-size: 1.875rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .dark .auth-title {
            color: #f9fafb;
        }
        
        .auth-subtitle {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        .dark .auth-subtitle {
            color: #9ca3af;
        }
        
        .social-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb !important;
            border-radius: 0.5rem;
            background: white !important;
            color: #374151 !important;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .dark .social-btn {
            background: #1f2937 !important;
            border-color: #374151 !important;
            color: #d1d5db !important;
        }
        
        .social-btn:hover {
            border-color: #1e40af !important;
            box-shadow: 0 2px 8px rgba(30, 64, 175, 0.1);
            transform: translateY(-1px);
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: #9ca3af;
            font-size: 0.875rem;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .dark .divider::before,
        .dark .divider::after {
            border-bottom-color: #374151;
        }
        
        .divider span {
            padding: 0 1rem;
        }
        
        /* Right Side - Illustration */
        .auth-illustration-side {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .illustration-content {
            text-align: center;
            color: white;
            z-index: 10;
            max-width: 500px;
        }
        
        .illustration-graphic {
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
        }
        
        .illustration-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .illustration-icon:nth-child(2) {
            animation-delay: 0.5s;
        }
        
        .illustration-icon:nth-child(3) {
            animation-delay: 1s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        
        .illustration-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .illustration-description {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .illustration-bg-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0.1;
        }
        
        .illustration-bg-pattern circle {
            fill: white;
        }
        
        .auth-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .dark .auth-footer {
            color: #9ca3af;
        }
        
        .auth-footer a {
            color: #1e40af;
            font-weight: 600;
            text-decoration: none;
        }
        
        .dark .auth-footer a {
            color: #3b82f6;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .modern-auth-container {
                grid-template-columns: 1fr;
            }
            
            .auth-illustration-side {
                display: none;
            }
        }
    </style>
    
    <div class="modern-auth-container">
        <!-- Left Side - Form -->
        <div class="auth-form-side">
            <div class="auth-form-wrapper">
                <div class="auth-logo">
                    <svg viewBox="0 0 40 40" fill="none">
                        <rect width="40" height="40" rx="8" fill="currentColor"/>
                        <path d="M12 20L18 26L28 14" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="auth-logo-text">GenERP</span>
                </div>
                
                <h1 class="auth-title">{{ __('filament-panels::pages/auth/login.heading') }}</h1>
                <p class="auth-subtitle">{{ __('Welcome back! Select method to log in:') }}</p>
                
                <!-- Social Login Buttons -->
                <div class="social-buttons">
                    <button type="button" class="social-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Google
                    </button>
                    <button type="button" class="social-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </button>
                </div>
                
                <div class="divider">
                    <span>{{ __('or continue with email') }}</span>
                </div>
                
                <!-- Login Form -->
                {{ $this->form }}
                
                <div class="auth-footer">
                    {{ __("Don't have an account?") }}
                    <a href="{{ route('filament.app.auth.register') }}">
                        {{ __('Create an account') }}
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Illustration -->
        <div class="auth-illustration-side">
            <svg class="illustration-bg-pattern" viewBox="0 0 400 400">
                <circle cx="50" cy="50" r="3"/>
                <circle cx="100" cy="50" r="3"/>
                <circle cx="150" cy="50" r="3"/>
                <circle cx="200" cy="50" r="3"/>
                <circle cx="250" cy="50" r="3"/>
                <circle cx="300" cy="50" r="3"/>
                <circle cx="350" cy="50" r="3"/>
                <circle cx="50" cy="100" r="3"/>
                <circle cx="100" cy="100" r="3"/>
                <circle cx="150" cy="100" r="3"/>
                <circle cx="200" cy="100" r="3"/>
                <circle cx="250" cy="100" r="3"/>
                <circle cx="300" cy="100" r="3"/>
                <circle cx="350" cy="100" r="3"/>
                <circle cx="50" cy="150" r="3"/>
                <circle cx="100" cy="150" r="3"/>
                <circle cx="150" cy="150" r="3"/>
                <circle cx="200" cy="150" r="3"/>
                <circle cx="250" cy="150" r="3"/>
                <circle cx="300" cy="150" r="3"/>
                <circle cx="350" cy="150" r="3"/>
            </svg>
            
            <div class="illustration-content">
                <div class="illustration-graphic">
                    <div class="illustration-icon">📊</div>
                    <div class="illustration-icon">🔒</div>
                    <div class="illustration-icon">⚡</div>
                </div>
                <h2 class="illustration-title">{{ __('Connect with every application.') }}</h2>
                <p class="illustration-description">{{ __('Everything you need in an easily customizable dashboard. Manage your business operations efficiently with GenERP.') }}</p>
            </div>
        </div>
    </div>
</x-filament-panels::page.simple>
