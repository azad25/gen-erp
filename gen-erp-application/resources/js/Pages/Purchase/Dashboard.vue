<template>
  
    <AppLayout>
      <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">
              Purchase Dashboard
            </h1>
            <p class="text-sm text-gray-1 dark:text-gray-400">
              Monitor procurement activities and supplier performance
            </p>
          </div>
          <div class="flex items-center gap-3">
            <Button variant="secondary" size="sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Purchase Report
            </Button>
            <Button size="sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              New Purchase Order
            </Button>
          </div>
        </div>

        <!-- Key Metrics Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard
            label="Total Purchases"
            :value="metrics.totalPurchases"
            subtitle="This month"
            :delta="metrics.purchasesDelta"
            is-currency
            color="teal"
            :sparkline="metrics.purchasesSparkline"
          >
            <template #icon>🛒</template>
          </StatCard>
          
          <StatCard
            label="Purchase Orders"
            :value="metrics.totalOrders"
            subtitle="This month"
            :delta="metrics.ordersDelta"
            color="green"
            :sparkline="metrics.ordersSparkline"
          >
            <template #icon>📋</template>
          </StatCard>
          
          <StatCard
            label="Pending Approvals"
            :value="metrics.pendingApprovals"
            subtitle="Awaiting approval"
            color="amber"
          >
            <template #icon>⏳</template>
          </StatCard>
          
          <StatCard
            label="Cost Savings"
            :value="metrics.costSavings"
            subtitle="vs last month"
            :delta="metrics.savingsDelta"
            is-currency
            color="green"
          >
            <template #icon>💰</template>
          </StatCard>
        </div>

        <!-- Charts Row -->
        <div class="grid gap-6 lg:grid-cols-3">
          <!-- Purchase Trend Chart -->
          <div class="lg:col-span-2">
            <Card>
              <template #header>
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-black dark:text-white">
                    Purchase Spending Trend
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
                :series="[{name: 'Purchases', data: chartData.purchases}]" 
                :categories="chartData.labels" 
                :height="320" 
              />
            </Card>
          </div>

          <!-- Top Suppliers -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Top Suppliers
              </h3>
            </template>
            <div class="space-y-4">
              <div 
                v-for="(supplier, index) in topSuppliers" 
                :key="supplier.id"
                class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold text-sm">
                    {{ index + 1 }}
                  </div>
                  <div>
                    <p class="font-medium text-black dark:text-white">{{ supplier.name }}</p>
                    <p class="text-xs text-gray-1">{{ supplier.orders }} orders</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-black dark:text-white">
                    <span class="font-bangla">৳</span>{{ formatCurrency(supplier.amount) }}
                  </p>
                  <div class="flex items-center gap-1 mt-1">
                    <div class="w-2 h-2 rounded-full" :class="getSupplierStatusColor(supplier.rating)"></div>
                    <span class="text-xs text-gray-1">{{ supplier.rating }}/5</span>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Purchase Orders & Inventory Status -->
        <div class="grid gap-6 lg:grid-cols-2">
          <!-- Recent Purchase Orders -->
          <Card>
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Recent Purchase Orders
                </h3>
                <Link 
                  href="/purchase/orders" 
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
                  <p class="text-sm text-gray-1">{{ order.supplier }}</p>
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

          <!-- Inventory Status -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Inventory Status
              </h3>
            </template>
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div class="text-2xl font-bold text-danger">{{ inventoryStatus.lowStock }}</div>
                  <div class="text-sm text-gray-1">Low Stock Items</div>
                </div>
                <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div class="text-2xl font-bold text-warning">{{ inventoryStatus.outOfStock }}</div>
                  <div class="text-sm text-gray-1">Out of Stock</div>
                </div>
              </div>
              
              <div class="space-y-3">
                <h4 class="font-medium text-black dark:text-white">Critical Items</h4>
                <div 
                  v-for="item in inventoryStatus.criticalItems" 
                  :key="item.id"
                  class="flex items-center justify-between p-2 rounded bg-red-50 dark:bg-red-900/20"
                >
                  <div>
                    <p class="text-sm font-medium text-black dark:text-white">{{ item.name }}</p>
                    <p class="text-xs text-gray-1">{{ item.category }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-semibold text-danger">{{ item.stock }} left</p>
                    <p class="text-xs text-gray-1">Min: {{ item.minStock }}</p>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Purchase Categories & Approval Workflow -->
        <div class="grid gap-6 lg:grid-cols-2">
          <!-- Purchase by Category -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Purchase by Category
              </h3>
            </template>
            <div class="space-y-4">
              <div 
                v-for="category in purchaseCategories" 
                :key="category.name"
                class="flex items-center justify-between"
              >
                <div class="flex items-center gap-3">
                  <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: category.color }"></div>
                  <span class="text-sm font-medium text-black dark:text-white">{{ category.name }}</span>
                </div>
                <div class="text-right">
                  <p class="text-sm font-semibold text-black dark:text-white">
                    <span class="font-bangla">৳</span>{{ formatCurrency(category.amount) }}
                  </p>
                  <p class="text-xs text-gray-1">{{ category.percentage }}%</p>
                </div>
              </div>
            </div>
          </Card>

          <!-- Approval Workflow -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Approval Workflow
              </h3>
            </template>
            <div class="space-y-4">
              <div 
                v-for="(stage, index) in approvalWorkflow" 
                :key="stage.name"
                class="flex items-center gap-4"
              >
                <div class="flex flex-col items-center">
                  <div 
                    :class="[
                      'w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold',
                      stage.completed ? 'bg-success text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'
                    ]"
                  >
                    {{ index + 1 }}
                  </div>
                  <div 
                    v-if="index < approvalWorkflow.length - 1"
                    :class="[
                      'w-0.5 h-8 mt-2',
                      stage.completed ? 'bg-success' : 'bg-gray-200 dark:bg-gray-700'
                    ]"
                  ></div>
                </div>
                <div class="flex-1">
                  <p class="font-medium text-black dark:text-white">{{ stage.name }}</p>
                  <p class="text-sm text-gray-1">{{ stage.description }}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-gray-1">{{ stage.pending }} pending</span>
                    <div class="w-16 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div 
                        class="h-full bg-primary rounded-full transition-all"
                        :style="{ width: `${stage.progress}%` }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </div>
    </AppLayout>
  
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/UI/StatCard.vue'
import Card from '@/Components/UI/Card.vue'
import Button from '@/Components/UI/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import AreaChart from '@/Components/Charts/AreaChart.vue'

// Props from backend
const props = defineProps({
  metrics: Object,
  chartData: Object,
  topSuppliers: Array,
  recentOrders: Array,
  inventoryStatus: Object,
  purchaseCategories: Array,
  approvalWorkflow: Array,
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
    'approved': 'success',
    'pending': 'warning',
    'draft': 'secondary',
    'rejected': 'danger',
    'received': 'success'
  }
  return variants[status.toLowerCase()] || 'secondary'
}

const getSupplierStatusColor = (rating) => {
  if (rating >= 4.5) return 'bg-success'
  if (rating >= 3.5) return 'bg-warning'
  return 'bg-danger'
}
</script>