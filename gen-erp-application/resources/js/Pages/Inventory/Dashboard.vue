<template>
  
    <AppLayout>
      <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">
              Inventory Dashboard
            </h1>
            <p class="text-sm text-gray-1 dark:text-gray-400">
              Monitor stock levels, movements, and warehouse performance
            </p>
          </div>
          <div class="flex items-center gap-3">
            <Button variant="secondary" size="sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Stock Report
            </Button>
            <Button size="sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Stock Adjustment
            </Button>
          </div>
        </div>

        <!-- Key Metrics Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard
            label="Total Stock Value"
            :value="metrics.totalStockValue"
            subtitle="Current inventory"
            :delta="metrics.stockValueDelta"
            is-currency
            color="teal"
            :sparkline="metrics.stockValueSparkline"
          >
            <template #icon>📦</template>
          </StatCard>
          
          <StatCard
            label="Total Products"
            :value="metrics.totalProducts"
            subtitle="Active SKUs"
            :delta="metrics.productsDelta"
            color="green"
          >
            <template #icon>🏷️</template>
          </StatCard>
          
          <StatCard
            label="Low Stock Items"
            :value="metrics.lowStockItems"
            subtitle="Below minimum"
            color="amber"
          >
            <template #icon>⚠️</template>
          </StatCard>
          
          <StatCard
            label="Out of Stock"
            :value="metrics.outOfStockItems"
            subtitle="Zero inventory"
            color="red"
          >
            <template #icon>🚫</template>
          </StatCard>
        </div>

        <!-- Charts Row -->
        <div class="grid gap-6 lg:grid-cols-3">
          <!-- Stock Movement Chart -->
          <div class="lg:col-span-2">
            <Card>
              <template #header>
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-black dark:text-white">
                    Stock Movement Trend
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
                :series="[
                  {name: 'Stock In', data: chartData.stockIn},
                  {name: 'Stock Out', data: chartData.stockOut}
                ]" 
                :categories="chartData.labels" 
                :height="320" 
              />
            </Card>
          </div>

          <!-- Warehouse Overview -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Warehouse Overview
              </h3>
            </template>
            <div class="space-y-4">
              <div 
                v-for="warehouse in warehouses" 
                :key="warehouse.id"
                class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800"
              >
                <div class="flex items-center justify-between mb-2">
                  <h4 class="font-medium text-black dark:text-white">{{ warehouse.name }}</h4>
                  <span class="text-xs text-gray-1">{{ warehouse.location }}</span>
                </div>
                <div class="space-y-2">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-1">Capacity</span>
                    <span class="text-black dark:text-white">{{ warehouse.utilization }}%</span>
                  </div>
                  <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div 
                      class="h-2 rounded-full transition-all"
                      :class="getCapacityColor(warehouse.utilization)"
                      :style="{ width: `${warehouse.utilization}%` }"
                    ></div>
                  </div>
                  <div class="flex justify-between text-xs text-gray-1">
                    <span>{{ warehouse.itemCount }} items</span>
                    <span><span class="font-bangla">৳</span>{{ formatCurrency(warehouse.value) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Critical Stock & Top Moving Items -->
        <div class="grid gap-6 lg:grid-cols-2">
          <!-- Critical Stock Levels -->
          <Card>
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Critical Stock Levels
                </h3>
                <Link 
                  href="/inventory/stock" 
                  class="text-sm text-primary hover:text-primary-dark font-medium"
                >
                  View All
                </Link>
              </div>
            </template>
            <div class="space-y-3">
              <div 
                v-for="item in criticalStock" 
                :key="item.id"
                class="flex items-center justify-between p-3 rounded-lg border border-stroke dark:border-gray-700"
                :class="getStockLevelBg(item.status)"
              >
                <div class="flex items-center gap-3">
                  <div 
                    class="w-3 h-3 rounded-full"
                    :class="getStockLevelColor(item.status)"
                  ></div>
                  <div>
                    <p class="font-medium text-black dark:text-white">{{ item.name }}</p>
                    <p class="text-sm text-gray-1">{{ item.sku }} • {{ item.category }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-black dark:text-white">{{ item.currentStock }}</p>
                  <p class="text-xs text-gray-1">Min: {{ item.minStock }}</p>
                </div>
              </div>
            </div>
          </Card>

          <!-- Top Moving Items -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Top Moving Items
              </h3>
            </template>
            <div class="space-y-4">
              <div 
                v-for="(item, index) in topMovingItems" 
                :key="item.id"
                class="flex items-center justify-between"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold text-sm">
                    {{ index + 1 }}
                  </div>
                  <div>
                    <p class="font-medium text-black dark:text-white">{{ item.name }}</p>
                    <p class="text-sm text-gray-1">{{ item.category }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-black dark:text-white">{{ item.movements }}</p>
                  <p class="text-xs text-gray-1">movements</p>
                  <div class="flex items-center gap-1 mt-1">
                    <div class="w-16 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div 
                        class="h-full bg-primary rounded-full transition-all"
                        :style="{ width: `${item.velocity}%` }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-1">{{ item.velocity }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Recent Movements & ABC Analysis -->
        <div class="grid gap-6 lg:grid-cols-2">
          <!-- Recent Stock Movements -->
          <Card>
            <template #header>
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Recent Stock Movements
                </h3>
                <Link 
                  href="/inventory/movements" 
                  class="text-sm text-primary hover:text-primary-dark font-medium"
                >
                  View All
                </Link>
              </div>
            </template>
            <div class="space-y-3">
              <div 
                v-for="movement in recentMovements" 
                :key="movement.id"
                class="flex items-center justify-between p-3 rounded-lg border border-stroke dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
              >
                <div class="flex items-center gap-3">
                  <div 
                    :class="[
                      'w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-semibold',
                      movement.type === 'in' ? 'bg-success' : 'bg-danger'
                    ]"
                  >
                    {{ movement.type === 'in' ? '+' : '-' }}
                  </div>
                  <div>
                    <p class="font-medium text-black dark:text-white">{{ movement.product }}</p>
                    <p class="text-sm text-gray-1">{{ movement.reason }} • {{ formatDate(movement.date) }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-black dark:text-white">{{ movement.quantity }}</p>
                  <p class="text-xs text-gray-1">{{ movement.warehouse }}</p>
                </div>
              </div>
            </div>
          </Card>

          <!-- ABC Analysis -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                ABC Analysis
              </h3>
            </template>
            <div class="space-y-4">
              <div class="grid grid-cols-3 gap-4">
                <div class="text-center p-4 rounded-lg bg-green-50 dark:bg-green-900/20">
                  <div class="text-2xl font-bold text-green-600">{{ abcAnalysis.categoryA.count }}</div>
                  <div class="text-sm text-gray-1">Category A</div>
                  <div class="text-xs text-green-600 mt-1">{{ abcAnalysis.categoryA.percentage }}% value</div>
                </div>
                <div class="text-center p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
                  <div class="text-2xl font-bold text-yellow-600">{{ abcAnalysis.categoryB.count }}</div>
                  <div class="text-sm text-gray-1">Category B</div>
                  <div class="text-xs text-yellow-600 mt-1">{{ abcAnalysis.categoryB.percentage }}% value</div>
                </div>
                <div class="text-center p-4 rounded-lg bg-red-50 dark:bg-red-900/20">
                  <div class="text-2xl font-bold text-red-600">{{ abcAnalysis.categoryC.count }}</div>
                  <div class="text-sm text-gray-1">Category C</div>
                  <div class="text-xs text-red-600 mt-1">{{ abcAnalysis.categoryC.percentage }}% value</div>
                </div>
              </div>
              
              <div class="space-y-3">
                <h4 class="font-medium text-black dark:text-white">High Value Items (Category A)</h4>
                <div 
                  v-for="item in abcAnalysis.topItems" 
                  :key="item.id"
                  class="flex items-center justify-between p-2 rounded bg-green-50 dark:bg-green-900/20"
                >
                  <div>
                    <p class="text-sm font-medium text-black dark:text-white">{{ item.name }}</p>
                    <p class="text-xs text-gray-1">{{ item.category }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-semibold text-green-600">
                      <span class="font-bangla">৳</span>{{ formatCurrency(item.value) }}
                    </p>
                    <p class="text-xs text-gray-1">{{ item.turnover }}x turnover</p>
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
  warehouses: Array,
  criticalStock: Array,
  topMovingItems: Array,
  recentMovements: Array,
  abcAnalysis: Object,
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

const getCapacityColor = (utilization) => {
  if (utilization >= 90) return 'bg-danger'
  if (utilization >= 75) return 'bg-warning'
  return 'bg-success'
}

const getStockLevelColor = (status) => {
  const colors = {
    'out_of_stock': 'bg-danger',
    'low_stock': 'bg-warning',
    'critical': 'bg-red-500'
  }
  return colors[status] || 'bg-gray-400'
}

const getStockLevelBg = (status) => {
  const backgrounds = {
    'out_of_stock': 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
    'low_stock': 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
    'critical': 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
  }
  return backgrounds[status] || ''
}
</script>