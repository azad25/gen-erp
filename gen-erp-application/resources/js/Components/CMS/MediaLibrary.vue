<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-medium text-gray-900">Media Library</h3>
          <p class="text-sm text-gray-600">Manage your images, videos, and documents</p>
        </div>
        <div class="flex items-center space-x-3">
          <div class="flex items-center space-x-2">
            <button
              @click="viewMode = 'grid'"
              :class="[
                'p-2 rounded-md',
                viewMode === 'grid' ? 'bg-indigo-100 text-indigo-600' : 'text-gray-400 hover:text-gray-600'
              ]"
            >
              <ViewColumnsIcon class="h-5 w-5" />
            </button>
            <button
              @click="viewMode = 'list'"
              :class="[
                'p-2 rounded-md',
                viewMode === 'list' ? 'bg-indigo-100 text-indigo-600' : 'text-gray-400 hover:text-gray-600'
              ]"
            >
              <ListBulletIcon class="h-5 w-5" />
            </button>
          </div>
          <button
            @click="showUploadModal = true"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Upload Files
          </button>
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input
              v-model="searchQuery"
              @input="debouncedSearch"
              type="text"
              placeholder="Search files..."
              class="pl-10 pr-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
          
          <select
            v-model="filters.type"
            @change="applyFilters"
            class="border border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">All Types</option>
            <option value="image">Images</option>
            <option value="video">Videos</option>
            <option value="document">Documents</option>
            <option value="audio">Audio</option>
          </select>
          
          <select
            v-model="filters.folder"
            @change="applyFilters"
            class="border border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">All Folders</option>
            <option v-for="folder in folders" :key="folder.id" :value="folder.id">
              {{ folder.name }}
            </option>
          </select>
        </div>
        
        <div class="flex items-center space-x-2">
          <button
            @click="showCreateFolderModal = true"
            class="text-gray-600 hover:text-gray-800 text-sm font-medium"
          >
            New Folder
          </button>
          <button
            v-if="selectedFiles.length > 0"
            @click="showBulkActions = !showBulkActions"
            class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-md text-sm font-medium"
          >
            Actions ({{ selectedFiles.length }})
          </button>
        </div>
      </div>
    </div>

    <!-- Bulk Actions -->
    <div v-if="showBulkActions && selectedFiles.length > 0" class="px-6 py-3 bg-yellow-50 border-b border-yellow-200">
      <div class="flex items-center justify-between">
        <span class="text-sm text-yellow-800">{{ selectedFiles.length }} files selected</span>
        <div class="flex space-x-2">
          <button
            @click="bulkMove"
            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm"
          >
            Move
          </button>
          <button
            @click="bulkDownload"
            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
          >
            Download
          </button>
          <button
            @click="bulkDelete"
            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      <span class="ml-2 text-gray-600">Loading media...</span>
    </div>

    <!-- Media Grid View -->
    <div v-else-if="viewMode === 'grid'" class="p-6">
      <div v-if="files.length === 0" class="text-center py-12">
        <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No media files</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by uploading your first file.</p>
      </div>
      
      <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div
          v-for="file in files"
          :key="file.id"
          class="relative group cursor-pointer"
          @click="selectFile(file)"
          @dblclick="openFile(file)"
        >
          <!-- Selection Checkbox -->
          <div class="absolute top-2 left-2 z-10">
            <input
              type="checkbox"
              :value="file.id"
              v-model="selectedFiles"
              @click.stop
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
          </div>
          
          <!-- File Preview -->
          <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden border-2 border-transparent group-hover:border-indigo-300 transition-colors">
            <!-- Image Preview -->
            <img
              v-if="file.type === 'image'"
              :src="file.thumbnail_url || file.url"
              :alt="file.name"
              class="w-full h-full object-cover"
              @error="handleImageError"
            />
            
            <!-- Video Preview -->
            <div v-else-if="file.type === 'video'" class="w-full h-full flex items-center justify-center bg-gray-200">
              <PlayIcon class="h-8 w-8 text-gray-600" />
            </div>
            
            <!-- Document Preview -->
            <div v-else-if="file.type === 'document'" class="w-full h-full flex items-center justify-center bg-gray-200">
              <DocumentIcon class="h-8 w-8 text-gray-600" />
            </div>
            
            <!-- Audio Preview -->
            <div v-else-if="file.type === 'audio'" class="w-full h-full flex items-center justify-center bg-gray-200">
              <MusicalNoteIcon class="h-8 w-8 text-gray-600" />
            </div>
            
            <!-- Generic File Preview -->
            <div v-else class="w-full h-full flex items-center justify-center bg-gray-200">
              <DocumentIcon class="h-8 w-8 text-gray-600" />
            </div>
          </div>
          
          <!-- File Info -->
          <div class="mt-2">
            <p class="text-xs font-medium text-gray-900 truncate" :title="file.name">
              {{ file.name }}
            </p>
            <p class="text-xs text-gray-500">
              {{ formatFileSize(file.size) }}
            </p>
          </div>
          
          <!-- Actions Overlay -->
          <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100">
            <div class="flex space-x-2">
              <button
                @click.stop="previewFile(file)"
                class="bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-700 p-2 rounded-full"
                title="Preview"
              >
                <EyeIcon class="h-4 w-4" />
              </button>
              <button
                @click.stop="downloadFile(file)"
                class="bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-700 p-2 rounded-full"
                title="Download"
              >
                <ArrowDownTrayIcon class="h-4 w-4" />
              </button>
              <button
                @click.stop="showFileActions(file)"
                class="bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-700 p-2 rounded-full"
                title="More actions"
              >
                <EllipsisVerticalIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Media List View -->
    <div v-else-if="viewMode === 'list'" class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left">
              <input
                type="checkbox"
                :checked="isAllSelected"
                @change="toggleSelectAll"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              File
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Type
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Size
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Modified
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="file in files" :key="file.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
              <input
                type="checkbox"
                :value="file.id"
                v-model="selectedFiles"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10">
                  <img
                    v-if="file.type === 'image'"
                    :src="file.thumbnail_url || file.url"
                    :alt="file.name"
                    class="h-10 w-10 rounded object-cover"
                  />
                  <div v-else class="h-10 w-10 bg-gray-200 rounded flex items-center justify-center">
                    <DocumentIcon class="h-6 w-6 text-gray-600" />
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ file.name }}</div>
                  <div class="text-sm text-gray-500">{{ file.folder?.name || 'Root' }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                {{ file.mime_type }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ formatFileSize(file.size) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ formatDate(file.updated_at) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <div class="flex space-x-2">
                <button
                  @click="previewFile(file)"
                  class="text-indigo-600 hover:text-indigo-900"
                >
                  Preview
                </button>
                <button
                  @click="downloadFile(file)"
                  class="text-green-600 hover:text-green-900"
                >
                  Download
                </button>
                <button
                  @click="showFileActions(file)"
                  class="text-gray-600 hover:text-gray-900"
                >
                  More
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.total > pagination.per_page" class="px-6 py-4 border-t border-gray-200">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} files
        </div>
        <div class="flex space-x-2">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="changePage(page)"
            :disabled="page === pagination.current_page"
            :class="[
              'px-3 py-2 text-sm font-medium rounded-md',
              page === pagination.current_page
                ? 'bg-indigo-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>

    <!-- Upload Modal -->
    <FileUploadModal
      v-if="showUploadModal"
      @close="showUploadModal = false"
      @uploaded="handleFilesUploaded"
    />

    <!-- Create Folder Modal -->
    <CreateFolderModal
      v-if="showCreateFolderModal"
      @close="showCreateFolderModal = false"
      @created="handleFolderCreated"
    />

    <!-- File Preview Modal -->
    <FilePreviewModal
      v-if="previewingFile"
      :file="previewingFile"
      @close="previewingFile = null"
      @updated="handleFileUpdated"
      @deleted="handleFileDeleted"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ViewColumnsIcon,
  ListBulletIcon,
  MagnifyingGlassIcon,
  PhotoIcon,
  PlayIcon,
  DocumentIcon,
  MusicalNoteIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  EllipsisVerticalIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'
import { debounce } from 'lodash'
import FileUploadModal from './FileUploadModal.vue'
import CreateFolderModal from './CreateFolderModal.vue'
import FilePreviewModal from './FilePreviewModal.vue'

const { get, post, delete: del, loading } = useApi()
const { showToast } = useToast()

// Reactive data
const files = ref([])
const folders = ref([])
const selectedFiles = ref([])
const pagination = ref({})
const viewMode = ref('grid')
const searchQuery = ref('')
const previewingFile = ref(null)

const showUploadModal = ref(false)
const showCreateFolderModal = ref(false)
const showBulkActions = ref(false)

const filters = ref({
  type: '',
  folder: ''
})

// Computed properties
const isAllSelected = computed(() => {
  return files.value.length > 0 && selectedFiles.value.length === files.value.length
})

const visiblePages = computed(() => {
  if (!pagination.value.last_page) return []
  
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []
  
  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i)
  }
  
  return pages
})

