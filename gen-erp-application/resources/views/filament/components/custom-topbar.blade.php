@php
    $company = \App\Services\CompanyContext::active();
    $branch = \App\Services\BranchContext::active();
    $user = auth()->user();
@endphp

<div class="generp-custom-topbar fi-topbar" 
     x-data="themeManager()" 
     x-init="initTheme()">
    <div class="topbar-left">
        <div class="page-title-area">
            <h1 class="page-title">{{ $heading ?? 'Dashboard' }}</h1>
            <div class="breadcrumb-nav">
                <span class="breadcrumb-item">{{ $company?->name ?? 'GenERP BD' }}</span>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current">Overview</span>
            </div>
        </div>
    </div>
    
    <div class="topbar-right">
        <!-- Global Search -->
        <div class="search-container">
            <button type="button" class="search-bar-custom" @click="searchOpen = !searchOpen">
                <x-filament::icon icon="heroicon-o-magnifying-glass" class="search-icon" />
                <span class="search-placeholder">Search anything...</span>
                <span class="search-kbd">⌘K</span>
            </button>
            
            <!-- Search Dropdown -->
            <div x-show="searchOpen" 
                 x-transition
                 @click.away="searchOpen = false"
                 class="search-dropdown">
                <div class="search-input-wrapper">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="search-icon" />
                    <input type="text" 
                           x-model="searchQuery"
                           x-ref="searchInput"
                           placeholder="Search invoices, customers, products..."
                           class="search-input"
                           autofocus>
                </div>
                <div class="search-results">
                    <template x-if="searchQuery.length === 0">
                        <div class="search-empty">
                            <p class="text-muted">Start typing to search...</p>
                        </div>
                    </template>
                    <template x-if="searchQuery.length > 0">
                        <div class="search-empty">
                            <p class="text-muted">No results found for "<span x-text="searchQuery"></span>"</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Language Switcher -->
        <div class="dropdown-container">
            <button type="button" class="topbar-icon-btn" @click="langMenuOpen = !langMenuOpen" title="Language">
                <x-filament::icon icon="heroicon-o-language" class="topbar-icon" />
            </button>
            
            <div x-show="langMenuOpen" 
                 x-transition
                 @click.away="langMenuOpen = false"
                 class="topbar-dropdown">
                <a href="{{ route('locale.set', 'en') }}" class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}">
                    <span class="dropdown-icon">🇬🇧</span>
                    <span>English</span>
                    @if(app()->getLocale() === 'en')
                        <x-filament::icon icon="heroicon-m-check" class="check-icon" />
                    @endif
                </a>
                <a href="{{ route('locale.set', 'bn') }}" class="dropdown-item {{ app()->getLocale() === 'bn' ? 'active' : '' }}">
                    <span class="dropdown-icon">🇧🇩</span>
                    <span>বাংলা</span>
                    @if(app()->getLocale() === 'bn')
                        <x-filament::icon icon="heroicon-m-check" class="check-icon" />
                    @endif
                </a>
            </div>
        </div>

        <!-- Theme Switcher -->
        <button type="button" 
                class="topbar-icon-btn" 
                @click="toggleTheme"
                :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
            <svg class="topbar-icon" x-show="!isDark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <svg class="topbar-icon" x-show="isDark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
        </button>
        
        <!-- Notifications -->
        <div class="dropdown-container">
            <button type="button" 
                    class="topbar-icon-btn" 
                    @click="notifMenuOpen = !notifMenuOpen" 
                    title="Notifications">
                <x-filament::icon icon="heroicon-o-bell" class="topbar-icon" />
                @if($user->unreadNotifications()->count() > 0)
                    <span class="notification-dot"></span>
                @endif
            </button>
            
            <div x-show="notifMenuOpen" 
                 x-transition
                 @click.away="notifMenuOpen = false"
                 class="topbar-dropdown notifications-dropdown">
                <div class="dropdown-header">
                    <span class="dropdown-title">Notifications</span>
                    @if($user->unreadNotifications()->count() > 0)
                        <span class="notification-badge">{{ $user->unreadNotifications()->count() }}</span>
                    @endif
                </div>
                @forelse($user->unreadNotifications()->take(5)->get() as $notification)
                    <a href="#" class="dropdown-item notification-item">
                        <div class="notification-content">
                            <p class="notification-title">{{ $notification->data['subject'] ?? $notification->data['title'] ?? 'Notification' }}</p>
                            <p class="notification-time">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </a>
                @empty
                    <div class="dropdown-empty">
                        <p class="text-muted">No new notifications</p>
                    </div>
                @endforelse
                @if($user->unreadNotifications()->count() > 0)
                    <a href="{{ route('filament.app.pages.dashboard') }}" class="dropdown-footer">
                        View all notifications
                    </a>
                @endif
            </div>
        </div>
        
        <!-- User Menu -->
        <div class="dropdown-container">
            <button type="button" class="topbar-icon-btn" @click="userMenuOpen = !userMenuOpen" title="User Menu">
                <x-filament::icon icon="heroicon-o-user-circle" class="topbar-icon" />
            </button>
            
            <div x-show="userMenuOpen" 
                 x-transition
                 @click.away="userMenuOpen = false"
                 class="topbar-dropdown user-dropdown">
                <div class="dropdown-header user-info">
                    <div class="user-avatar-large">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                    <div class="user-details">
                        <p class="user-name">{{ $user->name }}</p>
                        <p class="user-email">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="{{ route('filament.app.auth.profile') }}" class="dropdown-item">
                    <x-filament::icon icon="heroicon-o-user" class="dropdown-item-icon" />
                    <span>Profile</span>
                </a>
                <a href="{{ route('filament.app.pages.company-settings') }}" class="dropdown-item">
                    <x-filament::icon icon="heroicon-o-building-office" class="dropdown-item-icon" />
                    <span>Company Settings</span>
                </a>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger">
                        <x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="dropdown-item-icon" />
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.generp-custom-topbar {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    width: 100% !important;
    height: 56px !important;
    padding: 0 24px !important;
    background: #ffffff !important;
    border-bottom: 1px solid #e8ecf0 !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 40 !important;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.page-title-area {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.page-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.3px;
    line-height: 1.2;
    margin: 0;
}

