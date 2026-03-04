<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">
          Attachments ({{ attachments.length }})
        </h3>
        <div class="flex items-center space-x-2">
          <button
            @click="$refs.fileInput.click()"
            class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-2 py-1 rounded-md"
          >
            Add Files
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

    <!-- Hidden File Input -->
    <input
      ref="fileInput"
      type="file"
      multiple
      class="hidden"
      @change="handleFileUpload"
    />

    <!-- Content -->
    <div v-if="!collapsed" class="p-4">
      <!-- Upload Progress -->
      <div v-if="uploadProgress.length > 0" class="mb-4 space-y-2">
        <div
          v-for="upload in uploadProgress"
          :key="upload.id"
          class="flex items-center space-x-3 p-2 bg-gray-50 rounded-md"
        >
          <div class="flex-1">
            <div class="flex items-center justify-between text-sm">
              <span class="font-medium text-gray-900">{{ upload.name }}</span>
              <span class="text-gray-500">{{ upload.progress }}%</span>
            </div>
            <div class="mt-1 w-full bg-gray-200 rounded-full h-1.5">
              <div
                class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300"
                :style="{ width: `${upload.progress}%` }"
              ></div>
            </div>
          </div>
          <button
            v-if="upload.progress < 100"
            @click="cancelUpload(upload.id)"
            class="text-gray-400 hover:text-red-600"
          >
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>
      </div>

      <!-- Attachments Grid -->
      <div v-if="attachments.length > 0" class="grid grid-cols-1 gap-3">
        <div
          v-for="attachment in attachments"
          :key="attachment.id"
          class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
        >
          <!-- File Icon -->
          <div class="flex-shrink-0">
            <div
              class="w-10 h-10 rounded-lg flex items-center justify-center"
              :class="getFileIconClass(attachment.mime_type)"
            >
              <component :is="getFileIcon(attachment.mime_type)" class="h-5 w-5" />
            </div>
          </div>
          
          <!-- File Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ attachment.name }}
              </p>
              <div class="flex items-center space-x-2">
                <span class="text-xs text-gray-500">
                  {{ formatFileSize(attachment.size) }}
                </span>
                <div class="flex items-center space-x-1">
                  <button
                    @click="downloadAttachment(attachment)"
                    class="text-gray-400 hover:text-gray-600"
                    title="Download"
                  >
                    <ArrowDownTrayIcon class="h-4 w-4" />
                  </button>
                  <button
                    v-if="canPreview(attachment.mime_type)"
                    @click="previewAttachment(attachment)"
                    class="text-gray-400 hover:text-gray-600"
                    title="Preview"
                  >
                    <EyeIcon class="h-4 w-4" />
                  </button>
                  <button
                    v-if="canDeleteAttachment(attachment)"
                    @click="deleteAttachment(attachment)"
                    class="text-gray-400 hover:text-red-600"
                    title="Delete"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>
            <div class="flex items-center justify-between mt-1">
              <p class="text-xs text-gray-500">
                Uploaded by {{ attachment.user?.name }} • {{ formatDate(attachment.created_at) }}
              </p>
              <div v-if="attachment.is_image" class="flex items-center space-x-1">
                <span class="text-xs text-gray-400">{{ attachment.dimensions }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-8">
        <PaperClipIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No attachments</h3>
        <p class="mt-1 text-sm text-gray-500">Upload files to share with your team.</p>
        <div class="mt-6">
          <button
            @click="$refs.fileInput.click()"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
          >
            <PaperClipIcon class="h-4 w-4 mr-2" />
            Upload Files
          </button>
        </div>
      </div>

      <!-- Drag & Drop Zone -->
      <div
        v-if="isDragging"
        class="fixed inset-0 bg-indigo-600 bg-opacity-50 flex items-center justify-center z-50"
        @dragover.prevent
        @drop.prevent="handleDrop"
        @dragleave="isDragging = false"
      >
        <div class="bg-white rounded-lg p-8 text-center">
          <CloudArrowUpIcon class="mx-auto h-12 w-12 text-indigo-600" />
          <p class="mt-2 text-lg font-medium text-gray-900">Drop files here to upload</p>
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <AttachmentPreviewModal
      v-if="previewingAttachment"
      :attachment="previewingAttachment"
      @close="previewingAttachment = null"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  PaperClipIcon,
  XMarkIcon,
  ArrowDownTrayIcon,
  EyeIcon,
  TrashIcon,
  CloudArrowUpIcon,
  DocumentIcon,
  PhotoIcon,
  VideoCameraIcon,
  MusicalNoteIcon,
  ArchiveBoxIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useAuth } from '@/Composables/useAuth'
import { useToast } from '@/Composables/useToast'
import AttachmentPreviewModal from './AttachmentPreviewModal.vue'

