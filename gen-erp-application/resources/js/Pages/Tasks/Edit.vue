<template>
  <AppLayout title="Edit Task">
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Task: {{ task?.title }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">Task #{{ task?.task_number }}</p>
        </div>
        <div class="flex space-x-3">
          <Link :href="route('tasks.show', taskId)" 
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            View Task
          </Link>
          <Link :href="route('tasks.index')" 
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Back to Tasks
          </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-500">Loading task...</p>
        </div>

        <div v-else-if="task" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <form @submit.prevent="submitForm" class="space-y-6">
              <!-- Basic Information -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Task Title *</label>
                    <input
                      v-model="form.title"
                      type="text"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="Enter task title"
                    />
                    <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title[0] }}</p>
                  </div>

                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea
                      v-model="form.description"
                      rows="4"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="Enter task description"
                    ></textarea>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Project</label>
                    <select
                      v-model="form.project_id"
                      @change="loadProjectBoards"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="">Select Project</option>
                      <option v-for="project in projects" :key="project.id" :value="project.id">
                        {{ project.name }}
                      </option>
                    </select>
                    <p v-if="errors.project_id" class="mt-1 text-sm text-red-600">{{ errors.project_id[0] }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Board Column</label>
                    <select
                      v-model="form.board_column_id"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="">Select Column</option>
                      <option v-for="column in boardColumns" :key="column.id" :value="column.id">
                        {{ column.name }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Task Details -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Task Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Type</label>
                    <select
                      v-model="form.type"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="task">Task</option>
                      <option value="bug">Bug</option>
                      <option value="feature">Feature</option>
                      <option value="epic">Epic</option>
                      <option value="story">Story</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                      v-model="form.status"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="todo">To Do</option>
                      <option value="in_progress">In Progress</option>
                      <option value="review">Review</option>
                      <option value="done">Done</option>
                      <option value="blocked">Blocked</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Priority</label>
                    <select
                      v-model="form.priority"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="low">Low</option>
                      <option value="medium">Medium</option>
                      <option value="high">High</option>
                      <option value="urgent">Urgent</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Assignee</label>
                    <select
                      v-model="form.assignee_id"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="">Unassigned</option>
                      <option v-for="user in users" :key="user.id" :value="user.id">
                        {{ user.name }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Parent Task</label>
                    <select
                      v-model="form.parent_task_id"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="">No Parent Task</option>
                      <option v-for="task in parentTasks" :key="task.id" :value="task.id">
                        {{ task.title }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Phase/Milestone</label>
                    <select
                      v-model="form.phase_id"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                      <option value="">No Phase</option>
                      <option v-for="phase in projectPhases" :key="phase.id" :value="phase.id">
                        {{ phase.name }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Time & Dates -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Time & Dates</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Estimated Hours</label>
                    <input
                      v-model.number="form.estimated_hours"
                      type="number"
                      min="0"
                      step="0.5"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="0"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Progress (%)</label>
                    <input
                      v-model.number="form.progress"
                      type="number"
                      min="0"
                      max="100"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      placeholder="0"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input
                      v-model="form.start_date"
                      type="date"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">Due Date</label>
                    <input
                      v-model="form.due_date"
                      type="date"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                  </div>
                </div>
              </div>

              <!-- Labels -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Labels</h3>
                <div class="flex flex-wrap gap-2 mb-2">
                  <span
                    v-for="label in selectedLabels"
                    :key="label.id"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :style="`background-color: ${label.color}20; color: ${label.color}`"
                  >
                    {{ label.name }}
                    <button
                      type="button"
                      @click="removeLabel(label)"
                      class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-black hover:bg-opacity-10"
                    >
                      ×
                    </button>
                  </span>
                </div>
                <select
                  @change="addLabel"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                  <option value="">Add a label</option>
                  <option v-for="label in availableLabels" :key="label.id" :value="label.id">
                    {{ label.name }}
                  </option>
                </select>
              </div>

              <!-- Task Stats (Read-only) -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Task Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm font-medium text-gray-500">Actual Hours</p>
                    <p class="text-lg font-semibold text-gray-900">{{ task.actual_hours || 0 }} hrs</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm font-medium text-gray-500">Comments</p>
                    <p class="text-lg font-semibold text-gray-900">{{ task.comments?.length || 0 }}</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm font-medium text-gray-500">Created</p>
                    <p class="text-lg font-semibold text-gray-900">{{ formatDate(task.created_at) }}</p>
                  </div>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <Link :href="route('tasks.show', taskId)"
                      class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                  Cancel
                </Link>
                <button
                  type="submit"
                  :disabled="saving"
                  class="bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                >
                  <span v-if="saving">Saving...</span>
                  <span v-else>Save Changes</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  taskId: [String, Number]
})

const task = ref(null)
const projects = ref([])
const users = ref([])
const labels = ref([])
const boardColumns = ref([])
const parentTasks = ref([])
const projectPhases = ref([])
const selectedLabels = ref([])
const loading = ref(true)
const saving = ref(false)
const errors = ref({})

const form = reactive({
  title: '',
  description: '',
  project_id: '',
  phase_id: '',
  board_column_id: '',
  parent_task_id: '',
  type: 'task',
  status: 'todo',
  priority: 'medium',
  assignee_id: '',
  estimated_hours: null,
  progress: 0,
  start_date: '',
  due_date: ''
})

onMounted(async () => {
  await Promise.all([
    fetchTask(),
    fetchProjects(),
    fetchUsers(),
    fetchLabels()
  ])
})

const fetchTask = async () => {
  try {
    const response = await fetch(`/api/v1/tasks/${props.taskId}`, {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      task.value = data.data
      
      // Populate form with task data
      Object.keys(form).forEach(key => {
        if (task.value[key] !== undefined) {
          form[key] = task.value[key]
        }
      })
      
      // Set selected labels
      if (task.value.labels) {
        selectedLabels.value = [...task.value.labels]
      }
      
      // Load project-specific data
      if (form.project_id) {
        await loadProjectBoards()
      }
    }
  } catch (error) {
    console.error('Failed to fetch task:', error)
  } finally {
    loading.value = false
  }
}

const fetchProjects = async () => {
  try {
    const response = await fetch('/api/v1/projects', {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      projects.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch projects:', error)
  }
}

const fetchUsers = async () => {
  try {
    const response = await fetch('/api/v1/users', {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      users.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch users:', error)
  }
}

const fetchLabels = async () => {
  try {
    const response = await fetch('/api/v1/labels', {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      labels.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch labels:', error)
  }
}

const loadProjectBoards = async () => {
  if (!form.project_id) return
  
  try {
    const [boardsResponse, tasksResponse, phasesResponse] = await Promise.all([
      fetch(`/api/v1/projects/${form.project_id}/boards`, {
        headers: {
          'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
          'Accept': 'application/json',
        }
      }),
      fetch(`/api/v1/projects/${form.project_id}/tasks`, {
        headers: {
          'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
          'Accept': 'application/json',
        }
      }),
      fetch(`/api/v1/projects/${form.project_id}/phases`, {
        headers: {
          'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
          'Accept': 'application/json',
        }
      })
    ])
    
    if (boardsResponse.ok) {
      const boardsData = await boardsResponse.json()
      if (boardsData.data.length > 0) {
        boardColumns.value = boardsData.data[0].columns || []
      }
    }
    
    if (tasksResponse.ok) {
      const tasksData = await tasksResponse.json()
      parentTasks.value = tasksData.data.filter(t => 
        (t.type === 'epic' || t.type === 'story') && t.id !== parseInt(props.taskId)
      )
    }
    
    if (phasesResponse.ok) {
      const phasesData = await phasesResponse.json()
      projectPhases.value = phasesData.data
    }
  } catch (error) {
    console.error('Failed to load project data:', error)
  }
}

const availableLabels = computed(() => {
  return labels.value.filter(label => 
    !selectedLabels.value.some(selected => selected.id === label.id)
  )
})

const addLabel = (event) => {
  const labelId = parseInt(event.target.value)
  if (labelId) {
    const label = labels.value.find(l => l.id === labelId)
    if (label) {
      selectedLabels.value.push(label)
    }
    event.target.value = ''
  }
}

const removeLabel = (label) => {
  const index = selectedLabels.value.findIndex(l => l.id === label.id)
  if (index > -1) {
    selectedLabels.value.splice(index, 1)
  }
}

const submitForm = async () => {
  saving.value = true
  errors.value = {}
  
  const payload = {
    ...form,
    label_ids: selectedLabels.value.map(label => label.id)
  }
  
  try {
    const response = await fetch(`/api/v1/tasks/${props.taskId}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload)
    })
    
    const data = await response.json()
    
    if (response.ok) {
      router.visit(route('tasks.show', props.taskId))
    } else {
      if (data.errors) {
        errors.value = data.errors
      }
    }
  } catch (error) {
    console.error('Failed to update task:', error)
  } finally {
    saving.value = false
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}
</script>