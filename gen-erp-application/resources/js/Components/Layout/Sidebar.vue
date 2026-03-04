<template>
  <aside :class="['fixed inset-y-0 z-50 flex flex-col bg-white dark:bg-boxdark transition-transform duration-300 lg:static lg:inset-auto border-r border-gray-200 dark:border-white/8', open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0']">
    <div class="flex flex-col h-full w-[260px]">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-200 dark:border-white/8">
        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white text-lg font-bold">G</div>
        <div>
          <h1 class="text-gray-900 dark:text-white text-sm font-bold tracking-tight">GenERP BD</h1>
          <p class="text-gray-500 dark:text-white/30 text-[10px] font-mono">Enterprise Resource Planning</p>
        </div>
      </div>

      <!-- Company Switcher -->
      <div class="px-4 py-3">
        <CompanySwitcher />
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto px-3 py-2 space-y-4">
        <!-- Main -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400 dark:text-white/25 px-2 mb-2">Main</p>
          <div class="space-y-1">
            <NavItem icon="⊞" label="Dashboard" route="/dashboard" :page-url="pageUrl" />
          </div>
        </div>

        <!-- CRM -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400 dark:text-white/25 px-2 mb-2">CRM</p>
          <div class="space-y-1">
            <NavItem icon="📊" label="Dashboard" route="/crm/dashboard" :page-url="pageUrl" />
            <NavItem icon="🎯" label="Leads" route="/crm/leads" :page-url="pageUrl" />
            <NavItem icon="👥" label="Contacts" route="/crm/contacts" :page-url="pageUrl" />
            <NavItem icon="💼" label="Opportunities" route="/crm/opportunities" :page-url="pageUrl" />
            <NavItem icon="🔄" label="Pipeline" route="/crm/pipelines" :page-url="pageUrl" />
            <NavItem icon="📋" label="Activities" route="/crm/activities" :page-url="pageUrl" />
          </div>
        </div>

        <!-- Sales -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400 dark:text-white/25 px-2 mb-2">Sales</p>
          <div class="space-y-1">
            <NavItem icon="🧾" label="Invoices" route="/sales/invoices" :page-url="pageUrl" :badge="4" />
            <NavItem icon="🛒" label="Orders" route="/sales/orders" :page-url="pageUrl" />
            <NavItem icon="👥" label="Customers" route="/sales/customers" :page-url="pageUrl" />
            <NavItem icon="📝" label="Credit Notes" route="/sales/credit-notes" :page-url="pageUrl" />
            <NavItem icon="↩️" label="Returns" route="/sales/returns" :page-url="pageUrl" />
          </div>
        </div>

        <!-- Purchase -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400 dark:text-white/25 px-2 mb-2">Purchase</p>
          <div class="space-y-1">
            <NavItem icon="🚚" label="Orders" route="/purchase/orders" :page-url="pageUrl" />
            <NavItem icon="📥" label="Receipts" route="/purchase/receipts" :page-url="pageUrl" />
            <NavItem icon="🏬" label="Suppliers" route="/purchase/suppliers" :page-url="pageUrl" />
            <NavItem icon="↩️" label="Returns" route="/purchase/returns" :page-url="pageUrl" />
          </div>
        </div>

        <!-- Logistics -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400 dark:text-white/25 px-2 mb-2">Logistics</p>
          <div class="space-y-1">
            <NavItem icon="📦" label="Shipments" route="/logistics/shipments" :page-url="pageUrl" />
            <NavItem icon="🚚" label="Carriers" route="/logistics/carriers" :page-url="pageUrl" />
            <NavItem icon="📍" label="Tracking" route="/logistics/tracking" :page-url="pageUrl" />
            <NavItem icon="↩️" label="Returns" route="/logistics/returns" :page-url="pageUrl" />
            <NavItem icon="💰" label="COD Management" route="/logistics/cod" :page-url="pageUrl" />
          </div>
        </div>

        <!-- Inventory -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400 dark:text-white/25 px-2 mb-2">Inventory</p>
          <div class="space-y-1">
            <NavItem icon="📦" label="Products" route="/inventory/products" :page-url="pageUrl" />
            <NavItem icon="📊" label="Stock" route="/inventory/stock" :page-url="pageUrl" :badge="3" badge-variant="warning" />
            <NavItem icon="🏭" label="Warehouses" route="/inventory/warehouses" :page-url="pageUrl" />
            <NavItem icon="📤" label="Transfers" route="/inventory/transfers" :page-url="pageUrl" />
            <NavItem icon="🔧" label="Adjustments" route="/inventory/adjustments" :page-url="pageUrl" />
          </div>
        </div>

        <!-- Accounting -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400 dark:text-white/25 px-2 mb-2">Accounting</p>
          <div class="space-y-1">
            <NavItem icon="💵" label="Chart of Accounts" route="/accounting/chart-of-accounts" :page-url="pageUrl" />
            <NavItem icon="📒" label="Journal Entries" route="/accounting/journal-entries" :page-url="pageUrl" />
            <NavItem icon="🧮" label="Trial Balance" route="/accounting/trial-balance" :page-url="pageUrl" />
            <NavItem icon="📈" label="Profit & Loss" route="/accounting/profit-loss" :page-url="pageUrl" />
            <NavItem icon="📊" label="Balance Sheet" route="/accounting/balance-sheet" :page-url="pageUrl" />
          </div>
        </div>

        <!-- HR & Payroll -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400 dark:text-white/25 px-2 mb-2">HR & Payroll</p>
          <div class="space-y-1">
            <NavItem icon="👤" label="Employees" route="/hr/employees" :page-url="pageUrl" />
            <NavItem icon="📅" label="Attendance" route="/hr/attendance" :page-url="pageUrl" />
            <NavItem icon="🏖" label="Leave" route="/hr/leave" :page-url="pageUrl" />
            <NavItem icon="💰" label="Payroll" route="/hr/payroll" :page-url="pageUrl" />
          </div>
        </div>

        <!-- POS -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400 dark:text-white/25 px-2 mb-2">POS</p>
          <div class="space-y-1">
            <NavItem icon="🖥" label="Session" route="/pos/session" :page-url="pageUrl" />
          </div>
        </div>

        <!-- Reports -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400 dark:text-white/25 px-2 mb-2">Reports</p>
          <div class="space-y-1">
            <NavItem icon="📊" label="Reports" route="/reports" :page-url="pageUrl" />
          </div>
        </div>

        <!-- Settings -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400 dark:text-white/25 px-2 mb-2">Settings</p>
          <div class="space-y-1">
            <NavItem icon="⚙️" label="Company" route="/settings/company" :page-url="pageUrl" />
          </div>
        </div>
      </nav>

      <!-- User Footer -->
      <div class="border-t border-gray-200 dark:border-white/5 px-4 py-3">
        <div class="flex items-center gap-3">
          <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white text-xs font-bold">
            {{ userInitial }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-gray-900 dark:text-white/90 text-xs font-semibold truncate">{{ user?.name }}</p>
            <p class="text-gray-500 dark:text-white/30 text-[10px]">{{ planLabel }}</p>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import CompanySwitcher from './CompanySwitcher.vue'
import NavItem from './NavItem.vue'

defineProps({
  open: Boolean
})

defineEmits(['close'])

const page = usePage()
const pageUrl = computed(() => page.url)

const user = computed(() => page.props.auth?.user)
const company = computed(() => page.props.auth?.company)

const userInitial = computed(() => user.value?.name?.charAt(0).toUpperCase() || 'U')
const planLabel = computed(() => company.value?.plan || 'Free Plan')
</script>
