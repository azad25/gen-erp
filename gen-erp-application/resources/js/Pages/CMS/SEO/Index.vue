<template>
  <div>
    <Head title="SEO Management" />
    
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">SEO Management</h1>
              <div class="flex space-x-2">
                <button
                  @click="generateSitemap"
                  class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                  Generate Sitemap
                </button>
                <button
                  @click="runSeoAudit"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                  Run SEO Audit
                </button>
              </div>
            </div>

            <!-- SEO Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
              <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ seoStats.optimized }}</div>
                <div class="text-sm text-green-600">Optimized Pages</div>
              </div>
              <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600">{{ seoStats.needsWork }}</div>
                <div class="text-sm text-yellow-600">Needs Improvement</div>
              </div>
              <div class="bg-red-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-red-600">{{ seoStats.issues }}</div>
                <div class="text-sm text-red-600">SEO Issues</div>
              </div>
              <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">{{ seoStats.avgScore }}</div>
                <div class="text-sm text-blue-600">Average Score</div>
              </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
              <nav class="-mb-px flex space-x-8">
                <button
                  v-for="tab in tabs"
                  :key="tab.id"
                  @click="activeTab = tab.id"
                  :class="[
                    activeTab === tab.id
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                    'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                  ]"
                >
                  {{ tab.name }}
                </button>
              </nav>
            </div>

            <!-- Page SEO Analysis Tab -->
            <div v-if="activeTab === 'pages'" class="space-y-6">
              <div class="flex space-x-4 mb-4">
                <input
                  v-model="pageSearchQuery"
                  type="text"
                  placeholder="Search pages..."
                  class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                />
                <select
                  v-model="selectedSeoStatus"
                  class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">All Status</option>
                  <option value="good">Good</option>
                  <option value="warning">Needs Work</option>
                  <option value="error">Issues</option>
                </select>
              </div>

              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Page
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        SEO Score
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Issues
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Last Checked
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="page in filteredPages" :key="page.id">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                          <div class="text-sm font-medium text-gray-900">{{ page.title }}</div>
                          <div class="text-sm text-gray-500">{{ page.url }}</div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div
                            :class="{
                              'bg-green-100': page.seo_score >= 80,
                              'bg-yellow-100': page.seo_score >= 60 && page.seo_score < 80,
                              'bg-red-100': page.seo_score < 60
                            }"
                            class="px-2 py-1 rounded-full text-sm font-medium"
                          >
                            <span
                              :class="{
                                'text-green-800': page.seo_score >= 80,
                                'text-yellow-800': page.seo_score >= 60 && page.seo_score < 80,
                                'text-red-800': page.seo_score < 60
                              }"
                            >
                              {{ page.seo_score }}/100
                            </span>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                          <div v-for="issue in page.issues.slice(0, 2)" :key="issue" class="mb-1">
                            • {{ issue }}
                          </div>
                          <div v-if="page.issues.length > 2" class="text-gray-500">
                            +{{ page.issues.length - 2 }} more
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ formatDate(page.last_checked) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex space-x-2">
                          <button
                            @click="analyzePage(page)"
                            class="text-blue-600 hover:text-blue-900"
                          >
                            Analyze
                          </button>
                          <button
                            @click="optimizePage(page)"
                            class="text-green-600 hover:text-green-900"
                          >
                            Optimize
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Keywords Tab -->
            <div v-if="activeTab === 'keywords'" class="space-y-6">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Keyword Tracking</h3>
                <button
                  @click="addKeyword"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                  Add Keyword
                </button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div
                  v-for="keyword in keywords"
                  :key="keyword.id"
                  class="bg-white border border-gray-200 rounded-lg p-4"
                >
                  <div class="flex justify-between items-start mb-2">
                    <h4 class="font-medium text-gray-900">{{ keyword.term }}</h4>
                    <span
                      :class="{
                        'text-green-600': keyword.trend === 'up',
                        'text-red-600': keyword.trend === 'down',
                        'text-gray-600': keyword.trend === 'stable'
                      }"
                      class="text-sm"
                    >
                      {{ keyword.position }}
                    </span>
                  </div>
                  <div class="text-sm text-gray-500 mb-2">
                    Volume: {{ keyword.volume }} | Difficulty: {{ keyword.difficulty }}
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-400">
                      Updated {{ formatDate(keyword.updated_at) }}
                    </span>
                    <button
                      @click="removeKeyword(keyword.id)"
                      class="text-red-600 hover:text-red-900 text-sm"
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Settings Tab -->
            <div v-if="activeTab === 'settings'" class="space-y-6">
              <div class="max-w-2xl">
                <h3 class="text-lg font-medium text-gray-900 mb-4">SEO Settings</h3>
                
                <form @submit.prevent="saveSettings" class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Default Meta Description</label>
                    <textarea
                      v-model="seoSettings.default_meta_description"
                      rows="3"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    ></textarea>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Default Keywords</label>
                    <input
                      v-model="seoSettings.default_keywords"
                      type="text"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                      placeholder="keyword1, keyword2, keyword3"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Google Analytics ID</label>
                    <input
                      v-model="seoSettings.google_analytics_id"
                      type="text"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                      placeholder="GA-XXXXXXXXX-X"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Google Search Console</label>
                    <input
                      v-model="seoSettings.google_search_console"
                      type="text"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Verification code"
                    />
                  </div>

                  <div class="flex items-center">
                    <input
                      v-model="seoSettings.auto_generate_sitemap"
                      type="checkbox"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <label class="ml-2 block text-sm text-gray-900">
                      Auto-generate sitemap
                    </label>
                  </div>

                  <button
                    type="submit"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                  >
                    Save Settings
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const activeTab = ref('pages')
const pageSearchQuery = ref('')
const selectedSeoStatus = ref('')

