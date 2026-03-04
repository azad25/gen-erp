<template>
  <div class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-[95vh] overflow-hidden">
      <!-- Header -->
      <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
          <div class="mr-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
              {{ document.name }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ document.human_size }} • {{ formatDate(document.uploaded_at) }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="downloadDocument"
            class="btn btn-secondary btn-sm"
          >
            <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
            {{ $t('documents.download') }}
          </button>
          <button
            v-if="canEdit"
            @click="openEditor"
            class="btn btn-primary btn-sm"
          >
            <PencilIcon class="w-4 h-4 mr-2" />
            {{ $t('documents.edit') }}
          </button>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-hidden" style="height: calc(95vh - 80px);">
        <!-- Image Viewer -->
        <div v-if="isImage" class="h-full flex items-center justify-center bg-gray-100 dark:bg-gray-900 p-4">
          <img
            :src="previewUrl"
            :alt="document.name"
            class="max-w-full max-h-full object-contain rounded-lg shadow-lg"
            @load="imageLoaded = true"
            @error="imageError = true"
          />
          <div v-if="!imageLoaded && !imageError" class="text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-500 dark:text-gray-400">{{ $t('documents.loading_preview') }}</p>
          </div>
          <div v-if="imageError" class="text-center">
            <PhotoIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
            <p class="text-gray-500 dark:text-gray-400">{{ $t('documents.preview_error') }}</p>
          </div>
        </div>

        <!-- PDF Viewer -->
        <div v-else-if="isPdf" class="h-full">
          <iframe
            :src="previewUrl"
            class="w-full h-full border-0"
            @load="pdfLoaded = true"
            @error="pdfError = true"
          ></iframe>
          <div v-if="!pdfLoaded && !pdfError" class="h-full flex items-center justify-center">
            <div class="text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
              <p class="text-gray-500 dark:text-gray-400">{{ $t('documents.loading_pdf') }}</p>
            </div>
          </div>
          <div v-if="pdfError" class="h-full flex items-center justify-center">
            <div class="text-center">
              <DocumentIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
              <p class="text-gray-500 dark:text-gray-400">{{ $t('documents.pdf_error') }}</p>
            </div>
          </div>
        </div>

        <!-- Text File Viewer -->
        <div v-else-if="isText" class="h-full overflow-auto p-6">
          <div v-if="textContent" class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
            <pre class="whitespace-pre-wrap text-sm text-gray-900 dark:text-gray-100 font-mono">{{ textContent }}</pre>
          </div>
          <div v-else-if="textLoading" class="text-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-500 dark:text-gray-400">{{ $t('documents.loading_text') }}</p>
          </div>
          <div v-else class="text-center py-12">
            <DocumentIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
            <p class="text-gray-500 dark:text-gray-400">{{ $t('documents.text_error') }}</p>
          </div>
        </div>

        <!-- Office Document Viewer -->
        <div v-else-if="isOfficeDocument" class="h-full">
          <iframe
            :src="officeViewerUrl"
            class="w-full h-full border-0"
            @load="officeLoaded = true"
            @error="officeError = true"
          ></iframe>
          <div v-if="!officeLoaded && !officeError" class="h-full flex items-center justify-center">
            <div class="text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
              <p class="text-gray-500 dark:text-gray-400">{{ $t('documents.loading_office') }}</p>
            </div>
          </div>
          <div v-if="officeError" class="h-full flex items-center justify-center">
            <div class="text-center">
              <DocumentIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
              <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $t('documents.office_error') }}</p>
              <button
                @click="downloadDocument"
                class="btn btn-primary"
              >
                {{ $t('documents.download_to_view') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Video Player -->
        <div v-else-if="isVideo" class="h-full flex items-center justify-center bg-black p-4">
          <video
            :src="previewUrl"
            controls
            class="max-w-full max-h-full"
            @loadeddata="videoLoaded = true"
            @error="videoError = true"
          >
            {{ $t('documents.video_not_supported') }}
          </video>
          <div v-if="!videoLoaded && !videoError" class="text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-white">{{ $t('documents.loading_video') }}</p>
          </div>
        </div>

        <!-- Audio Player -->
        <div v-else-if="isAudio" class="h-full flex items-center justify-center p-8">
          <div class="text-center">
            <div class="w-32 h-32 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-6">
              <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM15.657 6.343a1 1 0 011.414 0A9.972 9.972 0 0119 12a9.972 9.972 0 01-1.929 5.657 1 1 0 11-1.414-1.414A7.971 7.971 0 0017 12a7.971 7.971 0 00-1.343-4.243 1 1 0 010-1.414z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M13.828 8.172a1 1 0 011.414 0A5.983 5.983 0 0117 12a5.983 5.983 0 01-1.758 3.828 1 1 0 11-1.414-1.414A3.987 3.987 0 0015 12a3.987 3.987 0 00-1.172-2.828 1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </div>
            <audio
              :src="previewUrl"
              controls
              class="w-full max-w-md"
              @loadeddata="audioLoaded = true"
              @error="audioError = true"
            >
              {{ $t('documents.audio_not_supported') }}
            </audio>
          </div>
        </div>

        <!-- Unsupported File Type -->
        <div v-else class="h-full flex items-center justify-center">
          <div class="text-center">
            <DocumentIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
              {{ $t('documents.preview_not_available') }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">
              {{ $t('documents.preview_not_supported', { type: document.mime_type }) }}
            </p>
            <button
              @click="downloadDocument"
              class="btn btn-primary"
            >
              <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
              {{ $t('documents.download_to_view') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Document Editor Modal -->
    <DocumentEditor
      v-if="showEditor"
      :document="document"
      @close="showEditor = false"
      @saved="handleDocumentSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import DocumentEditor from './DocumentEditor.vue'
import {
  XMarkIcon,
  ArrowDownTrayIcon,
  PencilIcon,
  DocumentIcon,
  PhotoIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  document: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close'])

const { $t } = useTranslations()

// State
const previewUrl = ref('')
const textContent = ref('')
const showEditor = ref(false)

// Loading states
const imageLoaded = ref(false)
const imageError = ref(false)
const pdfLoaded = ref(false)
const pdfError = ref(false)
const textLoading = ref(false)
const officeLoaded = ref(false)
const officeError = ref(false)
const videoLoaded = ref(false)
const videoError = ref(false)
const audioLoaded = ref(false)
const audioError = ref(false)

// Computed
const isImage = computed(() => props.document.mime_type.startsWith('image/'))
const isPdf = computed(() => props.document.mime_type === 'application/pdf')
const isText = computed(() => {
  const textTypes = ['text/plain', 'text/csv', 'text/html', 'text/markdown']
  return textTypes.includes(props.document.mime_type)
})
const isOfficeDocument = computed(() => {
  const officeTypes = [
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation'
  ]
  return officeTypes.includes(props.document.mime_type)
})
const isVideo = computed(() => props.document.mime_type.startsWith('video/'))
const isAudio = computed(() => props.document.mime_type.startsWith('audio/'))

const canEdit = computed(() => {
  const editableTypes = [
    'text/plain',
    'text/html',
    'text/markdown',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
  ]
  return editableTypes.includes(props.document.mime_type)
})

const officeViewerUrl = computed(() => {
  if (!isOfficeDocument.value) return ''
  // Use Microsoft Office Online viewer
  return `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(previewUrl.value)}`
})

// Methods
const loadPreviewUrl = async () => {
  try {
    const response = await axios.get(`/api/v1/documents/${props.document.id}/preview`)
    previewUrl.value = response.data.data.preview_url
  } catch (error) {
    console.error('Error loading preview URL:', error)
  }
}

const loadTextContent = async () => {
  if (!isText.value) return
  
  textLoading.value = true
  try {
    const response = await axios.get(previewUrl.value)
    textContent.value = response.data
  } catch (error) {
    console.error('Error loading text content:', error)
  } finally {
    textLoading.value = false
  }
}

const downloadDocument = async () => {
  try {
    const response = await axios.get(`/api/v1/documents/${props.document.id}/download`)
    const downloadUrl = response.data.data.download_url
    window.open(downloadUrl, '_blank')
  } catch (error) {
    console.error('Error downloading document:', error)
  }
}

const openEditor = () => {
  showEditor.value = true
}

const handleDocumentSaved = () => {
  showEditor.value = false
  // Reload preview if needed
  if (isText.value) {
    loadTextContent()
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

// Lifecycle
onMounted(async () => {
  await loadPreviewUrl()
  if (isText.value) {
    await loadTextContent()
  }
})
</script>