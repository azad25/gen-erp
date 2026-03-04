# Frontend Architecture

## Table of Contents
- [Overview](#overview)
- [Technology Stack](#technology-stack)
- [Application Structure](#application-structure)
- [Routing](#routing)
- [State Management](#state-management)
- [Component Library](#component-library)
- [API Integration](#api-integration)
- [Composables](#composables)
- [Theme System](#theme-system)
- [Internationalization](#internationalization)
- [Build & Development](#build--development)

---

## Overview

The Gen-ERP frontend is a modern Single Page Application (SPA) built with Vue.js 3.5 and Inertia.js 2.3, providing a seamless server-driven client-side experience. The application uses the TailAdmin theme as its design foundation with extensive customizations for ERP-specific workflows.

### Key Features
- Server-side rendering with Inertia.js (no separate API calls for page navigation)
- Reactive state management with Pinia 3.0
- Component-based architecture with Vue 3 Composition API
- Real-time updates via Laravel Reverb and Laravel Echo
- Dark mode support
- Responsive design (mobile-first approach)
- Bangla language support with custom fonts
- Rich text editing with TipTap
- Interactive charts with ApexCharts

---

## Technology Stack

```json
{
  "vue": "3.5.29",
  "@inertiajs/vue3": "2.3.17",
  "pinia": "3.0.4",
  "tailwindcss": "4.2.1",
  "vite": "7.0.7",
  "axios": "1.13.6",
  "vue3-apexcharts": "1.10.0",
  "@tiptap/vue-3": "2.1.13",
  "dayjs": "1.11.19",
  "vuedraggable": "4.1.0"
}
```


### Dependencies Breakdown

**Core Framework:**
- `vue@3.5.29` - Progressive JavaScript framework
- `@inertiajs/vue3@2.3.17` - Server-driven SPA framework
- `vite@7.0.7` - Next-generation frontend build tool
- `laravel-vite-plugin@2.0.0` - Laravel integration for Vite

**State & Data:**
- `pinia@3.0.4` - Vue store (Vuex successor)
- `axios@1.13.6` - HTTP client for API calls
- `@vueuse/core@14.2.1` - Collection of Vue composition utilities

**UI & Styling:**
- `tailwindcss@4.2.1` - Utility-first CSS framework
- `@tailwindcss/vite@4.0.0` - Vite plugin for Tailwind
- `vue3-apexcharts@1.10.0` - Interactive charts
- `apexcharts@5.7.0` - Charting library

**Rich Content:**
- `@tiptap/vue-3@2.1.13` - Headless rich text editor
- `@tiptap/starter-kit@2.1.13` - Essential TipTap extensions
- `@tiptap/extension-image@2.1.13` - Image support
- `@tiptap/extension-link@2.1.13` - Link support
- `@tiptap/extension-underline@2.1.13` - Underline formatting
- `@tiptap/extension-text-align@2.1.13` - Text alignment

**Utilities:**
- `dayjs@1.11.19` - Date/time manipulation
- `lodash@4.17.21` - Utility functions
- `vuedraggable@4.1.0` - Drag-and-drop functionality

**Real-time:**
- `laravel-echo@2.3.0` - WebSocket client
- `pusher-js@8.4.0` - Pusher protocol implementation
- `@laravel/echo-vue@2.3.0` - Vue integration for Echo

---

## Application Structure

```
resources/js/
├── app.js                    # Application entry point
├── bootstrap.js              # Axios & CSRF configuration
├── Components/               # Reusable Vue components
│   ├── Bangla/              # Bangla localization components
│   ├── Charts/              # Chart components (ApexCharts)
│   ├── CMS/                 # CMS-specific components
│   ├── common/              # Common utilities
│   ├── ecommerce/           # E-commerce widgets
│   ├── Forms/               # Form input components
│   ├── Home/                # Landing page components
│   ├── HR/                  # HR module components
│   ├── Layout/              # Layout components
│   ├── Notifications/       # Notification components
│   ├── profile/             # User profile components
│   ├── tables/              # Table components
│   └── UI/                  # Core UI components
├── Composables/             # Reusable composition functions
│   ├── useApi.js           # API request wrapper
│   ├── usePagination.js    # Pagination logic
│   ├── useSearch.js        # Search with debouncing
│   └── useSidebar.ts       # Sidebar state management
├── Pages/                   # Inertia page components (42 modules)
│   ├── Auth/               # Authentication pages
│   ├── Dashboard/          # Main dashboard
│   ├── Sales/              # Sales module pages
│   ├── Purchase/           # Purchase module pages
│   ├── Inventory/          # Inventory module pages
│   ├── Accounting/         # Accounting module pages
│   ├── HR/                 # HR module pages
│   ├── Projects/           # Project management pages
│   ├── Tasks/              # Task management pages
│   ├── CRM/                # CRM module pages
│   ├── CMS/                # CMS module pages
│   ├── POS/                # Point of Sale pages
│   ├── Reports/            # Reporting pages
│   ├── Settings/           # Settings pages
│   └── Profile/            # User profile pages
├── Services/                # API service layer
│   ├── api.js              # Axios instance & business APIs
│   └── auth.js             # Authentication service
├── Stores/                  # Pinia stores
│   └── pageBuilderStore.js # CMS page builder state
└── icons/                   # SVG icon components

resources/css/
└── app.css                  # Global styles & Tailwind imports
```


### Application Entry Point

**`resources/js/app.js`** - Main application bootstrap:

```javascript
import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import VueApexCharts from 'vue3-apexcharts'
import ThemeProvider from './Components/Layout/ThemeProvider.vue'

createInertiaApp({
  title: title => `${title} — GenERP BD`,
  resolve: name => resolvePageComponent(
    `./Pages/${name}.vue`, 
    import.meta.glob('./Pages/**/*.vue')
  ),
  setup({ el, App, props, plugin }) {
    // Sync company ID from server to sessionStorage
    const companyId = props.initialPage.props.auth?.company?.id
    if (companyId) {
      sessionStorage.setItem('active_company_id', companyId)
    }
    
    const app = createApp({
      render: () => h(ThemeProvider, {}, () => h(App, props))
    })
    
    app.use(plugin)
       .use(createPinia())
       .use(VueApexCharts)
       .mount(el)
  },
  progress: { color: '#14B8A6', showSpinner: false }
})
```

**Key Features:**
- Dynamic page component resolution
- Company context synchronization
- Theme provider wrapper for dark mode
- Pinia store integration
- ApexCharts global registration
- Progress bar configuration

**`resources/js/bootstrap.js`** - Axios configuration:

```javascript
import axios from 'axios'
window.axios = axios

// Sanctum SPA authentication
window.axios.defaults.withCredentials = true
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.headers.common['Accept'] = 'application/json'

// CSRF token from meta tag
const token = document.head.querySelector('meta[name="csrf-token"]')
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
}

// Global error handling
window.axios.interceptors.response.use(
    response => response,
    async error => {
        if (error.response?.status === 401) {
            window.location.href = '/login'
        }
        if (error.response?.status === 419) {
            // CSRF mismatch - refresh token and retry
            await window.axios.get('/sanctum/csrf-cookie')
            return window.axios.request(error.config)
        }
        return Promise.reject(error)
    }
)
```

---

## Routing

Gen-ERP uses **Inertia.js** for routing, which means routes are defined server-side in `routes/web.php` and rendered as Vue components.

### Route Structure

```php
// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Sales Module
Route::get('/sales/orders', [SalesOrderController::class, 'index'])->name('sales.orders');
Route::get('/sales/invoices', [InvoiceController::class, 'index'])->name('sales.invoices');
Route::get('/sales/customers', fn() => Inertia::render('Sales/Customers'))->name('sales.customers');

// CRM Module
Route::prefix('crm')->name('crm.')->group(function() {
    Route::get('/dashboard', fn() => Inertia::render('CRM/Dashboard/Index'))->name('dashboard');
    Route::get('/leads', fn() => Inertia::render('CRM/Leads/Index'))->name('leads.index');
    Route::get('/leads/create', fn() => Inertia::render('CRM/Leads/Create'))->name('leads.create');
});

// CMS Module
Route::prefix('cms')->name('cms.')->group(function() {
    Route::get('/sites', fn() => Inertia::render('CMS/Sites/Index'))->name('sites.index');
    Route::get('/sites/{site}/pages/{page}/builder', 
        fn() => Inertia::render('CMS/PageBuilder/Index')
    )->name('sites.pages.builder');
});
```


### Page Modules (42 Total)

| Module | Pages | Description |
|--------|-------|-------------|
| **Auth** | Signin, Signup, TwoFactorChallenge, CompanySetup | Authentication flows |
| **Dashboard** | Index | Main dashboard with metrics |
| **Sales** | Orders, Invoices, Customers, CreditNotes, Returns | Sales management |
| **Purchase** | Orders, Receipts, Suppliers, Returns | Procurement |
| **Inventory** | Products, Stock, Warehouses, Transfers, Adjustments | Stock management |
| **Accounting** | ChartOfAccounts, JournalEntries, TrialBalance, ProfitLoss, BalanceSheet | Financial accounting |
| **HR** | Employees, Attendance, Leave, Payroll, Tasks, Timesheet, Capacity, Skills, Availability, Performance | Human resources |
| **Projects** | Index, Dashboard, Create, Show, Edit, Board, Reports | Project management |
| **Tasks** | Index, Create, Show, Edit | Task tracking |
| **CRM** | Dashboard, Leads, Opportunities, Pipelines, Activities, Contacts | Customer relationship |
| **CMS** | Sites, Pages, PageBuilder, Blog, Menus, Contacts, Reviews, Wishlist, SEO | Content management |
| **POS** | Session | Point of sale |
| **Reports** | Index | Business reports |
| **Settings** | Company, Users, Roles, Workflows, Integrations | System configuration |
| **Profile** | Index | User profile |
| **Home** | Index | Public landing page |

### Navigation with Inertia

```vue
<script setup>
import { Link, router } from '@inertiajs/vue3'

// Declarative navigation
<Link href="/sales/orders" class="nav-link">Sales Orders</Link>

// Programmatic navigation
const goToInvoices = () => {
  router.visit('/sales/invoices')
}

// With data (POST request)
const createOrder = (formData) => {
  router.post('/sales/orders', formData, {
    onSuccess: () => console.log('Order created'),
    onError: (errors) => console.error(errors)
  })
}
</script>
```

---

## State Management

Gen-ERP uses **Pinia 3.0** for centralized state management.

### Page Builder Store

**`resources/js/Stores/pageBuilderStore.js`** - CMS page builder state:

```javascript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const usePageBuilderStore = defineStore('pageBuilder', () => {
  // State
  const currentPage = ref(null)
  const sections = ref([])
  const selectedSection = ref(null)
  const selectedSectionIndex = ref(null)
  const isDirty = ref(false)
  const isLoading = ref(false)
  const currentDevice = ref('desktop')
  
  // Getters
  const hasUnsavedChanges = computed(() => isDirty.value)
  const sectionCount = computed(() => sections.value.length)
  
  // Actions
  const initializePage = (page, pageSections) => {
    currentPage.value = page
    sections.value = [...pageSections]
    selectedSection.value = null
    isDirty.value = false
  }
  
  const addSection = (sectionType, index = null) => {
    const newSection = {
      id: Date.now(),
      type: sectionType.type,
      content: sectionType.defaultContent || {},
      sort_order: index !== null ? index : sections.value.length
    }
    
    if (index !== null) {
      sections.value.splice(index, 0, newSection)
      updateSortOrders()
    } else {
      sections.value.push(newSection)
    }
    
    markDirty()
    return newSection
  }
  
  const removeSection = (index) => {
    sections.value.splice(index, 1)
    updateSortOrders()
    markDirty()
  }
  
  const moveSection = (fromIndex, toIndex) => {
    const section = sections.value.splice(fromIndex, 1)[0]
    sections.value.splice(toIndex, 0, section)
    updateSortOrders()
    markDirty()
  }
  
  return {
    // State
    currentPage, sections, selectedSection, isDirty, isLoading, currentDevice,
    // Getters
    hasUnsavedChanges, sectionCount,
    // Actions
    initializePage, addSection, removeSection, moveSection
  }
})
```

### Usage in Components

```vue
<script setup>
import { usePageBuilderStore } from '@/Stores/pageBuilderStore'

const store = usePageBuilderStore()

// Access state
console.log(store.sections)
console.log(store.hasUnsavedChanges)

// Call actions
store.addSection({ type: 'hero', defaultContent: {} })
store.moveSection(0, 2)
</script>
```


---

## Component Library

Gen-ERP has a comprehensive component library organized by domain.

### Layout Components

**`Components/Layout/AdminLayout.vue`** - Main application layout:

```vue
<template>
  <div class="min-h-screen xl:flex">
    <app-sidebar />
    <Backdrop />
    <div
      class="flex-1 transition-all duration-300"
      :class="[isExpanded || isHovered ? 'lg:ml-[290px]' : 'lg:ml-[90px]']"
    >
      <app-header />
      <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppSidebar from './AppSidebar.vue'
import AppHeader from './AppHeader.vue'
import { useSidebar } from '@/composables/useSidebar'

const { isExpanded, isHovered } = useSidebar()
</script>
```

**Features:**
- Collapsible sidebar (90px collapsed, 290px expanded)
- Hover-to-expand on desktop
- Mobile-responsive with backdrop overlay
- Smooth transitions

**`Components/Layout/AppSidebar.vue`** - Navigation sidebar:

```vue
<template>
  <aside :class="[
    'fixed flex flex-col top-0 left-0 bg-white dark:bg-gray-900',
    'h-screen transition-all duration-300 z-99999 border-r',
    {
      'lg:w-[290px]': isExpanded || isHovered,
      'lg:w-[90px]': !isExpanded && !isHovered,
      'translate-x-0 w-[290px]': isMobileOpen,
      '-translate-x-full': !isMobileOpen
    }
  ]">
    <!-- Logo -->
    <div class="py-8 flex">
      <Link href="/" class="flex items-center gap-3">
        <HomeLogo class="w-10 h-10" />
        <span v-if="isExpanded || isHovered">GenERP BD</span>
      </Link>
    </div>
    
    <!-- Company Switcher -->
    <div v-if="isExpanded || isHovered" class="px-2 mb-6">
      <CompanySwitcher />
    </div>
    
    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto">
      <div v-for="menuGroup in menuGroups" :key="menuGroup.title">
        <h2 class="text-xs uppercase text-gray-400 mb-3">
          {{ menuGroup.title }}
        </h2>
        <ul class="flex flex-col gap-1">
          <li v-for="item in menuGroup.items" :key="item.name">
            <Link :href="item.path" :class="[
              'flex items-center gap-3 px-4 py-2.5 rounded-lg',
              isActive(item.path) 
                ? 'bg-primary/10 text-primary' 
                : 'text-gray-600 hover:bg-gray-100'
            ]">
              <component :is="item.icon" class="w-5 h-5" />
              <span v-if="isExpanded || isHovered">{{ item.name }}</span>
            </Link>
          </li>
        </ul>
      </div>
    </nav>
  </aside>
</template>
```

**Menu Groups:**
- Main (Dashboard)
- Sales (Orders, Invoices, Customers, Credit Notes, Returns)
- Purchase (Orders, Receipts, Suppliers, Returns)
- Inventory (Products, Stock, Warehouses, Transfers, Adjustments)
- Accounting (Chart of Accounts, Journal Entries, Reports)
- HR & Payroll (Employees, Attendance, Leave, Payroll, Tasks, Timesheet, Capacity, Skills, Performance)
- Project Management (Dashboard, Projects, Tasks, Reports)
- CRM (Leads, Opportunities, Pipelines, Activities)
- CMS (Sites, Pages, Blog, Menus, SEO)
- POS (Session)
- Settings (Company, Users, Roles, Workflows, Integrations)

**`Components/Layout/AppHeader.vue`** - Top navigation bar:

```vue
<template>
  <header class="sticky top-0 flex w-full bg-white border-b z-99999">
    <div class="flex items-center justify-between grow px-6">
      <!-- Left: Toggle & Search -->
      <div class="flex items-center gap-4">
        <button @click="handleToggle" class="w-10 h-10 rounded-lg">
          <MenuIcon />
        </button>
        <SearchBar />
      </div>
      
      <!-- Right: Theme, Notifications, User -->
      <div class="flex items-center gap-3">
        <ThemeToggler />
        <NotificationMenu />
        <UserMenu />
      </div>
    </div>
  </header>
</template>
```


### UI Components

**`Components/UI/Button.vue`** - Reusable button component:

```vue
<template>
  <button
    :class="[
      'inline-flex items-center justify-center font-medium gap-2 rounded-lg transition',
      sizeClasses[size],
      variantClasses[variant],
      { 'cursor-not-allowed opacity-50': disabled }
    ]"
    @click="onClick"
    :disabled="disabled"
  >
    <span v-if="startIcon" class="flex items-center">
      <component :is="startIcon" />
    </span>
    <slot></slot>
    <span v-if="endIcon" class="flex items-center">
      <component :is="endIcon" />
    </span>
  </button>
</template>

<script setup lang="ts">
interface ButtonProps {
  size?: 'sm' | 'md'
  variant?: 'primary' | 'outline'
  startIcon?: object
  endIcon?: object
  disabled?: boolean
}

const sizeClasses = {
  sm: 'px-4 py-3 text-sm',
  md: 'px-5 py-3.5 text-sm'
}

const variantClasses = {
  primary: 'bg-brand-500 text-white hover:bg-brand-600',
  outline: 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50'
}
</script>
```

**Usage:**
```vue
<Button variant="primary" size="md" :startIcon="PlusIcon">
  Create Order
</Button>
```

**`Components/UI/Modal.vue`** - Modal dialog:

```vue
<template>
  <div class="fixed inset-0 flex items-center justify-center z-99999">
    <div
      v-if="fullScreenBackdrop"
      class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]"
      @click="$emit('close')"
    ></div>
    <slot name="body"></slot>
  </div>
</template>

<script setup lang="ts">
interface ModalProps {
  fullScreenBackdrop?: boolean
}
defineProps<ModalProps>()
defineEmits(['close'])
</script>
```

**`Components/UI/FlashMessage.vue`** - Toast notifications:

```vue
<template>
  <Transition>
    <div v-if="message" class="mb-4">
      <div :class="[
        'flex items-center gap-3 px-4 py-3 rounded-lg border',
        type === 'success' 
          ? 'bg-success/10 border-success/30 text-success' 
          : 'bg-danger/10 border-danger/30 text-danger'
      ]">
        <span>{{ type === 'success' ? '✓' : '✕' }}</span>
        <p>{{ message }}</p>
        <button @click="close" class="ml-auto">✕</button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const message = computed(() => {
  if (page.props.flash?.success) return page.props.flash.success
  if (page.props.flash?.error) return page.props.flash.error
  return null
})

onMounted(() => {
  if (message.value) setTimeout(close, 5000)
})
</script>
```

**Other UI Components:**
- `Alert.vue` - Alert messages
- `Avatar.vue` - User avatars
- `Badge.vue` - Status badges
- `Card.vue` - Content cards
- `DataTable.vue` - Data tables with sorting/filtering
- `Icon.vue` - Icon wrapper
- `ImageUpload.vue` - Image upload widget
- `RichTextEditor.vue` - TipTap editor wrapper
- `StatCard.vue` - Dashboard stat cards
- `ColorPicker.vue` - Color selection
- `YouTubeEmbed.vue` - YouTube video embed

### Form Components

**`Components/Forms/TextInput.vue`** - Text input field:

```vue
<template>
  <div class="relative">
    <span v-if="prefix" class="absolute left-3 top-1/2 -translate-y-1/2">
      {{ prefix }}
    </span>
    <input
      v-bind="$attrs"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="w-full rounded-lg border px-3 h-9 outline-none transition-all"
      :class="[
        error ? 'border-danger' : 'focus:border-primary focus:ring-2 focus:ring-primary/10',
        prefix ? 'pl-7' : ''
      ]"
    />
  </div>
</template>

<script setup>
defineProps({ 
  modelValue: [String, Number], 
  prefix: String, 
  error: String 
})
defineEmits(['update:modelValue'])
</script>
```

**Other Form Components:**
- `CheckboxInput.vue` - Checkbox
- `DateInput.vue` - Date picker
- `NumberInput.vue` - Number input
- `SelectInput.vue` - Dropdown select
- `TextareaInput.vue` - Multi-line text
- `FormGroup.vue` - Form field wrapper with label/error
- `FormWrapper.vue` - Form container


### Chart Components

**`Components/Charts/BarChart.vue`** - Bar chart wrapper:

```vue
<template>
  <apexchart
    type="bar"
    :options="chartOptions"
    :series="series"
    :height="height"
  />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  series: Array,
  categories: Array,
  height: { type: Number, default: 350 }
})

const chartOptions = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false } },
  xaxis: { categories: props.categories },
  colors: ['#0F766E'],
  dataLabels: { enabled: false }
}))
</script>
```

**Other Chart Components:**
- `AreaChart.vue` - Area chart
- `DonutChart.vue` - Donut/pie chart
- `LineChart/` - Line chart variants

### Domain-Specific Components

**HR Components (`Components/HR/`):**
- `AvailabilityModal.vue` - Employee availability editor
- `CapacityModal.vue` - Capacity planning
- `PerformanceReviewModal.vue` - Performance review form
- `SkillModal.vue` - Skill management
- `TimeLogModal.vue` - Time tracking

**CMS Components (`Components/CMS/`):**
- `ColorPicker.vue` - Color selection for themes
- `ImageEditor.vue` - Image editing tools
- `ImageUpload.vue` - Image upload widget
- `MenuItemEditor.vue` - Navigation menu editor
- `RichTextEditor.vue` - Content editor
- `SectionPropertiesForm.vue` - Section configuration
- `SectionRenderer.vue` - Dynamic section rendering
- `Sections/` - Pre-built page sections (Hero, Features, etc.)

**Bangla Components (`Components/Bangla/`):**
- `BanglaAmount.vue` - Format numbers in Bangla
- `BanglaDate.vue` - Format dates in Bangla

**E-commerce Components (`Components/ecommerce/`):**
- `CustomerDemographic.vue` - Customer analytics
- `EcommerceMetrics.vue` - Sales metrics
- `MonthlySale.vue` - Monthly sales chart
- `MonthlyTarget.vue` - Target vs actual
- `RecentOrders.vue` - Order list widget
- `StatisticsChart.vue` - Statistics visualization

---

## API Integration

Gen-ERP uses a dual approach for API communication:
1. **Inertia.js** for page navigation (server-rendered)
2. **Axios** for AJAX requests (client-side data fetching)

### API Service Layer

**`resources/js/Services/api.js`** - Centralized API client:

```javascript
import axios from 'axios'

// Create axios instance
const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})

// Token management
const TOKEN_KEY = 'auth_token'
const COMPANY_KEY = 'active_company'

export const tokenManager = {
    get: () => localStorage.getItem(TOKEN_KEY),
    set: (token) => localStorage.setItem(TOKEN_KEY, token),
    remove: () => {
        localStorage.removeItem(TOKEN_KEY)
        localStorage.removeItem(COMPANY_KEY)
    },
    getCompany: () => {
        const company = localStorage.getItem(COMPANY_KEY)
        return company ? JSON.parse(company) : null
    },
    setCompany: (company) => localStorage.setItem(COMPANY_KEY, JSON.stringify(company))
}

// Request interceptor - add auth token
api.interceptors.request.use(
    (config) => {
        const token = tokenManager.get()
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }
        
        // Add company ID header
        const company = tokenManager.getCompany()
        if (company?.id) {
            config.headers['X-Company-ID'] = company.id
        }
        
        return config
    },
    (error) => Promise.reject(error)
)

// Response interceptor - handle auth errors
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            tokenManager.remove()
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)
```


### Authentication API

```javascript
export const authAPI = {
    async login(credentials) {
        const response = await api.post('/auth/login', credentials)
        
        if (response.data.success) {
            const { token, active_company, two_factor_required, temp_token } = response.data.data
            
            if (two_factor_required) {
                tokenManager.set(temp_token)
                return { success: true, requires2FA: true }
            }
            
            tokenManager.set(token)
            if (active_company) {
                tokenManager.setCompany(active_company)
            }
            
            return {
                success: true,
                user: response.data.data.user,
                company: active_company
            }
        }
        
        return { success: false, message: response.data.message }
    },

    async twoFactorChallenge(code) {
        const response = await api.post('/auth/two-factor/challenge', { code })
        
        if (response.data.success) {
            const { token, active_company } = response.data.data
            tokenManager.set(token)
            if (active_company) {
                tokenManager.setCompany(active_company)
            }
            
            return { success: true, user: response.data.data.user }
        }
        
        return { success: false, message: response.data.message }
    },

    async logout() {
        try {
            await api.post('/auth/logout')
        } finally {
            tokenManager.remove()
            window.location.href = '/login'
        }
    },

    async switchCompany(companyId) {
        const response = await api.post(`/auth/switch-company/${companyId}`)
        
        if (response.data.success) {
            tokenManager.setCompany(response.data.data.company)
            return response.data
        }
        
        throw new Error(response.data.message)
    }
}
```

### Business API Methods

```javascript
export const businessAPI = {
    // Customers
    async getCustomers(params = {}) {
        const response = await api.get('/customers', { params })
        return response.data
    },

    async createCustomer(data) {
        const response = await api.post('/customers', data)
        return response.data
    },

    // Products
    async getProducts(params = {}) {
        const response = await api.get('/products', { params })
        return response.data
    },

    // Sales Orders
    async getSalesOrders(params = {}) {
        const response = await api.get('/sales-orders', { params })
        return response.data
    },

    async confirmSalesOrder(id) {
        const response = await api.post(`/sales-orders/${id}/confirm`)
        return response.data
    },

    async convertToInvoice(id) {
        const response = await api.post(`/sales-orders/${id}/convert-to-invoice`)
        return response.data
    },

    // Invoices
    async getInvoices(params = {}) {
        const response = await api.get('/invoices', { params })
        return response.data
    },

    async createInvoice(data) {
        const response = await api.post('/invoices', data)
        return response.data
    },

    // Dashboard
    async getDashboard() {
        const response = await api.get('/dashboard')
        return response.data
    }
}

export default api
```

### Usage in Components

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { businessAPI } from '@/Services/api'

const invoices = ref([])
const loading = ref(false)

const fetchInvoices = async () => {
  loading.value = true
  try {
    const response = await businessAPI.getInvoices({
      page: 1,
      per_page: 15,
      status: 'pending'
    })
    invoices.value = response.data
  } catch (error) {
    console.error('Failed to fetch invoices:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchInvoices()
})
</script>
```


---

## Composables

Reusable composition functions for common patterns.

### useApi Composable

**`resources/js/Composables/useApi.js`** - API request wrapper with loading/error states:

```javascript
import { ref } from 'vue'
import api from '../Services/api.js'

export function useApi() {
  const loading = ref(false)
  const error = ref(null)

  const get = async (url, params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(url, { params })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const post = async (url, data = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(url, data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const put = async (url, data = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(url, data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const del = async (url) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.delete(url)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return { loading, error, get, post, put, delete: del }
}
```

**Usage:**
```vue
<script setup>
import { useApi } from '@/Composables/useApi'

const { loading, error, get, post } = useApi()

const fetchData = async () => {
  const data = await get('/customers')
  console.log(data)
}

const createCustomer = async (formData) => {
  const result = await post('/customers', formData)
  console.log(result)
}
</script>

<template>
  <div>
    <div v-if="loading">Loading...</div>
    <div v-if="error" class="text-red-600">{{ error }}</div>
  </div>
</template>
```

### usePagination Composable

**`resources/js/Composables/usePagination.js`** - Pagination logic:

```javascript
import { ref, computed } from 'vue'

export function usePagination(initialPage = 1, perPage = 15) {
  const page = ref(initialPage)
  const total = ref(0)
  const lastPage = ref(1)

  const from = computed(() => (page.value - 1) * perPage + 1)
  const to = computed(() => Math.min(page.value * perPage, total.value))

  const links = computed(() => {
    const links = []
    
    if (page.value > 1) {
      links.push({ label: '«', url: `?page=${page.value - 1}`, active: false })
    }
    
    for (let i = 1; i <= lastPage.value; i++) {
      links.push({ 
        label: i.toString(), 
        url: `?page=${i}`, 
        active: i === page.value 
      })
    }
    
    if (page.value < lastPage.value) {
      links.push({ label: '»', url: `?page=${page.value + 1}`, active: false })
    }
    
    return links
  })

  const setTotal = (count) => {
    total.value = count
    lastPage.value = Math.ceil(count / perPage)
  }

  const setPage = (newPage) => {
    page.value = Math.max(1, Math.min(newPage, lastPage.value))
  }

  return { page, total, lastPage, from, to, links, setTotal, setPage }
}
```

**Usage:**
```vue
<script setup>
import { usePagination } from '@/Composables/usePagination'

const { page, total, from, to, links, setTotal, setPage } = usePagination(1, 15)

// After fetching data
setTotal(150) // 150 total records

// Navigate
setPage(2)
</script>

<template>
  <div>
    <p>Showing {{ from }} to {{ to }} of {{ total }} results</p>
    <nav>
      <a
        v-for="link in links"
        :key="link.label"
        @click="setPage(parseInt(link.label))"
        :class="{ 'active': link.active }"
      >
        {{ link.label }}
      </a>
    </nav>
  </div>
</template>
```


### useSearch Composable

**`resources/js/Composables/useSearch.js`** - Debounced search:

```javascript
import { ref, watch } from 'vue'

export function useSearch(initialQuery = '', debounceMs = 300) {
  const query = ref(initialQuery)
  const debouncedQuery = ref(initialQuery)
  let timeoutId = null

  const updateQuery = (value) => {
    query.value = value
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => {
      debouncedQuery.value = value
    }, debounceMs)
  }

  const clear = () => {
    updateQuery('')
  }

  watch(query, (newVal) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => {
      debouncedQuery.value = newVal
    }, debounceMs)
  })

  return { query, debouncedQuery, updateQuery, clear }
}
```

**Usage:**
```vue
<script setup>
import { watch } from 'vue'
import { useSearch } from '@/Composables/useSearch'

const { query, debouncedQuery, updateQuery, clear } = useSearch('', 300)

// Watch debounced query for API calls
watch(debouncedQuery, (newQuery) => {
  if (newQuery) {
    fetchResults(newQuery)
  }
})
</script>

<template>
  <input
    :value="query"
    @input="updateQuery($event.target.value)"
    placeholder="Search..."
  />
  <button @click="clear">Clear</button>
</template>
```

### useSidebar Composable

**`resources/js/Composables/useSidebar.ts`** - Sidebar state management:

```typescript
import { ref, computed, onMounted, onUnmounted, provide, inject } from 'vue'
import type { Ref } from 'vue'

interface SidebarContextType {
  isExpanded: Ref<boolean>
  isMobileOpen: Ref<boolean>
  isHovered: Ref<boolean>
  activeItem: Ref<string | null>
  openSubmenu: Ref<string | null>
  toggleSidebar: () => void
  toggleMobileSidebar: () => void
  setIsHovered: (isHovered: boolean) => void
  setActiveItem: (item: string | null) => void
  toggleSubmenu: (item: string) => void
}

const SidebarSymbol = Symbol()

export function useSidebarProvider() {
  const isExpanded = ref(true)
  const isMobileOpen = ref(false)
  const isMobile = ref(false)
  const isHovered = ref(false)
  const activeItem = ref<string | null>(null)
  const openSubmenu = ref<string | null>(null)

  const handleResize = () => {
    const mobile = window.innerWidth < 768
    isMobile.value = mobile
    if (!mobile) {
      isMobileOpen.value = false
    }
  }

  onMounted(() => {
    handleResize()
    window.addEventListener('resize', handleResize)
  })

  onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
  })

  const toggleSidebar = () => {
    if (isMobile.value) {
      isMobileOpen.value = !isMobileOpen.value
    } else {
      isExpanded.value = !isExpanded.value
    }
  }

  const toggleMobileSidebar = () => {
    isMobileOpen.value = !isMobileOpen.value
  }

  const setIsHovered = (value: boolean) => {
    isHovered.value = value
  }

  const toggleSubmenu = (item: string) => {
    openSubmenu.value = openSubmenu.value === item ? null : item
  }

  const context: SidebarContextType = {
    isExpanded: computed(() => isMobile.value ? false : isExpanded.value),
    isMobileOpen,
    isHovered,
    activeItem,
    openSubmenu,
    toggleSidebar,
    toggleMobileSidebar,
    setIsHovered,
    setActiveItem: (item) => activeItem.value = item,
    toggleSubmenu
  }

  provide(SidebarSymbol, context)
  return context
}

export function useSidebar(): SidebarContextType {
  const context = inject<SidebarContextType>(SidebarSymbol)
  if (!context) {
    throw new Error('useSidebar must be used within SidebarProvider')
  }
  return context
}
```

---

## Theme System

Gen-ERP supports light and dark modes with TailwindCSS.

### Color Palette

**`tailwind.config.js`** - Theme configuration:

```javascript
export default {
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#0F766E',  // Teal-700
          dark: '#0D6B63',
          light: '#14B8A6'     // Teal-500
        },
        accent: '#14B8A6',     // Teal-500
        success: '#16A34A',    // Green-600
        warning: '#CA8A04',    // Yellow-600
        danger: '#B91C1C',     // Red-700
        bodybg: '#FAFAFA',     // Light gray background
        boxdark: '#0D1F1E',    // Dark sidebar background
        'boxdark-2': '#162221', // Dark sidebar dropdown
        stroke: '#E5E7EB',     // Gray-200
        'gray-1': '#6B7280',   // Gray-500
        'gray-2': '#D1D5DB',   // Gray-300
        'gray-3': '#F3F4F6',   // Gray-100
        whiten: '#F1F5F9',     // Slate-100
        black: '#1F2937',      // Gray-800
        'black-2': '#374151'   // Gray-700
      },
      fontFamily: {
        satoshi: ['"Plus Jakarta Sans"', 'sans-serif'],
        jakarta: ['"Plus Jakarta Sans"', 'sans-serif'],
        bangla: ['"Noto Sans Bengali"', '"Plus Jakarta Sans"', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'monospace']
      }
    }
  }
}
```


### Theme Provider

**`Components/Layout/ThemeProvider.vue`** - Dark mode wrapper:

```vue
<template>
  <div :class="{ 'dark': isDark }">
    <slot />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

const isDark = ref(false)

onMounted(() => {
  // Load theme preference from localStorage
  const savedTheme = localStorage.getItem('theme')
  isDark.value = savedTheme === 'dark'
  
  // Apply theme class to document
  if (isDark.value) {
    document.documentElement.classList.add('dark')
  }
})

watch(isDark, (newValue) => {
  if (newValue) {
    document.documentElement.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.setItem('theme', 'light')
  }
})

// Expose toggle function
defineExpose({ isDark, toggle: () => isDark.value = !isDark.value })
</script>
```

### Theme Toggler

**`Components/common/ThemeToggler.vue`** - Theme switch button:

```vue
<template>
  <button
    @click="toggleTheme"
    class="flex items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
  >
    <SunIcon v-if="isDark" class="w-5 h-5 text-gray-400" />
    <MoonIcon v-else class="w-5 h-5 text-gray-600" />
  </button>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline'

const isDark = ref(false)

onMounted(() => {
  isDark.value = localStorage.getItem('theme') === 'dark'
})

const toggleTheme = () => {
  isDark.value = !isDark.value
  
  if (isDark.value) {
    document.documentElement.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.setItem('theme', 'light')
  }
}
</script>
```

### Dark Mode Classes

TailwindCSS dark mode uses the `dark:` prefix:

```vue
<template>
  <!-- Light: white background, dark text -->
  <!-- Dark: gray-900 background, white text -->
  <div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
    <h1 class="text-2xl font-bold">Dashboard</h1>
    
    <!-- Light: gray-100 hover, dark: gray-800 hover -->
    <button class="hover:bg-gray-100 dark:hover:bg-gray-800">
      Click me
    </button>
    
    <!-- Light: gray-200 border, dark: gray-700 border -->
    <div class="border border-gray-200 dark:border-gray-700">
      Content
    </div>
  </div>
</template>
```

---

## Internationalization

Gen-ERP supports English and Bangla (Bengali) languages.

### Bangla Font Configuration

**`tailwind.config.js`:**
```javascript
fontFamily: {
  bangla: ['"Noto Sans Bengali"', '"Plus Jakarta Sans"', 'sans-serif']
}
```

**`resources/css/app.css`:**
```css
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
```

### Bangla Components

**`Components/Bangla/BanglaAmount.vue`** - Format currency in Bangla:

```vue
<template>
  <span class="font-bangla">{{ formattedAmount }}</span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  amount: { type: Number, required: true },
  currency: { type: String, default: '৳' }
})

const banglaDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯']

const toBanglaNumber = (num) => {
  return num.toString().split('').map(digit => {
    return digit >= '0' && digit <= '9' ? banglaDigits[digit] : digit
  }).join('')
}

const formattedAmount = computed(() => {
  const formatted = props.amount.toLocaleString('en-IN')
  return `${props.currency} ${toBanglaNumber(formatted)}`
})
</script>
```

**Usage:**
```vue
<BanglaAmount :amount="125000" />
<!-- Output: ৳ ১,২৫,০০০ -->
```

**`Components/Bangla/BanglaDate.vue`** - Format dates in Bangla:

```vue
<template>
  <span class="font-bangla">{{ formattedDate }}</span>
</template>

<script setup>
import { computed } from 'vue'
import dayjs from 'dayjs'

const props = defineProps({
  date: { type: [String, Date], required: true },
  format: { type: String, default: 'DD MMMM YYYY' }
})

const banglaMonths = [
  'জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
  'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'
]

const banglaDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯']

const toBanglaNumber = (num) => {
  return num.toString().split('').map(digit => {
    return digit >= '0' && digit <= '9' ? banglaDigits[digit] : digit
  }).join('')
}

const formattedDate = computed(() => {
  const d = dayjs(props.date)
  const day = toBanglaNumber(d.date())
  const month = banglaMonths[d.month()]
  const year = toBanglaNumber(d.year())
  
  return `${day} ${month} ${year}`
})
</script>
```

**Usage:**
```vue
<BanglaDate :date="new Date()" />
<!-- Output: ০৪ মার্চ ২০২৬ -->
```


---

## Build & Development

### Development Server

```bash
# Install dependencies
npm install

# Start Vite dev server (with HMR)
npm run dev

# In another terminal, start Laravel server
php artisan serve
```

**Vite Configuration (`vite.config.js`):**
```javascript
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false
                }
            }
        })
    ],
    resolve: {
        alias: {
            '@': '/resources/js'
        }
    }
})
```

### Production Build

```bash
# Build for production
npm run build

