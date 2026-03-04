<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Manual Time Entry</h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submitEntry">
        <!-- Project Selection -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Project</label>
          <select
            v-model="form.project_id"
            @change="fetchProjectTasks"
            required
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">Select Project</option>
            <option v-for="project in projects" :key="project.id" :value="project.id">
              {{ project.name }}
            </option>
          </select>
        </div>

        <!-- Task Selection -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Task</label>
          <select
            v-model="form.task_id"
            :disabled="!form.project_id"
            required
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">Select Task</option>
            <option v-for="task in availableTasks" :key="task.id" :value="task.id">
              {{ task.title }}
            </option>
          </select>
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
        <div class="flex justify-end space-x-3">
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
            {{ loading ? 'Saving...' : 'Save Entry' }}
          </button>
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
  projects: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'saved'])

const { get, post, loading } = useApi()

// Form data
const form = ref({
  project_id: '',
  task_id: '',
  date: new Date().toISOString().split('T')[0],
  hours: 0,
  minutes: 0,
  description: '',
  is_billable: true
})

const availableTasks = ref([])

// Computed properties
const totalSeconds = computed(() => {
  const hours = parseInt(form.value.hours) || 0
  const minutes = parseInt(form.value.minutes) || 0
  return (hours * 3600) + (minutes * 60)
})

const isFormValid = computed(() => {
  return form.value.project_id && 
         form.value.task_id && 
         form.value.date && 
         totalSeconds.value > 0
})

// Methods
const fetchProjectTasks = async () => {
  if (!form.value.project_id) {
    availableTasks.value = []
    form.value.task_id = ''
    return
  }
  
  try {
    const data = await get(`/api/v1/projects/${form.value.project_id}/tasks`)
    availableTasks.value = data.data
  } catch (err) {
    console.error('Failed to fetch tasks:', err)
  }
}

const submitEntry = async () => {
  if (!isFormValid.value) return
  
  try {
    const data = await post('/api/v1/time-tracking/manual', {
      task_id: form.value.task_id,
      date: form.value.date,
      duration: totalSeconds.value,
      description: form.value.description,
      is_billable: form.value.is_billable
    })
    
    emit('saved', data.data)
  } catch (err) {
    console.error('Failed to save time entry:', err)
  }
}

const formatTime = (seconds) => {
  if (!seconds) return '00:00:00'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}
</script>