<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-40 flex items-center justify-center"
  >
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full mx-4">
      <!-- Spinner -->
      <div class="flex justify-center mb-4">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
      
      <!-- Message -->
      <div class="text-center">
        <h3 class="text-lg font-medium text-gray-900 mb-2">
          {{ title }}
        </h3>
        <p v-if="message" class="text-sm text-gray-500 mb-4">
          {{ message }}
        </p>
        
        <!-- Progress Bar -->
        <div v-if="showProgress" class="w-full bg-gray-200 rounded-full h-2 mb-4">
          <div
            class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
        
        <!-- Duration -->
        <div v-if="showDuration" class="text-xs text-gray-400">
          {{ formatDuration(duration) }}
        </div>
        
        <!-- Cancel Button -->
        <button
          v-if="cancellable"
          @click="$emit('cancel')"
          class="mt-4 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Loading...'
  },
  message: {
    type: String,
    default: ''
  },
  progress: {
    type: Number,
    default: 0
  },
  showProgress: {
    type: Boolean,
    default: false
  },
  duration: {
    type: Number,
    default: 0
  },
  showDuration: {
    type: Boolean,
    default: false
  },
  cancellable: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['cancel'])

// Methods
const formatDuration = (ms) => {
  const seconds = Math.floor(ms / 1000)
  if (seconds < 60) {
    return `${seconds}s`
  }
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  return `${minutes}m ${remainingSeconds}s`
}
</script>