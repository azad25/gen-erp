<template>
  <aside
    :class="[
      'fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 dark:text-gray-100 h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200',
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
        <HomeLogo class="w-10 h-10" />
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
        <div class="flex flex-col gap-4">
          <div v-for="(menuGroup, groupIndex) in menuGroups" :key="groupIndex">
            <h2
              :class="[
                'text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 px-4',
                !isExpanded && !isHovered ? 'lg:hidden' : 'lg:block',
              ]"
            >
              {{ menuGroup.title }}
            </h2>
            <ul class="flex flex-col gap-1">
              <li v-for="(item, index) in menuGroup.items" :key="item.name">
                <button
                  v-if="item.subItems"
                  @click="toggleSubmenu(groupIndex, index)"
                  :class="[
                    'menu-item group w-full flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                    isActive(item.path)
                      ? 'bg-primary/10 text-primary dark:bg-primary/20 dark:text-teal-400'
                      : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100',
                    !isExpanded && !isHovered
                      ? 'lg:justify-center lg:px-2'
                      : 'lg:justify-start lg:px-4',
                  ]"
                >
                  <span
                    :class="[
                      'w-5 h-5 flex-shrink-0',
                      isActive(item.path)
                        ? 'text-primary dark:text-teal-400'
                        : 'text-gray-500 dark:text-gray-400',
                    ]"
                  >
                    <component :is="item.icon" />
                  </span>
                  <span
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="text-sm font-medium"
                    >{{ item.name }}</span
                  >
                  <ChevronDownIcon
                    v-if="isExpanded || isHovered || isMobileOpen"
                    :class="[
                      'ml-auto w-5 h-5 transition-transform duration-200 text-gray-400',
                      {
                        'rotate-180': isSubmenuOpen(groupIndex, index),
                      },
                    ]"
                  />
                </button>
                <Link
                  v-else-if="item.path"
                  :href="item.path"
                  :class="[
                    'menu-item group flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200',
                    isActive(item.path)
                      ? 'bg-primary/10 text-primary dark:bg-primary/20 dark:text-teal-400'
                      : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100',
                    !isExpanded && !isHovered
                      ? 'lg:justify-center lg:px-2'
                      : 'lg:justify-start lg:px-4',
                  ]"
                >
                  <span
                    :class="[
                      'w-5 h-5 flex-shrink-0',
                      isActive(item.path)
                        ? 'text-primary dark:text-teal-400'
                        : 'text-gray-500 dark:text-gray-400',
                    ]"
                  >
                    <component :is="item.icon" />
                  </span>
                  <span
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="text-sm font-medium"
                    >{{ item.name }}</span
                  >
                </Link>
                <transition
                  @enter="startTransition"
                  @after-enter="endTransition"
                  @before-leave="startTransition"
                  @after-leave="endTransition"
                >
                  <div
                    v-show="
                      isSubmenuOpen(groupIndex, index) &&
                      (isExpanded || isHovered || isMobileOpen)
                    "
                  >
                    <ul class="mt-2 space-y-1 ml-9">
                      <li v-for="subItem in item.subItems" :key="subItem.name">
                        <Link
                          :href="subItem.path"
                          :class="[
                            'menu-dropdown-item flex items-center gap-2 px-4 py-2 rounded-lg text-sm transition-all duration-200',
                            isActive(subItem.path)
                              ? 'bg-primary/10 text-primary dark:bg-primary/20 dark:text-teal-400'
                              : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100',
                          ]"
                        >
                          {{ subItem.name }}
                          <span class="flex items-center gap-1 ml-auto">
                            <span
                              v-if="subItem.new"
                              :class="[
                                'menu-dropdown-badge',
                                {
                                  'menu-dropdown-badge-active': isActive(
                                    subItem.path
                                  ),
                                  'menu-dropdown-badge-inactive': !isActive(
                                    subItem.path
                                  ),
                                },
                              ]"
                            >
                              new
                            </span>
                            <span
                              v-if="subItem.pro"
                              :class="[
                                'menu-dropdown-badge',
                                {
                                  'menu-dropdown-badge-active': isActive(
                                    subItem.path
                                  ),
                                  'menu-dropdown-badge-inactive': !isActive(
                                    subItem.path
                                  ),
                                },
                              ]"
                            >
                              pro
                            </span>
                          </span>
                        </Link>
                      </li>
                    </ul>
                  </div>
                </transition>
              </li>
            </ul>
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
} from "../../icons";
import SidebarWidget from "./SidebarWidget.vue";
import BoxCubeIcon from "@/icons/BoxCubeIcon.vue";
import HomeLogo from "@/Components/Home/Logo.vue";
import CompanySwitcher from "./CompanySwitcher.vue";
import { useSidebar } from "@/composables/useSidebar";

const page = usePage();
const route = computed(() => page.props.url || window.location.pathname);

const { isExpanded, isMobileOpen, isHovered, openSubmenu } = useSidebar();

