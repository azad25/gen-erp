<template>
  
    
      <AppLayout>
        <div class="space-y-6">
          <!-- Page Header -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-black dark:text-white">
                Subscription Management
              </h1>
              <p class="text-sm text-gray-1 dark:text-gray-400">
                Manage your subscription and billing
              </p>
            </div>
            <Button size="sm" @click="openPlans">
              View Plans
            </Button>
          </div>

          <!-- Current Subscription Card -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Current Subscription
              </h3>
            </template>

            <div v-if="loading" class="flex items-center justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>

            <div v-else-if="subscription" class="space-y-6">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-xl font-bold text-black dark:text-white">
                    {{ subscription.plan?.name }}
                  </h4>
                  <p class="text-sm text-gray-1 dark:text-gray-400">
                    {{ subscription.plan?.description }}
                  </p>
                </div>
                <Badge :variant="getStatusVariant(subscription.status)">
                  {{ subscription.status }}
                </Badge>
              </div>

              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <p class="text-sm text-gray-1 dark:text-gray-400">Billing Cycle</p>
                  <p class="font-semibold text-black dark:text-white capitalize">
                    {{ subscription.billing_cycle }}
                  </p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <p class="text-sm text-gray-1 dark:text-gray-400">Started</p>
                  <p class="font-semibold text-black dark:text-white">
                    {{ formatDate(subscription.starts_at) }}
                  </p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <p class="text-sm text-gray-1 dark:text-gray-400">Ends</p>
                  <p class="font-semibold text-black dark:text-white">
                    {{ formatDate(subscription.ends_at) }}
                  </p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <p class="text-sm text-gray-1 dark:text-gray-400">Days Remaining</p>
                  <p class="font-semibold text-black dark:text-white">
                    {{ subscription.daysRemaining() }}
                  </p>
                </div>
              </div>

              <div v-if="subscription.status === 'grace'" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex items-center gap-2">
                  <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <p class="text-sm text-yellow-700 dark:text-yellow-300">
                    Your subscription is in grace period. Renew now to avoid service interruption.
                  </p>
                </div>
              </div>

              <div v-if="subscription.status === 'expired'" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center gap-2">
                  <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <p class="text-sm text-red-700 dark:text-red-300">
                    Your subscription has expired. Upgrade to restore full access.
                  </p>
                </div>
              </div>

              <div class="flex gap-2">
                <Button variant="primary" @click="openPlans">
                  Upgrade Plan
                </Button>
                <Button variant="secondary" @click="viewInvoices">
                  View Invoices
                </Button>
              </div>
            </div>

            <div v-else class="text-center py-12">
              <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">📦</span>
              </div>
              <p class="text-gray-1 dark:text-gray-400 mb-4">No active subscription</p>
              <Button @click="openPlans">
                Choose a Plan
              </Button>
            </div>
          </Card>

          <!-- Usage Overview -->
          <Card>
            <template #header>
              <h3 class="text-lg font-semibold text-black dark:text-white">
                Usage Overview
              </h3>
            </template>

            <div v-if="usage" class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="text-sm text-gray-1 dark:text-gray-400">Products</p>
                <p class="text-2xl font-bold text-black dark:text-white">
                  {{ usage.products }} / {{ usage.products_limit }}
                </p>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                  <div
                    class="h-2 rounded-full bg-primary"
                    :style="{ width: `${(usage.products / usage.products_limit) * 100}%` }"
                  ></div>
                </div>
              </div>
              <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="text-sm text-gray-1 dark:text-gray-400">Users</p>
                <p class="text-2xl font-bold text-black dark:text-white">
                  {{ usage.users }} / {{ usage.users_limit }}
                </p>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                  <div
                    class="h-2 rounded-full bg-primary"
                    :style="{ width: `${(usage.users / usage.users_limit) * 100}%` }"
                  ></div>
                </div>
              </div>
              <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="text-sm text-gray-1 dark:text-gray-400">Branches</p>
                <p class="text-2xl font-bold text-black dark:text-white">
                  {{ usage.branches }} / {{ usage.branches_limit }}
                </p>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                  <div
                    class="h-2 rounded-full bg-primary"
                    :style="{ width: `${(usage.branches / usage.branches_limit) * 100}%` }"
                  ></div>
                </div>
              </div>
              <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <p class="text-sm text-gray-1 dark:text-gray-400">Storage</p>
                <p class="text-2xl font-bold text-black dark:text-white">
                  {{ formatStorage(usage.storage) }} / {{ formatStorage(usage.storage_limit) }}
                </p>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                  <div
                    class="h-2 rounded-full bg-primary"
                    :style="{ width: `${(usage.storage / usage.storage_limit) * 100}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </AppLayout>
    
  
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue"
import Card from '@/Components/UI/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/UI/Badge.vue'

const loading = ref(false)
const subscription = ref(null)
const usage = ref(null)

const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('en-BD').format(price / 100)
}

const formatStorage = (bytes) => {
  if (!bytes) return '0 MB'
  const gb = bytes / (1024 * 1024 * 1024)
  return `${gb.toFixed(1)} GB`
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-BD', {
    year: 'numeric',
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

const openPlans = () => {
  router.visit('/subscription/plans')
}

const viewInvoices = () => {
  router.visit('/subscription/invoices')
}

const loadSubscription = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/subscription/current')
    subscription.value = response.data.subscription
  } catch (error) {
    console.error('Failed to load subscription:', error)
  } finally {
    loading.value = false
  }
}

const loadUsage = async () => {
  try {
    const response = await axios.get('/api/v1/subscription/usage')
    usage.value = response.data.usage
  } catch (error) {
    console.error('Failed to load usage:', error)
  }
}

onMounted(() => {
  loadSubscription()
  loadUsage()
})
</script>
