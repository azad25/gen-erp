<template>
  <aside
    :class="[
      'fixed flex flex-col top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 dark:text-gray-100 h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200',
      {
        'lg:w-[290px]': isExpanded || isMobileOpen || isHovered,
        'lg:w-[90px]': !isExpanded && !isHovered,
        'translate-x-0 w-[290px]': isMobileOpen,
        '-translate-x-full': !isMobileOpen,
        'lg:translate-x-0': true,
      },
    ]"
    @mouseenter="!isExpanded && (isHovered = true)"
    @mouseleave="isHovered = false"
  >
    <div
      :class="[
        'py-8 flex',
        !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start',
      ]"
    >
      <Link href="/" class="flex items-center gap-3">
        <Logo class="w-10 h-10" />
        <span
          v-if="isExpanded || isHovered || isMobileOpen"
          class="text-xl font-extrabold text-black dark:text-white tracking-tight"
        >
          GenERP BD
        </span>
      </Link>
    </div>
    
    <!-- Company Switcher -->
    <div 
      v-if="isExpanded || isHovered || isMobileOpen"
      class="px-2 mb-6"
    >
      <CompanySwitcher />
    </div>
    
    <div
      class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar"
    >
      <nav class="mb-6">
        <div class="flex flex-col gap-2">
          <div v-for="(menuGroup, groupIndex) in menuGroups" :key="groupIndex">
            <!-- Collapsible Group Header -->
            <div 
              v-if="menuGroup.items.length > 1"
              @click="toggleGroupCollapse(menuGroup.key)"
              :class="[
                'menu-item group w-full cursor-pointer',
                {
                  'menu-item-active': !isGroupCollapsed(menuGroup.key) && isAnyRouteActive(menuGroup.items, menuGroup.key),
                  'menu-item-inactive': isGroupCollapsed(menuGroup.key) || !isAnyRouteActive(menuGroup.items, menuGroup.key)
                },
                !isExpanded && !isHovered ? 'lg:justify-center' : 'lg:justify-start',
              ]"
            >
              <span
                :class="[
                  !isGroupCollapsed(menuGroup.key) && isAnyRouteActive(menuGroup.items, menuGroup.key)
                    ? 'menu-item-icon-active'
                    : 'menu-item-icon-inactive'
                ]"
              >
                <component 
                  :is="menuGroup.icon" 
                  class="w-5 h-5 flex-shrink-0"
                />
              </span>
              <span
                v-if="isExpanded || isHovered || isMobileOpen"
                class="menu-item-text"
              >
                {{ menuGroup.title }}
              </span>
              <ChevronDownIcon 
                v-if="isExpanded || isHovered || isMobileOpen"
                :class="[
                  'ml-auto w-5 h-5 transition-transform duration-200',
                  {
                    'rotate-180': !isGroupCollapsed(menuGroup.key),
                  },
                ]"
              />
            </div>

            <!-- Single Item Group (Dashboard) -->
            <div v-else-if="menuGroup.items.length === 1">
              <Link
                :href="menuGroup.items[0].href"
                :class="[
                  'menu-item group w-full',
                  {
                    'menu-item-active': isCurrentRoute(menuGroup.items[0].routeName),
                    'menu-item-inactive': !isCurrentRoute(menuGroup.items[0].routeName)
                  },
                  !isExpanded && !isHovered ? 'lg:justify-center' : 'lg:justify-start',
                ]"
              >
                <span
                  :class="[
                    isCurrentRoute(menuGroup.items[0].routeName) 
                      ? 'menu-item-icon-active' 
                      : 'menu-item-icon-inactive'
                  ]"
                >
                  <component 
                    :is="menuGroup.items[0].icon" 
                    class="w-5 h-5 flex-shrink-0"
                  />
                </span>
                <span
                  v-if="isExpanded || isHovered || isMobileOpen"
                  class="menu-item-text"
                >
                  {{ menuGroup.items[0].title }}
                </span>
              </Link>
            </div>

            <!-- Collapsible Group Items -->
            <Transition
              enter-active-class="transition-all duration-300 ease-out"
              enter-from-class="opacity-0 max-h-0"
              enter-to-class="opacity-100 max-h-96"
              leave-active-class="transition-all duration-300 ease-in"
              leave-from-class="opacity-100 max-h-96"
              leave-to-class="opacity-0 max-h-0"
            >
              <div 
                v-if="menuGroup.items.length > 1 && !isGroupCollapsed(menuGroup.key)"
                class="overflow-hidden"
              >
                <div class="ml-4 border-l border-gray-200 dark:border-gray-700 pl-4 space-y-1">
                  <!-- Category Dashboard Link (skip for settings) -->
                  <Link
                    v-if="menuGroup.key !== 'settings'"
                    :href="`/${menuGroup.key}/dashboard`"
                    :class="[
                      'menu-dropdown-item group text-sm w-full',
                      {
                        'menu-dropdown-item-active': isCurrentRoute(`${menuGroup.key}.dashboard`),
                        'menu-dropdown-item-inactive': !isCurrentRoute(`${menuGroup.key}.dashboard`)
                      }
                    ]"
                  >
                    <ChartBarIcon class="w-4 h-4 flex-shrink-0" />
                    <span v-if="isExpanded || isHovered || isMobileOpen">
                      {{ $t(`sidebar.${menuGroup.key}.dashboard`) }}
                    </span>
                  </Link>
                  
                  <!-- Regular Menu Items -->
                  <Link
                    v-for="(item, itemIndex) in menuGroup.items"
                    :key="itemIndex"
                    :href="item.href"
                    :class="[
                      'menu-dropdown-item group text-sm w-full',
                      {
                        'menu-dropdown-item-active': isCurrentRoute(item.routeName),
                        'menu-dropdown-item-inactive': !isCurrentRoute(item.routeName)
                      }
                    ]"
                  >
                    <component 
                      :is="item.icon" 
                      class="w-4 h-4 flex-shrink-0" 
                    />
                    <span v-if="isExpanded || isHovered || isMobileOpen">
                      {{ item.title }}
                    </span>
                  </Link>
                </div>
              </div>
            </Transition>

            <!-- Separator -->
            <div 
              v-if="groupIndex < menuGroups.length - 1" 
              class="h-px bg-gray-200 dark:bg-gray-700 mx-4 my-3"
            ></div>
          </div>
        </div>
      </nav>
      <SidebarWidget v-if="isExpanded || isHovered || isMobileOpen" />
    </div>
  </aside>
