<template>
  <div class="space-y-4">
    <!-- Current Image Preview -->
    <div v-if="modelValue" class="relative">
      <img
        :src="modelValue"
        alt="Preview"
        class="w-full h-32 object-cover rounded-lg border border-gray-300"
      />
      <button
        @click="removeImage"
        class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    
    <!-- Upload Area -->
    <div
      @click="triggerFileInput"
      @dragover.prevent
      @drop.prevent="handleDrop"
      class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-gray-400 transition-colors"
    >
      <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      <div class="mt-4">
        <p class="text-sm text-gray-600">
          <span class="font-medium text-blue-600">Click to upload</span> or drag and drop
        </p>
        <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
      </div>
    </div>
    
    <!-- Hidden File Input -->
    <input
      ref="fileInput"
      type="file"
      accept="image/*"
      class="hidden"
      @change="handleFileSelect"
    />
    
    <!-- URL Input -->
    <div class="flex space-x-2">
      <input
        v-model="imageUrl"
        type="url"
        placeholder="Or enter image URL"
        class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm"
      />
      <button
        @click="setImageFromUrl"
        :disabled="!imageUrl"
        class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Use URL
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: String
})

const emit = defineEmits(['update:modelValue', 'update'])

const fileInput = ref(null)
const imageUrl = ref('')

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    handleFile(file)
  }
}

const handleDrop = (event) => {
  const file = event.dataTransfer.files[0]
  if (file && file.type.startsWith('image/')) {
    handleFile(file)
  }
}

const handleFile = (file) => {
  if (file.size > 10 * 1024 * 1024) { // 10MB limit
    alert('File size must be less than 10MB')
    return
  }
  
  const reader = new FileReader()
  reader.onload = (e) => {
    const imageDataUrl = e.target.result
    emit('update:modelValue', imageDataUrl)
    emit('update')
  }
  reader.readAsDataURL(file)
}

const setImageFromUrl = () => {
  if (imageUrl.value) {
    emit('update:modelValue', imageUrl.value)
    emit('update')
    imageUrl.value = ''
  }
}

const removeImage = () => {
  emit('update:modelValue', null)
  emit('update')
}
</script>