<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">Lead Scoring</h3>
        <div class="flex items-center space-x-2">
          <button
            @click="refreshScores"
            :disabled="loading"
            class="text-xs text-gray-500 hover:text-gray-700 disabled:opacity-50"
          >
            {{ loading ? 'Refreshing...' : 'Refresh' }}
          </button>
          <button
            @click="collapsed = !collapsed"
            class="text-gray-400 hover:text-gray-600"
          >
            <ChevronUpIcon v-if="!collapsed" class="h-4 w-4" />
            <ChevronDownIcon v-else class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div v-if="!collapsed" class="p-4">
      <!-- Score Overview -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700">Overall Score</span>
          <span class="text-lg font-bold" :class="getScoreColor(overallScore)">
            {{ overallScore }}/100
          </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            class="h-2 rounded-full transition-all duration-300"
            :class="getScoreBarColor(overallScore)"
            :style="{ width: `${overallScore}%` }"
          ></div>
        </div>
        <div class="flex justify-between text-xs text-gray-500 mt-1">
          <span>Cold</span>
          <span>Warm</span>
          <span>Hot</span>
        </div>
      </div>

      <!-- Score Categories -->
      <div class="space-y-4">
        <div v-for="category in scoreCategories" :key="category.name" class="space-y-2">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <component :is="category.icon" class="h-4 w-4 text-gray-400" />
              <span class="text-sm font-medium text-gray-700">{{ category.label }}</span>
            </div>
            <span class="text-sm font-medium" :class="getScoreColor(category.score)">
              {{ category.score }}/{{ category.maxScore }}
            </span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-1.5">
            <div
              class="h-1.5 rounded-full transition-all duration-300"
              :class="getScoreBarColor(category.percentage)"
              :style="{ width: `${category.percentage}%` }"
            ></div>
          </div>
          <div class="text-xs text-gray-500">{{ category.description }}</div>
        </div>
      </div>

      <!-- Score Factors -->
      <div class="mt-6 pt-4 border-t border-gray-200">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Score Factors</h4>
        <div class="space-y-2">
          <div v-for="factor in scoreFactors" :key="factor.name" class="flex items-center justify-between text-sm">
            <div class="flex items-center space-x-2">
              <div
                class="w-2 h-2 rounded-full"
                :class="factor.impact > 0 ? 'bg-green-400' : factor.impact < 0 ? 'bg-red-400' : 'bg-gray-400'"
              ></div>
              <span class="text-gray-700">{{ factor.label }}</span>
            </div>
            <span
              class="font-medium"
              :class="factor.impact > 0 ? 'text-green-600' : factor.impact < 0 ? 'text-red-600' : 'text-gray-600'"
            >
              {{ factor.impact > 0 ? '+' : '' }}{{ factor.impact }}
            </span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="mt-6 pt-4 border-t border-gray-200">
        <div class="flex space-x-2">
          <button
            @click="recalculateScore"
            :disabled="loading"
            class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-3 rounded-md"
          >
            Recalculate Score
          </button>
          <button
            @click="showScoreHistory = true"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 px-3 rounded-md"
          >
            History
          </button>
        </div>
      </div>

      <!-- Recommendations -->
      <div v-if="recommendations.length > 0" class="mt-6 pt-4 border-t border-gray-200">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Recommendations</h4>
        <div class="space-y-2">
          <div v-for="recommendation in recommendations" :key="recommendation.id" class="p-3 bg-blue-50 rounded-md">
            <div class="flex items-start space-x-2">
              <LightBulbIcon class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
              <div class="text-sm text-blue-800">{{ recommendation.message }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Score History Modal -->
    <ScoreHistoryModal
      v-if="showScoreHistory"
      :lead-id="leadId"
      @close="showScoreHistory = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  UserIcon,
  BuildingOfficeIcon,
  CurrencyDollarIcon,
  ClockIcon,
  LightBulbIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import ScoreHistoryModal from './ScoreHistoryModal.vue'

const props = defineProps({
  leadId: {
    type: [String, Number],
    required: true
  },
  initialScore: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['score-updated'])

const { get, post, loading } = useApi()

// Reactive data
const collapsed = ref(false)
const showScoreHistory = ref(false)
const scoreData = ref(props.initialScore)

// Computed properties
const overallScore = computed(() => {
  return scoreData.value.total_score || 0
})

const scoreCategories = computed(() => [
  {
    name: 'demographic',
    label: 'Demographics',
    icon: UserIcon,
    score: scoreData.value.demographic_score || 0,
    maxScore: 25,
    percentage: ((scoreData.value.demographic_score || 0) / 25) * 100,
    description: 'Job title, company size, industry fit'
  },
  {
    name: 'company',
    label: 'Company Fit',
    icon: BuildingOfficeIcon,
    score: scoreData.value.company_score || 0,
    maxScore: 25,
    percentage: ((scoreData.value.company_score || 0) / 25) * 100,
    description: 'Company size, revenue, industry match'
  },
  {
    name: 'budget',
    label: 'Budget & Authority',
    icon: CurrencyDollarIcon,
    score: scoreData.value.budget_score || 0,
    maxScore: 25,
    percentage: ((scoreData.value.budget_score || 0) / 25) * 100,
    description: 'Budget availability and decision-making power'
  },
  {
    name: 'engagement',
    label: 'Engagement',
    icon: ClockIcon,
    score: scoreData.value.engagement_score || 0,
    maxScore: 25,
    percentage: ((scoreData.value.engagement_score || 0) / 25) * 100,
    description: 'Email opens, website visits, content downloads'
  }
])

const scoreFactors = computed(() => {
  return scoreData.value.factors || []
})

const recommendations = computed(() => {
  return scoreData.value.recommendations || []
})

// Methods
const getScoreColor = (score) => {
  if (score >= 80) return 'text-green-600'
  if (score >= 60) return 'text-yellow-600'
  if (score >= 40) return 'text-orange-600'
  return 'text-red-600'
}

const getScoreBarColor = (score) => {
  if (score >= 80) return 'bg-green-500'
  if (score >= 60) return 'bg-yellow-500'
  if (score >= 40) return 'bg-orange-500'
  return 'bg-red-500'
}

const fetchScoreData = async () => {
  try {
    const data = await get(`/api/v1/crm/leads/${props.leadId}/score`)
    scoreData.value = data.data
    emit('score-updated', data.data)
  } catch (err) {
    console.error('Failed to fetch score data:', err)
  }
}

const recalculateScore = async () => {
  try {
    const data = await post(`/api/v1/crm/leads/${props.leadId}/recalculate-score`)
    scoreData.value = data.data
    emit('score-updated', data.data)
  } catch (err) {
    console.error('Failed to recalculate score:', err)
  }
}

const refreshScores = () => {
  fetchScoreData()
}

// Lifecycle
onMounted(() => {
  if (!props.initialScore.total_score) {
    fetchScoreData()
  }
})
</script>