<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">
          Bulk Operations ({{ selectedTasks.length }} selected)
        </h3>
        <button
          @click="collapsed = !collapsed"
          class="text-gray-400 hover:text-gray-600"
        >
          <ChevronUpIcon v-if="!collapsed" class="h-4 w-4" />
          <ChevronDownIcon v-else class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Content -->
    <div v-if="!collapsed" class="p-4">
      <!-- Task Selection -->
      <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
          <label class="text-sm font-medium text-gray-700">Select Tasks</label>
          <div class="flex items-center space-x-2">
            <button
              @click="selectAll"
              class="text-xs text-indigo-600 hover:text-indigo-500"
            >
              Select All
            </button>
            <button
              @click="clearSelection"
              class="text-xs text-gray-500 hover:text-gray-700"
            >
              Clear
            </button>
          </div>
        </div>
        
        <!-- Task List -->
        <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-md">
          <div class="divide-y divide-gray-200">
            <div
              v-for="task in tasks"
              :key="task.id"
              class="p-3 hover:bg-gray-50"
            >
              <label class="flex items-center space-x-3 cursor-pointer">
                <input
                  v-model="selectedTasks"
                  :value="task.id"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                />
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900 truncate">
                      {{ task.title }}
                    </p>
                    <div class="flex items-center space-x-2">
                      <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="getStatusClass(task.status)"
                      >
                        {{ task.status }}
                      </span>
                      <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="getPriorityClass(task.priority)"
                      >
                        {{ task.priority }}
                      </span>
                    </div>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">
                    {{ task.assignee?.name || 'Unassigned' }}
                  </p>
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Bulk Actions -->
      <div v-if="selectedTasks.length > 0" class="space-y-4">
        <!-- Status Update -->
        <div class="flex items-center space-x-3">
          <label class="text-sm font-medium text-gray-700 w-24">Status:</label>
          <select
            v-model="bulkOperations.status"
            class="flex-1 text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">No change</option>
            <option value="todo">To Do</option>
            <option value="in_progress">In Progress</option>
            <option value="review">Review</option>
            <option value="completed">Completed</option>
          </select>
          <button
            @click="updateStatus"
            :disabled="!bulkOperations.status || loading"
            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-sm font-medium py-1 px-3 rounded-md"
          >
            Update
          </button>
        </div>

        <!-- Priority Update -->
        <div class="flex items-center space-x-3">
          <label class="text-sm font-medium text-gray-700 w-24">Priority:</label>
          <select
            v-model="bulkOperations.priority"
            class="flex-1 text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">No change</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
          <button
            @click="updatePriority"
            :disabled="!bulkOperations.priority || loading"
            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-sm font-medium py-1 px-3 rounded-md"
          >
            Update
          </button>
        </div>

        <!-- Assignee Update -->
        <div class="flex items-center space-x-3">
          <label class="text-sm font-medium text-gray-700 w-24">Assignee:</label>
          <select
            v-model="bulkOperations.assignee_id"
            class="flex-1 text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">No change</option>
            <option value="unassign">Unassign</option>
            <option
              v-for="member in projectMembers"
              :key="member.id"
              :value="member.user_id"
            >
              {{ member.user?.name }}
            </option>
          </select>
          <button
            @click="updateAssignee"
            :disabled="!bulkOperations.assignee_id || loading"
            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-sm font-medium py-1 px-3 rounded-md"
          >
            Assign
          </button>
        </div>

        <!-- Due Date Update -->
        <div class="flex items-center space-x-3">
          <label class="text-sm font-medium text-gray-700 w-24">Due Date:</label>
          <input
            v-model="bulkOperations.due_date"
            type="date"
            class="flex-1 text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          />
          <button
            @click="updateDueDate"
            :disabled="!bulkOperations.due_date || loading"
            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-sm font-medium py-1 px-3 rounded-md"
          >
            Update
          </button>
        </div>

        <!-- Tags Update -->
        <div class="flex items-center space-x-3">
          <label class="text-sm font-medium text-gray-700 w-24">Tags:</label>
          <div class="flex-1">
            <input
              v-model="tagInput"
              type="text"
              placeholder="Enter tags separated by commas"
              class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              @keydown.enter="addTags"
            />
            <div v-if="bulkOperations.tags.length > 0" class="flex flex-wrap gap-1 mt-2">
              <span
                v-for="tag in bulkOperations.tags"
                :key="tag"
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
              >
                {{ tag }}
                <button
                  @click="removeTag(tag)"
                  class="ml-1 text-blue-600 hover:text-blue-800"
                >
                  <XMarkIcon class="h-3 w-3" />
                </button>
              </span>
            </div>
          </div>
          <button
            @click="addTags"
            :disabled="!tagInput.trim() || loading"
            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-sm font-medium py-1 px-3 rounded-md"
          >
            Add
          </button>
        </div>

        <!-- Advanced Actions -->
        <div class="pt-4 border-t border-gray-200">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Advanced Actions</h4>
          <div class="flex flex-wrap gap-2">
            <button
              @click="duplicateTasks"
              :disabled="loading"
              class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-3 rounded-md"
            >
              Duplicate Tasks
            </button>
            <button
              @click="moveTasks"
              :disabled="loading"
              class="bg-green-600 hover:bg-green-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-3 rounded-md"
            >
              Move to Project
            </button>
            <button
              @click="archiveTasks"
              :disabled="loading"
              class="bg-yellow-600 hover:bg-yellow-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-3 rounded-md"
            >
              Archive Tasks
            </button>
            <button
              @click="deleteTasks"
              :disabled="loading"
              class="bg-red-600 hover:bg-red-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-3 rounded-md"
            >
              Delete Tasks
            </button>
          </div>
        </div>

        <!-- Export Options -->
        <div class="pt-4 border-t border-gray-200">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Export</h4>
          <div class="flex space-x-2">
            <button
              @click="exportTasks('csv')"
              :disabled="loading"
              class="bg-gray-600 hover:bg-gray-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-3 rounded-md"
            >
              Export CSV
            </button>
            <button
              @click="exportTasks('excel')"
              :disabled="loading"
              class="bg-gray-600 hover:bg-gray-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-3 rounded-md"
            >
              Export Excel
            </button>
            <button
              @click="exportTasks('pdf')"
              :disabled="loading"
              class="bg-gray-600 hover:bg-gray-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-3 rounded-md"
            >
              Export PDF
            </button>
          </div>
        </div>
      </div>

      <!-- No Selection Message -->
      <div v-else class="text-center py-8">
        <CheckCircleIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No tasks selected</h3>
        <p class="mt-1 text-sm text-gray-500">Select tasks to perform bulk operations.</p>
      </div>
    </div>

    <!-- Move Tasks Modal -->
    <MoveTasksModal
      v-if="showMoveModal"
      :task-ids="selectedTasks"
      @close="showMoveModal = false"
      @moved="handleTasksMoved"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  XMarkIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'
