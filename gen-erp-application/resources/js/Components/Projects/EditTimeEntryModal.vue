<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Edit Time Entry</h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="updateEntry">
        <!-- Task Info (Read-only) -->
        <div class="mb-4 p-3 bg-gray-50 rounded-md">
          <div class="text-sm font-medium text-gray-900">{{ entry.task?.title }}</div>
          <div class="text-xs text-gray-500">{{ entry.task?.project?.name }}</div>
        </div>

        <!-- Date -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
          <input
            v-model="form.date"
            type="date"
            required
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>

        <!-- Time Input -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Time</label>
          <div class="flex space-x-2">
            <div class="flex-1">
              <input
                v-model="form.hours"
                type="number"
                min="0"
                max="23"
                placeholder="Hours"
                class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            <div class="flex-1">
              <input
                v-model="form.minutes"
                type="number"
                min="0"
                max="59"
                placeholder="Minutes"
                class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
          </div>
          <p class="text-xs text-gray-500 mt-1">Total: {{ formatTime(totalSeconds) }}</p>
        </div>

        <!-- Description -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="What did you work on?"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          ></textarea>
        </div>

        <!-- Billable -->
        <div class="mb-6">
          <label class="flex items-center">
            <input
              v-model="form.is_billable"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">Billable time</span>
          </label>
        </div>

        <!-- Actions -->
        <div class="flex justify-between">
          <button
            type="button"
            @click="deleteEntry"
            :disabled="loading"
            class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 hover:bg-red-200 disabled:bg-gray-100 rounded-md"
          >
            Delete
          </button>
          <div class="flex space-x-3">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading || !isFormValid"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 rounded-md"
            >
              {{ loading ? 'Updating...' : 'Update Entry' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'

const props = defineProps({
  entry: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'saved', 'deleted'])

const { put, delete: del, loading } = useApi()

// Form data
const form = ref({
  date: '',
  hours: 0,
  minutes: 0,
  description: '',
  is_billable: true
})

// Computed properties
const totalSeconds = computed(() => {
  const hours = parseInt(form.value.hours) || 0
  const minutes = parseInt(form.value.minutes) || 0
  return (hours * 3600) + (minutes * 60)
})

const isFormValid = computed(() => {
  return form.value.date && totalSeconds.value > 0
})

// Methods
const initializeForm = () => {
  const duration = props.entry.duration || 0
  const hours = Math.floor(duration / 3600)
  const minutes = Math.floor((duration % 3600) / 60)
  
  form.value = {
    date: props.entry.date || new Date().toISOString().split('T')[0],
    hours: hours,
    minutes: minutes,
    description: props.entry.description || '',
    is_billable: props.entry.is_billable ?? true
  }
}

const updateEntry = async () => {
  if (!isFormValid.value) return
  
  try {
    const data = await put(`/api/v1/time-tracking/${props.entry.id}`, {
      date: form.value.date,
      duration: totalSeconds.value,
      description: form.value.description,
      is_billable: form.value.is_billable
    })
    
    emit('saved', data.data)
  } catch (err) {
    console.error('Failed to update time entry:', err)
  }
}

const deleteEntry = async () => {
  if (!confirm('Are you sure you want to delete this time entry?')) {
    return
  }
  
  try {
    await del(`/api/v1/time-tracking/${props.entry.id}`)
    emit('deleted', props.entry)
    emit('close')
  } catch (err) {
    console.error('Failed to delete time entry:', err)
  }
}

const formatTime = (seconds) => {
  if (!seconds) return '00:00:00'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

// Lifecycle
onMounted(() => {
  initializeForm()
})
</script>