// Methods
const fetchFiles = async (page = 1) => {
  try {
    const params = {
      page,
      per_page: 24,
      search: searchQuery.value,
      ...filters.value
    }
    
    const data = await get('/api/v1/cms/media', params)
    files.value = data.data
    pagination.value = data.meta
  } catch (err) {
    console.error('Failed to fetch files:', err)
    showToast('Failed to load media files', 'error')
  }
}

const fetchFolders = async () => {
  try {
    const data = await get('/api/v1/cms/media/folders')
    folders.value = data.data
  } catch (err) {
    console.error('Failed to fetch folders:', err)
  }
}

const debouncedSearch = debounce(() => {
  fetchFiles()
}, 300)

const applyFilters = () => {
  fetchFiles()
}

const selectFile = (file) => {
  const index = selectedFiles.value.indexOf(file.id)
  if (index > -1) {
    selectedFiles.value.splice(index, 1)
  } else {
    selectedFiles.value.push(file.id)
  }
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedFiles.value = []
  } else {
    selectedFiles.value = files.value.map(file => file.id)
  }
}

const openFile = (file) => {
  if (file.type === 'image' || file.type === 'video' || file.type === 'document') {
    previewFile(file)
  } else {
    downloadFile(file)
  }
}

const previewFile = (file) => {
  previewingFile.value = file
}

