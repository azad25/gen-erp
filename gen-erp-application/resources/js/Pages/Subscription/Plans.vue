<template>
  
    
      <AppLayout>
        <div class="space-y-6">
          <!-- Page Header -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-black dark:text-white">
                Subscription Plans
              </h1>
              <p class="text-sm text-gray-1 dark:text-gray-400">
                Choose the perfect plan for your business
              </p>
            </div>
          </div>

          <!-- Plan Comparison Table -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Plan Comparison
              </h3>
            </template>
            <PlanComparisonTable 
              :plans="plans"
              :current-plan="currentPlan"
              @select-plan="handlePlanClick"
            />
          </Card>

          <!-- Current Plan Banner -->
          <div v-if="currentPlan" class="bg-primary/10 border border-primary/20 rounded-lg p-6">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-black dark:text-white">
                  Current Plan: {{ currentPlan.name }}
                </h3>
                <p class="text-sm text-gray-1 dark:text-gray-400">
                  {{ currentPlan.description }}
                </p>
              </div>
              <Badge :variant="getStatusVariant(currentSubscription?.status)">
                {{ currentSubscription?.status || 'Free' }}
              </Badge>
            </div>
          </div>

          <!-- Plans Grid -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div
              v-for="plan in plans"
              :key="plan.id"
              class="border rounded-xl p-6 hover:shadow-lg transition-shadow"
              :class="[
                currentPlan?.id === plan.id ? 'border-primary bg-primary/5' : 'border-gray-200 dark:border-gray-700'
              ]"
            >
              <div class="text-center mb-6">
                <h3 class="text-xl font-bold text-black dark:text-white">{{ plan.name }}</h3>
                <p class="text-sm text-gray-1 dark:text-gray-400 mt-2">{{ plan.description }}</p>
              </div>

              <div class="text-center mb-6">
                <div class="text-3xl font-bold text-black dark:text-white">
                  <span class="font-bangla">৳</span>{{ formatPrice(plan.monthly_price) }}
                </div>
                <p class="text-sm text-gray-1 dark:text-gray-400">per month</p>
                <div v-if="plan.annual_price" class="mt-2 text-sm text-primary">
                  <span class="font-bangla">৳</span>{{ formatPrice(plan.annual_price) }}/year
                  <span class="text-gray-1"> (save {{ calculateSavings(plan) }}%)</span>
                </div>
              </div>

              <div class="space-y-3 mb-6">
                <div class="flex items-center gap-2 text-sm">
                  <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span class="text-black dark:text-white">{{ plan.limits?.products || 'Unlimited' }} Products</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                  <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span class="text-black dark:text-white">{{ plan.limits?.users || 'Unlimited' }} Users</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                  <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span class="text-black dark:text-white">{{ plan.limits?.branches || 'Unlimited' }} Branches</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                  <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span class="text-black dark:text-white">{{ formatStorage(plan.limits?.storage_bytes) }} Storage</span>
                </div>
                <div v-if="plan.feature_flags?.api_access" class="flex items-center gap-2 text-sm">
                  <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span class="text-black dark:text-white">API Access</span>
                </div>
                <div v-if="plan.feature_flags?.integrations" class="flex items-center gap-2 text-sm">
                  <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span class="text-black dark:text-white">{{ plan.feature_flags.integrations }} Integrations</span>
                </div>
                <div v-if="plan.feature_flags?.plugin_sdk" class="flex items-center gap-2 text-sm">
                  <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span class="text-black dark:text-white">Plugin SDK</span>
                </div>
              </div>

              <Button
                :variant="currentPlan?.id === plan.id ? 'secondary' : 'primary'"
                class="w-full"
                @click="handlePlanClick(plan)"
              >
                {{ currentPlan?.id === plan.id ? 'Current Plan' : 'Upgrade Now' }}
              </Button>
            </div>
          </div>
        </div>
      </AppLayout>
    
  
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue"
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import Card from '@/Components/UI/Card.vue'
import PlanComparisonTable from '@/Components/Subscription/PlanComparisonTable.vue'
import AreaChart from '@/Components/Charts/AreaChart.vue'

const plans = ref([])
const currentPlan = ref(null)
const currentSubscription = ref(null)

const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('en-BD').format(price / 100)
}

const formatStorage = (bytes) => {
  if (!bytes) return 'Unlimited'
  const gb = bytes / (1024 * 1024 * 1024)
  return `${gb.toFixed(1)} GB`
}

const calculateSavings = (plan) => {
  if (!plan.monthly_price || !plan.annual_price) return 0
  const monthlyTotal = plan.monthly_price * 12
  const savings = ((monthlyTotal - plan.annual_price) / monthlyTotal) * 100
  return Math.round(savings)
}

const handlePlanClick = (plan) => {
  if (currentPlan.value?.id === plan.id) return
  
  if (confirm(`Are you sure you want to upgrade to ${plan.name}?`)) {
    // TODO: Call API to upgrade plan
    alert('Plan upgrade coming soon!')
  }
}

const loadPlans = async () => {
  try {
    const response = await axios.get('/api/v1/subscription/plans')
    plans.value = response.data.plans
    
    // Get current subscription
    const subResponse = await axios.get('/api/v1/subscription/current')
    if (subResponse.data.subscription) {
      currentPlan.value = subResponse.data.plan
      currentSubscription.value = subResponse.data.subscription
    }
  } catch (error) {
    console.error('Failed to load plans:', error)
  }
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

onMounted(() => {
  loadPlans()
})
</script>
