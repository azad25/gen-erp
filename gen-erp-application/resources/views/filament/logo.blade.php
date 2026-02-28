<div class="fi-logo flex items-center gap-3">
    <!-- Premium SaaS ERP Logo - -->
    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-10 w-10">
        <!-- Base Glow/Shadow Element -->
        <path d="M50 15 L85 35 L85 75 L50 95 L15 75 L15 35 Z" fill="url(#core_shadow)" opacity="0.3" filter="blur(8px)"/>
        
        <!-- Outer Hexagon representing the Ecosystem / Enterprise -->
        <path d="M50 10 L85 30 L85 70 L50 90 L15 70 L15 30 Z" stroke="url(#hex_stroke)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="white" fill-opacity="0.05"/>

        <!-- Left Block - Pillar of Growth -->
        <path d="M15 30 L50 48 L50 90 L15 70 Z" fill="url(#grad_left)" />
        
        <!-- Right Block - Modular Intelligence (Shorter to form the 'G' cutout) -->
        <path d="M50 48 L85 30 L85 60 L65 70 L50 63 Z" fill="url(#grad_right)" />
        
        <!-- Top Block - Abstract Cloud/Hub -->
        <path d="M50 10 L85 30 L50 48 L15 30 Z" fill="url(#grad_top)" />
        
        <!-- Inner glowing data node -->
        <circle cx="50" cy="48" r="8" fill="#FFFFFF" fill-opacity="0.9" style="filter: drop-shadow(0 0 10px rgba(20, 184, 166, 0.8));"/>
        <circle cx="50" cy="48" r="3" fill="var(--color-primary-light, #14B8A6)"/>

        <defs>
            <!-- Deep Teal -->
            <linearGradient id="grad_left" x1="15" y1="30" x2="50" y2="90" gradientUnits="userSpaceOnUse">
                <stop stop-color="var(--color-primary, #0F766E)"/>
                <stop offset="1" stop-color="var(--color-primary-dark, #115E59)"/>
            </linearGradient>
            
            <!-- Light Teal to Primary -->
            <linearGradient id="grad_right" x1="50" y1="48" x2="85" y2="70" gradientUnits="userSpaceOnUse">
                <stop stop-color="var(--color-primary-light, #14B8A6)"/>
                <stop offset="1" stop-color="var(--color-primary, #0F766E)"/>
            </linearGradient>

            <!-- Bright Horizon -> Green -->
            <linearGradient id="grad_top" x1="15" y1="30" x2="85" y2="30" gradientUnits="userSpaceOnUse">
                <stop stop-color="var(--color-primary-light, #14B8A6)"/>
                <stop offset="1" stop-color="var(--color-success, #16A34A)"/>
            </linearGradient>
            
            <linearGradient id="hex_stroke" x1="15" y1="10" x2="85" y2="90" gradientUnits="userSpaceOnUse">
                <stop stop-color="var(--color-primary, #0F766E)" stop-opacity="0.5"/>
                <stop offset="1" stop-color="var(--color-primary-light, #14B8A6)" stop-opacity="0.5"/>
            </linearGradient>

            <linearGradient id="core_shadow" x1="50" y1="15" x2="50" y2="95" gradientUnits="userSpaceOnUse">
                <stop stop-color="var(--color-primary-dark, #115E59)"/>
                <stop offset="1" stop-color="var(--color-primary, #0F766E)" stop-opacity="0"/>
            </linearGradient>
        </defs>
    </svg>
    <div class="flex flex-col min-w-0 overflow-hidden fi-sidebar-logo-text transition-opacity duration-300" x-show="$store.sidebar ? $store.sidebar.isOpen : true">
        <span class="text-lg font-extrabold text-white tracking-tight leading-tight truncate">GenERP BD</span>
        <span class="text-[10px] text-gray-400 font-mono tracking-wider truncate">ENTERPRISE CLOUD</span>
    </div>
</div>
