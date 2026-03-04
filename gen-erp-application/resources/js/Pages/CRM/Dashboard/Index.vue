<template>
  <AppLayout title="CRM Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        CRM Dashboard
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="space-y-6">
          <!-- Header -->
          <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between">
              <div>
                <h1 class="text-2xl font-bold text-gray-900">CRM Dashboard</h1>
                <p class="mt-1 text-sm text-gray-600">
                  Overview of your sales pipeline and customer relationships
                </p>
              </div>
              <div class="flex items-center space-x-3">
                <select v-model="selectedPeriod" class="rounded-md border-gray-300 text-sm">
                  <option value="7">Last 7 days</option>
                  <option value="30">Last 30 days</option>
                  <option value="90">Last 90 days</option>
                  <option value="365">Last year</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Key Metrics -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white shadow rounded-lg p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <UserGroupIcon class="h-8 w-8 text-blue-600" />
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ metrics.totalLeads }}</div>
                  <div class="text-sm text-gray-600">Total Leads</div>
                  <div class="text-xs text-green-600">+5.2% from last period</div>
                </div>
              </div>
            </div>
            
            <div class="bg-white shadow rounded-lg p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <ChartBarIcon class="h-8 w-8 text-green-600" />
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ metrics.totalOpportunities }}</div>
                  <div class="text-sm text-gray-600">Active Opportunities</div>
                  <div class="text-xs text-green-600">+12.1% from last period</div>
                </div>
              </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <CurrencyDollarIcon class="h-8 w-8 text-purple-600" />
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">৳{{ formatNumber(metrics.totalRevenue) }}</div>
                  <div class="text-sm text-gray-600">Pipeline Value</div>
                  <div class="text-xs text-green-600">+8.7% from last period</div>
                </div>
              </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <TrophyIcon class="h-8 w-8 text-yellow-600" />
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ metrics.conversionRate }}%</div>
                  <div class="text-sm text-gray-600">Conversion Rate</div>
                  <div class="text-xs text-red-600">-2.3% from last period</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Top Performers -->
          <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900">Top Performers</h3>
              <select v-model="performanceMetric" class="text-sm rounded-md border-gray-300">
                <option value="deals_won">Deals Won</option>
                <option value="revenue">Revenue</option>
                <option value="activities">Activities</option>
              </select>
            </div>
            <div class="space-y-4">
              <div v-for="(performer, index) in topPerformers" :key="performer.id" class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                      <span class="text-sm font-medium text-gray-700">{{ index + 1 }}</span>
                    </div>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ performer.name }}</p>
                    <p class="text-xs text-gray-500">Sales Rep</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-sm font-semibold text-gray-900">
                    <span v-if="performanceMetric === 'revenue'">৳{{ formatNumber(performer.value) }}</span>
                    <span v-else>{{ performer.value }}</span>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  UserGroupIcon,
  ChartBarIcon,
  CurrencyDollarIcon,
  TrophyIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  metrics: {
    type: Object,
    default: () => ({
      totalLeads: 0,
      qualifiedLeads: 0,
      totalOpportunities: 0,
      wonOpportunities: 0,
      totalRevenue: 0,
      conversionRate: 0,
      averageDealSize: 0,
      activitiesCompleted: 0,
    })
  },
  topPerformers: {
    type: Array,
    default: () => []
  }
})

// Reactive data
const selectedPeriod = ref(30)
const performanceMetric = ref('deals_won')

// Use props data
const metrics = ref(props.metrics)
const topPerformers = ref(props.topPerformers)

// Helper methods
const formatNumber = (num) => {
  if (num >= 100000) {
    return (num / 100000).toFixed(1) + 'L'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}
</script>