.breadcrumb-nav {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
}

.breadcrumb-item {
    color: #64748b;
}

.breadcrumb-sep {
    color: #94a3b8;
}

.breadcrumb-current {
    color: #94a3b8;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
}

.search-bar-custom {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f5f7fa;
    border: 1px solid #e8ecf0;
    border-radius: 8px;
    padding: 0 12px;
    height: 34px;
    width: 220px;
    cursor: pointer;
    transition: all 0.12s;
}

.search-bar-custom:hover {
    background: #eef1f5;
    border-color: #d0d7de;
}

.search-icon {
    width: 13px;
    height: 13px;
    color: #64748b;
}

.search-placeholder {
    flex: 1;
    font-size: 12px;
    color: #64748b;
}

.search-kbd {
    font-family: 'JetBrains Mono', monospace;
    font-size: 10px;
    color: #94a3b8;
    background: white;
    border: 1px solid #e8ecf0;
    border-radius: 4px;
    padding: 1px 5px;
}

.topbar-icon-btn {
    position: relative;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid #e8ecf0;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    transition: all 0.12s;
}

.topbar-icon-btn:hover {
    background: #f5f7fa;
    border-color: #d0d7de;
}

.topbar-icon {
    width: 15px;
    height: 15px;
}

.notification-dot {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 6px;
    height: 6px;
    background: #ef4444;
    border-radius: 50%;
    border: 1.5px solid white;
}
</style>


<style>
.generp-custom-topbar {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    width: 100% !important;
    height: 56px !important;
    padding: 0 24px !important;
    background: #ffffff !important;
    border-bottom: 1px solid #e8ecf0 !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 40 !important;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.page-title-area {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.page-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.3px;
    line-height: 1.2;
    margin: 0;
}

