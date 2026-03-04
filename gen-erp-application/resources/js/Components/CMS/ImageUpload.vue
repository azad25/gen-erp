<template>
  <div class="image-upload">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">
      {{ label }}
    </label>

    <!-- Upload Area -->
    <div
      @drop="handleDrop"
      @dragover.prevent
      @dragenter.prevent
      @dragleave="dragLeave"
      :class="{
        'border-blue-400 bg-blue-50': isDragging,
        'border-gray-300': !isDragging
      }"
      class="relative border-2 border-dashed rounded-lg p-6 text-center hover:border-gray-400 transition-colors"
    >
      <!-- Current Image Preview -->
      <div v-if="currentImage" class="relative">
        <img
          :src="currentImage"
          :alt="alt"
          class="max-w-full max-h-48 mx-auto rounded-lg shadow-sm"
        />
        
        <!-- Image Actions -->
        <div class="absolute top-2 right-2 flex space-x-1">
          <button
            @click="editImage"
            class="p-1 bg-white rounded-full shadow-md hover:bg-gray-50 transition-colors"
            title="Edit Image"
          >
            <Icon name="heroicons:pencil" class="w-4 h-4 text-gray-600" />
          </button>
          <button
            @click="removeImage"
            class="p-1 bg-white rounded-full shadow-md hover:bg-gray-50 transition-colors"
            title="Remove Image"
          >
            <Icon name="heroicons:trash" class="w-4 h-4 text-red-600" />
          </button>
        </div>

        <!-- Image Info -->
        <div v-if="imageInfo" class="mt-2 text-xs text-gray-500">
          {{ imageInfo.name }} ({{ formatFileSize(imageInfo.size) }})
        </div>
      </div>

      <!-- Upload Prompt -->
      <div v-else>
        <Icon name="heroicons:cloud-arrow-up" class="w-12 h-12 mx-auto mb-4 text-gray-400" />
        <div class="space-y-2">
          <p class="text-sm text-gray-600">
            <button
              @click="openFileDialog"
              class="font-medium text-blue-600 hover:text-blue-500"
            >
              Click to upload
            </button>
            or drag and drop
          </p>
          <p class="text-xs text-gray-500">
            {{ acceptedFormats.join(', ').toUpperCase() }} up to {{ formatFileSize(maxSize) }}
          </p>
        </div>
      </div>

      <!-- Hidden File Input -->
      <input
        ref="fileInput"
        type="file"
        :accept="acceptedFormats.map(f => `.${f}`).join(',')"
        @change="handleFileSelect"
        class="hidden"
      />
    </div>

    <!-- Upload Progress -->
    <div v-if="uploading" class="mt-4">
      <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
        <span>Uploading...</span>
        <span>{{ uploadProgress }}%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          class="bg-blue-600 h-2 rounded-full transition-all duration-300"
          :style="{ width: `${uploadProgress}%` }"
        ></div>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="mt-2 text-sm text-red-600">
      {{ error }}
    </div>

    <!-- Image Editor Modal -->
    <ImageEditor
      v-if="showEditor"
      :image="currentImage"
      @save="handleImageEdit"
      @cancel="showEditor = false"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Icon from '@/Components/UI/Icon.vue'
import ImageEditor from './ImageEditor.vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  alt: {
    type: String,
    default: 'Uploaded image'
  },
  maxSize: {
    type: Number,
    default: 5 * 1024 * 1024 // 5MB
  },
  acceptedFormats: {
    type: Array,
    default: () => ['jpg', 'jpeg', 'png', 'gif', 'webp']
  },
  uploadEndpoint: {
    type: String,
    default: '/api/v1/cms/media/upload'
  }
})

const emit = defineEmits(['update:modelValue', 'uploaded'])

const fileInput = ref(null)
const isDragging = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const error = ref('')
const imageInfo = ref(null)
const showEditor = ref(false)

const currentImage = computed(() => props.modelValue)

// Methods
const openFileDialog = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    processFile(file)
  }
}

const handleDrop = (event) => {
  event.preventDefault()
  isDragging.value = false
  
  const files = event.dataTransfer.files
  if (files.length > 0) {
    processFile(files[0])
  }
}

const dragLeave = (event) => {
  if (!event.currentTarget.contains(event.relatedTarget)) {
    isDragging.value = false
  }
}

const processFile = (file) => {
  error.value = ''

  // Validate file type
  const fileExtension = file.name.split('.').pop().toLowerCase()
  if (!props.acceptedFormats.includes(fileExtension)) {
    error.value = `File type not supported. Please use: ${props.acceptedFormats.join(', ')}`
    return
  }

  // Validate file size
  if (file.size > props.maxSize) {
    error.value = `File too large. Maximum size is ${formatFileSize(props.maxSize)}`
    return
  }

  // Store file info
  imageInfo.value = {
    name: file.name,
    size: file.size,
    type: file.type
  }

  // Upload file
  uploadFile(file)
}

const uploadFile = async (file) => {
  uploading.value = true
  uploadProgress.value = 0

  const formData = new FormData()
  formData.append('file', file)
  formData.append('alt', props.alt)

  try {
    const response = await fetch(props.uploadEndpoint, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Accept': 'application/json',
      },
      // Track upload progress
      onUploadProgress: (progressEvent) => {
        uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      }
    })

    if (!response.ok) {
      throw new Error('Upload failed')
    }

    const data = await response.json()
    
    emit('update:modelValue', data.url)
    emit('uploaded', data)
    
    uploadProgress.value = 100
  } catch (err) {
    error.value = 'Upload failed. Please try again.'
    console.error('Upload error:', err)
  } finally {
    uploading.value = false
    setTimeout(() => {
      uploadProgress.value = 0
    }, 1000)
  }
}

const removeImage = () => {
  if (confirm('Are you sure you want to remove this image?')) {
    emit('update:modelValue', '')
    imageInfo.value = null
    error.value = ''
  }
}

const editImage = () => {
  showEditor.value = true
}

const handleImageEdit = (editedImageUrl) => {
  emit('update:modelValue', editedImageUrl)
  showEditor.value = false
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>

<style scoped>
.image-upload {
  position: relative;
}
</style>