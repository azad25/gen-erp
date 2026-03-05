<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <!-- Page Header -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-black dark:text-white">
                Invoice Dashboard
              </h1>
              <p class="text-sm text-gray-1 dark:text-gray-400">
                Monitor your invoices and payment status
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
                New Invoice
              </Button>
            </div>
          </div>

          <!-- Key Metrics Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
              label="Total Invoices"
              :value="metrics.totalInvoices"
              subtitle="This month"
              :delta="metrics.invoicesDelta"
              color="teal"
              :sparkline="metrics.invoicesSparkline"
            >
              <template #icon>📄</template>
            </StatCard>
            
            <StatCard
              label="Pending Invoices"
              :value="metrics.pendingInvoices"
              subtitle="Awaiting payment"
              color="amber"
            >
              <template #icon>⏳</template>
            </StatCard>
            
            <StatCard
              label="Overdue Invoices"
              :value="metrics.overdueInvoices"
              subtitle="Past due date"
              color="red"
            >
              <template #icon>⚠️</template>
            </StatCard>
            
            <StatCard
              label="Total Outstanding"
              :value="metrics.totalOutstanding"
              subtitle="Unpaid amount"
              is-currency
              color="orange"
            >
              <template #icon>💰</template>
            </StatCard>
          </div>

          <!-- Charts Row -->
          <div class="grid gap-6 lg:grid-cols-3">
            <!-- Invoice Trend Chart -->
            <div class="lg:col-span-2">
              <Card>
                <template #header>
                  <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-black dark:text-white">
                      Invoice Revenue Trend
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

            <!-- Invoice Status Breakdown -->
            <Card>
              <template #header>
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Invoice Status
                </h3>
              </template>
              <div class="space-y-4">
                <div 
                  v-for="status in invoiceStatus" 
                  :key="status.name"
                  class="flex items-center justify-between"
                >
                  <div class="flex items-center gap-3">
                    <div 
                      class="w-3 h-3 rounded-full"
                      :class="getStatusColor(status.name)"
                    ></div>
                    <span class="text-sm font-medium text-black dark:text-white">{{ status.name }}</span>
                  </div>
                  <div class="text-right">
                    <span class="font-semibold text-black dark:text-white">{{ status.count }}</span>
                    <span class="text-xs text-gray-1 ml-2">({{ status.percentage }}%)</span>
                  </div>
                </div>
              </div>
            </Card>
          </div>

          <!-- Invoice Aging & Recent Invoices -->
          <div class="grid gap-6 lg:grid-cols-2">
            <!-- Invoice Aging -->
            <Card>
              <template #header>
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Invoice Aging
                </h3>
              </template>
              <div class="space-y-4">
                <div 
                  v-for="bucket in agingBuckets" 
                  :key="bucket.name"
                  class="relative"
                >
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-black dark:text-white">{{ bucket.name }}</span>
                    <span class="text-sm text-gray-1">{{ bucket.count }} ({{ bucket.percentage }}%)</span>
                  </div>
                  <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div 
                      class="h-2 rounded-full transition-all duration-500"
                      :class="getAgingColor(bucket.days)"
                      :style="{ width: `${bucket.percentage}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </Card>

            <!-- Recent Invoices -->
            <Card>
              <template #header>
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-black dark:text-white">
                    Recent Invoices
                  </h3>
                  <Link 
                    href="/sales/invoices" 
                    class="text-sm text-primary hover:text-primary-dark font-medium"
                  >
                    View All
                  </Link>
                </div>
              </template>
              <div class="space-y-3">
                <div 
                  v-for="invoice in recentInvoices" 
                  :key="invoice.id"
                  class="flex items-center justify-between p-3 rounded-lg border border-stroke dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                >
                  <div>
                    <p class="font-medium text-black dark:text-white">{{ invoice.invoice_number }}</p>
                    <p class="text-sm text-gray-1">{{ invoice.customer }}</p>
                    <p class="text-xs text-gray-1">{{ formatDate(invoice.invoice_date) }}</p>
                  </div>
                  <div class="text-right">
                    <p class="font-semibold text-black dark:text-white">
                      <span class="font-bangla">৳</span>{{ formatCurrency(invoice.total_amount) }}
                    </p>
                    <Badge :variant="getStatusVariant(invoice.status)">
                      {{ invoice.status }}
                    </Badge>
                  </div>
                </div>
              </div>
            </Card>
          </div>

          <!-- Collection Status & Top Customers -->
          <div class="grid gap-6 lg:grid-cols-2">
            <!-- Collection Status -->
            <Card>
              <template #header>
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Collection Status
                </h3>
              </template>
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div class="text-2xl font-bold text-success">{{ collectionStatus.collected }}</div>
                  <div class="text-sm text-gray-1">Collected</div>
                  <div class="text-xs text-gray-1 mt-1">{{ collectionStatus.collectedPercent }}%</div>
                </div>
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div class="text-2xl font-bold text-primary">{{ collectionStatus.pending }}</div>
                  <div class="text-sm text-gray-1">Pending</div>
                  <div class="text-xs text-gray-1 mt-1">{{ collectionStatus.pendingPercent }}%</div>
                </div>
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div class="text-2xl font-bold text-warning">{{ collectionStatus.overdue }}</div>
                  <div class="text-sm text-gray-1">Overdue</div>
                  <div class="text-xs text-gray-1 mt-1">{{ collectionStatus.overduePercent }}%</div>
                </div>
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div class="text-2xl font-bold text-danger">{{ collectionStatus.badDebt }}</div>
                  <div class="text-sm text-gray-1">Bad Debt</div>
                  <div class="text-xs text-gray-1 mt-1">{{ collectionStatus.badDebtPercent }}%</div>
                </div>
              </div>
            </Card>

            <!-- Top Customers by Invoice Value -->
            <Card>
              <template #header>
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Top Customers by Invoice Value
                </h3>
              </template>
              <div class="space-y-4">
                <div 
                  v-for="(customer, index) in topCustomers" 
                  :key="customer.id"
                  class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold text-sm">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <p class="font-medium text-black dark:text-white">{{ customer.name }}</p>
                      <p class="text-xs text-gray-1">{{ customer.invoice_count }} invoices</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="font-semibold text-black dark:text-white">
                      <span class="font-bangla">৳</span>{{ formatCurrency(customer.total_value) }}
                    </p>
                  </div>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import StatCard from '@/Components/UI/StatCard.vue'
import Card from '@/Components/UI/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import AreaChart from '@/Components/Charts/AreaChart.vue'

// Props from backend
const props = defineProps({
  metrics: Object,
  chartData: Object,
  invoiceStatus: Array,
  agingBuckets: Array,
  recentInvoices: Array,
  collectionStatus: Object,
  topCustomers: Array,
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
    'draft': 'secondary',
    'sent': 'default',
    'paid': 'success',
    'partial': 'warning',
    'overdue': 'danger'
  }
  return variants[status.toLowerCase()] || 'secondary'
}

const getStatusColor = (status) => {
  const colors = {
    'Draft': 'bg-gray-400',
    'Sent': 'bg-blue-400',
    'Paid': 'bg-green-400',
    'Partial': 'bg-yellow-400',
    'Overdue': 'bg-red-400'
  }
  return colors[status] || 'bg-gray-400'
}

const getAgingColor = (days) => {
  if (days <= 30) return 'bg-green-400'
  if (days <= 60) return 'bg-yellow-400'
  if (days <= 90) return 'bg-orange-400'
  return 'bg-red-400'
}
</script>
