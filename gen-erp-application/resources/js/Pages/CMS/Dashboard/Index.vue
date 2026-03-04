<template>
  <AppLayout title="CMS Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        CMS Dashboard
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <h1 class="text-2xl font-bold mb-4">CMS Dashboard</h1>
            <p class="mb-6">Manage your websites, pages, and content</p>
            
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
              <span class="ml-2 text-gray-600">Loading dashboard data...</span>
            </div>
            
            <!-- Error State -->
            <div v-else-if="error" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
              <p><strong>Error:</strong> {{ error }}</p>
              <button @click="fetchDashboardData" class="mt-2 text-sm underline">Retry</button>
            </div>
            
            <!-- Dashboard Content -->
            <div v-else>
              <!-- Main Metrics Grid -->
              <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-blue-50 p-6 rounded-lg">
                  <div class="text-3xl font-bold text-blue-600">{{ metrics.totalSites || 0 }}</div>
                  <div class="text-sm text-gray-600">Total Sites</div>
                </div>
                
                <div class="bg-green-50 p-6 rounded-lg">
                  <div class="text-3xl font-bold text-green-600">{{ metrics.totalPages || 0 }}</div>
                  <div class="text-sm text-gray-600">Total Pages</div>
                </div>
                
                <div class="bg-purple-50 p-6 rounded-lg">
                  <div class="text-3xl font-bold text-purple-600">{{ metrics.totalBlogPosts || 0 }}</div>
                  <div class="text-sm text-gray-600">Blog Posts</div>
                </div>
                
                <div class="bg-orange-50 p-6 rounded-lg">
                  <div class="text-3xl font-bold text-orange-600">{{ metrics.totalMedia || 0 }}</div>
                  <div class="text-sm text-gray-600">Media Files</div>
                </div>
              </div>

              <!-- Secondary Metrics -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-yellow-50 p-6 rounded-lg">
                  <div class="text-3xl font-bold text-yellow-600">{{ metrics.publishedPages || 0 }}</div>
                  <div class="text-sm text-gray-600">Published Pages</div>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-lg">
                  <div class="text-3xl font-bold text-gray-600">{{ metrics.draftPages || 0 }}</div>
                  <div class="text-sm text-gray-600">Draft Pages</div>
                </div>
                
                <div class="bg-indigo-50 p-6 rounded-lg">
                  <div class="text-3xl font-bold text-indigo-600">{{ metrics.totalContacts || 0 }}</div>
                  <div class="text-sm text-gray-600">Contact Submissions</div>
                </div>
              </div>

              <!-- Recent Activity -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Pages -->
                <div class="bg-white border rounded-lg p-6">
                  <h3 class="text-lg font-semibold mb-4">Recent Pages</h3>
                  <div v-if="recentPages.length === 0" class="text-gray-500 text-sm">
                    No recent pages found
                  </div>
                  <div v-else class="space-y-3">
                    <div v-for="page in recentPages" :key="page.id" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                      <div>
                        <div class="font-medium text-sm">{{ page.title }}</div>
                        <div class="text-xs text-gray-500">{{ page.site?.name || 'Unknown Site' }}</div>
                      </div>
                      <div class="text-xs text-gray-400">
                        {{ formatDate(page.updated_at) }}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Recent Blog Posts -->
                <div class="bg-white border rounded-lg p-6">
                  <h3 class="text-lg font-semibold mb-4">Recent Blog Posts</h3>
                  <div v-if="recentBlogPosts.length === 0" class="text-gray-500 text-sm">
                    No recent blog posts found
                  </div>
                  <div v-else class="space-y-3">
                    <div v-for="post in recentBlogPosts" :key="post.id" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                      <div>
                        <div class="font-medium text-sm">{{ post.title }}</div>
                        <div class="text-xs text-gray-500">{{ post.author?.name || 'Unknown Author' }}</div>
                      </div>
                      <div class="text-xs text-gray-400">
                        {{ formatDate(post.created_at) }}
                      </div>
                    </div>
                  </div>
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
import { ref, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const { get, loading, error } = useApi()
const { showToast } = useToast()

// Reactive data
const metrics = ref({
  totalSites: 0,
  totalPages: 0,
  totalBlogPosts: 0,
  totalMedia: 0,
  publishedPages: 0,
  draftPages: 0,
  totalContacts: 0,
})

const recentPages = ref([])
const recentBlogPosts = ref([])

// Methods
const fetchDashboardData = async () => {
  try {
    // Fetch dashboard metrics
    const dashboardData = await get('/api/v1/cms/dashboard')
    metrics.value = dashboardData.data || metrics.value

    // Fetch recent pages
    const pagesData = await get('/api/v1/cms/pages', { limit: 5, sort: 'updated_at', order: 'desc' })
    recentPages.value = pagesData.data || []

    // Fetch recent blog posts
    const blogData = await get('/api/v1/cms/blog', { limit: 5, sort: 'created_at', order: 'desc' })
    recentBlogPosts.value = blogData.data || []

  } catch (err) {
    console.error('Failed to fetch CMS dashboard data:', err)
    showToast('Failed to load dashboard data', 'error')
  }
}

const formatDate = (date) => {
  if (!date) return 'Unknown'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  fetchDashboardData()
})
</script>