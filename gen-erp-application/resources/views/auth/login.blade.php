<x-layouts.guest :title="__('Login')">
    <style>
        .auth-container {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
            font-family: 'Inter', system-ui, sans-serif;
            padding: 2rem;
        }

        .auth-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            animation: pulse-slow 10s infinite alternate cubic-bezier(0.4, 0, 0.2, 1);
        }
        .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: rgba(79, 70, 229, 0.2); }
        .blob-2 { bottom: -10%; right: -10%; width: 500px; height: 500px; background: rgba(14, 165, 233, 0.2); animation-delay: -5s; }

        .auth-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            border-radius: 24px;
            width: 100%;
            max-width: 440px;
            padding: 3rem 2.5rem;
            position: relative;
            z-index: 1;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .auth-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #4f46e5;
            margin-bottom: 1.5rem;
        }

        .auth-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            font-size: 0.95rem;
            color: #64748b;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #0f172a;
            font-size: 1rem;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: #ffffff;
        }

        .error-text {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .flex-between {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #475569;
            cursor: pointer;
        }

        .checkbox-input {
            width: 1rem;
            height: 1rem;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            accent-color: #4f46e5;
        }

        .forgot-link {
            font-size: 0.875rem;
            color: #4f46e5;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #4338ca;
            text-decoration: underline;
        }

        .btn-submit {
            width: 100%;
            padding: 0.875rem;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #0ea5e9);
            color: white;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.95rem;
            color: #64748b;
        }

        .auth-footer a {
            color: #4f46e5;
            font-weight: 600;
            text-decoration: none;
            margin-left: 0.25rem;
            transition: color 0.2s;
        }

        .auth-footer a:hover {
            text-decoration: underline;
            color: #4338ca;
        }

        @keyframes pulse-slow {
            0% { transform: scale(1) translate(0, 0); }
            100% { transform: scale(1.1) translate(20px, 20px); }
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: #94a3b8;
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .divider::before { margin-right: .5em; }
        .divider::after { margin-left: .5em; }
    </style>

    <div class="auth-container">
        <div class="auth-blob blob-1"></div>
        <div class="auth-blob blob-2"></div>

        <div class="auth-card">
            <div class="auth-header">
                <a href="/" class="auth-logo">
                    <svg width="48" height="48" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="10" fill="url(#paint0_linear)"/>
                        <path d="M12 20L18 26L28 14" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <defs>
                            <linearGradient id="paint0_linear" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#4F46E5"/>
                                <stop offset="1" stop-color="#0EA5E9"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </a>
                <h1 class="auth-title">{{ __('Welcome back') }}</h1>
                <p class="auth-subtitle">{{ __('Enter your credentials to access your account') }}</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">{{ __('Email Address') }}</label>
                    <input id="email" type="email" name="email" class="form-input" value="{{ old('email') }}" required autofocus placeholder="name@company.com">
                    @error('email') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <div class="flex-between" style="margin-bottom: 0.5rem;">
                        <label for="password" class="form-label" style="margin-bottom: 0;">{{ __('Password') }}</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="forgot-link">{{ __('Forgot password?') }}</a>
                        @endif
                    </div>
                    <input id="password" type="password" name="password" class="form-input" required placeholder="••••••••">
                    @error('password') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" class="checkbox-input" value="1" {{ old('remember') ? 'checked' : '' }}>
                        {{ __('Remember me for 30 days') }}
                    </label>
                </div>

                <button type="submit" class="btn-submit">{{ __('Sign in to GenERP') }}</button>
            </form>

            <div class="auth-footer">
                {{ __('Don\'t have an account?') }} 
                <a href="{{ route('register') }}">{{ __('Create an account') }}</a>
            </div>
        </div>
    </div>
</x-layouts.guest>
