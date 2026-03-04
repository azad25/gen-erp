<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-2/3 max-w-4xl shadow-lg rounded-md bg-white">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Score History</h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Content -->
      <div v-else class="space-y-6">
        <!-- Score Chart -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Score Trend</h4>
          <div class="h-64">
            <LineChart
              :data="chartData"
              :options="chartOptions"
            />
          </div>
        </div>

        <!-- History Timeline -->
        <div>
          <h4 class="text-sm font-medium text-gray-900 mb-3">Score Changes</h4>
          <div class="space-y-4 max-h-96 overflow-y-auto">
            <div
              v-for="entry in history"
              :key="entry.id"
              class="flex items-start space-x-3 p-3 bg-white border border-gray-200 rounded-lg"
            >
              <div class="flex-shrink-0">
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-medium text-white"
                  :class="getScoreChangeColor(entry.score_change)"
                >
                  {{ entry.score_change > 0 ? '+' : '' }}{{ entry.score_change }}
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <p class="text-sm font-medium text-gray-900">
                    Score: {{ entry.total_score }}/100
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ formatDate(entry.created_at) }}
                  </p>
                </div>
                <p class="text-sm text-gray-600 mt-1">{{ entry.reason }}</p>
                <div v-if="entry.factors && entry.factors.length > 0" class="mt-2">
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="factor in entry.factors"
                      :key="factor.name"
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs"
                      :class="factor.impact > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    >
                      {{ factor.label }}: {{ factor.impact > 0 ? '+' : '' }}{{ factor.impact }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-200">
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">{{ stats.highest_score }}</div>
            <div class="text-sm text-gray-500">Highest Score</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">{{ stats.average_score }}</div>
            <div class="text-sm text-gray-500">Average Score</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-900">{{ stats.total_changes }}</div>
            <div class="text-sm text-gray-500">Total Changes</div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end mt-6 pt-4 border-t border-gray-200">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import LineChart from '@/Components/Charts/LineChart.vue'

const props = defineProps({
  leadId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['close'])

const { get, loading } = useApi()

// Reactive data
const history = ref([])
const stats = ref({
  highest_score: 0,
  average_score: 0,
  total_changes: 0
})

// Computed properties
const chartData = computed(() => {
  if (!history.value.length) return { labels: [], datasets: [] }
  
  const labels = history.value.map(entry => formatDate(entry.created_at, true))
  const scores = history.value.map(entry => entry.total_score)
  
  return {
    labels,
    datasets: [
      {
        label: 'Lead Score',
        data: scores,
        borderColor: 'rgb(79, 70, 229)',
        backgroundColor: 'rgba(79, 70, 229, 0.1)',
        tension: 0.1,
        fill: true
      }
    ]
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: {
      beginAtZero: true,
      max: 100,
      title: {
        display: true,
        text: 'Score'
      }
    },
    x: {
      title: {
        display: true,
        text: 'Date'
      }
    }
  },
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          return `Score: ${context.parsed.y}/100`
        }
      }
    }
  }
}))

// Methods
const fetchHistory = async () => {
  try {
    const data = await get(`/api/v1/crm/leads/${props.leadId}/score-history`)
    history.value = data.data.history || []
    stats.value = data.data.stats || stats.value
  } catch (err) {
    console.error('Failed to fetch score history:', err)
  }
}

const getScoreChangeColor = (change) => {
  if (change > 0) return 'bg-green-500'
  if (change < 0) return 'bg-red-500'
  return 'bg-gray-500'
}

const formatDate = (dateString, short = false) => {
  const date = new Date(dateString)
  if (short) {
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  }
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  fetchHistory()
})
</script>