.breadcrumb-nav {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
}

.breadcrumb-item {
    color: #64748b;
}

.breadcrumb-sep {
    color: #94a3b8;
}

.breadcrumb-current {
    color: #94a3b8;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
}

/* Search Container */
.search-container {
    position: relative;
}

.search-bar-custom {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f5f7fa;
    border: 1px solid #e8ecf0;
    border-radius: 8px;
    padding: 0 12px;
    height: 34px;
    width: 220px;
    cursor: pointer;
    transition: all 0.12s;
}

.search-bar-custom:hover {
    background: #eef1f5;
    border-color: #d0d7de;
}

.search-icon {
    width: 13px;
    height: 13px;
    color: #64748b;
    flex-shrink: 0;
}

.search-placeholder {
    flex: 1;
    font-size: 12px;
    color: #64748b;
}

.search-kbd {
    font-family: 'JetBrains Mono', monospace;
    font-size: 10px;
    color: #94a3b8;
    background: white;
    border: 1px solid #e8ecf0;
    border-radius: 4px;
    padding: 1px 5px;
}

/* Search Dropdown */
.search-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 400px;
    background: white;
    border: 1px solid #e8ecf0;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    z-index: 50;
    overflow: hidden;
}

.search-input-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid #e8ecf0;
}

.search-input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 13px;
    color: #0f172a;
}

.search-input::placeholder {
    color: #94a3b8;
}

.search-results {
    max-height: 300px;
    overflow-y: auto;
}

.search-empty {
    padding: 40px 20px;
    text-align: center;
}

.text-muted {
    font-size: 12px;
    color: #94a3b8;
}

/* Dropdown Container */
.dropdown-container {
    position: relative;
}

.topbar-icon-btn {
    position: relative;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid #e8ecf0;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    transition: all 0.12s;
}

.topbar-icon-btn:hover {
    background: #f5f7fa;
    border-color: #d0d7de;
}

.topbar-icon {
    width: 15px;
    height: 15px;
}

.notification-dot {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 6px;
    height: 6px;
    background: #ef4444;
    border-radius: 50%;
    border: 1.5px solid white;
}

/* Dropdown Menu */
.topbar-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 200px;
    background: white;
    border: 1px solid #e8ecf0;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    z-index: 50;
    overflow: hidden;
}

