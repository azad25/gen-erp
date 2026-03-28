<template>
  
    <AppLayout :title="$t('documents.recent')">
      <div class="p-6">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ $t('documents.recent') }}
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ $t('documents.recent_description') }}
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

      <!-- Time Filter -->
      <div class="mb-6">
        <div class="flex gap-2">
          <button
            v-for="filter in timeFilters"
            :key="filter.key"
            @click="selectedTimeFilter = filter.key"
            :class="[
              'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
              selectedTimeFilter === filter.key
                ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
            ]"
          >
            {{ filter.label }}
          </button>
        </div>
      </div>

      <!-- Documents List -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6">
          <div v-if="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          </div>
          
          <div v-else-if="documents.length === 0" class="text-center py-12">
            <DocumentIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
              {{ $t('documents.no_recent_documents') }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">
              {{ $t('documents.upload_documents_to_see_recent') }}
            </p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="document in documents"
              :key="document.id"
              class="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group"
            >
              <!-- File Icon/Thumbnail -->
              <div class="flex-shrink-0 mr-4">
                <div
                  v-if="document.is_image"
                  class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center overflow-hidden"
                >
                  <img
                    v-if="document.thumbnail_url"
                    :src="document.thumbnail_url"
                    :alt="document.name"
                    class="w-full h-full object-cover"
                  />
                  <PhotoIcon v-else class="w-6 h-6 text-gray-400" />
                </div>
                <div
                  v-else
                  class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center"
                >
                  <DocumentIcon class="w-6 h-6 text-gray-400" />
                </div>
              </div>

              <!-- Document Info -->
              <div class="flex-1 min-w-0">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                  {{ document.name }}
                </h3>
                <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                  <span>{{ document.human_size }}</span>
                  <span>{{ formatDate(document.uploaded_at) }}</span>
                  <span v-if="document.folder">{{ document.folder.name }}</span>
                  <span>{{ $t('documents.uploaded_by') }} {{ document.uploader?.name }}</span>
                </div>
              </div>

              <!-- Actions -->
              <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                <button
                  v-if="document.is_previewable"
                  @click="previewDocument(document)"
                  class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                  :title="$t('documents.preview')"
                >
                  <EyeIcon class="w-4 h-4" />
                </button>
                <button
                  @click="downloadDocument(document)"
                  class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600"
                  :title="$t('documents.download')"
                >
                  <ArrowDownTrayIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Document Viewer Modal -->
    <DocumentViewer
      v-if="viewingDocument"
      :document="viewingDocument"
      @close="viewingDocument = null"
    />
  </AppLayout>

</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import AppLayout from '@/Layouts/AppLayout.vue'
import DocumentViewer from '@/Components/Documents/DocumentViewer.vue'
import {
  DocumentIcon,
  PhotoIcon,
  CloudArrowUpIcon,
  EyeIcon,
  ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'

const { $t } = useTranslations()

// State
const loading = ref(false)
const documents = ref([])
const selectedTimeFilter = ref('week')
const viewingDocument = ref(null)

// Time filters
const timeFilters = computed(() => [
  { key: 'today', label: $t('documents.today') },
  { key: 'week', label: $t('documents.this_week') },
  { key: 'month', label: $t('documents.this_month') },
  { key: 'quarter', label: $t('documents.this_quarter') },
  { key: 'year', label: $t('documents.this_year') },
])

// Methods
const loadDocuments = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/documents', {
      params: {
        recent: selectedTimeFilter.value,
        per_page: 50
      }
    })
    documents.value = response.data.data.data || []
  } catch (error) {
    console.error('Error loading recent documents:', error)
  } finally {
    loading.value = false
  }
}

const previewDocument = (document) => {
  viewingDocument.value = document
}

const downloadDocument = async (document) => {
  try {
    const response = await axios.get(`/api/v1/documents/${document.id}/download`)
    const downloadUrl = response.data.data.download_url
    window.open(downloadUrl, '_blank')
  } catch (error) {
    console.error('Error downloading document:', error)
  }
}

const navigateToUpload = () => {
  window.location.href = '/documents'
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

// Watchers
watch(selectedTimeFilter, () => {
  loadDocuments()
})

// Lifecycle
onMounted(() => {
  loadDocuments()
})
</script>