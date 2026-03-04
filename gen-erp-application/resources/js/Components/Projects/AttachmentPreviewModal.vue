<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50">
    <div class="relative min-h-screen flex items-center justify-center p-4">
      <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
          <div class="flex items-center space-x-3">
            <div
              class="w-8 h-8 rounded-lg flex items-center justify-center"
              :class="getFileIconClass(attachment.mime_type)"
            >
              <component :is="getFileIcon(attachment.mime_type)" class="h-4 w-4" />
            </div>
            <div>
              <h3 class="text-lg font-medium text-gray-900">{{ attachment.name }}</h3>
              <p class="text-sm text-gray-500">
                {{ formatFileSize(attachment.size) }} • 
                Uploaded by {{ attachment.user?.name }} • 
                {{ formatDate(attachment.created_at) }}
              </p>
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <button
              @click="downloadAttachment"
              class="text-gray-400 hover:text-gray-600"
              title="Download"
            >
              <ArrowDownTrayIcon class="h-5 w-5" />
            </button>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="p-4 max-h-[calc(90vh-120px)] overflow-auto">
          <!-- Image Preview -->
          <div v-if="attachment.mime_type.startsWith('image/')" class="text-center">
            <img
              :src="attachment.preview_url || attachment.url"
              :alt="attachment.name"
              class="max-w-full max-h-[60vh] object-contain mx-auto rounded-lg"
              @load="imageLoaded = true"
              @error="imageError = true"
            />
            <div v-if="!imageLoaded && !imageError" class="flex justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
            <div v-if="imageError" class="text-center py-8">
              <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
              <p class="mt-2 text-sm text-gray-500">Failed to load image</p>
            </div>
          </div>

          <!-- Video Preview -->
          <div v-else-if="attachment.mime_type.startsWith('video/')" class="text-center">
            <video
              :src="attachment.url"
              controls
              class="max-w-full max-h-[60vh] mx-auto rounded-lg"
              @loadeddata="videoLoaded = true"
              @error="videoError = true"
            >
              Your browser does not support the video tag.
            </video>
            <div v-if="!videoLoaded && !videoError" class="flex justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
            <div v-if="videoError" class="text-center py-8">
              <VideoCameraIcon class="mx-auto h-12 w-12 text-gray-400" />
              <p class="mt-2 text-sm text-gray-500">Failed to load video</p>
            </div>
          </div>

          <!-- Audio Preview -->
          <div v-else-if="attachment.mime_type.startsWith('audio/')" class="text-center">
            <div class="bg-gray-100 rounded-lg p-8 mb-4">
              <MusicalNoteIcon class="mx-auto h-16 w-16 text-gray-400 mb-4" />
              <h4 class="text-lg font-medium text-gray-900 mb-2">{{ attachment.name }}</h4>
            </div>
            <audio
              :src="attachment.url"
              controls
              class="w-full max-w-md mx-auto"
              @loadeddata="audioLoaded = true"
              @error="audioError = true"
            >
              Your browser does not support the audio tag.
            </audio>
            <div v-if="audioError" class="text-center py-4">
              <p class="text-sm text-gray-500">Failed to load audio</p>
            </div>
          </div>

          <!-- PDF Preview -->
          <div v-else-if="attachment.mime_type === 'application/pdf'" class="text-center">
            <iframe
              :src="attachment.url"
              class="w-full h-[60vh] border border-gray-300 rounded-lg"
              @load="pdfLoaded = true"
              @error="pdfError = true"
            ></iframe>
            <div v-if="!pdfLoaded && !pdfError" class="flex justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
            <div v-if="pdfError" class="text-center py-8">
              <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
              <p class="mt-2 text-sm text-gray-500">Failed to load PDF</p>
              <button
                @click="downloadAttachment"
                class="mt-2 text-indigo-600 hover:text-indigo-500"
              >
                Download to view
              </button>
            </div>
          </div>

          <!-- Text Preview -->
          <div v-else-if="attachment.mime_type.startsWith('text/')" class="text-left">
            <div class="bg-gray-50 rounded-lg p-4 font-mono text-sm overflow-auto max-h-[60vh]">
              <pre v-if="textContent">{{ textContent }}</pre>
              <div v-else-if="loadingText" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
              </div>
              <div v-else class="text-center py-8">
                <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm text-gray-500">Failed to load text content</p>
              </div>
            </div>
          </div>

          <!-- Unsupported File Type -->
          <div v-else class="text-center py-8">
            <component :is="getFileIcon(attachment.mime_type)" class="mx-auto h-16 w-16 text-gray-400" />
            <h3 class="mt-4 text-lg font-medium text-gray-900">{{ attachment.name }}</h3>
            <p class="mt-2 text-sm text-gray-500">
              This file type cannot be previewed in the browser.
            </p>
            <button
              @click="downloadAttachment"
              class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
            >
              <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
              Download File
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  XMarkIcon,
  ArrowDownTrayIcon,
  PhotoIcon,
  VideoCameraIcon,
  MusicalNoteIcon,
  DocumentIcon,
  ArchiveBoxIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  attachment: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close'])

// Reactive data
const imageLoaded = ref(false)
const imageError = ref(false)
const videoLoaded = ref(false)
const videoError = ref(false)
const audioLoaded = ref(false)
const audioError = ref(false)
const pdfLoaded = ref(false)
const pdfError = ref(false)
const textContent = ref('')
const loadingText = ref(false)

// Methods
const downloadAttachment = () => {
  const link = document.createElement('a')
  link.href = props.attachment.download_url || props.attachment.url
  link.download = props.attachment.name
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
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
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadTextContent = async () => {
  if (!props.attachment.mime_type.startsWith('text/')) return
  
  loadingText.value = true
  try {
    const response = await fetch(props.attachment.url)
    const text = await response.text()
    textContent.value = text
  } catch (err) {
    console.error('Failed to load text content:', err)
  } finally {
    loadingText.value = false
  }
}

// Lifecycle
onMounted(() => {
  if (props.attachment.mime_type.startsWith('text/')) {
    loadTextContent()
  }
})
</script>