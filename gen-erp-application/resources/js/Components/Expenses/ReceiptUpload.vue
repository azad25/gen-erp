<template>
  <div class="space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1">
        Receipt <span class="text-red-500">*</span>
      </label>
      <div
        class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer hover:border-blue-500 transition-colors"
        :class="{ 'border-blue-500': isDragging }"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        @click="$refs.fileInput.click()"
      >
        <input
          ref="fileInput"
          type="file"
          accept="image/*,.pdf"
          class="hidden"
          @change="handleFileSelect"
        />
        
        <div v-if="!receiptUrl" class="space-y-2">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <p class="text-sm text-gray-600">
            Drag and drop receipt here, or <span class="text-blue-600 font-medium">browse</span>
          </p>
          <p class="text-xs text-gray-500">PNG, JPG, PDF up to 10MB</p>
        </div>

        <div v-else class="space-y-2">
          <img
            v-if="isImage(receiptUrl)"
            :src="receiptUrl"
            alt="Receipt"
            class="max-h-48 mx-auto rounded"
          />
          <div v-else class="flex items-center justify-center space-x-2 text-gray-600">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="text-sm">PDF Receipt</span>
          </div>
          
          <button
            type="button"
            @click="removeReceipt"
            class="text-red-600 hover:text-red-800 text-sm font-medium"
          >
            Remove
          </button>
        </div>
      </div>
    </div>

    <div v-if="uploadProgress > 0 && uploadProgress < 100" class="w-full bg-gray-200 rounded-full h-2">
      <div
        class="bg-blue-600 h-2 rounded-full transition-all duration-300"
        :style="{ width: uploadProgress + '%' }"
      ></div>
    </div>

    <div v-if="error" class="text-red-500 text-sm">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

const isDragging = ref(false)
const uploadProgress = ref(0)
const error = ref('')
const receiptUrl = ref(props.modelValue)

const isImage = (url) => {
  return /\.(jpg|jpeg|png|gif|webp)$/i.test(url)
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    uploadFile(file)
  }
}

const handleDrop = (event) => {
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file) {
    uploadFile(file)
  }
}

const uploadFile = async (file) => {
  // Validate file size (10MB max)
  if (file.size > 10 * 1024 * 1024) {
    error.value = 'File size must be less than 10MB'
    return
  }

  // Validate file type
  if (!file.type.match(/^(image\/|application\/pdf)/)) {
    error.value = 'Only images and PDF files are allowed'
    return
  }

  error.value = ''
  uploadProgress.value = 0

  const formData = new FormData()
  formData.append('file', file)

  try {
    const response = await axios.post('/api/v1/upload/receipt', formData, {
      onUploadProgress: (progressEvent) => {
        uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      }
    })

    receiptUrl.value = response.data.url
    emit('update:modelValue', response.data.url)
    uploadProgress.value = 100
  } catch (err) {
    error.value = 'Failed to upload receipt. Please try again.'
    uploadProgress.value = 0
  }
}

const removeReceipt = () => {
  receiptUrl.value = ''
  emit('update:modelValue', '')
  error.value = ''
}
</script>
