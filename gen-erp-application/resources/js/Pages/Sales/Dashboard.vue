<template>
  <SidebarProvider>
    <AppLayout>
      <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">
              Sales Dashboard
            </h1>
            <p class="text-sm text-gray-1 dark:text-gray-400">
              Monitor your sales performance and key metrics
            </p>
          </div>
          <div class="flex items-center gap-3">
            <Button variant="secondary" size="sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export Report
            </Button>
            <Button size="sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              New Sale
            </Button>
          </div>
        </div>

        <!-- Key Metrics Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard
            label="Total Revenue"
            :value="metrics.totalRevenue"
            subtitle="This month"
            :delta="metrics.revenueDelta"
            is-currency
            color="teal"
            :sparkline="metrics.revenueSparkline"
          >
            <template #icon>💰</template>
          </StatCard>
          
          <StatCard
            label="Sales Orders"
            :value="metrics.totalOrders"
            subtitle="This month"
            :delta="metrics.ordersDelta"
            color="green"
            :sparkline="metrics.ordersSparkline"
          >
            <template #icon>📋</template>
          </StatCard>
          
          <StatCard
            label="Outstanding"
            :value="metrics.outstanding"
            subtitle="Unpaid invoices"
            is-currency
            color="amber"
          >
            <template #icon>⏳</template>
          </StatCard>
          
          <StatCard
            label="Conversion Rate"
            :value="`${metrics.conversionRate}%`"
            subtitle="Leads to sales"
            :delta="metrics.conversionDelta"
            color="teal"
          >
            <template #icon>📈</template>
          </StatCard>
        </div>

        <!-- Charts Row -->
        <div class="grid gap-6 lg:grid-cols-3">
          <!-- Revenue Trend Chart -->
          <div class="lg:col-span-2">
            <Card>
              <template #header>
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-black dark:text-white">
                    Sales Revenue Trend
                  </h3>
                  <div class="flex gap-1">
                    <button 
                      v-for="period in ['7d', '30d', '90d']" 
                      :key="period"
                      @click="selectedPeriod = period"
                      :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-lg transition-colors',
                        selectedPeriod === period 
                          ? 'bg-primary text-white' 
                          : 'text-gray-1 hover:bg-gray-3 dark:hover:bg-gray-800'
                      ]"
                    >
                      {{ period }}
                    </button>
                  </div>
                </div>
              </template>
              <AreaChart 
                :series="[{name: 'Revenue', data: chartData.revenue}]" 
                :categories="chartData.labels" 
                :height="320" 
              />
            </Card>
          </div>

          <!-- Top Products -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Top Selling Products
              </h3>
            </template>
            <div class="space-y-4">
              <div 
                v-for="(product, index) in topProducts" 
                :key="product.id"
                class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold text-sm">
                    {{ index + 1 }}
                  </div>
                  <div>
                    <p class="font-medium text-black dark:text-white">{{ product.name }}</p>
                    <p class="text-xs text-gray-1">{{ product.sales }} sold</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-black dark:text-white">
                    <span class="font-bangla">৳</span>{{ formatCurrency(product.revenue) }}
                  </p>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Sales Performance & Recent Orders -->
        <div class="grid gap-6 lg:grid-cols-2">
          <!-- Sales Team Performance -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Sales Team Performance
              </h3>
            </template>
            <div class="space-y-4">
              <div 
                v-for="member in salesTeam" 
                :key="member.id"
                class="flex items-center justify-between"
              >
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-gradient-to-r from-primary to-teal-400 flex items-center justify-center text-white font-semibold">
                    {{ member.name.charAt(0) }}
                  </div>
                  <div>
                    <p class="font-medium text-black dark:text-white">{{ member.name }}</p>
                    <p class="text-xs text-gray-1">{{ member.role }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-black dark:text-white">
                    <span class="font-bangla">৳</span>{{ formatCurrency(member.sales) }}
                  </p>
                  <div class="flex items-center gap-1 mt-1">
                    <div class="w-16 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div 
                        class="h-full bg-primary rounded-full transition-all"
                        :style="{ width: `${member.progress}%` }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-1">{{ member.progress }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Recent Sales Orders -->
          <Card>
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Recent Sales Orders
                </h3>
                <Link 
                  href="/sales/orders" 
                  class="text-sm text-primary hover:text-primary-dark font-medium"
                >
                  View All
                </Link>
              </div>
            </template>
            <div class="space-y-3">
              <div 
                v-for="order in recentOrders" 
                :key="order.id"
                class="flex items-center justify-between p-3 rounded-lg border border-stroke dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
              >
                <div>
                  <p class="font-medium text-black dark:text-white">{{ order.orderNumber }}</p>
                  <p class="text-sm text-gray-1">{{ order.customer }}</p>
                  <p class="text-xs text-gray-1">{{ formatDate(order.date) }}</p>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-black dark:text-white">
                    <span class="font-bangla">৳</span>{{ formatCurrency(order.amount) }}
                  </p>
                  <Badge :variant="getStatusVariant(order.status)">
                    {{ order.status }}
                  </Badge>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Sales Funnel & Customer Insights -->
        <div class="grid gap-6 lg:grid-cols-2">
          <!-- Sales Funnel -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Sales Funnel
              </h3>
            </template>
            <div class="space-y-4">
              <div 
                v-for="(stage, index) in salesFunnel" 
                :key="stage.name"
                class="relative"
              >
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-medium text-black dark:text-white">{{ stage.name }}</span>
                  <span class="text-sm text-gray-1">{{ stage.count }} ({{ stage.percentage }}%)</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div 
                    class="h-2 rounded-full transition-all duration-500"
                    :class="getFunnelColor(index)"
                    :style="{ width: `${stage.percentage}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Customer Insights -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Customer Insights
              </h3>
            </template>
            <div class="grid grid-cols-2 gap-4">
              <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                <div class="text-2xl font-bold text-primary">{{ customerInsights.newCustomers }}</div>
                <div class="text-sm text-gray-1">New Customers</div>
                <div class="text-xs text-success mt-1">+{{ customerInsights.newCustomersDelta }}%</div>
              </div>
              <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                <div class="text-2xl font-bold text-primary">{{ customerInsights.repeatCustomers }}</div>
                <div class="text-sm text-gray-1">Repeat Customers</div>
                <div class="text-xs text-success mt-1">+{{ customerInsights.repeatCustomersDelta }}%</div>
              </div>
              <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                <div class="text-2xl font-bold text-primary">
                  <span class="font-bangla">৳</span>{{ formatCurrency(customerInsights.avgOrderValue) }}
                </div>
                <div class="text-sm text-gray-1">Avg Order Value</div>
                <div class="text-xs text-warning mt-1">{{ customerInsights.avgOrderValueDelta }}%</div>
              </div>
              <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                <div class="text-2xl font-bold text-primary">{{ customerInsights.customerLifetime }}</div>
                <div class="text-sm text-gray-1">Avg Lifetime (days)</div>
                <div class="text-xs text-success mt-1">+{{ customerInsights.customerLifetimeDelta }}%</div>
              </div>
            </div>
          </Card>
        </div>
      </div>
    </AppLayout>
  </SidebarProvider>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import StatCard from '@/Components/UI/StatCard.vue'
import Card from '@/Components/UI/Card.vue'
import Button from '@/Components/UI/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import AreaChart from '@/Components/Charts/AreaChart.vue'

// Props from backend
const props = defineProps({
  metrics: Object,
  chartData: Object,
  topProducts: Array,
  salesTeam: Array,
  recentOrders: Array,
  salesFunnel: Array,
  customerInsights: Object,
})

// Reactive state
const selectedPeriod = ref('30d')

// Helper functions
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-BD', {
    maximumFractionDigits: 0
  }).format(amount / 100)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-BD', {
    month: 'short',
    day: 'numeric'
  })
}

const getStatusVariant = (status) => {
  const variants = {
    'confirmed': 'success',
    'pending': 'warning',
    'draft': 'secondary',
    'cancelled': 'danger'
  }
  return variants[status.toLowerCase()] || 'secondary'
}

const getFunnelColor = (index) => {
  const colors = [
    'bg-primary',
    'bg-teal-500',
    'bg-teal-400',
    'bg-teal-300',
    'bg-teal-200'
  ]
  return colors[index] || 'bg-gray-400'
}
</script>