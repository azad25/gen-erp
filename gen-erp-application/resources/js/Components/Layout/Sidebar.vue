<template>
  <aside :class="['fixed inset-y-0 z-50 flex flex-col bg-boxdark transition-transform duration-300 lg:static lg:inset-auto', open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0']">
    <div class="flex flex-col h-full w-[260px]">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 py-4 border-b border-white/8">
        <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white text-lg font-bold">G</div>
        <div>
          <h1 class="text-white text-sm font-bold tracking-tight">GenERP BD</h1>
          <p class="text-white/30 text-[10px] font-mono">Enterprise Resource Planning</p>
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
          <p class="text-[10px] font-mono uppercase tracking-widest text-white/25 px-2 mb-2">Main</p>
          <div class="space-y-1">
            <NavItem icon="⊞" label="Dashboard" route="dashboard" :page-url="pageUrl" />
            <NavItem icon="🧾" label="Invoices" route="invoices.index" :page-url="pageUrl" :badge="4" />
            <NavItem icon="🛒" label="Sales Orders" route="sales-orders.index" :page-url="pageUrl" />
            <NavItem icon="👥" label="Customers" route="customers.index" :page-url="pageUrl" />
          </div>
        </div>

        <!-- Inventory -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-white/25 px-2 mb-2">Inventory</p>
          <div class="space-y-1">
            <NavItem icon="📦" label="Products" route="products.index" :page-url="pageUrl" />
            <NavItem icon="📊" label="Stock Levels" route="stock-levels.index" :page-url="pageUrl" :badge="3" badge-variant="warning" />
            <NavItem icon="🏭" label="Warehouses" route="warehouses.index" :page-url="pageUrl" />
          </div>
        </div>

        <!-- Purchases -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-white/25 px-2 mb-2">Purchases</p>
          <div class="space-y-1">
            <NavItem icon="🚚" label="Purchase Orders" route="purchase-orders.index" :page-url="pageUrl" />
            <NavItem icon="📥" label="Goods Receipts" route="goods-receipts.index" :page-url="pageUrl" />
            <NavItem icon="🏬" label="Suppliers" route="suppliers.index" :page-url="pageUrl" />
          </div>
        </div>

        <!-- Finance -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-white/25 px-2 mb-2">Finance</p>
          <div class="space-y-1">
            <NavItem icon="💵" label="Accounts" route="accounts.index" :page-url="pageUrl" />
            <NavItem icon="📒" label="Journal Entries" route="journal-entries.index" :page-url="pageUrl" />
            <NavItem icon="🧮" label="Expenses" route="expenses.index" :page-url="pageUrl" />
            <NavItem icon="📈" label="Reports" route="reports.index" :page-url="pageUrl" />
          </div>
        </div>

        <!-- HR & Payroll -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-white/25 px-2 mb-2">HR & Payroll</p>
          <div class="space-y-1">
            <NavItem icon="👤" label="Employees" route="employees.index" :page-url="pageUrl" />
            <NavItem icon="📅" label="Attendance" route="attendance.index" :page-url="pageUrl" />
            <NavItem icon="🏖" label="Leave" route="leave.index" :page-url="pageUrl" />
            <NavItem icon="💰" label="Payroll" route="payroll.index" :page-url="pageUrl" />
          </div>
        </div>

        <!-- Branches & POS -->
        <div>
          <p class="text-[10px] font-mono uppercase tracking-widest text-white/25 px-2 mb-2">Branches & POS</p>
          <div class="space-y-1">
            <NavItem icon="🏢" label="Branches" route="branches.index" :page-url="pageUrl" />
            <NavItem icon="🖥" label="POS Terminal" route="pos.index" :page-url="pageUrl" />
          </div>
        </div>
      </nav>

      <!-- User Footer -->
      <div class="border-t border-white/5 px-4 py-3">
        <div class="flex items-center gap-3">
          <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white text-xs font-bold">
            {{ userInitial }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-white/90 text-xs font-semibold truncate">{{ user?.name }}</p>
            <p class="text-white/30 text-[10px]">{{ planLabel }}</p>
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