const downloadFile = async (file) => {
  try {
    const response = await fetch(file.download_url || file.url)
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = file.name
    a.click()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Failed to download file:', err)
    showToast('Failed to download file', 'error')
  }
}

const showFileActions = (file) => {
  // TODO: Implement file actions menu
  showToast('File actions menu coming soon', 'info')
}

const bulkMove = () => {
  // TODO: Implement bulk move
  showToast('Bulk move functionality coming soon', 'info')
}

const bulkDownload = async () => {
  // TODO: Implement bulk download
  showToast('Bulk download functionality coming soon', 'info')
}

const bulkDelete = async () => {
  if (!confirm(`Are you sure you want to delete ${selectedFiles.value.length} files?`)) return
  
  try {
    await post('/api/v1/cms/media/bulk-delete', {
      file_ids: selectedFiles.value
    })
    showToast('Files deleted successfully', 'success')
    selectedFiles.value = []
    showBulkActions.value = false
    fetchFiles()
  } catch (err) {
    console.error('Failed to delete files:', err)
    showToast('Failed to delete files', 'error')
  }
}

const changePage = (page) => {
  fetchFiles(page)
}

const handleFilesUploaded = () => {
  showUploadModal.value = false
  fetchFiles()
  showToast('Files uploaded successfully', 'success')
}

const handleFolderCreated = () => {
  showCreateFolderModal.value = false
  fetchFolders()
  showToast('Folder created successfully', 'success')
}

const handleFileUpdated = () => {
  fetchFiles()
  showToast('File updated successfully', 'success')
}

const handleFileDeleted = () => {
  previewingFile.value = null
  fetchFiles()
  showToast('File deleted successfully', 'success')
}

const handleImageError = (event) => {
  event.target.style.display = 'none'
  event.target.parentNode.innerHTML = '<div class="w-full h-full flex items-center justify-center bg-gray-200"><svg class="h-8 w-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div>'
}

// Utility functions
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Lifecycle
onMounted(() => {
  fetchFiles()
  fetchFolders()
})
</script>