</template>

<script setup>
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import { useTranslations } from "@/Composables/useTranslations";
import { 
  CreditCardIcon, 
  UsersIcon, 
  DocumentTextIcon as FileTextIcon 
} from "@heroicons/vue/24/outline";

import {
  GridIcon,
  CalenderIcon,
  UserCircleIcon,
  ChatIcon,
  MailIcon,
  DocsIcon,
  PieChartIcon,
  ChevronDownIcon,
  HorizontalDots,
  PageIcon,
  TableIcon,
  ListIcon,
  PlugInIcon,
  SettingsIcon,
  TaskIcon,
  FolderIcon,
  BarChartIcon,
  ChartBarIcon,
  BoxIcon,
  ArchiveIcon,
} from "../../icons";
import SidebarWidget from "./SidebarWidget.vue";
import BoxCubeIcon from "@/icons/BoxCubeIcon.vue";
import Logo from "@/Components/Home/Logo.vue";
import CompanySwitcher from "./CompanySwitcher.vue";
import { useSidebar } from "@/composables/useSidebar";

const page = usePage();
const { $t } = useTranslations();

const { isExpanded, isMobileOpen, isHovered } = useSidebar();

// Helper function to check current route
const isCurrentRoute = (routeName) => {
  const currentPath = page.url || window.location.pathname;
  
  // Handle main dashboard
  if (routeName === 'dashboard') {
    return currentPath === '/dashboard' || currentPath === '/';
  }
  
  // Handle documents routes with exact matching
  if (routeName.startsWith('documents.')) {
    if (routeName === 'documents.dashboard') {
      return currentPath === '/documents/dashboard';
    }
    if (routeName === 'documents.index') {
      return currentPath === '/documents' || currentPath === '/documents/';
    }
    if (routeName === 'documents.folders') {
      return currentPath === '/documents/folders';
    }
    if (routeName === 'documents.recent') {
      return currentPath === '/documents/recent';
    }
  }
  
  // Handle other routes with exact matching
  const expectedPath = '/' + routeName.replace('.', '/');
  return currentPath === expectedPath || currentPath.startsWith(expectedPath + '/');
};

