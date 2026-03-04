<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Add Task Dependency</h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="addDependency">
        <!-- Predecessor Task -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Predecessor Task (must finish before)
          </label>
          <select
            v-model="form.predecessor_id"
            required
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">Select predecessor task</option>
            <option
              v-for="task in availablePredecessors"
              :key="task.id"
              :value="task.id"
            >
              {{ task.title }} ({{ task.status }})
            </option>
          </select>
        </div>

        <!-- Successor Task -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Successor Task (depends on predecessor)
          </label>
          <select
            v-model="form.successor_id"
            required
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">Select successor task</option>
            <option
              v-for="task in availableSuccessors"
              :key="task.id"
              :value="task.id"
            >
              {{ task.title }} ({{ task.status }})
            </option>
          </select>
        </div>

        <!-- Dependency Type -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Dependency Type
          </label>
          <select
            v-model="form.type"
            required
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="finish-to-start">Finish-to-Start (FS)</option>
            <option value="start-to-start">Start-to-Start (SS)</option>
            <option value="finish-to-finish">Finish-to-Finish (FF)</option>
            <option value="start-to-finish">Start-to-Finish (SF)</option>
          </select>
          <p class="text-xs text-gray-500 mt-1">
            {{ getDependencyTypeDescription(form.type) }}
          </p>
        </div>

        <!-- Lag Time -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Lag Time (days)
          </label>
          <input
            v-model.number="form.lag_days"
            type="number"
            min="0"
            step="0.5"
            placeholder="0"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          />
          <p class="text-xs text-gray-500 mt-1">
            Additional delay between tasks (optional)
          </p>
        </div>

        <!-- Notes -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Notes
          </label>
          <textarea
            v-model="form.notes"
            rows="2"
            placeholder="Optional notes about this dependency..."
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          ></textarea>
        </div>

        <!-- Validation Warnings -->
        <div v-if="validationWarnings.length > 0" class="mb-4">
          <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
            <div class="flex">
              <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
              <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Warnings</h3>
                <div class="mt-2 text-sm text-yellow-700">
                  <ul class="list-disc pl-5 space-y-1">
                    <li v-for="warning in validationWarnings" :key="warning">
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
            type="submit"
            :disabled="loading || !isFormValid"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 rounded-md"
          >
            {{ loading ? 'Adding...' : 'Add Dependency' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { XMarkIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'

const props = defineProps({
  projectId: {
    type: [String, Number],
    required: true
  },
  tasks: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'saved'])

const { post, loading } = useApi()

// Form data
const form = ref({
  predecessor_id: '',
  successor_id: '',
  type: 'finish-to-start',
  lag_days: 0,
  notes: ''
})

// Computed properties
const availablePredecessors = computed(() => {
  return props.tasks.filter(task => task.id !== form.value.successor_id)
})

const availableSuccessors = computed(() => {
  return props.tasks.filter(task => task.id !== form.value.predecessor_id)
})

const isFormValid = computed(() => {
  return form.value.predecessor_id && 
         form.value.successor_id && 
         form.value.type &&
         form.value.predecessor_id !== form.value.successor_id
})

const validationWarnings = computed(() => {
  const warnings = []
  
  if (form.value.predecessor_id && form.value.successor_id) {
    const predecessor = props.tasks.find(t => t.id == form.value.predecessor_id)
    const successor = props.tasks.find(t => t.id == form.value.successor_id)
    
    if (predecessor && successor) {
      // Check for circular dependencies
      if (wouldCreateCircularDependency(form.value.predecessor_id, form.value.successor_id)) {
        warnings.push('This dependency would create a circular reference')
      }
      
      // Check task status compatibility
      if (predecessor.status === 'completed' && successor.status === 'todo') {
        warnings.push('Predecessor is already completed while successor hasn\'t started')
      }
      
      // Check dates
      if (predecessor.due_date && successor.start_date) {
        const predDue = new Date(predecessor.due_date)
        const succStart = new Date(successor.start_date)
        if (predDue > succStart) {
          warnings.push('Predecessor due date is after successor start date')
        }
      }
    }
  }
  
  return warnings
})

// Methods
const getDependencyTypeDescription = (type) => {
  const descriptions = {
    'finish-to-start': 'Predecessor must finish before successor can start',
    'start-to-start': 'Both tasks must start at the same time',
    'finish-to-finish': 'Both tasks must finish at the same time',
    'start-to-finish': 'Predecessor must start before successor can finish'
  }
  return descriptions[type] || ''
}

const wouldCreateCircularDependency = (predecessorId, successorId) => {
  // Simple circular dependency check
  // In a real implementation, this would do a more thorough graph traversal
  const existingDeps = getExistingDependencies()
  
  // Check if successor is already a predecessor of the predecessor
  return existingDeps.some(dep => 
    dep.predecessor_id == successorId && dep.successor_id == predecessorId
  )
}

const getExistingDependencies = () => {
  // This would come from props or API call in a real implementation
  return []
}

const addDependency = async () => {
  if (!isFormValid.value) return
  
  try {
    const data = await post(`/api/v1/projects/${props.projectId}/dependencies`, {
      predecessor_id: form.value.predecessor_id,
      successor_id: form.value.successor_id,
      type: form.value.type,
      lag_days: form.value.lag_days || 0,
      notes: form.value.notes
    })
    
    emit('saved', data.data)
  } catch (err) {
    console.error('Failed to add dependency:', err)
  }
}

// Watch for form changes to clear invalid selections
watch(() => form.value.predecessor_id, (newVal) => {
  if (newVal === form.value.successor_id) {
    form.value.successor_id = ''
  }
})

watch(() => form.value.successor_id, (newVal) => {
  if (newVal === form.value.predecessor_id) {
    form.value.predecessor_id = ''
  }
})
</script>