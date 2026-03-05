<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <!-- Page Header -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-black dark:text-white">
                Subscription Dashboard
              </h1>
              <p class="text-sm text-gray-1 dark:text-gray-400">
                Overview of all subscriptions and revenue
              </p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportReport">
                Export Report
              </Button>
              <Button size="sm" @click="openPlans">
                Manage Plans
              </Button>
            </div>
          </div>

          <!-- Key Metrics -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
              label="Total Subscriptions"
              :value="metrics.totalSubscriptions"
              subtitle="Active subscriptions"
              color="teal"
            >
              <template #icon>📊</template>
            </StatCard>
            
            <StatCard
              label="Monthly Recurring Revenue"
              :value="metrics.mrr"
              subtitle="Monthly revenue"
              is-currency
              color="green"
            >
              <template #icon>💰</template>
            </StatCard>
            
            <StatCard
              label="Annual Recurring Revenue"
              :value="metrics.arr"
              subtitle="Annual revenue"
              is-currency
              color="blue"
            >
              <template #icon>📈</template>
            </StatCard>
            
            <StatCard
              label="Churn Rate"
              :value="`${metrics.churnRate}%`"
              subtitle="Last 30 days"
              color="red"
            >
              <template #icon>📉</template>
            </StatCard>
          </div>

          <!-- Subscription Trends -->
          <div class="grid gap-6 lg:grid-cols-2">
            <!-- Revenue Trend -->
            <Card>
              <template #header>
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-black dark:text-white">
                    Revenue Trend
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

            <!-- Plan Distribution -->
            <Card>
              <template #header>
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Plan Distribution
                </h3>
              </template>
              <div class="space-y-4">
                <div 
                  v-for="plan in planDistribution" 
                  :key="plan.name"
                  class="relative"
                >
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-black dark:text-white">{{ plan.name }}</span>
                    <span class="text-sm text-gray-1">{{ plan.count }} ({{ plan.percentage }}%)</span>
                  </div>
                  <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div 
                      class="h-2 rounded-full transition-all duration-500"
                      :class="getPlanColor(plan.name)"
                      :style="{ width: `${plan.percentage}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </Card>
          </div>

          <!-- Recent Subscriptions & Expiring Soon -->
          <div class="grid gap-6 lg:grid-cols-2">
            <!-- Recent Subscriptions -->
            <Card>
              <template #header>
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-black dark:text-white">
                    Recent Subscriptions
                  </h3>
                  <Link 
                    href="/subscription/all" 
                    class="text-sm text-primary hover:text-primary-dark font-medium"
                  >
                    View All
                  </Link>
                </div>
              </template>
              <div class="space-y-3">
                <div 
                  v-for="subscription in recentSubscriptions" 
                  :key="subscription.id"
                  class="flex items-center justify-between p-3 rounded-lg border border-stroke dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                >
                  <div>
                    <p class="font-medium text-black dark:text-white">{{ subscription.company?.name }}</p>
                    <p class="text-sm text-gray-1">{{ subscription.plan?.name }}</p>
                    <p class="text-xs text-gray-1">{{ formatDate(subscription.starts_at) }}</p>
                  </div>
                  <div class="text-right">
                    <p class="font-semibold text-black dark:text-white">
                      <span class="font-bangla">৳</span>{{ formatPrice(subscription.amount) }}
                    </p>
                    <Badge :variant="getStatusVariant(subscription.status)">
                      {{ subscription.status }}
                    </Badge>
                  </div>
                </div>
              </div>
            </Card>

            <!-- Expiring Soon -->
            <Card>
              <template #header>
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-black dark:text-white">
                    Expiring Soon
                  </h3>
                  <span class="text-sm text-gray-1">Next 30 days</span>
                </div>
              </template>
              <div class="space-y-3">
                <div 
                  v-for="subscription in expiringSoon" 
                  :key="subscription.id"
                  class="flex items-center justify-between p-3 rounded-lg border border-stroke dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                >
                  <div>
                    <p class="font-medium text-black dark:text-white">{{ subscription.company?.name }}</p>
                    <p class="text-sm text-gray-1">{{ subscription.plan?.name }}</p>
                    <p class="text-xs text-red-500">Expires in {{ subscription.daysRemaining }} days</p>
                  </div>
                  <div class="text-right">
                    <Badge variant="warning">
                      Expiring
                    </Badge>
                  </div>
                </div>
              </div>
            </Card>
          </div>

          <!-- Subscription Status Breakdown -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Subscription Status Breakdown
              </h3>
            </template>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
              <div class="text-center p-4 rounded-lg bg-green-50 dark:bg-green-900/20">
                <div class="text-3xl font-bold text-green-600">{{ statusBreakdown.active }}</div>
                <div class="text-sm text-gray-1">Active</div>
              </div>
              <div class="text-center p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                <div class="text-3xl font-bold text-blue-600">{{ statusBreakdown.trialing }}</div>
                <div class="text-sm text-gray-1">Trial</div>
              </div>
              <div class="text-center p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
                <div class="text-3xl font-bold text-yellow-600">{{ statusBreakdown.grace }}</div>
                <div class="text-sm text-gray-1">Grace</div>
              </div>
              <div class="text-center p-4 rounded-lg bg-red-50 dark:bg-red-900/20">
                <div class="text-3xl font-bold text-red-600">{{ statusBreakdown.expired }}</div>
                <div class="text-sm text-gray-1">Expired</div>
              </div>
              <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                <div class="text-3xl font-bold text-gray-600">{{ statusBreakdown.cancelled }}</div>
                <div class="text-sm text-gray-1">Cancelled</div>
              </div>
            </div>
          </Card>
        </div>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
import StatCard from '@/Components/UI/StatCard.vue'
import Card from '@/Components/UI/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import AreaChart from '@/Components/Charts/AreaChart.vue'

const selectedPeriod = ref('30d')

const metrics = ref({
  totalSubscriptions: 0,
  mrr: 0,
  arr: 0,
  churnRate: 0
})

const chartData = ref({
  labels: [],
  revenue: []
})

const planDistribution = ref([])
const recentSubscriptions = ref([])
const expiringSoon = ref([])
const statusBreakdown = ref({
  active: 0,
  trialing: 0,
  grace: 0,
  expired: 0,
  cancelled: 0
})

const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('en-BD').format(price / 100)
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-BD', {
    month: 'short',
    day: 'numeric'
  })
}

const getStatusVariant = (status) => {
  const variants = {
    'active': 'success',
    'trialing': 'info',
    'grace': 'warning',
    'expired': 'danger',
    'cancelled': 'secondary'
  }
  return variants[status] || 'secondary'
}

const getPlanColor = (planName) => {
  const colors = {
    'Free': 'bg-gray-400',
    'Pro': 'bg-blue-400',
    'Enterprise': 'bg-purple-400'
  }
  return colors[planName] || 'bg-gray-400'
}

const exportReport = () => {
  alert('Report export coming soon!')
}

const openPlans = () => {
  window.location.href = '/admin/subscription/plans'
}

const loadDashboard = async () => {
  try {
    const response = await axios.get('/api/v1/admin/subscription/dashboard')
    metrics.value = response.data.metrics
    chartData.value = response.data.chartData
    planDistribution.value = response.data.planDistribution
    recentSubscriptions.value = response.data.recentSubscriptions
    expiringSoon.value = response.data.expiringSoon
    statusBreakdown.value = response.data.statusBreakdown
  } catch (error) {
    console.error('Failed to load dashboard:', error)
  }
}

onMounted(() => {
  loadDashboard()
})
</script>