const isAnyRouteActive = (items, groupKey) => {
  // Check if the dashboard route for this group is active
  if (isCurrentRoute(`${groupKey}.dashboard`)) return true;
  
  // Check if any of the group items are active
  if (!items) return false;
  return items.some(item => isCurrentRoute(item.routeName));
};

// Collapsible groups state
const collapsedGroups = ref(new Set());

const menuGroups = computed(() => [
  {
    key: "main",
    title: $t('sidebar.main.dashboard'),
    icon: GridIcon,
    items: [
      {
        icon: GridIcon,
        title: $t('sidebar.main.dashboard'),
        href: "/dashboard",
        routeName: "dashboard",
      },
    ],
  },
  {
    key: "notifications",
    title: $t('sidebar.notifications.title'),
    icon: MailIcon,
    items: [
      {
        icon: MailIcon,
        title: $t('sidebar.notifications.all'),
        href: "/notifications",
        routeName: "notifications.index",
      },
    ],
  },
  {
    key: "documents",
    title: $t('sidebar.documents.title'),
    icon: FolderIcon,
    items: [
      {
        icon: FolderIcon,
        title: $t('sidebar.documents.all'),
        href: "/documents",
        routeName: "documents.index",
      },
      {
        icon: FolderIcon,
        title: $t('sidebar.documents.folders'),
        href: "/documents/folders",
        routeName: "documents.folders",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.documents.recent'),
        href: "/documents/recent",
        routeName: "documents.recent",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.documents.forms'),
        href: "/documents/forms",
        routeName: "documents.forms.index",
      },
      {
        icon: SettingsIcon,
        title: $t('sidebar.documents.custom_fields'),
        href: "/documents/custom-fields",
        routeName: "documents.custom-fields.index",
      },
    ],
  },
  {
    key: "sales",
    title: $t('sidebar.sales.title'),
    icon: DocsIcon,
    items: [
      {
        icon: BarChartIcon,
        title: $t('sidebar.sales.invoice_dashboard'),
        href: "/sales/invoice-dashboard",
        routeName: "sales.invoice-dashboard",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.sales.orders'),
        href: "/sales/orders",
        routeName: "sales.orders",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.sales.invoices'),
        href: "/sales/invoices",
        routeName: "sales.invoices",
      },
      {
        icon: UserCircleIcon,
        title: $t('sidebar.sales.customers'),
        href: "/sales/customers",
        routeName: "sales.customers",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.sales.credit_notes'),
        href: "/sales/credit-notes",
        routeName: "sales.credit-notes",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.sales.returns'),
        href: "/sales/returns",
        routeName: "sales.returns",
      },
    ],
  },
  {
    key: "pos",
    title: $t('sidebar.pos.title'),
    icon: BoxCubeIcon,
    items: [
      {
        icon: GridIcon,
        title: $t('sidebar.pos.terminal'),
        href: "/pos/terminal",
        routeName: "pos.terminal",
      },
      {
        icon: CalenderIcon,
        title: $t('sidebar.pos.sessions'),
        href: "/pos/sessions",
        routeName: "pos.sessions.index",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.pos.sales'),
        href: "/pos/sales",
        routeName: "pos.sales.index",
      },
    ],
  },
  {
    key: "purchase",
    title: $t('sidebar.purchase.title'),
    icon: DocsIcon,
    items: [
      {
        icon: DocsIcon,
        title: $t('sidebar.purchase.orders'),
        href: "/purchase/orders",
        routeName: "purchase.orders",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.purchase.receipts'),
        href: "/purchase/receipts",
        routeName: "purchase.receipts",
      },
      {
        icon: UserCircleIcon,
        title: $t('sidebar.purchase.suppliers'),
        href: "/purchase/suppliers",
        routeName: "purchase.suppliers",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.purchase.returns'),
        href: "/purchase/returns",
        routeName: "purchase.returns",
      },
    ],
  },
  {
    key: "inventory",
    title: $t('sidebar.inventory.title'),
    icon: BoxCubeIcon,
    items: [
      {
        icon: BoxCubeIcon,
        title: $t('sidebar.inventory.products'),
        href: "/inventory/products",
        routeName: "inventory.products",
      },
      {
        icon: BoxCubeIcon,
        title: $t('sidebar.inventory.stock'),
        href: "/inventory/stock",
        routeName: "inventory.stock",
      },
      {
        icon: BoxCubeIcon,
        title: $t('sidebar.inventory.warehouses'),
        href: "/inventory/warehouses",
        routeName: "inventory.warehouses",
      },
      {
        icon: BoxCubeIcon,
        title: $t('sidebar.inventory.transfers'),
        href: "/inventory/transfers",
        routeName: "inventory.transfers",
      },
      {
        icon: BoxCubeIcon,
        title: $t('sidebar.inventory.adjustments'),
        href: "/inventory/adjustments",
        routeName: "inventory.adjustments",
      },
    ],
  },
  {
    key: "accounting",
    title: $t('sidebar.accounting.title'),
    icon: PieChartIcon,
    items: [
      {
        icon: PieChartIcon,
        title: $t('sidebar.accounting.chart_of_accounts'),
        href: "/accounting/chart-of-accounts",
        routeName: "accounting.chart-of-accounts",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.accounting.journal_entries'),
        href: "/accounting/journal-entries",
        routeName: "accounting.journal-entries",
      },
      {
        icon: FolderIcon,
        title: $t('sidebar.accounting.cost_centers'),
        href: "/accounting/cost-centers",
        routeName: "accounting.cost-centers",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.accounting.trial_balance'),
        href: "/accounting/trial-balance",
        routeName: "accounting.trial-balance",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.accounting.profit_loss'),
        href: "/accounting/profit-loss",
        routeName: "accounting.profit-loss",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.accounting.balance_sheet'),
        href: "/accounting/balance-sheet",
        routeName: "accounting.balance-sheet",
      },
      {
        icon: SettingsIcon,
        title: $t('sidebar.accounting.lock_date'),
        href: "/accounting/lock-date",
        routeName: "accounting.lock-date",
      },
    ],
  },
  {
    key: "payments",
    title: $t('sidebar.payments.title'),
    icon: DocsIcon,
    items: [
      {
        icon: DocsIcon,
        title: $t('sidebar.payments.customer_payments'),
        href: "/payments",
        routeName: "payments.index",
      },
    ],
  },
  {
    key: "hr",
    title: $t('sidebar.hr.title'),
    icon: UserCircleIcon,
    items: [
      {
        icon: UserCircleIcon,
        title: $t('sidebar.hr.employees'),
        href: "/hr/employees",
        routeName: "hr.employees",
      },
      {
        icon: CalenderIcon,
        title: $t('sidebar.hr.attendance'),
        href: "/hr/attendance",
        routeName: "hr.attendance",
      },
      {
        icon: CalenderIcon,
        title: $t('sidebar.hr.leave'),
        href: "/hr/leave",
        routeName: "hr.leave",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.hr.payroll'),
        href: "/hr/payroll",
        routeName: "hr.payroll",
      },
      {
        icon: TaskIcon,
        title: $t('sidebar.hr.tasks'),
        href: "/hr/tasks/dashboard",
        routeName: "hr.tasks.dashboard",
      },
      {
        icon: CalenderIcon,
        title: $t('sidebar.hr.timesheet'),
        href: "/hr/timesheet",
        routeName: "hr.timesheet",
      },
      {
        icon: ChartBarIcon,
        title: $t('sidebar.hr.capacity'),
        href: "/hr/capacity",
        routeName: "hr.capacity.index",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.hr.skills'),
        href: "/hr/skills",
        routeName: "hr.skills.index",
      },
      {
        icon: CalenderIcon,
        title: $t('sidebar.hr.availability'),
        href: "/hr/availability",
        routeName: "hr.availability.calendar",
      },
      {
        icon: ChartBarIcon,
        title: $t('sidebar.hr.performance'),
        href: "/hr/performance",
        routeName: "hr.performance.index",
      },
    ],
  },
  {
    key: "projects",
    title: $t('sidebar.projects.title'),
    icon: FolderIcon,
    items: [
      {
        icon: FolderIcon,
        title: $t('sidebar.projects.projects'),
        href: "/projects",
        routeName: "projects.index",
      },
      {
        icon: TaskIcon,
        title: $t('sidebar.projects.tasks'),
        href: "/tasks",
        routeName: "tasks.index",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.projects.reports'),
        href: "/projects/reports",
        routeName: "projects.reports",
      },
    ],
  },
  {
    key: "crm",
    title: $t('sidebar.crm.title'),
    icon: UserCircleIcon,
    items: [
      {
        icon: UserCircleIcon,
        title: $t('sidebar.crm.contacts'),
        href: "/contacts",
        routeName: "contacts.index",
      },
      {
        icon: UserCircleIcon,
        title: $t('sidebar.crm.leads'),
        href: "/crm/leads",
        routeName: "crm.leads.index",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.crm.opportunities'),
        href: "/crm/opportunities",
        routeName: "crm.opportunities.index",
      },
      {
        icon: ListIcon,
        title: $t('sidebar.crm.pipelines'),
        href: "/crm/pipelines",
        routeName: "crm.pipelines.index",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.crm.activities'),
        href: "/crm/activities",
        routeName: "crm.activities.index",
      },
      {
        icon: UserCircleIcon,
        title: $t('sidebar.crm.crm_contacts'),
        href: "/crm/contacts",
        routeName: "crm.contacts.index",
      },
    ],
  },
  {
    key: "calendar",
    title: $t('sidebar.calendar.title'),
    icon: CalenderIcon,
    items: [
      {
        icon: CalenderIcon,
        title: $t('sidebar.calendar.view'),
        href: "/calendar",
        routeName: "calendar",
      },
    ],
  },
  {
    key: "cms",
    title: $t('sidebar.cms.title'),
    icon: PageIcon,
    items: [
      {
        icon: PageIcon,
        title: $t('sidebar.cms.sites'),
        href: "/cms/sites",
        routeName: "cms.sites",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.cms.pages'),
        href: "/cms/pages",
        routeName: "cms.pages",
      },
      {
        icon: ChatIcon,
        title: $t('sidebar.cms.blog'),
        href: "/cms/blog",
        routeName: "cms.blog",
      },
      {
        icon: ListIcon,
        title: $t('sidebar.cms.menus'),
        href: "/cms/menus",
        routeName: "cms.menus",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.cms.reviews'),
        href: "/cms/reviews",
        routeName: "cms.reviews.index",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.cms.wishlist'),
        href: "/cms/wishlist",
        routeName: "cms.wishlist.index",
      },
      {
        icon: SettingsIcon,
        title: $t('sidebar.cms.seo'),
        href: "/cms/seo",
        routeName: "cms.seo.index",
      },
      {
        icon: MailIcon,
        title: $t('sidebar.cms.contacts'),
        href: "/cms/contacts",
        routeName: "cms.contacts.index",
      },
    ],
  },
  {
    key: "reports",
    title: $t('sidebar.reports.title'),
    icon: BarChartIcon,
    items: [
      {
        icon: BarChartIcon,
        title: $t('sidebar.reports.dashboard'),
        href: "/reports/dashboard",
        routeName: "reports.dashboard",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.reports.trial_balance'),
        href: "/reports/financial/trial-balance",
        routeName: "reports.financial.trial-balance",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.reports.profit_loss'),
        href: "/reports/financial/profit-loss",
        routeName: "reports.financial.profit-loss",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.reports.balance_sheet'),
        href: "/reports/financial/balance-sheet",
        routeName: "reports.financial.balance-sheet",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.reports.cash_flow'),
        href: "/reports/financial/cash-flow",
        routeName: "reports.financial.cash-flow",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.reports.aging_ar'),
        href: "/reports/aging/accounts-receivable",
        routeName: "reports.aging.ar",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.reports.aging_ap'),
        href: "/reports/aging/accounts-payable",
        routeName: "reports.aging.ap",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.reports.inventory_valuation'),
        href: "/reports/inventory/valuation",
        routeName: "reports.inventory.valuation",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.reports.vat_liability'),
        href: "/reports/vat/liability",
        routeName: "reports.vat.liability",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.reports.mushak63'),
        href: "/reports/vat/mushak63",
        routeName: "reports.vat.mushak63",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.reports.yoy_profit_loss'),
        href: "/reports/comparative/yoy-profit-loss",
        routeName: "reports.comparative.yoy-profit-loss",
      },
      {
        icon: BarChartIcon,
        title: $t('sidebar.reports.builder'),
        href: "/reports/builder",
        routeName: "reports.builder.index",
      },
    ],
  },
  {
    key: "logistics",
    title: $t('sidebar.logistics.title'),
    icon: BoxIcon,
    items: [
      {
        icon: GridIcon,
        title: $t('sidebar.logistics.dashboard'),
        href: "/logistics/dashboard",
        routeName: "logistics.dashboard",
      },
      {
        icon: BoxIcon,
        title: $t('sidebar.logistics.shipments'),
        href: "/logistics/shipments",
        routeName: "logistics.shipments.index",
      },
      {
        icon: ListIcon,
        title: $t('sidebar.logistics.tracking'),
        href: "/logistics/tracking",
        routeName: "logistics.tracking.index",
      },
      {
        icon: ArchiveIcon,
        title: $t('sidebar.logistics.returns'),
        href: "/logistics/returns",
        routeName: "logistics.returns.index",
      },
      {
        icon: DocsIcon,
        title: $t('sidebar.logistics.cod'),
        href: "/logistics/cod",
        routeName: "logistics.cod.index",
      },
      {
        icon: SettingsIcon,
        title: $t('sidebar.logistics.carriers'),
        href: "/logistics/carriers",
        routeName: "logistics.carriers.index",
      },
    ],
  },
  {
    key: "integrations",
    title: $t('sidebar.integrations.title'),
    icon: PlugInIcon,
    items: [
      {
        icon: PlugInIcon,
        title: $t('sidebar.integrations.dashboard'),
        href: "/integrations",
        routeName: "integrations.index",
      },
    ],
  },
  {
    key: "subscription",
    title: $t('sidebar.subscription.title'),
    icon: CreditCardIcon,
    items: [
      {
        icon: CreditCardIcon,
        title: $t('sidebar.subscription.plans'),
        href: "/subscription/plans",
        routeName: "subscription.plans",
      },
      {
        icon: CreditCardIcon,
        title: $t('sidebar.subscription.manage'),
        href: "/subscription",
        routeName: "subscription.index",
      },
    ],
  },
  {
    key: "admin_subscription",
    title: $t('sidebar.admin_subscription.title'),
    icon: CreditCardIcon,
    items: [
      {
        icon: BarChartIcon,
        title: $t('sidebar.admin_subscription.dashboard'),
        href: "/admin/subscription/dashboard",
        routeName: "admin.subscription.dashboard",
      },
      {
        icon: UsersIcon,
        title: $t('sidebar.admin_subscription.subscriptions'),
        href: "/admin/subscription/subscriptions",
        routeName: "admin.subscription.subscriptions",
      },
      {
        icon: CreditCardIcon,
        title: $t('sidebar.admin_subscription.payment_requests'),
        href: "/admin/subscription/payment-requests",
        routeName: "admin.subscription.payment-requests",
      },
      {
        icon: FileTextIcon,
        title: $t('sidebar.admin_subscription.invoices'),
        href: "/admin/subscription/invoices",
        routeName: "admin.subscription.invoices",
      },
    ],
    show: computed(() => page.props.auth?.user?.is_superadmin),
  },
  {
    key: "settings",
    title: $t('sidebar.settings.title'),
    icon: SettingsIcon,
    items: [
      {
        icon: SettingsIcon,
        title: $t('sidebar.settings.company'),
        href: "/settings/company",
        routeName: "settings.company",
      },
      {
        icon: UserCircleIcon,
        title: $t('sidebar.settings.users'),
        href: "/settings/users",
        routeName: "settings.users",
      },
      {
        icon: SettingsIcon,
        title: $t('sidebar.settings.roles'),
        href: "/settings/roles",
        routeName: "settings.roles",
      },
      {
        icon: PlugInIcon,
        title: $t('sidebar.settings.integrations'),
        href: "/settings/integrations",
        routeName: "settings.integrations",
      },
      {
        icon: SettingsIcon,
        title: $t('sidebar.settings.workflows'),
        href: "/settings/workflows",
        routeName: "settings.workflows",
      },
    ],
  },
]);

const toggleGroupCollapse = (groupKey) => {
  if (collapsedGroups.value.has(groupKey)) {
    collapsedGroups.value.delete(groupKey);
  } else {
    collapsedGroups.value.add(groupKey);
  }
};

const isGroupCollapsed = (groupKey) => {
  return collapsedGroups.value.has(groupKey);
};
</script>