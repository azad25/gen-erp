<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">
          Move {{ taskIds.length }} Task{{ taskIds.length > 1 ? 's' : '' }}
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Project Selection -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Select Destination Project
        </label>
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search projects..."
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 pl-10"
            @input="searchProjects"
          />
          <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
        </div>
      </div>

      <!-- Project List -->
      <div class="mb-6">
        <div class="max-h-64 overflow-y-auto border border-gray-300 rounded-md">
          <div v-if="searchLoading" class="p-4 text-center">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600 mx-auto"></div>
          </div>
          <div v-else-if="availableProjects.length === 0" class="p-4 text-center text-gray-500 text-sm">
            {{ searchQuery ? 'No projects found' : 'Start typing to search projects' }}
          </div>
          <div v-else class="divide-y divide-gray-200">
            <div
              v-for="project in availableProjects"
              :key="project.id"
              class="p-3 hover:bg-gray-50 cursor-pointer"
              :class="{ 'bg-indigo-50': selectedProject?.id === project.id }"
              @click="selectProject(project)"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900">{{ project.name }}</p>
                  <p class="text-xs text-gray-500">
                    {{ project.status }} • {{ project.tasks_count || 0 }} tasks
                  </p>
                </div>
                <div class="flex items-center space-x-2">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="getStatusClass(project.status)"
                  >
                    {{ project.status }}
                  </span>
                  <div v-if="selectedProject?.id === project.id">
                    <CheckIcon class="h-5 w-5 text-indigo-600" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Move Options -->
      <div v-if="selectedProject" class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Move Options</h4>
        <div class="space-y-3">
          <label class="flex items-center">
            <input
              v-model="moveOptions.keepAssignees"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">Keep current assignees</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="moveOptions.keepDueDates"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">Keep due dates</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="moveOptions.keepDependencies"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">Keep task dependencies</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="moveOptions.notifyMembers"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            />
            <span class="ml-2 text-sm text-gray-700">Notify project members</span>
          </label>
        </div>
      </div>

      <!-- Warning Messages -->
      <div v-if="warnings.length > 0" class="mb-4">
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
          <div class="flex">
            <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
            <div class="ml-3">
              <h3 class="text-sm font-medium text-yellow-800">Warnings</h3>
              <div class="mt-2 text-sm text-yellow-700">
                <ul class="list-disc pl-5 space-y-1">
                  <li v-for="warning in warnings" :key="warning">
                    {{ warning }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
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
          @click="moveTasks"
          :disabled="loading || !selectedProject"
          class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 rounded-md"
        >
          {{ loading ? 'Moving...' : `Move ${taskIds.length} Task${taskIds.length > 1 ? 's' : ''}` }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import {
  XMarkIcon,
  MagnifyingGlassIcon,
  CheckIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'

const props = defineProps({
  taskIds: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['close', 'moved'])

const { get, post, loading } = useApi()

// Reactive data
const searchQuery = ref('')
const searchLoading = ref(false)
const availableProjects = ref([])
const selectedProject = ref(null)

const moveOptions = reactive({
  keepAssignees: true,
  keepDueDates: true,
  keepDependencies: false,
  notifyMembers: true
})

// Computed properties
const warnings = computed(() => {
  const warnings = []
  
  if (selectedProject.value) {
    if (!moveOptions.keepAssignees) {
      warnings.push('Tasks will be unassigned after moving')
    }
    
    if (!moveOptions.keepDependencies) {
      warnings.push('Task dependencies will be removed')
    }
    
    if (selectedProject.value.status === 'completed') {
      warnings.push('Moving tasks to a completed project')
    }
    
    if (selectedProject.value.status === 'archived') {
      warnings.push('Moving tasks to an archived project')
    }
  }
  
  return warnings
})

// Methods
const searchProjects = async () => {
  if (!searchQuery.value.trim()) {
    availableProjects.value = []
    return
  }
  
  searchLoading.value = true
  try {
    const data = await get('/api/v1/projects/search', {
      query: searchQuery.value,
      exclude_current: true
    })
    availableProjects.value = data.data
  } catch (err) {
    console.error('Failed to search projects:', err)
  } finally {
    searchLoading.value = false
  }
}

const selectProject = (project) => {
  selectedProject.value = selectedProject.value?.id === project.id ? null : project
}

const getStatusClass = (status) => {
  const classes = {
    'active': 'bg-green-100 text-green-800',
    'on_hold': 'bg-yellow-100 text-yellow-800',
    'completed': 'bg-blue-100 text-blue-800',
    'cancelled': 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const moveTasks = async () => {
  if (!selectedProject.value) return
  
  try {
    await post('/api/v1/tasks/bulk-move', {
      task_ids: props.taskIds,
      destination_project_id: selectedProject.value.id,
      options: moveOptions
    })
    
    emit('moved')
  } catch (err) {
    console.error('Failed to move tasks:', err)
  }
}

// Watch for project selection changes to update warnings
watch(() => selectedProject.value, () => {
  // Warnings are computed automatically
})
</script>