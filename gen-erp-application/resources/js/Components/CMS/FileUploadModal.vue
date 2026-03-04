<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b">
          <h3 class="text-lg font-medium text-gray-900">Upload Files</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Upload Area -->
        <div class="mt-6">
          <div
            @drop="handleDrop"
            @dragover.prevent
            @dragenter.prevent
            @dragleave="handleDragLeave"
            :class="[
              'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
              isDragging ? 'border-indigo-400 bg-indigo-50' : 'border-gray-300 hover:border-gray-400'
            ]"
          >
            <CloudArrowUpIcon class="mx-auto h-12 w-12 text-gray-400" />
            <div class="mt-4">
              <label for="file-upload" class="cursor-pointer">
                <span class="mt-2 block text-sm font-medium text-gray-900">
                  Drop files here, or 
                  <span class="text-indigo-600 hover:text-indigo-500">browse</span>
                </span>
                <input
                  id="file-upload"
                  name="file-upload"
                  type="file"
                  multiple
                  @change="handleFileSelect"
                  class="sr-only"
                  :accept="acceptedTypes"
                />
              </label>
              <p class="mt-1 text-xs text-gray-500">
                {{ acceptedTypesText }}
              </p>
              <p class="text-xs text-gray-500">
                Maximum file size: {{ maxFileSizeText }}
              </p>
            </div>
          </div>
        </div>

        <!-- File List -->
        <div v-if="selectedFiles.length > 0" class="mt-6">
          <h4 class="text-sm font-medium text-gray-900 mb-4">Selected Files ({{ selectedFiles.length }})</h4>
          <div class="space-y-3 max-h-64 overflow-y-auto">
            <div
              v-for="(file, index) in selectedFiles"
              :key="index"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
            >
              <div class="flex items-center space-x-3">
                <!-- File Icon -->
                <div class="flex-shrink-0">
                  <PhotoIcon v-if="file.type.startsWith('image/')" class="h-8 w-8 text-green-600" />
                  <VideoCameraIcon v-else-if="file.type.startsWith('video/')" class="h-8 w-8 text-blue-600" />
                  <DocumentIcon v-else class="h-8 w-8 text-gray-600" />
                </div>
                
                <!-- File Info -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ file.name }}</p>
                  <p class="text-xs text-gray-500">
                    {{ formatFileSize(file.size) }} • {{ file.type }}
                  </p>
                </div>
              </div>
              
              <!-- Upload Status -->
              <div class="flex items-center space-x-2">
                <!-- Progress Bar -->
                <div v-if="file.uploading" class="w-20">
                  <div class="bg-gray-200 rounded-full h-2">
                    <div
                      class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                      :style="`width: ${file.progress}%`"
                    ></div>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">{{ file.progress }}%</p>
                </div>
                
                <!-- Success -->
                <CheckCircleIcon v-else-if="file.uploaded" class="h-5 w-5 text-green-600" />
                
                <!-- Error -->
                <ExclamationCircleIcon v-else-if="file.error" class="h-5 w-5 text-red-600" />
                
                <!-- Remove Button -->
                <button
                  v-if="!file.uploading"
                  @click="removeFile(index)"
                  class="text-gray-400 hover:text-red-600"
                >
                  <XMarkIcon class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Upload Settings -->
        <div v-if="selectedFiles.length > 0" class="mt-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Upload to Folder</label>
            <select
              v-model="uploadSettings.folder_id"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="">Root Folder</option>
              <option v-for="folder in folders" :key="folder.id" :value="folder.id">
                {{ folder.name }}
              </option>
            </select>
          </div>
          
          <div class="flex items-center">
            <input
              id="optimize-images"
              v-model="uploadSettings.optimize_images"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="optimize-images" class="ml-2 block text-sm text-gray-900">
              Optimize images for web (recommended)
            </label>
          </div>
          
          <div class="flex items-center">
            <input
              id="generate-thumbnails"
              v-model="uploadSettings.generate_thumbnails"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="generate-thumbnails" class="ml-2 block text-sm text-gray-900">
              Generate thumbnails
            </label>
          </div>
        </div>

        <!-- Error Messages -->
        <div v-if="errors.length > 0" class="mt-4">
          <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
              <ExclamationCircleIcon class="h-5 w-5 text-red-400" />
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Upload Errors</h3>
                <div class="mt-2 text-sm text-red-700">
                  <ul class="list-disc pl-5 space-y-1">
                    <li v-for="error in errors" :key="error">{{ error }}</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-6 border-t mt-6">
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="startUpload"
            :disabled="selectedFiles.length === 0 || uploading"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
          >
            <span v-if="uploading">Uploading...</span>
            <span v-else>Upload {{ selectedFiles.length }} File{{ selectedFiles.length !== 1 ? 's' : '' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  XMarkIcon,
  CloudArrowUpIcon,
  PhotoIcon,
  VideoCameraIcon,
  DocumentIcon,
  CheckCircleIcon,
  ExclamationCircleIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const emit = defineEmits(['close', 'uploaded'])

const { get, post } = useApi()
const { showToast } = useToast()

// Configuration
const maxFileSize = 10 * 1024 * 1024 // 10MB
const acceptedTypes = 'image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar'

// Reactive data
const selectedFiles = ref([])
const folders = ref([])
const isDragging = ref(false)
const uploading = ref(false)
const errors = ref([])

const uploadSettings = ref({
  folder_id: '',
  optimize_images: true,
  generate_thumbnails: true
})

// Computed properties
const acceptedTypesText = computed(() => {
  return 'Images, videos, documents, and archives'
})

const maxFileSizeText = computed(() => {
  return formatFileSize(maxFileSize)
})

// Methods
const handleDrop = (event) => {
  event.preventDefault()
  isDragging.value = false
  
  const files = Array.from(event.dataTransfer.files)
  processFiles(files)
}

const handleDragLeave = (event) => {
  if (!event.currentTarget.contains(event.relatedTarget)) {
    isDragging.value = false
  }
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  processFiles(files)
}

const processFiles = (files) => {
  errors.value = []
  
  files.forEach(file => {
    // Validate file size
    if (file.size > maxFileSize) {
      errors.value.push(`${file.name} is too large (max ${maxFileSizeText.value})`)
      return
    }
    
    // Check for duplicates
    const isDuplicate = selectedFiles.value.some(f => f.name === file.name && f.size === file.size)
    if (isDuplicate) {
      errors.value.push(`${file.name} is already selected`)
      return
    }
    
    // Add file to selection
    selectedFiles.value.push({
      file,
      name: file.name,
      size: file.size,
      type: file.type,
      uploading: false,
      uploaded: false,
      error: false,
      progress: 0
    })
  })
}

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1)
}

