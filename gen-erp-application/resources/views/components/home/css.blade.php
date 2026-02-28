<style>
    /* 
     * GenERP Core Design System - Landing Page 
     * Extracting reusable custom CSS for our 2026 Organic SaaS aesthetic
     */

    .font-bn { font-family: 'Noto Sans Bengali', sans-serif; }
    
    /* Glassmorphism primitives - Warm Neutrals base */
    .glass-panel {
        background: rgba(250, 250, 250, 0.7);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }
    
    .glass-panel-dark {
        background: rgba(31, 41, 55, 0.7); 
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Ambient Background Animations */
    .animated-blob {
        animation: blob-bounce 10s infinite alternate cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    @keyframes blob-bounce {
        0% { transform: scale(1) translate(0, 0); }
        100% { transform: scale(1.1) translate(20px, 20px); }
    }

    /* Floating UI Elements */
    .float-animation {
        animation: float 6s ease-in-out infinite;
    }

    .float-animation-delayed {
        animation: float 7s ease-in-out infinite 2s;
    }

    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
        100% { transform: translateY(0px); }
    }

    /* Marquee Animation for trusted logos */
    .animate-marquee {
        animation: marquee 35s linear infinite;
    }

    .group:hover .animate-marquee {
        animation-play-state: paused;
    }

    @keyframes marquee {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    
    /* Premium Gradients */
    .text-gradient {
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-image: linear-gradient(to right, var(--color-primary), var(--color-primary-light));
    }
    
    .bg-gradient-premium {
        background-image: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    }
</style>
