<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4">
    <div class="max-w-md w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">
      <div class="text-center">
        <h1 class="text-6xl font-bold text-red-600 dark:text-red-400 mb-4">
          {{ status }}
        </h1>
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
          {{ title }}
        </h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
          {{ message }}
        </p>
        <div class="flex gap-4 justify-center">
          <Link 
            href="/dashboard" 
            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
          >
            Go to Dashboard
          </Link>
          <button 
            @click="goBack"
            class="px-6 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition-colors"
          >
            Go Back
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  status: {
    type: Number,
    default: 500
  },
  message: {
    type: String,
    default: 'An error occurred'
  }
})

const title = computed(() => {
  const titles = {
    503: 'Service Unavailable',
    500: 'Server Error',
    404: 'Page Not Found',
    403: 'Forbidden',
    401: 'Unauthorized',
  }
  return titles[props.status] || 'Error'
})

const goBack = () => {
  window.history.back()
}
</script>
