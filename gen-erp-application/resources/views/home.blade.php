<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('home.title') }} - GenERP BD</title>
    <meta name="description" content="{{ __('home.meta_description') }}">
    
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --primary-dark: #1e3a8a;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            transition: background-color 0.3s, color 0.3s;
            background: #ffffff;
            color: var(--gray-900);
        }
        
        body.bn {
            font-family: 'Noto Sans Bengali', 'Inter', sans-serif;
        }
        
        .dark body {
            background: #0f172a;
            color: #f8fafc;
        }
        
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        /* Navigation */
        nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--gray-200);
            transition: all 0.3s;
        }
        
        .dark nav {
            background: rgba(15, 23, 42, 0.95);
            border-bottom-color: var(--gray-700);
        }
        
        .nav-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-link {
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: var(--primary);
        }
        
        .dark .nav-link {
            color: var(--gray-400);
        }
        
        .dark .nav-link:hover {
            color: var(--primary-light);
        }
        
        .nav-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--gray-300);
        }
        
        .btn-secondary:hover {
            background: var(--gray-50);
            border-color: var(--primary);
        }
        
        .dark .btn-secondary {
            color: var(--primary-light);
            border-color: var(--gray-700);
        }
        
        .dark .btn-secondary:hover {
            background: var(--gray-800);
            border-color: var(--primary-light);
        }
        
        /* Language & Theme Switcher */
        .switcher {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .lang-btn, .theme-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .dark .lang-btn, .dark .theme-btn {
            background: var(--gray-800);
            border-color: var(--gray-700);
            color: var(--gray-100);
        }
        
        .lang-btn:hover, .theme-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .lang-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Hero Section */
        .hero {
            padding: 5rem 0 4rem;
            text-align: center;
        }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 2rem;
            font-size: 0.9rem;
            color: var(--primary);
            margin-bottom: 2rem;
            font-weight: 500;
        }
        
        .dark .hero-badge {
            background: rgba(30, 64, 175, 0.1);
            border-color: rgba(30, 64, 175, 0.3);
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            color: var(--gray-900);
        }
        
        .dark .hero-title {
            color: #f9fafb;
        }
        
        .hero-title .highlight {
            color: var(--primary);
            font-weight: 900;
        }
        
        .hero-description {
            font-size: 1.2rem;
            color: var(--gray-600);
            margin-bottom: 2.5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 400;
        }
        
        .dark .hero-description {
            color: var(--gray-400);
        }
        
        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 4rem;
        }
        
        /* Company Logos Carousel */
        .companies-section {
            padding: 3rem 0;
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
            border-bottom: 1px solid var(--gray-200);
            overflow: hidden;
        }
        
        .dark .companies-section {
            background: var(--gray-800);
            border-color: var(--gray-700);
        }
        
        .companies-title {
            text-align: center;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--gray-500);
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .dark .companies-title {
            color: var(--gray-400);
        }
        
        .logos-track {
            display: flex;
            gap: 4rem;
            animation: scroll 30s linear infinite;
        }
        
        .logos-track:hover {
            animation-play-state: paused;
        }
        
        @keyframes scroll {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-50%);
            }
        }
        
        .company-logo {
            flex-shrink: 0;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 2rem;
            font-weight: 600;
            color: var(--gray-400);
            font-size: 1.2rem;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .company-logo:hover {
            opacity: 1;
        }
        
        .dark .company-logo {
            color: var(--gray-500);
        }
        
        /* Stats Section */
        .stats-section {
            padding: 4rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1rem;
            color: var(--gray-600);
            font-weight: 400;
        }
        
        .dark .stat-label {
            color: var(--gray-400);
        }
        
        /* Features Section */
        .features {
            padding: 6rem 0;
            background: var(--gray-50);
        }
        
        .dark .features {
            background: var(--gray-800);
        }
        
        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 4rem;
        }
        
        .section-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 2rem;
            font-size: 0.85rem;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .dark .section-badge {
            background: rgba(30, 64, 175, 0.1);
            border-color: rgba(30, 64, 175, 0.3);
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--gray-900);
        }
        
        .dark .section-title {
            color: #f9fafb;
        }
        
        .section-description {
            font-size: 1.1rem;
            color: var(--gray-600);
            font-weight: 400;
        }
        
        .dark .section-description {
            color: var(--gray-400);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            border: 1px solid var(--gray-200);
            transition: all 0.3s;
        }
        
        .dark .feature-card {
            background: #0f172a;
            border-color: var(--gray-700);
        }
        
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 1rem;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .dark .feature-icon {
            background: rgba(30, 64, 175, 0.1);
        }
        
        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--gray-900);
        }
        
        .dark .feature-title {
            color: #f9fafb;
        }
        
        .feature-description {
            color: var(--gray-600);
            font-weight: 400;
            line-height: 1.6;
        }
        
        .dark .feature-description {
            color: var(--gray-400);
        }
        
        /* Modules Section */
        .modules {
            padding: 6rem 0;
        }
        
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }
        
        .module-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid var(--gray-200);
            transition: all 0.3s;
            text-align: center;
        }
        
        .dark .module-card {
            background: #0f172a;
            border-color: var(--gray-700);
        }
        
        .module-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
            border-color: var(--primary);
        }
        
        .module-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .module-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .dark .module-name {
            color: #f9fafb;
        }
        
        /* Product Showcase */
        .showcase {
            padding: 6rem 0;
            background: var(--gray-50);
        }
        
        .dark .showcase {
            background: var(--gray-800);
        }
        
        .showcase-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            margin-bottom: 4rem;
        }
        
        .showcase-content:nth-child(even) {
            direction: rtl;
        }
        
        .showcase-content:nth-child(even) > * {
            direction: ltr;
        }
        
        .showcase-text h3 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--gray-900);
        }
        
        .dark .showcase-text h3 {
            color: #f9fafb;
        }
        
        .showcase-text p {
            font-size: 1.1rem;
            color: var(--gray-600);
            margin-bottom: 1.5rem;
            font-weight: 400;
            line-height: 1.7;
        }
        
        .dark .showcase-text p {
            color: var(--gray-400);
        }
        
        .showcase-features {
            list-style: none;
            padding: 0;
        }
        
        .showcase-features li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-700);
            font-weight: 400;
        }
        
        .dark .showcase-features li {
            color: var(--gray-300);
        }
        
        .showcase-features li::before {
            content: "✓";
            color: var(--success);
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .showcase-image {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 1rem;
            padding: 2rem;
            border: 1px solid var(--gray-200);
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            font-size: 3rem;
        }
        
        .dark .showcase-image {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.1) 0%, rgba(30, 64, 175, 0.05) 100%);
            border-color: var(--gray-700);
        }
        
        /* Testimonials */
        .testimonials {
            padding: 6rem 0;
        }
        
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .testimonial-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            border: 1px solid var(--gray-200);
        }
        
        .dark .testimonial-card {
            background: #0f172a;
            border-color: var(--gray-700);
        }
        
        .testimonial-rating {
            color: #f59e0b;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .testimonial-text {
            color: var(--gray-700);
            margin-bottom: 1.5rem;
            font-weight: 400;
            line-height: 1.7;
            font-style: italic;
        }
        
        .dark .testimonial-text {
            color: var(--gray-300);
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .author-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .author-info h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }
        
        .dark .author-info h4 {
            color: #f9fafb;
        }
        
        .author-info p {
            font-size: 0.9rem;
            color: var(--gray-500);
            font-weight: 400;
        }
        
        .dark .author-info p {
            color: var(--gray-400);
        }
        
        /* Pricing Section */
        .pricing {
            padding: 6rem 0;
            background: var(--gray-50);
        }
        
        .dark .pricing {
            background: var(--gray-800);
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            max-width: 1100px;
            margin: 0 auto;
        }
        
        .pricing-card {
            background: white;
            padding: 2.5rem;
            border-radius: 1rem;
            border: 1px solid var(--gray-200);
            position: relative;
            transition: all 0.3s;
        }
        
        .dark .pricing-card {
            background: #0f172a;
            border-color: var(--gray-700);
        }
        
        .pricing-card.featured {
            border-color: var(--primary);
            box-shadow: 0 12px 24px rgba(30, 64, 175, 0.15);
            transform: scale(1.05);
        }
        
        .pricing-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .pricing-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--gray-900);
        }
        
        .dark .pricing-name {
            color: #f9fafb;
        }
        
        .pricing-description {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
            font-weight: 400;
        }
        
        .dark .pricing-description {
            color: var(--gray-400);
        }
        
        .pricing-price {
            font-size: 3rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }
        
        .dark .pricing-price {
            color: #f9fafb;
        }
        
        .pricing-price span {
            font-size: 1.2rem;
            font-weight: 400;
            color: var(--gray-500);
        }
        
        .pricing-period {
            color: var(--gray-500);
            margin-bottom: 2rem;
            font-weight: 400;
        }
        
        .dark .pricing-period {
            color: var(--gray-400);
        }
        
        .pricing-features {
            list-style: none;
            padding: 0;
            margin-bottom: 2rem;
        }
        
        .pricing-features li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: var(--gray-700);
            font-weight: 400;
        }
        
        .dark .pricing-features li {
            color: var(--gray-300);
        }
        
        .pricing-features li::before {
            content: "✓";
            color: var(--success);
            font-weight: 700;
        }
        
        /* CTA Section */
        .cta {
            padding: 6rem 0;
            text-align: center;
        }
        
        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .cta h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--gray-900);
        }
        
        .dark .cta h2 {
            color: #f9fafb;
        }
        
        .cta p {
            font-size: 1.2rem;
            color: var(--gray-600);
            margin-bottom: 2.5rem;
            font-weight: 400;
        }
        
        .dark .cta p {
            color: var(--gray-400);
        }
        
        .cta-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        /* Footer */
        footer {
            background: var(--gray-900);
            color: var(--gray-400);
            padding: 4rem 0 2rem;
        }
        
        .dark footer {
            background: #000000;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .footer-brand h3 {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .footer-brand p {
            font-weight: 400;
            line-height: 1.7;
        }
        
        .footer-section h4 {
            color: white;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.75rem;
        }
        
        .footer-links a {
            color: var(--gray-400);
            text-decoration: none;
            transition: color 0.3s;
            font-weight: 400;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid var(--gray-700);
            padding-top: 2rem;
            text-align: center;
            font-weight: 400;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .modules-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .showcase-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .pricing-grid {
                grid-template-columns: 1fr;
            }
            
            .pricing-card.featured {
                transform: scale(1);
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .modules-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .testimonials-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .cta h2 {
                font-size: 2rem;
            }
        }
    </style>
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="{{ app()->getLocale() }}">
    <!-- Navigation -->
    <nav>
        <div class="container">
            <div class="nav-content">
                <a href="/" class="logo">
                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                        <rect width="40" height="40" rx="8" fill="currentColor"/>
                        <path d="M12 20L18 26L28 14" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>GenERP</span>
                </a>
                
                <div class="nav-links">
                    <a href="#features" class="nav-link">{{ __('home.nav.features') }}</a>
                    <a href="#modules" class="nav-link">{{ __('home.nav.modules') }}</a>
                    <a href="#pricing" class="nav-link">{{ __('home.nav.pricing') }}</a>
                    <a href="#testimonials" class="nav-link">{{ __('home.nav.testimonials') }}</a>
                </div>
                
                <div class="nav-actions">
                    <div class="switcher">
                        <a href="{{ route('locale.set', 'bn') }}" class="lang-btn {{ app()->getLocale() === 'bn' ? 'active' : '' }}">বাংলা</a>
                        <a href="{{ route('locale.set', 'en') }}" class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">English</a>
                        <button @click="darkMode = !darkMode" class="theme-btn">
                            <span x-show="!darkMode">🌙</span>
                            <span x-show="darkMode">☀️</span>
                        </button>
                    </div>
                    
                    @auth
                        <a href="{{ route('filament.app.pages.dashboard') }}" class="btn btn-primary">{{ __('home.nav.dashboard') }}</a>
                    @else
                        <a href="{{ route('filament.app.auth.login') }}" class="btn btn-secondary">{{ __('home.nav.sign_in') }}</a>
                        <a href="{{ route('filament.app.auth.register') }}" class="btn btn-primary">{{ __('home.nav.start_trial') }}</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-badge">
                <span>✨</span>
                <span>{{ __('home.hero.badge') }}</span>
            </div>
            
            <h1 class="hero-title">
                {{ __('home.hero.title_part1') }}<br>
                <span class="highlight">{{ __('home.hero.title_part2') }}</span>
            </h1>
            
            <p class="hero-description">
                {{ __('home.hero.description') }}
            </p>
            
            <div class="hero-actions">
                <a href="{{ route('filament.app.auth.register') }}" class="btn btn-primary">
                    {{ __('home.hero.cta_primary') }}
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                <a href="#features" class="btn btn-secondary">
                    {{ __('home.hero.cta_secondary') }}
                </a>
            </div>
        </div>
    </section>

    <!-- Company Logos Carousel -->
    <section class="companies-section">
        <div class="container">
            <p class="companies-title">{{ __('home.companies.title') }}</p>
        </div>
        <div class="logos-track">
            @foreach(__('home.companies.logos') as $logo)
                <div class="company-logo">{{ $logo }}</div>
            @endforeach
            @foreach(__('home.companies.logos') as $logo)
                <div class="company-logo">{{ $logo }}</div>
            @endforeach
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">500+</div>
                    <div class="stat-label">{{ __('home.stats.companies') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">99.9%</div>
                    <div class="stat-label">{{ __('home.stats.uptime') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">{{ __('home.stats.support') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">50+</div>
                    <div class="stat-label">{{ __('home.stats.integrations') }}</div>
                </div>
            </div>
        </div>
    </section>


    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">{{ __('home.features.badge') }}</div>
                <h2 class="section-title">{{ __('home.features.title') }}</h2>
                <p class="section-description">{{ __('home.features.description') }}</p>
            </div>
            
            <div class="features-grid">
                @foreach(__('home.features.items') as $feature)
                <div class="feature-card">
                    <div class="feature-icon">{{ $feature['icon'] }}</div>
                    <h3 class="feature-title">{{ $feature['title'] }}</h3>
                    <p class="feature-description">{{ $feature['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Modules Section -->
    <section class="modules" id="modules">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">{{ __('home.modules.badge') }}</div>
                <h2 class="section-title">{{ __('home.modules.title') }}</h2>
                <p class="section-description">{{ __('home.modules.description') }}</p>
            </div>
            
            <div class="modules-grid">
                @foreach(__('home.modules.items') as $module)
                <div class="module-card">
                    <div class="module-icon">{{ $module['icon'] }}</div>
                    <div class="module-name">{{ $module['name'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Product Showcase -->
    <section class="showcase">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">{{ __('home.showcase.badge') }}</div>
                <h2 class="section-title">{{ __('home.showcase.title') }}</h2>
                <p class="section-description">{{ __('home.showcase.description') }}</p>
            </div>
            
            @foreach(__('home.showcase.items') as $item)
            <div class="showcase-content">
                <div class="showcase-text">
                    <h3>{{ $item['title'] }}</h3>
                    <p>{{ $item['description'] }}</p>
                    <ul class="showcase-features">
                        @foreach($item['features'] as $feature)
                        <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="showcase-image">
                    {{ $item['icon'] }}
                </div>
            </div>
            @endforeach
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials" id="testimonials">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">{{ __('home.testimonials.badge') }}</div>
                <h2 class="section-title">{{ __('home.testimonials.title') }}</h2>
                <p class="section-description">{{ __('home.testimonials.description') }}</p>
            </div>
            
            <div class="testimonials-grid">
                @foreach(__('home.testimonials.items') as $testimonial)
                <div class="testimonial-card">
                    <div class="testimonial-rating">★★★★★</div>
                    <p class="testimonial-text">"{{ $testimonial['text'] }}"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">{{ $testimonial['avatar'] }}</div>
                        <div class="author-info">
                            <h4>{{ $testimonial['name'] }}</h4>
                            <p>{{ $testimonial['role'] }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section class="pricing" id="pricing">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">{{ __('home.pricing.badge') }}</div>
                <h2 class="section-title">{{ __('home.pricing.title') }}</h2>
                <p class="section-description">{{ __('home.pricing.description') }}</p>
            </div>
            
            <div class="pricing-grid">
                @foreach(__('home.pricing.plans') as $plan)
                <div class="pricing-card {{ $plan['featured'] ? 'featured' : '' }}">
                    @if($plan['featured'])
                    <div class="pricing-badge">{{ __('home.pricing.popular') }}</div>
                    @endif
                    <h3 class="pricing-name">{{ $plan['name'] }}</h3>
                    <p class="pricing-description">{{ $plan['description'] }}</p>
                    <div class="pricing-price">{{ $plan['price'] }}<span>{{ $plan['currency'] }}</span></div>
                    <p class="pricing-period">{{ $plan['period'] }}</p>
                    <ul class="pricing-features">
                        @foreach($plan['features'] as $feature)
                        <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('filament.app.auth.register') }}" class="btn {{ $plan['featured'] ? 'btn-primary' : 'btn-secondary' }}" style="width: 100%; justify-content: center;">
                        {{ __('home.pricing.get_started') }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>{{ __('home.cta.title') }}</h2>
                <p>{{ __('home.cta.description') }}</p>
                <div class="cta-actions">
                    <a href="{{ route('filament.app.auth.register') }}" class="btn btn-primary">
                        {{ __('home.cta.button') }}
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                    <a href="{{ route('filament.app.auth.login') }}" class="btn btn-secondary">
                        {{ __('home.cta.sign_in') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <h3>GenERP</h3>
                    <p>{{ __('home.footer.tagline') }}</p>
                </div>
                
                <div class="footer-section">
                    <h4>{{ __('home.footer.product.title') }}</h4>
                    <ul class="footer-links">
                        @foreach(__('home.footer.product.links') as $link)
                        <li><a href="#">{{ $link }}</a></li>
                        @endforeach
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>{{ __('home.footer.company.title') }}</h4>
                    <ul class="footer-links">
                        @foreach(__('home.footer.company.links') as $link)
                        <li><a href="#">{{ $link }}</a></li>
                        @endforeach
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>{{ __('home.footer.support.title') }}</h4>
                    <ul class="footer-links">
                        @foreach(__('home.footer.support.links') as $link)
                        <li><a href="#">{{ $link }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>{{ __('home.footer.copyright', ['year' => date('Y')]) }}</p>
            </div>
        </div>
    </footer>
</body>
</html>
