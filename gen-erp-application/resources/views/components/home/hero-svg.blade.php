<div class="relative rounded-[2rem] bg-base-bg/90 backdrop-blur-xl border border-neutral-100 shadow-2xl p-2 transform rotate-y-[0deg] rotate-x-[0deg] float-animation z-10 w-full aspect-[4/3]">
    <!-- Clean, organic header -->
    <div class="flex items-center gap-2 px-4 py-3 border-b border-neutral-100">
        <div class="w-3 h-3 rounded-full bg-danger"></div>
        <div class="w-3 h-3 rounded-full bg-warning"></div>
        <div class="w-3 h-3 rounded-full bg-success"></div>
        <div class="w-32 h-2 ml-4 bg-neutral-100 rounded-full"></div>
    </div>
    
    <!-- Dashboard Content SVG -->
    <div class="p-4 w-full h-[calc(100%-48px)] rounded-xl bg-white overflow-hidden relative">
        <svg class="w-full h-full text-neutral-100" viewBox="0 0 400 300" fill="none">
            <!-- Sidebar -->
            <rect x="0" y="0" width="80" height="300" fill="#F3F4F6"/>
            <rect x="15" y="20" width="50" height="8" rx="4" fill="#D1D5DB"/>
            <rect x="15" y="40" width="30" height="8" rx="4" fill="#0F766E" opacity="0.8"/>
            <rect x="15" y="60" width="45" height="8" rx="4" fill="#D1D5DB"/>
            <rect x="15" y="80" width="40" height="8" rx="4" fill="#D1D5DB"/>
            
            <!-- Main Area Background -->
            <rect x="95" y="10" width="305" height="140" rx="16" fill="#F3F4F6" opacity="0.5"/>
            
            <!-- Organic Growth Chart Area using Teal and Forest Green -->
            <!-- Soft shaded area -->
            <path d="M 95 120 Q 150 70 220 90 T 320 40 T 395 60 L 395 150 L 95 150 Z" fill="url(#hero_teal_gradient)" opacity="0.15"/>
            
            <!-- Solid path line -->
            <path d="M 95 120 Q 150 70 220 90 T 320 40 T 395 60" stroke="#0F766E" stroke-width="4" stroke-linecap="round"/>
            
            <!-- Growth data point -->
            <circle cx="320" cy="40" r="7" fill="#16A34A" stroke="white" stroke-width="3" style="filter: drop-shadow(0 4px 6px rgba(22, 163, 74, 0.4));"/>
            <circle cx="320" cy="40" r="3" fill="white"/>
            
            <!-- Bottom Cards Row 1 - Soft organic blocks -->
            <rect x="95" y="165" width="95" height="80" rx="16" fill="#F3F4F6"/>
            <rect x="110" y="180" width="20" height="20" rx="8" fill="#0F766E" opacity="0.2"/>
            <rect x="115" y="185" width="10" height="10" rx="3" fill="#0F766E"/>
            <rect x="110" y="215" width="50" height="6" rx="3" fill="#D1D5DB"/>
            
            <rect x="200" y="165" width="95" height="80" rx="16" fill="#F3F4F6"/>
            <rect x="215" y="180" width="20" height="20" rx="8" fill="#16A34A" opacity="0.2"/>
            <rect x="220" y="185" width="10" height="10" rx="3" fill="#16A34A"/>
            <rect x="215" y="215" width="40" height="6" rx="3" fill="#D1D5DB"/>

            <rect x="305" y="165" width="95" height="80" rx="16" fill="#F3F4F6"/>
            <rect x="320" y="180" width="20" height="20" rx="8" fill="#CA8A04" opacity="0.2"/>
            <rect x="325" y="185" width="10" height="10" rx="3" fill="#CA8A04"/>
            <rect x="320" y="215" width="60" height="6" rx="3" fill="#D1D5DB"/>

            <!-- Bottom Data Table -->
            <rect x="95" y="260" width="305" height="40" rx="12" fill="#F3F4F6"/>
            <rect x="110" y="278" width="40" height="4" rx="2" fill="#D1D5DB"/>
            <rect x="180" y="278" width="80" height="4" rx="2" fill="#6B7280"/>
            <rect x="340" y="278" width="30" height="4" rx="2" fill="#16A34A"/>

            <defs>
                <linearGradient id="hero_teal_gradient" x1="250" y1="20" x2="250" y2="150" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#0F766E"/>
                    <stop offset="1" stop-color="#14B8A6" stop-opacity="0"/>
                </linearGradient>
            </defs>
        </svg>
    </div>
</div>