.dropdown-header {
    padding: 12px 16px;
    border-bottom: 1px solid #e8ecf0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.dropdown-title {
    font-size: 12px;
    font-weight: 600;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.notification-badge {
    background: #ef4444;
    color: white;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    font-size: 13px;
    color: #0f172a;
    text-decoration: none;
    transition: background 0.12s;
    cursor: pointer;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
}

.dropdown-item:hover {
    background: #f5f7fa;
}

.dropdown-item.active {
    background: rgba(34, 197, 94, 0.08);
    color: #16a34a;
}

.dropdown-item.text-danger {
    color: #ef4444;
}

.dropdown-item.text-danger:hover {
    background: rgba(239, 68, 68, 0.08);
}

.dropdown-icon {
    font-size: 16px;
}

.dropdown-item-icon {
    width: 16px;
    height: 16px;
    color: currentColor;
}

.check-icon {
    width: 14px;
    height: 14px;
    margin-left: auto;
    color: #16a34a;
}

.dropdown-divider {
    height: 1px;
    background: #e8ecf0;
    margin: 4px 0;
}

.dropdown-empty {
    padding: 20px;
    text-align: center;
}

.dropdown-footer {
    display: block;
    padding: 10px 16px;
    text-align: center;
    font-size: 12px;
    font-weight: 600;
    color: #2563eb;
    text-decoration: none;
    border-top: 1px solid #e8ecf0;
    transition: background 0.12s;
}

.dropdown-footer:hover {
    background: #f5f7fa;
}

/* User Dropdown */
.user-dropdown {
    min-width: 240px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
}

.user-avatar-large {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    color: white;
    flex-shrink: 0;
}

.user-details {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    margin: 0 0 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-email {
    font-size: 11px;
    color: #94a3b8;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Notifications Dropdown */
.notifications-dropdown {
    min-width: 320px;
    max-width: 400px;
}

.notification-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
}

.notification-content {
    width: 100%;
}

.notification-title {
    font-size: 13px;
    color: #0f172a;
    margin: 0 0 4px 0;
    line-height: 1.4;
}

.notification-time {
    font-size: 11px;
    color: #94a3b8;
    margin: 0;
}

/* Dark mode support */
.dark .generp-custom-topbar {
    background: #161b22 !important;
    border-bottom-color: #30363d !important;
}

.dark .page-title {
    color: #e6edf3;
}

.dark .breadcrumb-item,
.dark .breadcrumb-current {
    color: #8b949e;
}

.dark .search-bar-custom,
.dark .topbar-icon-btn {
    background: #21262d;
    border-color: #30363d;
    color: #8b949e;
}

.dark .search-bar-custom:hover,
.dark .topbar-icon-btn:hover {
    background: #30363d;
}

.dark .topbar-dropdown,
.dark .search-dropdown {
    background: #161b22;
    border-color: #30363d;
}

.dark .dropdown-item {
    color: #e6edf3;
}

.dark .dropdown-item:hover {
    background: #21262d;
}

.dark .dropdown-divider {
    background: #30363d;
}

.dark .search-input {
    background: transparent;
    color: #e6edf3;
}
</style>


<script>
    // Alpine.js Theme Manager Component
    function themeManager() {
        return {
            searchOpen: false,
            userMenuOpen: false,
            langMenuOpen: false,
            notifMenuOpen: false,
            searchQuery: '',
            isDark: false,
            
            initTheme() {
                // Get theme from localStorage, default to light
                const savedTheme = localStorage.getItem('theme');
                this.isDark = savedTheme === 'dark';
                
                // Apply theme immediately
                this.applyTheme();
                
                // Setup keyboard shortcuts
                document.addEventListener('keydown', (e) => {
                    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                        e.preventDefault();
                        this.searchOpen = !this.searchOpen;
                        if (this.searchOpen) {
                            this.$nextTick(() => {
                                this.$refs.searchInput?.focus();
                            });
                        }
                    }
                });
            },
            
            applyTheme() {
                const html = document.documentElement;
                
                if (this.isDark) {
                    html.classList.add('dark');
                } else {
                    html.classList.remove('dark');
                }
                
                // Save to localStorage
                localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
            },
            
            toggleTheme() {
                this.isDark = !this.isDark;
                this.applyTheme();
            }
        }
    }
    
    // Ensure light mode on initial load (before Alpine initializes)
    (function() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
            // Ensure light mode is default
            if (!savedTheme) {
                localStorage.setItem('theme', 'light');
            }
        }
    })();
</script>

<style>
/* Additional dark mode styles for dashboard content */
.dark .fi-main {
    background-color: #0d1117 !important;
}

.dark .fi-section-content-ctn {
    background-color: #161b22 !important;
    border-color: #30363d !important;
}

.dark .fi-section-header {
    background-color: #161b22 !important;
}

.dark .fi-wi-stats-overview-stat {
    background-color: #161b22 !important;
    border-color: #30363d !important;
}

.dark .fi-wi-stats-overview-stat-label,
.dark .fi-wi-stats-overview-stat-value {
    color: #e6edf3 !important;
}

.dark .fi-ta {
    background-color: #161b22 !important;
    border-color: #30363d !important;
}

.dark .fi-ta-header {
    background-color: #161b22 !important;
    border-color: #30363d !important;
}

.dark .fi-ta-row {
    background-color: #161b22 !important;
    border-color: #30363d !important;
}

.dark .fi-ta-row:hover {
    background-color: #21262d !important;
}

.dark .fi-ta-text {
    color: #e6edf3 !important;
}

.dark .fi-section-header-heading {
    color: #e6edf3 !important;
}

.dark .fi-section-description {
    color: #8b949e !important;
}
</style>