const props = defineProps({
  taskId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['attachment-added', 'attachment-deleted'])

const { get, post, delete: del, loading } = useApi()
const { user: currentUser } = useAuth()
const { showSuccess, showError } = useToast()

// Reactive data
const collapsed = ref(false)
const attachments = ref([])
const uploadProgress = ref([])
const isDragging = ref(false)
const previewingAttachment = ref(null)

// Methods
const fetchAttachments = async () => {
  try {
    const data = await get(`/api/v1/tasks/${props.taskId}/attachments`)
    attachments.value = data.data
  } catch (err) {
    console.error('Failed to fetch attachments:', err)
  }
}

const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  uploadFiles(files)
  event.target.value = '' // Reset input
}

const handleDrop = (event) => {
  isDragging.value = false
  const files = Array.from(event.dataTransfer.files)
  uploadFiles(files)
}

const uploadFiles = async (files) => {
  for (const file of files) {
    const uploadId = Date.now() + Math.random()
    const upload = {
      id: uploadId,
      name: file.name,
      progress: 0
    }
    
    uploadProgress.value.push(upload)
    
    try {
      const formData = new FormData()
      formData.append('file', file)
      
      // Simulate progress (in real implementation, use XMLHttpRequest for progress tracking)
      const progressInterval = setInterval(() => {
        if (upload.progress < 90) {
          upload.progress += Math.random() * 20
        }
      }, 200)
      
      const data = await post(`/api/v1/tasks/${props.taskId}/attachments`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      
      clearInterval(progressInterval)
      upload.progress = 100
      
      // Add to attachments list
      attachments.value.unshift(data.data)
      
      // Remove from upload progress after a delay
      setTimeout(() => {
        const index = uploadProgress.value.findIndex(u => u.id === uploadId)
        if (index > -1) {
          uploadProgress.value.splice(index, 1)
        }
      }, 1000)
      
      showSuccess(`${file.name} uploaded successfully`)
      emit('attachment-added', data.data)
    } catch (err) {
      console.error('Failed to upload file:', err)
      showError(`Failed to upload ${file.name}`)
      
      // Remove from upload progress
      const index = uploadProgress.value.findIndex(u => u.id === uploadId)
      if (index > -1) {
        uploadProgress.value.splice(index, 1)
      }
    }
  }
}

const cancelUpload = (uploadId) => {
  const index = uploadProgress.value.findIndex(u => u.id === uploadId)
  if (index > -1) {
    uploadProgress.value.splice(index, 1)
  }
}

const downloadAttachment = (attachment) => {
  const link = document.createElement('a')
  link.href = attachment.download_url
  link.download = attachment.name
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const previewAttachment = (attachment) => {
  previewingAttachment.value = attachment
}

const deleteAttachment = async (attachment) => {
  if (!confirm(`Are you sure you want to delete ${attachment.name}?`)) return
  
  try {
    await del(`/api/v1/attachments/${attachment.id}`)
    
    const index = attachments.value.findIndex(a => a.id === attachment.id)
    if (index > -1) {
      attachments.value.splice(index, 1)
    }
    
    showSuccess('Attachment deleted successfully')
    emit('attachment-deleted', attachment)
  } catch (err) {
    console.error('Failed to delete attachment:', err)
    showError('Failed to delete attachment')
  }
}

const canDeleteAttachment = (attachment) => {
  return currentUser.value?.id === attachment.user_id
}

const canPreview = (mimeType) => {
  return mimeType.startsWith('image/') || 
         mimeType.startsWith('video/') || 
         mimeType === 'application/pdf' ||
         mimeType.startsWith('text/')
}

const getFileIcon = (mimeType) => {
  if (mimeType.startsWith('image/')) return PhotoIcon
  if (mimeType.startsWith('video/')) return VideoCameraIcon
  if (mimeType.startsWith('audio/')) return MusicalNoteIcon
  if (mimeType.includes('zip') || mimeType.includes('rar')) return ArchiveBoxIcon
  return DocumentIcon
}

const getFileIconClass = (mimeType) => {
  if (mimeType.startsWith('image/')) return 'bg-green-100 text-green-600'
  if (mimeType.startsWith('video/')) return 'bg-purple-100 text-purple-600'
  if (mimeType.startsWith('audio/')) return 'bg-yellow-100 text-yellow-600'
  if (mimeType.includes('zip') || mimeType.includes('rar')) return 'bg-orange-100 text-orange-600'
  return 'bg-blue-100 text-blue-600'
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: date.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined
  })
}

// Drag & Drop Event Listeners
const handleDragEnter = (e) => {
  e.preventDefault()
  isDragging.value = true
}

const handleDragLeave = (e) => {
  e.preventDefault()
  if (e.target === document.body) {
    isDragging.value = false
  }
}

const handleDragOver = (e) => {
  e.preventDefault()
}

// Lifecycle
onMounted(() => {
  fetchAttachments()
  
  // Add drag & drop event listeners
  document.addEventListener('dragenter', handleDragEnter)
  document.addEventListener('dragleave', handleDragLeave)
  document.addEventListener('dragover', handleDragOver)
})

onUnmounted(() => {
  // Remove drag & drop event listeners
  document.removeEventListener('dragenter', handleDragEnter)
  document.removeEventListener('dragleave', handleDragLeave)
  document.removeEventListener('dragover', handleDragOver)
})
</script>