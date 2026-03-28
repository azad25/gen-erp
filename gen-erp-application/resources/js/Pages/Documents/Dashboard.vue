<template>
  
    <AppLayout :title="$t('documents.dashboard')">
      <div class="p-6">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ $t('documents.dashboard') }}
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ $t('documents.dashboard_description') }}
          </p>
        </div>
        <div class="flex gap-3">
          <button
            @click="navigateToUpload"
            class="btn btn-primary"
          >
            <CloudArrowUpIcon class="w-4 h-4 mr-2" />
            {{ $t('documents.upload') }}
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <DocumentIcon class="w-8 h-8 text-blue-500" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ $t('documents.total_documents') }}
              </p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.total_documents || 0 }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <FolderIcon class="w-8 h-8 text-green-500" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ $t('documents.total_folders') }}
              </p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.total_folders || 0 }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CloudArrowUpIcon class="w-8 h-8 text-purple-500" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ $t('documents.storage_used') }}
              </p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.storage_used || '0 MB' }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ClockIcon class="w-8 h-8 text-orange-500" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ $t('documents.recent_uploads') }}
              </p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ stats.recent_uploads || 0 }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            {{ $t('documents.quick_actions') }}
          </h3>
          <div class="space-y-3">
            <button
              @click="navigateToUpload"
              class="w-full flex items-center p-3 text-left bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
            >
              <CloudArrowUpIcon class="w-5 h-5 text-blue-500 mr-3" />
              <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ $t('documents.upload_documents') }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('documents.upload_description') }}</p>
              </div>
            </button>
            
            <button
              @click="navigateToFolders"
              class="w-full flex items-center p-3 text-left bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors"
            >
              <FolderPlusIcon class="w-5 h-5 text-green-500 mr-3" />
              <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ $t('documents.manage_folders') }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('documents.folders_description') }}</p>
              </div>
            </button>
          </div>
        </div>

        <!-- Storage Usage -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            {{ $t('documents.storage_overview') }}
          </h3>
          <div class="space-y-4">
            <div>
              <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-600 dark:text-gray-400">{{ $t('documents.used') }}</span>
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ stats.storage_used || '0 MB' }} / {{ stats.storage_quota || '50 MB' }}
                </span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div 
                  class="h-2 rounded-full transition-all duration-300"
                  :style="{ width: `${Math.min(stats.storage_percent || 0, 100)}%` }"
                  :class="{
                    'bg-red-500': (stats.storage_percent || 0) > 90,
                    'bg-yellow-500': (stats.storage_percent || 0) > 75,
                    'bg-blue-500': (stats.storage_percent || 0) <= 75
                  }"
                ></div>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                {{ stats.storage_remaining || '50 MB' }} {{ $t('documents.remaining') }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Documents -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ $t('documents.recent_documents') }}
            </h3>
            <Link
              href="/documents/recent"
              class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium"
            >
              {{ $t('documents.view_all') }}
            </Link>
          </div>
        </div>
        <div class="p-6">
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          </div>
          
          <div v-else-if="recentDocuments.length === 0" class="text-center py-8">
            <DocumentIcon class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
            <p class="text-gray-500 dark:text-gray-400">{{ $t('documents.no_recent_documents') }}</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="document in recentDocuments"
              :key="document.id"
              class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
              <div class="flex-shrink-0 mr-3">
                <div
                  v-if="document.is_image"
                  class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center overflow-hidden"
                >
                  <img
                    v-if="document.thumbnail_url"
                    :src="document.thumbnail_url"
                    :alt="document.name"
                    class="w-full h-full object-cover"
                  />
                  <PhotoIcon v-else class="w-5 h-5 text-gray-400" />
                </div>
                <div
                  v-else
                  class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center"
                >
                  <DocumentIcon class="w-5 h-5 text-gray-400" />
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                  {{ document.name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  {{ document.human_size }} • {{ formatDate(document.uploaded_at) }}
                </p>
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
import { useTranslations } from '@/Composables/useTranslations'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  DocumentIcon,
  FolderIcon,
  CloudArrowUpIcon,
  ClockIcon,
  FolderPlusIcon,
  PhotoIcon,
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  company: {
    type: Object,
    default: () => ({})
  }
})

const { $t } = useTranslations()

// State
const loading = ref(false)
const stats = ref({})
const recentDocuments = ref([])

// Methods
const loadDashboardData = async () => {
  loading.value = true
  try {
    // Set default stats first
    stats.value = {
      total_documents: 0,
      total_folders: 0,
      storage_used: '0 MB',
      storage_quota: '50 MB',
      storage_percent: 0,
      storage_remaining: '50 MB',
      recent_uploads: 0
    }
    
    console.log('[Documents Dashboard] Loading data...')
    
    // Try to load real data from API with better error handling
    const [statsResponse, documentsResponse] = await Promise.allSettled([
      axios.get('/api/v1/documents/storage-info'),
      axios.get('/api/v1/documents', { params: { per_page: 5 } })
    ])

    // Handle stats response
    if (statsResponse.status === 'fulfilled' && statsResponse.value.data.data) {
      console.log('[Documents Dashboard] Storage info loaded successfully')
      const storageData = statsResponse.value.data.data
      stats.value.storage_used = storageData.used_formatted || '0 MB'
      stats.value.storage_quota = storageData.quota_formatted || '50 MB'
      stats.value.storage_percent = storageData.usage_percent || 0
      stats.value.storage_remaining = storageData.remaining_formatted || '50 MB'
    } else {
      console.warn('[Documents Dashboard] Storage info failed:', statsResponse.reason?.message || 'Unknown error')
    }
    
    // Handle documents response
    if (documentsResponse.status === 'fulfilled' && documentsResponse.value.data.data) {
      console.log('[Documents Dashboard] Documents loaded successfully')
      const documentsData = documentsResponse.value.data.data
      recentDocuments.value = documentsData.data || []
      stats.value.total_documents = documentsData.total || 0
      stats.value.recent_uploads = recentDocuments.value.length
    } else {
      console.warn('[Documents Dashboard] Documents failed:', documentsResponse.reason?.message || 'Unknown error')
    }
    
    // Get folder count with error handling
    try {
      const foldersResponse = await axios.get('/api/v1/document-folders', { params: { per_page: 1 } })
      stats.value.total_folders = foldersResponse.data.data.total || 0
      console.log('[Documents Dashboard] Folders loaded successfully')
    } catch (error) {
      console.warn('[Documents Dashboard] Could not load folder count:', error.response?.status, error.message)
      stats.value.total_folders = 0
    }
    
  } catch (error) {
    console.error('[Documents Dashboard] Error loading dashboard data:', error)
    // Keep default stats on error - don't let this break the page
  } finally {
    loading.value = false
    console.log('[Documents Dashboard] Data loading completed')
  }
}

const navigateToUpload = () => {
  window.location.href = '/documents'
}

const navigateToFolders = () => {
  window.location.href = '/documents/folders'
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) {
    return $t('documents.today')
  } else if (diffDays === 2) {
    return $t('documents.yesterday')
  } else if (diffDays <= 7) {
    return $t('documents.days_ago', { count: diffDays - 1 })
  } else {
    return date.toLocaleDateString()
  }
}

// Lifecycle
onMounted(() => {
  loadDashboardData()
})
</script>