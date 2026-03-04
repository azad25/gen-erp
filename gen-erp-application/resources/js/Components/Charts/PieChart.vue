<template>
  <div class="w-full h-full">
    <canvas ref="chartCanvas" :width="width" :height="height"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  options: {
    type: Object,
    default: () => ({})
  },
  width: {
    type: Number,
    default: 400
  },
  height: {
    type: Number,
    default: 200
  },
  responsive: {
    type: Boolean,
    default: true
  },
  doughnut: {
    type: Boolean,
    default: false
  }
})

const chartCanvas = ref(null)
const chartInstance = ref(null)

// Default options
const defaultOptions = {
  responsive: props.responsive,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'right'
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: 'rgba(255, 255, 255, 0.1)',
      borderWidth: 1,
      callbacks: {
        label: function(context) {
          const label = context.label || ''
          const value = context.parsed
          const total = context.dataset.data.reduce((a, b) => a + b, 0)
          const percentage = ((value / total) * 100).toFixed(1)
          return `${label}: ${value} (${percentage}%)`
        }
      }
    }
  },
  elements: {
    arc: {
      borderWidth: 2,
      borderColor: '#fff'
    }
  }
}

const createChart = async () => {
  if (!chartCanvas.value) return

  // Dynamically import Chart.js to reduce bundle size
  const { Chart, registerables } = await import('chart.js')
  Chart.register(...registerables)

  const ctx = chartCanvas.value.getContext('2d')
  
  // Destroy existing chart if it exists
  if (chartInstance.value) {
    chartInstance.value.destroy()
  }

  // Merge options
  const mergedOptions = {
    ...defaultOptions,
    ...props.options
  }

  chartInstance.value = new Chart(ctx, {
    type: props.doughnut ? 'doughnut' : 'pie',
    data: props.data,
    options: mergedOptions
  })
}

const updateChart = () => {
  if (chartInstance.value) {
    chartInstance.value.data = props.data
    chartInstance.value.update()
  }
}

const destroyChart = () => {
  if (chartInstance.value) {
    chartInstance.value.destroy()
    chartInstance.value = null
  }
}

// Watch for data changes
watch(() => props.data, () => {
  updateChart()
}, { deep: true })

// Watch for options changes
watch(() => props.options, () => {
  createChart()
}, { deep: true })

onMounted(async () => {
  await nextTick()
  createChart()
})

onUnmounted(() => {
  destroyChart()
})
</script>