const tabs = [
  { id: 'pages', name: 'Page Analysis' },
  { id: 'keywords', name: 'Keywords' },
  { id: 'settings', name: 'Settings' }
]

const seoStats = ref({
  optimized: 12,
  needsWork: 5,
  issues: 3,
  avgScore: 78
})

const pages = ref([
  {
    id: 1,
    title: 'Home Page',
    url: '/',
    seo_score: 85,
    issues: ['Missing alt text on 2 images'],
    last_checked: '2024-01-15T10:30:00Z'
  },
  {
    id: 2,
    title: 'About Us',
    url: '/about',
    seo_score: 65,
    issues: ['Meta description too short', 'Missing H1 tag', 'No internal links'],
    last_checked: '2024-01-14T14:20:00Z'
  }
])

const keywords = ref([
  {
    id: 1,
    term: 'premium widgets',
    position: '#3',
    volume: '1.2K',
    difficulty: 'Medium',
    trend: 'up',
    updated_at: '2024-01-15T10:30:00Z'
  },
  {
    id: 2,
    term: 'best gadgets',
    position: '#12',
    volume: '5.4K',
    difficulty: 'High',
    trend: 'down',
    updated_at: '2024-01-14T14:20:00Z'
  }
])

const seoSettings = ref({
  default_meta_description: 'Your trusted partner for premium products and services.',
  default_keywords: 'widgets, gadgets, premium, quality',
  google_analytics_id: '',
  google_search_console: '',
  auto_generate_sitemap: true
})

const filteredPages = computed(() => {
  return pages.value.filter(page => {
    const searchMatch = !pageSearchQuery.value || 
      page.title.toLowerCase().includes(pageSearchQuery.value.toLowerCase()) ||
      page.url.toLowerCase().includes(pageSearchQuery.value.toLowerCase())
    
    let statusMatch = true
    if (selectedSeoStatus.value) {
      if (selectedSeoStatus.value === 'good' && page.seo_score < 80) statusMatch = false
      if (selectedSeoStatus.value === 'warning' && (page.seo_score < 60 || page.seo_score >= 80)) statusMatch = false
      if (selectedSeoStatus.value === 'error' && page.seo_score >= 60) statusMatch = false
    }
    
    return searchMatch && statusMatch
  })
})

const analyzePage = (page) => {
  alert(`Analyzing SEO for: ${page.title}`)
}

const optimizePage = (page) => {
  alert(`Opening SEO optimization for: ${page.title}`)
}

const addKeyword = () => {
  alert('Add keyword functionality will be implemented')
}

const removeKeyword = (keywordId) => {
  if (confirm('Are you sure you want to remove this keyword?')) {
    alert('Keyword removal functionality will be implemented')
  }
}

const generateSitemap = () => {
  alert('Sitemap generation functionality will be implemented')
}

const runSeoAudit = () => {
  alert('SEO audit functionality will be implemented')
}

const saveSettings = () => {
  alert('Settings save functionality will be implemented')
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}
</script>