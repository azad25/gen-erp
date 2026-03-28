<template>
  
    
      <AppLayout>
        <div class="space-y-6">
          <!-- Header -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white">POS Dashboard</h1>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Monitor your point of sale performance and active sessions
              </p>
            </div>
            <div class="flex items-center gap-3">
              <select
                v-model="selectedPeriod"
                @change="handlePeriodChange"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm"
              >
                <option value="7d">Last 7 days</option>
                <option value="30d">Last 30 days</option>
                <option value="90d">Last 90 days</option>
              </select>
            </div>
          </div>

          <!-- Metrics Cards -->
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Revenue -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
                  <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ formatCurrency(metrics.totalRevenue) }}
                  </p>
                </div>
                <div class="rounded-full bg-green-100 dark:bg-green-900/20 p-3">
                  <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <div class="mt-4 flex items-center text-sm">
                <span :class="metrics.revenueDelta >= 0 ? 'text-green-600' : 'text-red-600'" class="font-medium">
                  {{ metrics.revenueDelta >= 0 ? '+' : '' }}{{ metrics.revenueDelta }}%
                </span>
                <span class="ml-2 text-gray-500 dark:text-gray-400">vs previous period</span>
              </div>
            </div>

            <!-- Total Sales -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Sales</p>
                  <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ metrics.totalSales }}
                  </p>
                </div>
                <div class="rounded-full bg-blue-100 dark:bg-blue-900/20 p-3">
                  <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                </div>
              </div>
              <div class="mt-4 flex items-center text-sm">
                <span :class="metrics.salesDelta >= 0 ? 'text-green-600' : 'text-red-600'" class="font-medium">
                  {{ metrics.salesDelta >= 0 ? '+' : '' }}{{ metrics.salesDelta }}%
                </span>
                <span class="ml-2 text-gray-500 dark:text-gray-400">vs previous period</span>
              </div>
            </div>

            <!-- Active Sessions -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Sessions</p>
                  <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ metrics.activeSessions }}
                  </p>
                </div>
                <div class="rounded-full bg-purple-100 dark:bg-purple-900/20 p-3">
                  <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <div class="mt-4 flex items-center text-sm">
                <span class="text-gray-500 dark:text-gray-400">Currently open</span>
              </div>
            </div>

            <!-- Avg Transaction -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Transaction</p>
                  <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ formatCurrency(metrics.avgTransaction) }}
                  </p>
                </div>
                <div class="rounded-full bg-orange-100 dark:bg-orange-900/20 p-3">
                  <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>
              <div class="mt-4 flex items-center text-sm">
                <span :class="metrics.avgTransactionDelta >= 0 ? 'text-green-600' : 'text-red-600'" class="font-medium">
                  {{ metrics.avgTransactionDelta >= 0 ? '+' : '' }}{{ metrics.avgTransactionDelta }}%
                </span>
                <span class="ml-2 text-gray-500 dark:text-gray-400">vs previous period</span>
              </div>
            </div>
          </div>

          <!-- Charts Row -->
          <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Revenue Trend -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Revenue Trend</h3>
              <div class="h-64">
                <!-- Chart placeholder - integrate with your chart library -->
                <div class="flex items-center justify-center h-full text-gray-400">
                  Revenue chart will be displayed here
                </div>
              </div>
            </div>

            <!-- Top Products -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Selling Products</h3>
              <div class="space-y-4">
                <div
                  v-for="product in topProducts"
                  :key="product.id"
                  class="flex items-center justify-between"
                >
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ product.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ product.sales }} units sold</p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      {{ formatCurrency(product.revenue) }}
                    </p>
                  </div>
                </div>
                <div v-if="topProducts.length === 0" class="text-center py-8 text-gray-400">
                  No sales data available
                </div>
              </div>
            </div>
          </div>

          <!-- Active Sessions & Recent Sales -->
          <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Active Sessions -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Active Sessions</h3>
              <div class="space-y-4">
                <div
                  v-for="session in activeSessions"
                  :key="session.id"
                  class="flex items-center justify-between p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50"
                >
                  <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ session.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Cashier: {{ session.cashier }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      Opened: {{ formatDate(session.openedAt) }}
                    </p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      {{ formatCurrency(session.currentSales) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Current sales</p>
                  </div>
                </div>
                <div v-if="activeSessions.length === 0" class="text-center py-8 text-gray-400">
                  No active sessions
                </div>
              </div>
            </div>

            <!-- Recent Sales -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Sales</h3>
              <div class="space-y-3">
                <div
                  v-for="sale in recentSales"
                  :key="sale.id"
                  class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700 last:border-0"
                >
                  <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ sale.receiptNumber }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ sale.paymentMethod }} • {{ formatDate(sale.date) }}
                    </p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                      {{ formatCurrency(sale.amount) }}
                    </p>
                    <span
                      :class="{
                        'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': sale.status === 'Completed',
                        'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': sale.status === 'Voided',
                      }"
                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                    >
                      {{ sale.status }}
                    </span>
                  </div>
                </div>
                <div v-if="recentSales.length === 0" class="text-center py-8 text-gray-400">
                  No recent sales
                </div>
              </div>
            </div>
          </div>
        </div>
      </AppLayout>
    
  
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue"
import { formatCurrency, formatDate } from '@/utils/formatters'

const props = defineProps({
  metrics: Object,
  chartData: Object,
  topProducts: Array,
  recentSales: Array,
  activeSessions: Array,
  hourlyPerformance: Object,
})

const selectedPeriod = ref('30d')

const handlePeriodChange = () => {
  router.get(route('pos.dashboard'), { period: selectedPeriod.value }, {
    preserveState: true,
    preserveScroll: true,
  })
}
</script>
