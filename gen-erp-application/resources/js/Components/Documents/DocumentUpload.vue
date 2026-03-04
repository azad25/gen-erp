<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            {{ $t('documents.upload_documents') }}
          </h2>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <!-- Drag and Drop Area -->
        <div
          @drop="handleDrop"
          @dragover.prevent
          @dragenter.prevent
          :class="[
            'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
            isDragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600'
          ]"
          @dragenter="isDragging = true"
          @dragleave="isDragging = false"
        >
          <CloudArrowUpIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
          <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            {{ $t('documents.drag_drop_files') }}
          </p>
          <p class="text-gray-500 dark:text-gray-400 mb-4">
            {{ $t('documents.or_click_to_browse') }}
          </p>
          <input
            ref="fileInput"
            type="file"
            multiple
            @change="handleFileSelect"
            class="hidden"
            accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.bmp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.html,.md,.zip,.rar,.gz,.mp4,.mpeg,.webm,.mp3,.wav,.ogg"
          />
          <button
            @click="$refs.fileInput.click()"
            class="btn btn-primary"
          >
            {{ $t('documents.select_files') }}
          </button>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
            {{ $t('documents.supported_formats') }}
          </p>
        </div>

        <!-- Selected Files -->
        <div v-if="selectedFiles.length > 0" class="mt-6">
          <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
            {{ $t('documents.selected_files') }} ({{ selectedFiles.length }})
          </h3>
          <div class="space-y-2 max-h-60 overflow-y-auto">
            <div
              v-for="(file, index) in selectedFiles"
              :key="index"
              class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
            >
              <div class="flex items-center flex-1 min-w-0">
                <DocumentIcon class="w-5 h-5 text-gray-400 mr-3 flex-shrink-0" />
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ file.name }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatFileSize(file.size) }}
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <div v-if="uploadProgress[index] !== undefined" class="flex items-center">
                  <div class="w-16 bg-gray-200 dark:bg-gray-600 rounded-full h-2 mr-2">
                    <div
                      class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                      :style="{ width: `${uploadProgress[index]}%` }"
                    ></div>
                  </div>
                  <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ uploadProgress[index] }}%
                  </span>
                </div>
                <button
                  @click="removeFile(index)"
                  class="text-red-500 hover:text-red-700 dark:hover:text-red-400"
                  :disabled="uploading"
                >
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Upload Options -->
        <div v-if="selectedFiles.length > 0" class="mt-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ $t('documents.description') }} ({{ $t('common.optional') }})
            </label>
            <textarea
              v-model="description"
              rows="2"
              class="input w-full"
              :placeholder="$t('documents.description_placeholder')"
            ></textarea>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
          <button
            @click="$emit('close')"
            class="btn btn-secondary"
            :disabled="uploading"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            @click="uploadFiles"
            class="btn btn-primary"
            :disabled="selectedFiles.length === 0 || uploading"
          >
            <div v-if="uploading" class="flex items-center">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
              {{ $t('documents.uploading') }}
            </div>
            <span v-else>
              {{ $t('documents.upload') }} ({{ selectedFiles.length }})
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import {
  XMarkIcon,
  CloudArrowUpIcon,
  DocumentIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  folderId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['close', 'uploaded'])

const { $t } = useTranslations()

// State
const selectedFiles = ref([])
const description = ref('')
const uploading = ref(false)
const isDragging = ref(false)
const uploadProgress = reactive({})

// Methods
const handleDrop = (e) => {
  e.preventDefault()
  isDragging.value = false
  const files = Array.from(e.dataTransfer.files)
  addFiles(files)
}

const handleFileSelect = (e) => {
  const files = Array.from(e.target.files)
  addFiles(files)
}

const addFiles = (files) => {
  // Filter out duplicates and validate files
  const newFiles = files.filter(file => {
    const isDuplicate = selectedFiles.value.some(existing => 
      existing.name === file.name && existing.size === file.size
    )
    return !isDuplicate && validateFile(file)
  })
  
  selectedFiles.value.push(...newFiles)
}

const validateFile = (file) => {
  const maxSize = 10 * 1024 * 1024 // 10MB
  const allowedTypes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp',
    'application/pdf',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain', 'text/csv', 'text/html', 'text/markdown',
    'application/zip', 'application/x-rar-compressed', 'application/gzip',
    'video/mp4', 'video/mpeg', 'video/webm',
    'audio/mpeg', 'audio/wav', 'audio/ogg'
  ]

  if (file.size > maxSize) {
    alert($t('documents.file_too_large', { name: file.name, max: '10MB' }))
    return false
  }

  if (!allowedTypes.includes(file.type)) {
    alert($t('documents.file_type_not_allowed', { name: file.name, type: file.type }))
    return false
  }

  return true
}

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1)
  delete uploadProgress[index]
}

const formatFileSize = (bytes) => {
  if (bytes >= 1073741824) {
    return (bytes / 1073741824).toFixed(1) + ' GB'
  }
  if (bytes >= 1048576) {
    return (bytes / 1048576).toFixed(1) + ' MB'
  }
  if (bytes >= 1024) {
    return (bytes / 1024).toFixed(1) + ' KB'
  }
  return bytes + ' B'
}

const uploadFiles = async () => {
  if (selectedFiles.value.length === 0) return

  uploading.value = true

  try {
    const uploadPromises = selectedFiles.value.map(async (file, index) => {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('name', file.name)
      if (props.folderId) {
        formData.append('folder_id', props.folderId)
      }
      if (description.value) {
        formData.append('description', description.value)
      }

      return axios.post('/api/v1/documents', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        },
        onUploadProgress: (progressEvent) => {
          const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total)
          uploadProgress[index] = progress
        }
      })
    })

    await Promise.all(uploadPromises)
    emit('uploaded')
  } catch (error) {
    console.error('Upload error:', error)
    alert($t('documents.upload_error'))
  } finally {
    uploading.value = false
  }
}
</script>