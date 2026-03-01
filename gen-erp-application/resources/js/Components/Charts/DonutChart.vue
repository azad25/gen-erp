<template>
  <apexchart type="donut" :height="height" :options="opts" :series="series" />
</template>

<script setup>
import { computed } from 'vue'

const p = defineProps({
  series: Array,
  labels: Array,
  height: { type: Number, default: 280 },
  colors: { type: Array, default: () => ['#0F766E', '#14B8A6', '#2DD4BF', '#5EEAD4'] }
})

const opts = computed(() => ({
  chart: {
    type: 'donut',
    toolbar: { show: false },
    animations: { enabled: true }
  },
  labels: p.labels,
  colors: p.colors,
  legend: {
    position: 'bottom',
    fontSize: '12px',
    fontFamily: 'Plus Jakarta Sans, sans-serif',
    labels: {
      colors: '#6B7280'
    },
    markers: {
      width: 8,
      height: 8,
      radius: 2
    }
  },
  dataLabels: {
    enabled: true,
    style: {
      fontSize: '12px',
      fontFamily: 'Plus Jakarta Sans, sans-serif',
      fontWeight: 600
    },
    dropShadow: {
      enabled: false
    }
  },
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: true,
          name: {
            show: true,
            fontSize: '14px',
            fontFamily: 'Plus Jakarta Sans, sans-serif',
            color: '#1F2937',
            offsetY: -10
          },
          value: {
            show: true,
            fontSize: '20px',
            fontFamily: 'Plus Jakarta Sans, sans-serif',
            fontWeight: 700,
            color: '#111827',
            offsetY: 5,
            formatter: (val) => val + '%'
          },
          total: {
            show: true,
            label: 'Total',
            fontSize: '12px',
            fontFamily: 'Plus Jakarta Sans, sans-serif',
            color: '#6B7280',
            formatter: () => '100%'
          }
        }
      }
    }
  },
  tooltip: {
    theme: 'light',
    style: {
      fontSize: '12px',
      fontFamily: 'Plus Jakarta Sans, sans-serif'
    },
    y: {
      formatter: (val) => val + '%'
    }
  },
  stroke: {
    width: 0
  }
}))
</script>
