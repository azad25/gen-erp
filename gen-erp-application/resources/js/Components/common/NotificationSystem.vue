<template>
  <div
    aria-live="assertive"
    class="fixed inset-0 flex items-end px-4 py-6 pointer-events-none sm:p-6 sm:items-start z-50"
  >
    <div class="w-full flex flex-col items-center space-y-4 sm:items-end">
      <transition-group
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden"
        >
          <div class="p-4">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <CheckCircleIcon
                  v-if="toast.type === 'success'"
                  class="h-6 w-6 text-green-400"
                  aria-hidden="true"
                />
                <XCircleIcon
                  v-else-if="toast.type === 'error'"
                  class="h-6 w-6 text-red-400"
                  aria-hidden="true"
                />
                <ExclamationTriangleIcon
                  v-else-if="toast.type === 'warning'"
                  class="h-6 w-6 text-yellow-400"
                  aria-hidden="true"
                />
                <InformationCircleIcon
                  v-else
                  class="h-6 w-6 text-blue-400"
                  aria-hidden="true"
                />
              </div>
              <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-gray-900">
                  {{ getToastTitle(toast.type) }}
                </p>
                <p class="mt-1 text-sm text-gray-500">
                  {{ toast.message }}
                </p>
                <div v-if="toast.actions && toast.actions.length > 0" class="mt-3 flex space-x-7">
                  <button
                    v-for="action in toast.actions"
                    :key="action.label"
                    @click="handleAction(action, toast)"
                    class="bg-white rounded-md text-sm font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    {{ action.label }}
                  </button>
                </div>
              </div>
              <div class="ml-4 flex-shrink-0 flex">
                <button
                  @click="removeToast(toast.id)"
                  class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  <span class="sr-only">Close</span>
                  <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                </button>
              </div>
            </div>
          </div>
          <!-- Progress bar for auto-dismiss -->
          <div
            v-if="toast.duration > 0"
            class="h-1 bg-gray-200"
          >
            <div
              class="h-full transition-all ease-linear"
              :class="getProgressBarColor(toast.type)"
              :style="{ 
                width: `${getProgressPercentage(toast)}%`,
                transitionDuration: `${toast.duration}ms`
              }"
            ></div>
          </div>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import {
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'

const { toasts, removeToast } = useToast()

// Methods
const getToastTitle = (type) => {
  const titles = {
    success: 'Success',
    error: 'Error',
    warning: 'Warning',
    info: 'Information'
  }
  return titles[type] || 'Notification'
}

const getProgressBarColor = (type) => {
  const colors = {
    success: 'bg-green-500',
    error: 'bg-red-500',
    warning: 'bg-yellow-500',
    info: 'bg-blue-500'
  }
  return colors[type] || 'bg-gray-500'
}

const getProgressPercentage = (toast) => {
  if (!toast.startTime || toast.duration <= 0) return 100
  
  const elapsed = Date.now() - toast.startTime
  const percentage = Math.max(0, 100 - (elapsed / toast.duration) * 100)
  return percentage
}

const handleAction = (action, toast) => {
  if (action.handler) {
    action.handler(toast)
  }
  
  if (action.dismissOnClick !== false) {
    removeToast(toast.id)
  }
}

// Auto-update progress bars
let progressInterval = null

onMounted(() => {
  progressInterval = setInterval(() => {
    // Force reactivity update for progress bars
    toasts.value.forEach(toast => {
      if (toast.duration > 0 && toast.startTime) {
        const elapsed = Date.now() - toast.startTime
        if (elapsed >= toast.duration) {
          removeToast(toast.id)
        }
      }
    })
  }, 100)
})

onUnmounted(() => {
  if (progressInterval) {
    clearInterval(progressInterval)
  }
})
</script>