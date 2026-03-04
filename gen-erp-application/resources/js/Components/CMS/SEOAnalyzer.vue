<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">SEO Analysis</h3>
        <div class="flex items-center space-x-3">
          <button
            @click="analyzePage"
            :disabled="loading"
            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-3 rounded-md"
          >
            {{ loading ? 'Analyzing...' : 'Analyze' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      <span class="ml-2 text-gray-600">Analyzing SEO...</span>
    </div>

    <!-- SEO Score Overview -->
    <div v-else-if="analysis" class="p-6">
      <!-- Overall Score -->
      <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-lg font-medium text-gray-900">Overall SEO Score</h4>
          <div class="flex items-center space-x-2">
            <div
              class="w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold text-white"
              :class="getScoreColor(analysis.overall_score)"
            >
              {{ analysis.overall_score }}
            </div>
            <div class="text-sm text-gray-600">
              <div>{{ getScoreLabel(analysis.overall_score) }}</div>
              <div class="text-xs">out of 100</div>
            </div>
          </div>
        </div>
        
        <!-- Score Breakdown -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div
            v-for="category in scoreCategories"
            :key="category.key"
            class="text-center p-4 bg-gray-50 rounded-lg"
          >
            <div
              class="w-12 h-12 mx-auto rounded-full flex items-center justify-center text-sm font-bold text-white mb-2"
              :class="getScoreColor(analysis[category.key + '_score'])"
            >
              {{ analysis[category.key + '_score'] || 0 }}
            </div>
            <div class="text-sm font-medium text-gray-900">{{ category.label }}</div>
          </div>
        </div>
      </div>

      <!-- SEO Issues -->
      <div class="space-y-6">
        <!-- Critical Issues -->
        <div v-if="analysis.critical_issues?.length > 0">
          <h5 class="text-md font-medium text-red-600 mb-3 flex items-center">
            <ExclamationTriangleIcon class="h-5 w-5 mr-2" />
            Critical Issues ({{ analysis.critical_issues.length }})
          </h5>
          <div class="space-y-3">
            <div
              v-for="issue in analysis.critical_issues"
              :key="issue.id"
              class="border border-red-200 rounded-lg p-4 bg-red-50"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <h6 class="text-sm font-medium text-red-900">{{ issue.title }}</h6>
                  <p class="text-sm text-red-700 mt-1">{{ issue.description }}</p>
                  <div v-if="issue.recommendation" class="mt-2">
                    <p class="text-xs font-medium text-red-800">Recommendation:</p>
                    <p class="text-xs text-red-700">{{ issue.recommendation }}</p>
                  </div>
                </div>
                <div class="ml-4 flex-shrink-0">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    Critical
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Warnings -->
        <div v-if="analysis.warnings?.length > 0">
          <h5 class="text-md font-medium text-yellow-600 mb-3 flex items-center">
            <ExclamationTriangleIcon class="h-5 w-5 mr-2" />
            Warnings ({{ analysis.warnings.length }})
          </h5>
          <div class="space-y-3">
            <div
              v-for="warning in analysis.warnings"
              :key="warning.id"
              class="border border-yellow-200 rounded-lg p-4 bg-yellow-50"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <h6 class="text-sm font-medium text-yellow-900">{{ warning.title }}</h6>
                  <p class="text-sm text-yellow-700 mt-1">{{ warning.description }}</p>
                  <div v-if="warning.recommendation" class="mt-2">
                    <p class="text-xs font-medium text-yellow-800">Recommendation:</p>
                    <p class="text-xs text-yellow-700">{{ warning.recommendation }}</p>
                  </div>
                </div>
                <div class="ml-4 flex-shrink-0">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    Warning
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Opportunities -->
        <div v-if="analysis.opportunities?.length > 0">
          <h5 class="text-md font-medium text-blue-600 mb-3 flex items-center">
            <LightBulbIcon class="h-5 w-5 mr-2" />
            Opportunities ({{ analysis.opportunities.length }})
          </h5>
          <div class="space-y-3">
            <div
              v-for="opportunity in analysis.opportunities"
              :key="opportunity.id"
              class="border border-blue-200 rounded-lg p-4 bg-blue-50"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <h6 class="text-sm font-medium text-blue-900">{{ opportunity.title }}</h6>
                  <p class="text-sm text-blue-700 mt-1">{{ opportunity.description }}</p>
                  <div v-if="opportunity.recommendation" class="mt-2">
                    <p class="text-xs font-medium text-blue-800">Recommendation:</p>
                    <p class="text-xs text-blue-700">{{ opportunity.recommendation }}</p>
                  </div>
                </div>
                <div class="ml-4 flex-shrink-0">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Opportunity
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Good Practices -->
        <div v-if="analysis.good_practices?.length > 0">
          <h5 class="text-md font-medium text-green-600 mb-3 flex items-center">
            <CheckCircleIcon class="h-5 w-5 mr-2" />
            Good Practices ({{ analysis.good_practices.length }})
          </h5>
          <div class="space-y-3">
            <div
              v-for="practice in analysis.good_practices"
              :key="practice.id"
              class="border border-green-200 rounded-lg p-4 bg-green-50"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <h6 class="text-sm font-medium text-green-900">{{ practice.title }}</h6>
                  <p class="text-sm text-green-700 mt-1">{{ practice.description }}</p>
                </div>
                <div class="ml-4 flex-shrink-0">
                  <CheckCircleIcon class="h-5 w-5 text-green-500" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Technical Details -->
      <div class="mt-8 pt-6 border-t border-gray-200">
        <h4 class="text-lg font-medium text-gray-900 mb-4">Technical Details</h4>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Meta Information -->
          <div>
            <h5 class="text-sm font-medium text-gray-900 mb-3">Meta Information</h5>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Title Length:</span>
                <span :class="getTitleLengthClass(analysis.meta?.title_length)">
                  {{ analysis.meta?.title_length || 0 }} characters
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Description Length:</span>
                <span :class="getDescriptionLengthClass(analysis.meta?.description_length)">
                  {{ analysis.meta?.description_length || 0 }} characters
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">H1 Tags:</span>
                <span :class="analysis.meta?.h1_count === 1 ? 'text-green-600' : 'text-red-600'">
                  {{ analysis.meta?.h1_count || 0 }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Images without Alt:</span>
                <span :class="analysis.meta?.images_without_alt === 0 ? 'text-green-600' : 'text-red-600'">
                  {{ analysis.meta?.images_without_alt || 0 }}
                </span>
              </div>
            </div>
          </div>

          <!-- Performance -->
          <div>
            <h5 class="text-sm font-medium text-gray-900 mb-3">Performance</h5>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Page Size:</span>
                <span class="font-medium">{{ formatBytes(analysis.performance?.page_size) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Load Time:</span>
                <span :class="getLoadTimeClass(analysis.performance?.load_time)">
                  {{ analysis.performance?.load_time || 0 }}ms
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">External Links:</span>
                <span class="font-medium">{{ analysis.performance?.external_links || 0 }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Internal Links:</span>
                <span class="font-medium">{{ analysis.performance?.internal_links || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Keywords Analysis -->
      <div v-if="analysis.keywords" class="mt-8 pt-6 border-t border-gray-200">
        <h4 class="text-lg font-medium text-gray-900 mb-4">Keywords Analysis</h4>
        
        <div class="space-y-4">
          <!-- Top Keywords -->
          <div>
            <h5 class="text-sm font-medium text-gray-900 mb-2">Top Keywords</h5>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="keyword in analysis.keywords.top_keywords"
                :key="keyword.word"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800"
              >
                {{ keyword.word }}
                <span class="ml-1 text-xs text-indigo-600">({{ keyword.count }})</span>
              </span>
            </div>
          </div>

          <!-- Keyword Density -->
          <div>
            <h5 class="text-sm font-medium text-gray-900 mb-2">Keyword Density</h5>
            <div class="space-y-2">
              <div
                v-for="keyword in analysis.keywords.density"
                :key="keyword.word"
                class="flex items-center justify-between"
              >
                <span class="text-sm text-gray-700">{{ keyword.word }}</span>
                <div class="flex items-center space-x-2">
                  <div class="w-24 bg-gray-200 rounded-full h-2">
                    <div
                      class="bg-indigo-600 h-2 rounded-full"
                      :style="{ width: `${Math.min(keyword.density * 10, 100)}%` }"
                    ></div>
                  </div>
                  <span class="text-xs text-gray-500 w-12 text-right">
                    {{ keyword.density.toFixed(1) }}%
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Export Options -->
      <div class="mt-8 pt-6 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <h4 class="text-lg font-medium text-gray-900">Export Report</h4>
          <div class="flex space-x-2">
            <button
              @click="exportReport('pdf')"
              class="bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium py-2 px-3 rounded-md"
            >
              Export PDF
            </button>
            <button
              @click="exportReport('csv')"
              class="bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium py-2 px-3 rounded-md"
            >
              Export CSV
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <MagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No SEO Analysis</h3>
      <p class="mt-1 text-sm text-gray-500">Click "Analyze" to start SEO analysis for this page.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  ExclamationTriangleIcon,
  LightBulbIcon,
  CheckCircleIcon,
  MagnifyingGlassIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  pageId: {
    type: [String, Number],
    required: true
  },
  url: {
    type: String,
    default: ''
  }
})

const { get, post, loading } = useApi()
const { showSuccess, showError } = useToast()

// Reactive data
const analysis = ref(null)

const scoreCategories = [
  { key: 'content', label: 'Content' },
  { key: 'technical', label: 'Technical' },
  { key: 'performance', label: 'Performance' },
  { key: 'mobile', label: 'Mobile' }
]

// Methods
const analyzePage = async () => {
  try {
    const data = await post(`/api/v1/cms/pages/${props.pageId}/seo-analysis`, {
      url: props.url
    })
    analysis.value = data.data
    showSuccess('SEO analysis completed')
  } catch (err) {
    console.error('Failed to analyze page:', err)
    showError('Failed to analyze page SEO')
  }
}

const getScoreColor = (score) => {
  if (score >= 80) return 'bg-green-500'
  if (score >= 60) return 'bg-yellow-500'
  if (score >= 40) return 'bg-orange-500'
  return 'bg-red-500'
}

const getScoreLabel = (score) => {
  if (score >= 80) return 'Excellent'
  if (score >= 60) return 'Good'
  if (score >= 40) return 'Needs Work'
  return 'Poor'
}

const getTitleLengthClass = (length) => {
  if (length >= 30 && length <= 60) return 'text-green-600'
  if (length >= 20 && length <= 70) return 'text-yellow-600'
  return 'text-red-600'
}

const getDescriptionLengthClass = (length) => {
  if (length >= 120 && length <= 160) return 'text-green-600'
  if (length >= 100 && length <= 180) return 'text-yellow-600'
  return 'text-red-600'
}

const getLoadTimeClass = (time) => {
  if (time <= 2000) return 'text-green-600'
  if (time <= 4000) return 'text-yellow-600'
  return 'text-red-600'
}

const formatBytes = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const exportReport = async (format) => {
  try {
    const response = await fetch(`/api/v1/cms/pages/${props.pageId}/seo-report`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('api_token')}`
      },
      body: JSON.stringify({
        format: format,
        analysis: analysis.value
      })
    })
    
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `seo-report.${format}`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
      
      showSuccess(`SEO report exported as ${format.toUpperCase()}`)
    } else {
      throw new Error('Export failed')
    }
  } catch (err) {
    console.error('Failed to export report:', err)
    showError('Failed to export SEO report')
  }
}

// Lifecycle
onMounted(() => {
  // Auto-analyze if URL is provided
  if (props.url) {
    analyzePage()
  }
})
</script>