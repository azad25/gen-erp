<x-filament-panels::page>
    <style>
        .loop-card {
            background: white;
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.3s ease;
        }
        .dark .loop-card { background: #1F2937; border-color: rgba(255,255,255,0.05); }
        .loop-card:hover { transform: translateY(-2px); box-shadow: 0 15px 50px -10px rgba(0, 0, 0, 0.06); }
        .dark .loop-card:hover { box-shadow: 0 15px 50px -10px rgba(0, 0, 0, 0.3); }
        .loop-stat { 
            display: flex; flex-direction: column; justify-content: center;
            border-right: 1px solid rgba(0,0,0,0.04);
            padding: 0 1.5rem;
        }
        .dark .loop-stat { border-color: rgba(255,255,255,0.05); }
        .loop-stat:last-child { border-right: none; }
        .loop-stat-val { font-size: 1.875rem; font-weight: 800; color: #1F2937; margin-top: 0.25rem; }
        .dark .loop-stat-val { color: #F9FAFB; }
        .loop-stat-label { font-size: 0.875rem; font-weight: 600; color: #64748B; display: flex; align-items: center; justify-content: space-between; }
        .dark .loop-stat-label { color: #94A3B8; }
        .loop-badge { padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; }
        .loop-badge-green { background: #ECFDF5; color: #059669; }
        .dark .loop-badge-green { background: rgba(5, 150, 105, 0.2); color: #34D399; }
        .loop-badge-red { background: #FEF2F2; color: #DC2626; }
        .dark .loop-badge-red { background: rgba(220, 38, 38, 0.2); color: #F87171; }
        .loop-action-btn {
            background: #F8FAFC; border: 1px solid rgba(0,0,0,0.03); border-radius: 1rem;
            padding: 1rem; display: flex; align-items: center; gap: 0.75rem;
            transition: all 0.2s; cursor: pointer; color: #1F2937; font-weight: 600;
        }
        .dark .loop-action-btn { background: #374151; border-color: rgba(255,255,255,0.05); color: #F9FAFB; }
        .loop-action-btn:hover { background: #F1F5F9; transform: translateY(-2px); }
        .dark .loop-action-btn:hover { background: #4B5563; }
        .loop-icon-box {
            width: 36px; height: 36px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 1.125rem;
        }
        /* Specific dark mode tweaks for the "How can I help you" box */
        .dark .loop-help-box { background: linear-gradient(to bottom right, #1F2937, #111827) !important; border-color: rgba(255,255,255,0.05); }
    </style>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <!-- LEFT COLUMN (Main Stats & Charts) -->
        <div class="xl:col-span-2 space-y-6">
            
            <!-- Top Stats Row -->
            <div class="loop-card flex flex-col sm:flex-row justify-between p-0 py-6">
                <!-- Clients Stat -->
                <div class="loop-stat flex-1">
                    <div class="loop-stat-label">
                        {{ __('Active Customers') }}
                        <span class="loop-badge loop-badge-green">+15%</span>
                    </div>
                    <div class="loop-stat-val">0</div>
                    <div class="text-xs text-gray-500 mt-2">{{ __('Compared to last month') }}</div>
                </div>
                
                <!-- Revenue Stat -->
                <div class="loop-stat flex-1">
                    <div class="loop-stat-label">
                        {{ __('Total Revenue') }}
                        <span class="loop-badge loop-badge-red">-3%</span>
                    </div>
                    <div class="loop-stat-val">৳ 0.00</div>
                    <div class="text-xs text-gray-500 mt-2">{{ __('Compared to last month') }}</div>
                </div>
                
                <!-- Projects/Orders Stat -->
                <div class="loop-stat flex-1">
                    <div class="loop-stat-label">
                        {{ __('Pending Orders') }}
                        <span class="loop-badge loop-badge-green">+8%</span>
                    </div>
                    <div class="loop-stat-val">0</div>
                    <div class="text-xs text-gray-500 mt-2">{{ __('Awaiting fulfillment') }}</div>
                </div>
            </div>

            <!-- Revenue Analytics Chart -->
            <div class="loop-card">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Revenue Analytics') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Earnings over the last 30 days') }}</p>
                    </div>
                    <button class="fi-btn fi-btn-color-gray px-4 py-2 rounded-full text-sm font-semibold">
                        {{ __('Download Report') }}
                    </button>
                </div>
                
                <div class="h-72 w-full flex items-center justify-center relative">
                    <!-- Placeholder Chart (Wait for actual Filament widget or render SVG) -->
                    <svg class="absolute inset-0 w-full h-full" viewBox="0 0 600 200" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="gradientLoop" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#0F766E;stop-opacity:0.2" />
                                <stop offset="100%" style="stop-color:#0F766E;stop-opacity:0" />
                            </linearGradient>
                        </defs>
                        <path d="M0,150 C50,140 100,60 150,80 C200,100 250,40 300,50 C350,60 400,120 450,110 C500,100 550,20 600,30 L600,200 L0,200 Z" fill="url(#gradientLoop)" />
                        <path d="M0,150 C50,140 100,60 150,80 C200,100 250,40 300,50 C350,60 400,120 450,110 C500,100 550,20 600,30" fill="none" stroke="#0F766E" stroke-width="4" stroke-linecap="round" />
                        <!-- Data Points -->
                        <circle cx="150" cy="80" r="4" fill="white" stroke="#0F766E" stroke-width="2"/>
                        <circle cx="300" cy="50" r="4" fill="white" stroke="#0F766E" stroke-width="2"/>
                        <circle cx="450" cy="110" r="4" fill="white" stroke="#0F766E" stroke-width="2"/>
                    </svg>
                </div>
            </div>

            <!-- Generic Table / Orders list -->
            <div class="loop-card">
                 <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Recent Orders') }}</h3>
                    <a href="#" class="text-sm font-semibold text-teal-600 dark:text-teal-400 hover:text-teal-700">{{ __('View all') }}</a>
                </div>
                <div class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
                    {{ __('No recent orders found.') }}
                </div>
            </div>
            
        </div>

        <!-- RIGHT COLUMN (Priority & Actions) -->
        <div class="xl:col-span-1 space-y-6">
            
            <!-- Priority Tasks -->
            <div class="loop-card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Priority tasks') }}</h3>
                    <a href="#" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ __('See all') }}</a>
                </div>

                <div class="space-y-4">
                    <!-- Task 1 -->
                    <div class="flex gap-3">
                        <div class="mt-1">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ __('Setup Company Profile') }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Complete your company details to start invoicing.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- "How can I help you?" Widget (Like LoopAI) -->
            <div class="loop-card loop-help-box bg-gradient-to-br from-gray-50 to-white">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                    Hi, {{ auth()->user()->name }} 👋
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 font-medium">{{ __('What would you like to do today?') }}</p>

                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('filament.app.resources.customers.create') }}" class="loop-action-btn">
                        <div class="loop-icon-box bg-teal-100 text-teal-600">👤</div>
                        <span class="text-sm">{{ __('New Customer') }}</span>
                    </a>
                    <a href="{{ route('filament.app.resources.invoices.create') }}" class="loop-action-btn">
                        <div class="loop-icon-box bg-blue-100 text-blue-600">📄</div>
                        <span class="text-sm">{{ __('Create Invoice') }}</span>
                    </a>
                    <a href="{{ route('filament.app.resources.products.create') }}" class="loop-action-btn">
                        <div class="loop-icon-box bg-orange-100 text-orange-600">📦</div>
                        <span class="text-sm">{{ __('Add Product') }}</span>
                    </a>
                    <a href="#" class="loop-action-btn">
                        <div class="loop-icon-box bg-purple-100 text-purple-600">📊</div>
                        <span class="text-sm">{{ __('View Reports') }}</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-filament-panels::page>
