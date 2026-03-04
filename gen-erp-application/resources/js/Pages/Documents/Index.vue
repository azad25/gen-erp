<template>
  <SidebarProvider>
    <AppLayout :title="$t('documents.title')">
      <div class="p-6">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ $t('documents.title') }}
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ $t('documents.manage_description') }}
          </p>
        </div>
        <div class="flex gap-3">
          <button
            @click="showCreateFolder = true"
            class="btn btn-secondary"
          >
            <FolderPlusIcon class="w-4 h-4 mr-2" />
            {{ $t('documents.create_folder') }}
          </button>
          <button
            @click="showUpload = true"
            class="btn btn-primary"
          >
            <CloudArrowUpIcon class="w-4 h-4 mr-2" />
            {{ $t('documents.upload') }}
          </button>
        </div>
      </div>

      <!-- Storage Usage -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
              {{ $t('documents.storage_usage') }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              {{ storageUsed }} / {{ storageQuota }} ({{ storagePercent }}%)
            </p>
          </div>
          <div class="w-32">
            <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div 
                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${Math.min(storagePercent, 100)}%` }"
                :class="{
                  'bg-red-600': storagePercent > 90,
                  'bg-yellow-600': storagePercent > 75,
                  'bg-blue-600': storagePercent <= 75
                }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Breadcrumb -->
      <nav class="flex mb-4" v-if="currentPath.length > 0">
        <ol class="flex items-center space-x-2">
          <li>
            <button
              @click="navigateToFolder(null)"
              class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            >
              {{ $t('documents.root') }}
            </button>
          </li>
          <li v-for="(folder, index) in currentPath" :key="folder.id" class="flex items-center">
            <ChevronRightIcon class="w-4 h-4 text-gray-400 mx-2" />
            <button
              v-if="index < currentPath.length - 1"
              @click="navigateToFolder(folder.id)"
              class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            >
              {{ folder.name }}
            </button>
            <span v-else class="text-gray-500 dark:text-gray-400">
              {{ folder.name }}
            </span>
          </li>
        </ol>
      </nav>

      <!-- Search and Filters -->
      <div class="flex gap-4 mb-6">
        <div class="flex-1">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="$t('documents.search_placeholder')"
            class="input w-full"
            @input="debouncedSearch"
          />
        </div>
        <select v-model="selectedMimeType" class="input w-48">
          <option value="">{{ $t('documents.all_types') }}</option>
          <option value="image/">{{ $t('documents.images') }}</option>
          <option value="application/pdf">{{ $t('documents.pdfs') }}</option>
          <option value="application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">{{ $t('documents.documents') }}</option>
          <option value="application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">{{ $t('documents.spreadsheets') }}</option>
        </select>
      </div>

      <!-- Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Folders -->
        <div
          v-for="folder in folders"
          :key="`folder-${folder.id}`"
          @click="navigateToFolder(folder.id)"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 cursor-pointer hover:shadow-md transition-shadow group"
        >
          <div class="flex items-center">
            <FolderIcon class="w-8 h-8 text-blue-500 mr-3" />
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                {{ folder.name }}
              </h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ folder.documents_count }} {{ $t('documents.files') }}
              </p>
            </div>
            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
              <button
                @click.stop="editFolder(folder)"
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              >
                <PencilIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Documents -->
        <div
          v-for="document in documents"
          :key="`doc-${document.id}`"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 group"
        >
          <div class="flex items-start">
            <div class="flex-shrink-0 mr-3">
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
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                {{ document.name }}
              </h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ document.human_size }} • {{ formatDate(document.uploaded_at) }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $t('documents.uploaded_by') }} {{ document.uploader?.name }}
              </p>
            </div>
            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
              <button
                v-if="document.is_previewable"
                @click="previewDocument(document)"
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                :title="$t('documents.preview')"
              >
                <EyeIcon class="w-4 h-4" />
              </button>
              <button
                @click="downloadDocument(document)"
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                :title="$t('documents.download')"
              >
                <ArrowDownTrayIcon class="w-4 h-4" />
              </button>
              <button
                @click="editDocument(document)"
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                :title="$t('documents.edit')"
              >
                <PencilIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="folders.length === 0 && documents.length === 0 && !loading" class="text-center py-12">
        <FolderIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
          {{ $t('documents.empty_state_title') }}
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">
          {{ $t('documents.empty_state_description') }}
        </p>
        <button
          @click="showUpload = true"
          class="btn btn-primary"
        >
          {{ $t('documents.upload_first') }}
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
      </div>
    </div>

    <!-- Upload Modal -->
    <DocumentUpload
      v-if="showUpload"
      :folder-id="currentFolderId"
      @close="showUpload = false"
      @uploaded="handleDocumentUploaded"
    />

    <!-- Create Folder Modal -->
    <CreateFolderModal
      v-if="showCreateFolder"
      :parent-id="currentFolderId"
      @close="showCreateFolder = false"
      @created="handleFolderCreated"
    />

    <!-- Document Viewer Modal -->
    <DocumentViewer
      v-if="viewingDocument"
      :document="viewingDocument"
      @close="viewingDocument = null"
    />

    <!-- Edit Document Modal -->
    <EditDocumentModal
      v-if="editingDocument"
      :document="editingDocument"
      @close="editingDocument = null"
      @updated="handleDocumentUpdated"
    />

    <!-- Edit Folder Modal -->
    <EditFolderModal
      v-if="editingFolder"
      :folder="editingFolder"
      @close="editingFolder = null"
      @updated="handleFolderUpdated"
    />
  </AppLayout>
</SidebarProvider>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import AppLayout from '@/Layouts/AppLayout.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import DocumentUpload from '@/Components/Documents/DocumentUpload.vue'
import CreateFolderModal from '@/Components/Documents/CreateFolderModal.vue'
import DocumentViewer from '@/Components/Documents/DocumentViewer.vue'
import EditDocumentModal from '@/Components/Documents/EditDocumentModal.vue'
import EditFolderModal from '@/Components/Documents/EditFolderModal.vue'
import {
  FolderIcon,
  FolderPlusIcon,
  CloudArrowUpIcon,
  ChevronRightIcon,
  DocumentIcon,
  PhotoIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  PencilIcon,
} from '@heroicons/vue/24/outline'
import { debounce } from 'lodash'

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
const folders = ref([])
const documents = ref([])
const currentFolderId = ref(null)
const currentPath = ref([])
const searchQuery = ref('')
const selectedMimeType = ref('')
const storageUsed = ref('0 MB')
const storageQuota = ref('50 MB')
const storagePercent = ref(0)

// Modals
const showUpload = ref(false)
const showCreateFolder = ref(false)
const viewingDocument = ref(null)
const editingDocument = ref(null)
const editingFolder = ref(null)

// Methods
const loadData = async () => {
  loading.value = true
  try {
    const [foldersResponse, documentsResponse] = await Promise.all([
      axios.get('/api/v1/document-folders', {
        params: {
          parent_id: currentFolderId.value,
          search: searchQuery.value || undefined,
          per_page: 100
        }
      }),
      axios.get('/api/v1/documents', {
        params: {
          folder_id: currentFolderId.value,
          search: searchQuery.value || undefined,
          mime_type: selectedMimeType.value || undefined,
          per_page: 100
        }
      })
    ])

    folders.value = foldersResponse.data.data.data || []
    documents.value = documentsResponse.data.data.data || []
  } catch (error) {
    console.error('Error loading documents:', error)
  } finally {
    loading.value = false
  }
}

const loadStorageInfo = async () => {
  try {
    const response = await axios.get('/api/v1/documents/storage-info')
    const data = response.data.data
    storageUsed.value = data.used_formatted
    storageQuota.value = data.quota_formatted
    storagePercent.value = data.usage_percent
  } catch (error) {
    console.error('Error loading storage info:', error)
  }
}

const navigateToFolder = async (folderId) => {
  currentFolderId.value = folderId
  
  // Build path
  if (folderId) {
    try {
      const response = await axios.get(`/api/v1/document-folders/${folderId}`)
      const folder = response.data.data
      
      // Build path from folder hierarchy
      const path = []
      let current = folder
      while (current) {
        path.unshift(current)
        current = current.parent
      }
      currentPath.value = path
    } catch (error) {
      console.error('Error loading folder path:', error)
    }
  } else {
    currentPath.value = []
  }
  
  await loadData()
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

const editDocument = (document) => {
  editingDocument.value = document
}

const editFolder = (folder) => {
  editingFolder.value = folder
}

const handleDocumentUploaded = () => {
  showUpload.value = false
  loadData()
  loadStorageInfo()
}

const handleFolderCreated = () => {
  showCreateFolder.value = false
  loadData()
}

const handleDocumentUpdated = () => {
  editingDocument.value = null
  loadData()
}

const handleFolderUpdated = () => {
  editingFolder.value = null
  loadData()
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

const debouncedSearch = debounce(() => {
  loadData()
}, 300)

// Watchers
watch([selectedMimeType], () => {
  loadData()
})

// Lifecycle
onMounted(() => {
  loadData()
  loadStorageInfo()
})
</script>