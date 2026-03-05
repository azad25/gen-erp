<template>
  <div class="space-y-6">
    <!-- Revenue Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
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

    <!-- Subscription Growth -->
    <Card>
      <template #header>
        <h3 class="text-lg font-semibold text-black dark:text-white">
          Subscription Growth
        </h3>
      </template>
      <div class="space-y-4">
        <div 
          v-for="item in subscriptionGrowth" 
          :key="item.period"
          class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800"
        >
          <div>
            <p class="font-medium text-black dark:text-white">{{ item.period }}</p>
            <p class="text-sm text-gray-1">New subscriptions</p>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-black dark:text-white">{{ item.count }}</p>
            <p :class="item.growth >= 0 ? 'text-green-500' : 'text-red-500'">
              {{ item.growth >= 0 ? '+' : '' }}{{ item.growth }}%
            </p>
          </div>
        </div>
      </div>
    </Card>

    <!-- Revenue by Plan -->
    <Card>
      <template #header>
        <h3 class="text-lg font-semibold text-black dark:text-white">
          Revenue by Plan
        </h3>
      </template>
      <div class="space-y-4">
        <div 
          v-for="plan in revenueByPlan" 
          :key="plan.name"
          class="relative"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-black dark:text-white">{{ plan.name }}</span>
            <span class="text-sm text-gray-1">
              <span class="font-bangla">৳</span>{{ formatPrice(plan.revenue) }} ({{ plan.percentage }}%)
            </span>
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
</template>

<script setup>
import { ref, onMounted } from 'vue'
import StatCard from '@/Components/UI/StatCard.vue'
import Card from '@/Components/UI/Card.vue'
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

const subscriptionGrowth = ref([])
const revenueByPlan = ref([])

const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('en-BD').format(price / 100)
}

const getPlanColor = (planName) => {
  const colors = {
    'Free': 'bg-gray-400',
    'Pro': 'bg-blue-400',
    'Enterprise': 'bg-purple-400'
  }
  return colors[planName] || 'bg-gray-400'
}

const loadAnalytics = async () => {
  try {
    const response = await axios.get('/api/v1/admin/subscription/analytics')
    metrics.value = response.data.metrics
    chartData.value = response.data.chartData
    subscriptionGrowth.value = response.data.subscriptionGrowth
    revenueByPlan.value = response.data.revenueByPlan
  } catch (error) {
    console.error('Failed to load analytics:', error)
  }
}

onMounted(() => {
  loadAnalytics()
})
</script>
