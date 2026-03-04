<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Widget Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">{{ title }}</h3>
        <div class="flex items-center space-x-2">
          <button
            v-if="refreshable"
            @click="refresh"
            :disabled="loading"
            class="text-xs text-indigo-600 hover:text-indigo-500"
          >
            {{ loading ? 'Refreshing...' : 'Refresh' }}
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

    <!-- Widget Content -->
    <div v-if="!collapsed" class="p-4">
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-8">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
        <span class="ml-2 text-sm text-gray-600">Loading...</span>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-8">
        <ExclamationTriangleIcon class="mx-auto h-8 w-8 text-red-400" />
        <p class="mt-2 text-sm text-red-600">{{ error }}</p>
        <button
          @click="refresh"
          class="mt-2 text-xs text-indigo-600 hover:text-indigo-500"
        >
          Try Again
        </button>
      </div>

      <!-- Content -->
      <div v-else>
        <slot :data="data" :loading="loading" :error="error" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ChevronUpIcon, ChevronDownIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  endpoint: {
    type: String,
    required: true
  },
  refreshable: {
    type: Boolean,
    default: true
  },
  autoRefresh: {
    type: Number,
    default: 0 // 0 = no auto refresh, otherwise interval in seconds
  },
  collapsed: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['data-loaded', 'error'])

const { get, loading } = useApi()

// Reactive data
const collapsed = ref(props.collapsed)
const data = ref(null)
const error = ref(null)
const refreshInterval = ref(null)

// Methods
const fetchData = async () => {
  try {
    error.value = null
    const response = await get(props.endpoint)
    data.value = response.data
    emit('data-loaded', response.data)
  } catch (err) {
    error.value = err.message || 'Failed to load data'
    emit('error', err)
  }
}

const refresh = () => {
  fetchData()
}

// Lifecycle
onMounted(() => {
  fetchData()
  
  // Set up auto-refresh if specified
  if (props.autoRefresh > 0) {
    refreshInterval.value = setInterval(() => {
      fetchData()
    }, props.autoRefresh * 1000)
  }
})

// Cleanup
onUnmounted(() => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
  }
})
</script>