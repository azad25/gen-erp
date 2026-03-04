<template>
  <div
    class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6"
  >
    <div class="flex justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
          Revenue by Type
        </h3>
        <p class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400">
          Current month breakdown
        </p>
      </div>
    </div>
    
    <div class="px-4 py-6 my-6 overflow-hidden flex justify-center items-center border border-gary-200 rounded-2xl bg-gray-50 dark:border-gray-800 dark:bg-gray-900 sm:px-6">
      <VueApexCharts
        type="donut"
        width="380"
        :options="chartOptions"
        :series="chartSeries"
      />
    </div>

    <div class="space-y-5">
      <div 
        v-for="(label, index) in chartLabels" 
        :key="index"
        class="flex items-center justify-between"
      >
        <div class="flex items-center gap-3">
          <div 
            class="w-3 h-3 rounded-full" 
            :style="{ backgroundColor: getChartColor(index) }"
          ></div>
          <div>
            <p class="font-semibold text-gray-800 text-theme-sm dark:text-white/90">{{ label }}</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
            {{ calculatePercentage(chartSeries[index]) }}%
          </p>
        </div>
      </div>
      
      <div v-if="!chartSeries || chartSeries.length === 0" class="text-center text-gray-500 py-4">
        No revenue data available
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
  revenueByType: {
    type: Object,
    default: () => ({ series: [], labels: [] })
  }
})

// Deep Teal branding palette
const colors = ['#0F766E', '#14B8A6', '#5EEAD4', '#CCFBF1', '#042F2E', '#115E59']

// Fallback to dummy data if props aren't populated yet
const dummySeries = [44, 55, 13, 33]
const dummyLabels = ['Product Sales', 'Services', 'Subscriptions', 'Other']

const chartSeries = computed(() => {
  return props.revenueByType?.series?.length > 0 
    ? props.revenueByType.series 
    : dummySeries
})

const chartLabels = computed(() => {
  return props.revenueByType?.labels?.length > 0 
    ? props.revenueByType.labels 
    : dummyLabels
})

const getChartColor = (index) => {
  return colors[index % colors.length]
}

const totalSum = computed(() => {
  return chartSeries.value.reduce((acc, curr) => acc + curr, 0) || 1
})

const calculatePercentage = (value) => {
  if (!value) return 0
  return Math.round((value / totalSum.value) * 100)
}

const chartOptions = computed(() => ({
  chart: {
    fontFamily: 'Inter, sans-serif',
    type: 'donut',
  },
  colors: colors,
  labels: chartLabels.value,
  legend: {
    show: false,
  },
  plotOptions: {
    pie: {
      donut: {
        size: '65%',
        background: 'transparent',
      },
    },
  },
  dataLabels: {
    enabled: false,
  },
  stroke: {
    show: true,
    colors: ['transparent'],
  },
  theme: {
    mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
  },
  tooltip: {
    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
  }
}))
</script>