const menuGroups = [
  {
    title: "Main",
    items: [
      {
        icon: GridIcon,
        name: "Dashboard",
        path: "/dashboard",
      },
    ],
  },
  {
    title: "Sales",
    items: [
      {
        icon: DocsIcon,
        name: "Sales Orders",
        path: "/sales/orders",
      },
      {
        icon: DocsIcon,
        name: "Invoices",
        path: "/sales/invoices",
      },
      {
        icon: UserCircleIcon,
        name: "Customers",
        path: "/sales/customers",
      },
      {
        icon: DocsIcon,
        name: "Credit Notes",
        path: "/sales/credit-notes",
      },
      {
        icon: DocsIcon,
        name: "Returns",
        path: "/sales/returns",
      },
    ],
  },
  {
    title: "Purchase",
    items: [
      {
        icon: DocsIcon,
        name: "Purchase Orders",
        path: "/purchase/orders",
      },
      {
        icon: DocsIcon,
        name: "Goods Receipts",
        path: "/purchase/receipts",
      },
      {
        icon: UserCircleIcon,
        name: "Suppliers",
        path: "/purchase/suppliers",
      },
      {
        icon: DocsIcon,
        name: "Returns",
        path: "/purchase/returns",
      },
    ],
  },
  {
    title: "Inventory",
    items: [
      {
        icon: BoxCubeIcon,
        name: "Products",
        path: "/inventory/products",
      },
      {
        icon: BoxCubeIcon,
        name: "Stock",
        path: "/inventory/stock",
      },
      {
        icon: BoxCubeIcon,
        name: "Warehouses",
        path: "/inventory/warehouses",
      },
      {
        icon: BoxCubeIcon,
        name: "Transfers",
        path: "/inventory/transfers",
      },
      {
        icon: BoxCubeIcon,
        name: "Adjustments",
        path: "/inventory/adjustments",
      },
    ],
  },
  {
    title: "Accounting",
    items: [
      {
        icon: PieChartIcon,
        name: "Chart of Accounts",
        path: "/accounting/chart-of-accounts",
      },
      {
        icon: DocsIcon,
        name: "Journal Entries",
        path: "/accounting/journal-entries",
      },
      {
        icon: PieChartIcon,
        name: "Trial Balance",
        path: "/accounting/trial-balance",
      },
      {
        icon: PieChartIcon,
        name: "Profit & Loss",
        path: "/accounting/profit-loss",
      },
      {
        icon: PieChartIcon,
        name: "Balance Sheet",
        path: "/accounting/balance-sheet",
      },
    ],
  },
  {
    title: "HR & Payroll",
    items: [
      {
        icon: UserCircleIcon,
        name: "Employees",
        path: "/hr/employees",
      },
      {
        icon: CalenderIcon,
        name: "Attendance",
        path: "/hr/attendance",
      },
      {
        icon: CalenderIcon,
        name: "Leave",
        path: "/hr/leave",
      },
      {
        icon: DocsIcon,
        name: "Payroll",
        path: "/hr/payroll",
      },
    ],
  },
  {
    title: "POS",
    items: [
      {
        icon: BoxCubeIcon,
        name: "POS Session",
        path: "/pos/session",
      },
    ],
  },
  {
    title: "Settings",
    items: [
      {
        icon: SettingsIcon,
        name: "Company",
        path: "/settings/company",
      },
      {
        icon: UserCircleIcon,
        name: "Profile",
        path: "/profile",
      },
      {
        icon: SettingsIcon,
        name: "Users",
        path: "/settings/users",
      },
      {
        icon: SettingsIcon,
        name: "Roles & Permissions",
        path: "/settings/roles",
      },
      {
        icon: SettingsIcon,
        name: "Workflows",
        path: "/settings/workflows",
      },
      {
        icon: SettingsIcon,
        name: "Integrations",
        path: "/settings/integrations",
      },
    ],
  },
]

const isActive = (path) => {
  const currentPath = route.value
  return currentPath === path
}

const toggleSubmenu = (groupIndex, itemIndex) => {
  const key = `${groupIndex}-${itemIndex}`
  openSubmenu.value = openSubmenu.value === key ? null : key
}

const isAnySubmenuRouteActive = computed(() => {
  return menuGroups.some((group) =>
    group.items.some(
      (item) =>
        item.subItems && item.subItems.some((subItem) => isActive(subItem.path))
    )
  )
})

const isSubmenuOpen = (groupIndex, itemIndex) => {
  const key = `${groupIndex}-${itemIndex}`
  return (
    openSubmenu.value === key ||
    (isAnySubmenuRouteActive.value &&
      menuGroups[groupIndex].items[itemIndex].subItems?.some((subItem) =>
        isActive(subItem.path)
      ))
  )
}

const startTransition = (el) => {
  el.style.height = "auto"
  const height = el.scrollHeight
  el.style.height = "0px"
  el.offsetHeight // force reflow
  el.style.height = height + "px"
}

const endTransition = (el) => {
  el.style.height = ""
}
</script>
