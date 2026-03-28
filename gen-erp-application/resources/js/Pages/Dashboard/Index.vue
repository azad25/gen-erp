<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('dashboard.title') }}</h1>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ $t('dashboard.welcome', { name: $page.props.auth.user.name }) }}
          </p>
        </div>
        <div class="text-sm text-gray-500">
          {{ currentDate }}
        </div>
      </div>

      <!-- TailAdmin Dashboard Widgets -->
      <div class="grid grid-cols-12 gap-4 md:gap-6">
        <div class="col-span-12 space-y-6 xl:col-span-7">
          <ecommerce-metrics :stats="stats" />
          <monthly-target />
        </div>
        <div class="col-span-12 xl:col-span-5">
          <monthly-sale />
        </div>

        <div class="col-span-12">
          <statistics-chart :chart-data="chartData" :chart-labels="chartLabels" />
        </div>

        <div class="col-span-12 xl:col-span-5">
          <customer-demographic :revenue-by-type="revenueByType" />
        </div>

        <div class="col-span-12 xl:col-span-7">
          <recent-orders :invoices="invoices" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useTranslations } from '@/Composables/useTranslations'

// Import TailAdmin components
import EcommerceMetrics from '@/Components/ecommerce/EcommerceMetrics.vue'
import MonthlyTarget from '@/Components/ecommerce/MonthlySale.vue'
import MonthlySale from '@/Components/ecommerce/MonthlyTarget.vue'
import CustomerDemographic from '@/Components/ecommerce/CustomerDemographic.vue'
import StatisticsChart from '@/Components/ecommerce/StatisticsChart.vue'
import RecentOrders from '@/Components/ecommerce/RecentOrders.vue'

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  invoices: { type: Array, default: () => [] },
  activity: { type: Array, default: () => [] },
  chartData: { type: Array, default: () => [] },
  chartLabels: { type: Array, default: () => [] },
  revenueByType: { type: Object, default: () => ({ series: [], labels: [] }) },
})

const page = usePage()
const { $t, currentLocale } = useTranslations()

const currentDate = computed(() => {
  const locale = currentLocale.value === 'bn' ? 'bn-BD' : 'en-US'
  return new Date().toLocaleDateString(locale, { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
})
</script>