const startUpload = async () => {
  if (selectedFiles.value.length === 0) return
  
  uploading.value = true
  errors.value = []
  
  const uploadPromises = selectedFiles.value.map(async (fileItem, index) => {
    if (fileItem.uploaded) return
    
    fileItem.uploading = true
    fileItem.progress = 0
    
    try {
      const formData = new FormData()
      formData.append('file', fileItem.file)
      formData.append('folder_id', uploadSettings.value.folder_id || '')
      formData.append('optimize_images', uploadSettings.value.optimize_images)
      formData.append('generate_thumbnails', uploadSettings.value.generate_thumbnails)
      
      // Simulate progress for better UX
      const progressInterval = setInterval(() => {
        if (fileItem.progress < 90) {
          fileItem.progress += Math.random() * 20
        }
      }, 200)
      
      const response = await fetch('/api/v1/cms/media/upload', {
        method: 'POST',
        body: formData,
        headers: {
          'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      
      clearInterval(progressInterval)
      fileItem.progress = 100
      
      if (response.ok) {
        fileItem.uploaded = true
        fileItem.uploading = false
      } else {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Upload failed')
      }
    } catch (error) {
      console.error(`Failed to upload ${fileItem.name}:`, error)
      fileItem.error = true
      fileItem.uploading = false
      errors.value.push(`${fileItem.name}: ${error.message}`)
    }
  })
  
  await Promise.all(uploadPromises)
  
  uploading.value = false
  
  // Check if all files uploaded successfully
  const successfulUploads = selectedFiles.value.filter(f => f.uploaded).length
  const failedUploads = selectedFiles.value.filter(f => f.error).length
  
  if (successfulUploads > 0) {
    showToast(`${successfulUploads} file${successfulUploads !== 1 ? 's' : ''} uploaded successfully`, 'success')
    
    if (failedUploads === 0) {
      // All files uploaded successfully, close modal
      setTimeout(() => {
        emit('uploaded')
      }, 1000)
    }
  }
  
  if (failedUploads > 0) {
    showToast(`${failedUploads} file${failedUploads !== 1 ? 's' : ''} failed to upload`, 'error')
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

// Utility functions
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Lifecycle
onMounted(() => {
  fetchFolders()
  
  // Prevent default drag behaviors
  document.addEventListener('dragenter', (e) => e.preventDefault())
  document.addEventListener('dragover', (e) => e.preventDefault())
  document.addEventListener('drop', (e) => e.preventDefault())
})
</script>