import MoveTasksModal from './MoveTasksModal.vue'

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

const emit = defineEmits(['tasks-updated', 'tasks-deleted'])

const { get, post, put, delete: del, loading } = useApi()
const { showSuccess, showError } = useToast()

// Reactive data
const collapsed = ref(false)
const selectedTasks = ref([])
const projectMembers = ref([])
const tagInput = ref('')
const showMoveModal = ref(false)

const bulkOperations = reactive({
  status: '',
  priority: '',
  assignee_id: '',
  due_date: '',
  tags: []
})

// Methods
const fetchProjectMembers = async () => {
  try {
    const data = await get(`/api/v1/projects/${props.projectId}/members`)
    projectMembers.value = data.data
  } catch (err) {
    console.error('Failed to fetch project members:', err)
  }
}

const selectAll = () => {
  selectedTasks.value = props.tasks.map(task => task.id)
}

const clearSelection = () => {
  selectedTasks.value = []
}

const getStatusClass = (status) => {
  const classes = {
    'todo': 'bg-gray-100 text-gray-800',
    'in_progress': 'bg-blue-100 text-blue-800',
    'review': 'bg-yellow-100 text-yellow-800',
    'completed': 'bg-green-100 text-green-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityClass = (priority) => {
  const classes = {
    'low': 'bg-gray-100 text-gray-800',
    'medium': 'bg-yellow-100 text-yellow-800',
    'high': 'bg-orange-100 text-orange-800',
    'urgent': 'bg-red-100 text-red-800'
  }
  return classes[priority] || 'bg-gray-100 text-gray-800'
}

const updateStatus = async () => {
  try {
    await post(`/api/v1/projects/${props.projectId}/tasks/bulk-update`, {
      task_ids: selectedTasks.value,
      updates: { status: bulkOperations.status }
    })
    
    showSuccess(`Updated status for ${selectedTasks.value.length} tasks`)
    emit('tasks-updated')
    bulkOperations.status = ''
  } catch (err) {
    console.error('Failed to update status:', err)
    showError('Failed to update task status')
  }
}

const updatePriority = async () => {
  try {
    await post(`/api/v1/projects/${props.projectId}/tasks/bulk-update`, {
      task_ids: selectedTasks.value,
      updates: { priority: bulkOperations.priority }
    })
    
    showSuccess(`Updated priority for ${selectedTasks.value.length} tasks`)
    emit('tasks-updated')
    bulkOperations.priority = ''
  } catch (err) {
    console.error('Failed to update priority:', err)
    showError('Failed to update task priority')
  }
}

const updateAssignee = async () => {
  try {
    const assigneeId = bulkOperations.assignee_id === 'unassign' ? null : bulkOperations.assignee_id
    
    await post(`/api/v1/projects/${props.projectId}/tasks/bulk-update`, {
      task_ids: selectedTasks.value,
      updates: { assigned_to: assigneeId }
    })
    
    showSuccess(`Updated assignee for ${selectedTasks.value.length} tasks`)
    emit('tasks-updated')
    bulkOperations.assignee_id = ''
  } catch (err) {
    console.error('Failed to update assignee:', err)
    showError('Failed to update task assignee')
  }
}

const updateDueDate = async () => {
  try {
    await post(`/api/v1/projects/${props.projectId}/tasks/bulk-update`, {
      task_ids: selectedTasks.value,
      updates: { due_date: bulkOperations.due_date }
    })
    
    showSuccess(`Updated due date for ${selectedTasks.value.length} tasks`)
    emit('tasks-updated')
    bulkOperations.due_date = ''
  } catch (err) {
    console.error('Failed to update due date:', err)
    showError('Failed to update task due date')
  }
}

const addTags = () => {
  if (!tagInput.value.trim()) return
  
  const newTags = tagInput.value.split(',').map(tag => tag.trim()).filter(tag => tag)
  bulkOperations.tags.push(...newTags.filter(tag => !bulkOperations.tags.includes(tag)))
  tagInput.value = ''
  
  // Apply tags immediately
  applyTags()
}

const removeTag = (tag) => {
  const index = bulkOperations.tags.indexOf(tag)
  if (index > -1) {
    bulkOperations.tags.splice(index, 1)
  }
}

const applyTags = async () => {
  if (bulkOperations.tags.length === 0) return
  
  try {
    await post(`/api/v1/projects/${props.projectId}/tasks/bulk-update`, {
      task_ids: selectedTasks.value,
      updates: { tags: bulkOperations.tags }
    })
    
    showSuccess(`Added tags to ${selectedTasks.value.length} tasks`)
    emit('tasks-updated')
    bulkOperations.tags = []
  } catch (err) {
    console.error('Failed to add tags:', err)
    showError('Failed to add tags')
  }
}

const duplicateTasks = async () => {
  if (!confirm(`Duplicate ${selectedTasks.value.length} tasks?`)) return
  
  try {
    await post(`/api/v1/projects/${props.projectId}/tasks/bulk-duplicate`, {
      task_ids: selectedTasks.value
    })
    
    showSuccess(`Duplicated ${selectedTasks.value.length} tasks`)
    emit('tasks-updated')
  } catch (err) {
    console.error('Failed to duplicate tasks:', err)
    showError('Failed to duplicate tasks')
  }
}

const moveTasks = () => {
  showMoveModal.value = true
}

const archiveTasks = async () => {
  if (!confirm(`Archive ${selectedTasks.value.length} tasks?`)) return
  
  try {
    await post(`/api/v1/projects/${props.projectId}/tasks/bulk-archive`, {
      task_ids: selectedTasks.value
    })
    
    showSuccess(`Archived ${selectedTasks.value.length} tasks`)
    emit('tasks-updated')
  } catch (err) {
    console.error('Failed to archive tasks:', err)
    showError('Failed to archive tasks')
  }
}

const deleteTasks = async () => {
  if (!confirm(`Delete ${selectedTasks.value.length} tasks? This action cannot be undone.`)) return
  
  try {
    await post(`/api/v1/projects/${props.projectId}/tasks/bulk-delete`, {
      task_ids: selectedTasks.value
    })
    
    showSuccess(`Deleted ${selectedTasks.value.length} tasks`)
    emit('tasks-deleted', selectedTasks.value)
    selectedTasks.value = []
  } catch (err) {
    console.error('Failed to delete tasks:', err)
    showError('Failed to delete tasks')
  }
}

const exportTasks = async (format) => {
  try {
    const response = await fetch(`/api/v1/projects/${props.projectId}/tasks/export`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('api_token')}`
      },
      body: JSON.stringify({
        task_ids: selectedTasks.value,
        format: format
      })
    })
    
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `tasks.${format}`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
      
      showSuccess(`Tasks exported as ${format.toUpperCase()}`)
    } else {
      throw new Error('Export failed')
    }
  } catch (err) {
    console.error('Failed to export tasks:', err)
    showError('Failed to export tasks')
  }
}

const handleTasksMoved = () => {
  showMoveModal.value = false
  showSuccess('Tasks moved successfully')
  emit('tasks-updated')
}

// Lifecycle
onMounted(() => {
  fetchProjectMembers()
})
</script>