# Output files will be in public/build/
```

**Build Optimization:**
- Code splitting by route
- Tree shaking for unused code
- CSS minification
- Asset hashing for cache busting
- Lazy loading for page components

### Hot Module Replacement (HMR)

Vite provides instant HMR for Vue components:

```vue
<script setup>
import { ref } from 'vue'

// Changes to this component will hot-reload without page refresh
const count = ref(0)
</script>
```

### Environment Variables

**`.env`:**
```bash
VITE_APP_NAME="GenERP BD"
VITE_API_URL="${APP_URL}/api/v1"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
```

**Usage in JavaScript:**
```javascript
const appName = import.meta.env.VITE_APP_NAME
const apiUrl = import.meta.env.VITE_API_URL
```

---

## Page Component Example

Complete example of a typical page component:

**`Pages/CRM/Dashboard/Index.vue`:**

```vue
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">CRM Dashboard</h1>
          <p class="mt-1 text-sm text-gray-600">
            Overview of your sales pipeline
          </p>
        </div>
        <select v-model="selectedPeriod" @change="fetchData" 
                class="rounded-md border-gray-300 text-sm">
          <option value="7">Last 7 days</option>
          <option value="30">Last 30 days</option>
          <option value="90">Last 90 days</option>
        </select>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <UserGroupIcon class="h-8 w-8 text-blue-600" />
          <div class="ml-4">
            <div class="text-2xl font-bold">{{ metrics.total_leads }}</div>
            <div class="text-sm text-gray-600">Total Leads</div>
            <div class="text-xs" :class="metrics.leads_change >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ metrics.leads_change >= 0 ? '+' : '' }}{{ metrics.leads_change }}%
            </div>
          </div>
        </div>
      </div>
      
      <!-- More metric cards... -->
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium mb-4">Sales Pipeline</h3>
        <div class="space-y-4">
          <div v-for="stage in pipelineData" :key="stage.id" 
               class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-4 h-4 rounded-full" 
                   :style="{ backgroundColor: stage.color }"></div>
              <span class="text-sm font-medium">{{ stage.name }}</span>
            </div>
            <div class="text-right">
              <div class="text-sm font-semibold">{{ stage.opportunities_count }}</div>
              <div class="text-xs text-gray-500">৳{{ formatNumber(stage.total_value) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { UserGroupIcon } from '@heroicons/vue/24/outline'

const selectedPeriod = ref(30)
const metrics = ref({
  total_leads: 0,
  total_opportunities: 0,
  pipeline_value: 0,
  conversion_rate: 0,
  leads_change: 0
})
const pipelineData = ref([])

const fetchData = async () => {
  await Promise.all([
    fetchMetrics(),
    fetchPipelineData()
  ])
}

const fetchMetrics = async () => {
  try {
    const response = await fetch(
      `/api/v1/crm/dashboard/metrics?period=${selectedPeriod.value}`,
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json'
        }
      }
    )
    
    if (response.ok) {
      const data = await response.json()
      metrics.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch metrics:', error)
  }
}

const fetchPipelineData = async () => {
  try {
    const response = await fetch('/api/v1/crm/pipelines/statistics', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      pipelineData.value = data.data.stages || []
    }
  } catch (error) {
    console.error('Failed to fetch pipeline data:', error)
  }
}

const formatNumber = (num) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

onMounted(() => {
  fetchData()
})
</script>
```

---

## Best Practices

### Component Organization

1. **Single Responsibility** - Each component should do one thing well
2. **Composition API** - Use `<script setup>` for cleaner code
3. **Props Validation** - Always define prop types
4. **Emit Events** - Use `defineEmits()` for type safety
5. **Composables** - Extract reusable logic into composables

### Performance Optimization

1. **Lazy Loading** - Use dynamic imports for routes
   ```javascript
   const Dashboard = () => import('./Pages/Dashboard/Index.vue')
   ```

2. **v-show vs v-if** - Use `v-show` for frequently toggled elements
3. **Computed Properties** - Cache expensive calculations
4. **Virtual Scrolling** - For large lists (use `vue-virtual-scroller`)
5. **Debounce** - Use `useSearch` composable for search inputs

### Code Style

1. **Naming Conventions:**
   - Components: PascalCase (`UserProfile.vue`)
   - Composables: camelCase with `use` prefix (`useApi.js`)
   - Props: camelCase in script, kebab-case in template
   - Events: kebab-case (`@update-value`)

2. **File Structure:**
   ```
   ComponentName.vue
   ├── <template>     (HTML structure)
   ├── <script setup> (Logic)
   └── <style scoped> (Component-specific styles)
   ```

3. **Import Order:**
   ```javascript
   // 1. Vue core
   import { ref, computed, onMounted } from 'vue'
   
   // 2. Third-party libraries
   import { usePage } from '@inertiajs/vue3'
   import dayjs from 'dayjs'
   
   // 3. Local components
   import Button from '@/Components/UI/Button.vue'
   
   // 4. Composables
   import { useApi } from '@/Composables/useApi'
   
   // 5. Services
   import { businessAPI } from '@/Services/api'
   ```

### Error Handling

```vue
<script setup>
import { ref } from 'vue'

const error = ref(null)
const loading = ref(false)

const fetchData = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await fetch('/api/data')
    if (!response.ok) throw new Error('Failed to fetch')
    const data = await response.json()
    return data
  } catch (err) {
    error.value = err.message
    console.error('Error:', err)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <div v-if="loading">Loading...</div>
    <div v-else-if="error" class="text-red-600">{{ error }}</div>
    <div v-else><!-- Content --></div>
  </div>
</template>
```

---

## Troubleshooting

### Common Issues

**1. Inertia page not loading:**
```bash
# Clear Inertia cache
php artisan cache:clear
php artisan view:clear

# Rebuild assets
npm run build
```

**2. CSRF token mismatch:**
```javascript
// Ensure CSRF token is set in bootstrap.js
const token = document.head.querySelector('meta[name="csrf-token"]')
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
}
```

**3. Dark mode not working:**
```javascript
// Check if ThemeProvider is wrapping the app
// Verify dark class is added to <html> element
document.documentElement.classList.add('dark')
```

**4. Company context not syncing:**
```javascript
// Ensure company ID is synced in app.js
const companyId = props.initialPage.props.auth?.company?.id
if (companyId) {
    sessionStorage.setItem('active_company_id', companyId)
}
```

---

**Last Updated:** March 4, 2026  
**Version:** 1.0.0  
**Maintainer:** Gen-ERP Development Team
