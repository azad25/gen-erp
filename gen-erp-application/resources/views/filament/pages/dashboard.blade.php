<x-filament-panels::page>
    <style>
        /* Force light mode and remove Filament's default styling */
        .fi-page {
            background: #F8FAFC !important;
        }
        
        /* Custom Dashboard Styles */
        .dashboard-card {
            background: white;
            border-radius: 1.25rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #E2E8F0;
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            box-shadow: 0 8px 24px rgba(15, 118, 110, 0.12);
            transform: translateY(-4px);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
            border-radius: 1.25rem;
            padding: 1.5rem;
            border-left: 4px solid #0F766E;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(15, 118, 110, 0.1) 0%, transparent 70%);
        }
        
        .stat-card:hover {
            border-left-color: #14B8A6;
            box-shadow: 0 8px 24px rgba(15, 118, 110, 0.15);
            transform: translateY(-4px) scale(1.02);
        }
        
        .stat-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: #0F766E;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-change {
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .stat-change.positive {
            color: #16A34A;
        }
        
        .stat-change.negative {
            color: #DC2626;
        }
        
        .chart-card {
            background: white;
            border-radius: 1.25rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #E2E8F0;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1F2937;
        }
        
        .chart-subtitle {
            font-size: 0.875rem;
            color: #64748B;
        }
        
        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }
        
        .activity-item:hover {
            background: rgba(15, 118, 110, 0.03);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .activity-icon.success {
            background: rgba(22, 163, 74, 0.1);
            color: #16A34A;
        }
        
        .activity-icon.warning {
            background: rgba(202, 138, 4, 0.1);
            color: #CA8A04;
        }
        
        .activity-icon.info {
            background: rgba(15, 118, 110, 0.1);
            color: #0F766E;
        }
        
        .quick-action-btn {
            background: linear-gradient(135deg, #0F766E, #14B8A6);
            color: white;
            padding: 0.875rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.25);
            cursor: pointer;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15, 118, 110, 0.35);
        }
    </style>

    <!-- Welcome Section -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">
            {{ __('Welcome back') }}, {{ auth()->user()->name }}! 👋
        </h1>
        <p class="text-gray-600 mt-1">{{ __('Here\'s what\'s happening with your business today') }}</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Revenue -->
        <div class="stat-card">
            <div class="stat-label">{{ __('Total Revenue') }}</div>
            <div class="stat-value">৳ 0.00</div>
            <div class="stat-change positive">
                <span>↑ +0%</span>
                <span class="text-gray-500 ml-2">{{ __('vs last month') }}</span>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="stat-card" style="border-left-color: #CA8A04;">
            <div class="stat-label">{{ __('Pending Orders') }}</div>
            <div class="stat-value" style="color: #CA8A04;">0</div>
            <div class="stat-change" style="color: #64748B;">
                <span>{{ __('Awaiting fulfillment') }}</span>
            </div>
        </div>

        <!-- Total Customers -->
        <div class="stat-card" style="border-left-color: #14B8A6;">
            <div class="stat-label">{{ __('Total Customers') }}</div>
            <div class="stat-value" style="color: #14B8A6;">0</div>
            <div class="stat-change positive">
                <span>↑ +0%</span>
                <span class="text-gray-500 ml-2">{{ __('Active customer base') }}</span>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="stat-card" style="border-left-color: #DC2626;">
            <div class="stat-label">{{ __('Low Stock Items') }}</div>
            <div class="stat-value" style="color: #DC2626;">0</div>
            <div class="stat-change" style="color: #64748B;">
                <span>{{ __('Below minimum level') }}</span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <div class="chart-title">{{ __('Revenue Overview') }}</div>
                    <div class="chart-subtitle">{{ __('Monthly revenue trend') }}</div>
                </div>
            </div>
            <div class="h-64 flex items-center justify-center text-gray-400">
                <svg class="w-full h-full" viewBox="0 0 400 200">
                    <defs>
                        <linearGradient id="gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#0F766E;stop-opacity:0.3" />
                            <stop offset="100%" style="stop-color:#0F766E;stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <!-- Grid lines -->
                    <line x1="0" y1="40" x2="400" y2="40" stroke="#E5E7EB" stroke-width="1"/>
                    <line x1="0" y1="80" x2="400" y2="80" stroke="#E5E7EB" stroke-width="1"/>
                    <line x1="0" y1="120" x2="400" y2="120" stroke="#E5E7EB" stroke-width="1"/>
                    <line x1="0" y1="160" x2="400" y2="160" stroke="#E5E7EB" stroke-width="1"/>
                    <!-- Chart line -->
                    <polyline fill="url(#gradient)" stroke="#0F766E" stroke-width="3" points="0,180 50,160 100,140 150,120 200,100 250,110 300,90 350,70 400,60 400,200 0,200"/>
                    <polyline fill="none" stroke="#0F766E" stroke-width="3" points="0,180 50,160 100,140 150,120 200,100 250,110 300,90 350,70 400,60"/>
                </svg>
            </div>
        </div>

        <!-- Inventory Status -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <div class="chart-title">{{ __('Inventory Status') }}</div>
                    <div class="chart-subtitle">{{ __('Stock levels overview') }}</div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="p-4 bg-gradient-to-br from-teal-50 to-white rounded-xl border border-teal-100">
                    <div class="text-2xl font-bold text-teal-600">0</div>
                    <div class="text-sm text-gray-600 mt-1">{{ __('Total Products') }}</div>
                    <div class="text-xs text-gray-500 mt-2">{{ __('In inventory') }} 📦</div>
                </div>
                <div class="p-4 bg-gradient-to-br from-green-50 to-white rounded-xl border border-green-100">
                    <div class="text-2xl font-bold text-green-600">0</div>
                    <div class="text-sm text-gray-600 mt-1">{{ __('In Stock') }}</div>
                    <div class="text-xs text-gray-500 mt-2">{{ __('Available') }} ✓</div>
                </div>
                <div class="p-4 bg-gradient-to-br from-amber-50 to-white rounded-xl border border-amber-100">
                    <div class="text-2xl font-bold text-amber-600">0</div>
                    <div class="text-sm text-gray-600 mt-1">{{ __('Low Stock') }}</div>
                    <div class="text-xs text-gray-500 mt-2">{{ __('Needs reorder') }} ⚠️</div>
                </div>
                <div class="p-4 bg-gradient-to-br from-red-50 to-white rounded-xl border border-red-100">
                    <div class="text-2xl font-bold text-red-600">0</div>
                    <div class="text-sm text-gray-600 mt-1">{{ __('Out of Stock') }}</div>
                    <div class="text-xs text-gray-500 mt-2">{{ __('Urgent') }} 🚨</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Activity -->
        <div class="lg:col-span-2 dashboard-card">
            <div class="chart-header">
                <div class="chart-title">{{ __('Recent Activity') }}</div>
            </div>
            <div class="space-y-2">
                <div class="activity-item">
                    <div class="activity-icon info">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold text-gray-900">{{ __('System Ready') }}</div>
                        <div class="text-sm text-gray-600">{{ __('Your ERP system is configured and ready to use') }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ now()->format('M d, Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <div class="chart-title mb-4">{{ __('Quick Actions') }}</div>
            <div class="space-y-3">
                <a href="{{ route('filament.app.resources.customers.create') }}" class="quick-action-btn block">
                    <span class="mr-2">👤</span> {{ __('Add Customer') }}
                </a>
                <a href="{{ route('filament.app.resources.products.create') }}" class="quick-action-btn block" style="background: linear-gradient(135deg, #14B8A6, #10B981);">
                    <span class="mr-2">📦</span> {{ __('Add Product') }}
                </a>
                <a href="{{ route('filament.app.resources.invoices.create') }}" class="quick-action-btn block" style="background: linear-gradient(135deg, #CA8A04, #F59E0B);">
                    <span class="mr-2">📄</span> {{ __('Create Invoice') }}
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>
