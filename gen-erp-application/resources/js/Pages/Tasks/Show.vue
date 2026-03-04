<template>
  <AppLayout title="Task Details">
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Task #{{ task?.task_number }}: {{ task?.title }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">{{ task?.project?.name }}</p>
        </div>
        <div class="flex space-x-3">
          <Link :href="route('tasks.edit', taskId)" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Edit Task
          </Link>
          <Link :href="route('tasks.index')" 
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Back to Tasks
          </Link>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-500">Loading task...</p>
        </div>

        <div v-else-if="task" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Main Content -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Task Details -->
            <div class="bg-white rounded-lg shadow p-6">
              <div class="flex items-start justify-between mb-4">
                <div class="flex items-center space-x-3">
                  <span :class="getTypeClass(task.type)" 
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                    {{ task.type }}
                  </span>
                  <span :class="getPriorityClass(task.priority)" 
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                    {{ task.priority }}
                  </span>
                  <span :class="getStatusClass(task.status)" 
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                    {{ task.status.replace('_', ' ').toUpperCase() }}
                  </span>
                </div>
                <div class="flex space-x-2">
                  <button @click="startTimer" v-if="!timerRunning && task.status !== 'done'"
                          class="text-green-600 hover:text-green-500 text-sm font-medium">
                    Start Timer
                  </button>
                  <button @click="stopTimer" v-if="timerRunning"
                          class="text-red-600 hover:text-red-500 text-sm font-medium">
                    Stop Timer ({{ formatTime(elapsedTime) }})
                  </button>
                  <button @click="showLogTimeModal = true"
                          class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                    Log Time
                  </button>
                </div>
              </div>

              <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ task.title }}</h1>
              
              <div v-if="task.description" class="prose max-w-none mb-6">
                <p class="text-gray-700 whitespace-pre-wrap">{{ task.description }}</p>
              </div>

              <!-- Labels -->
              <div v-if="task.labels && task.labels.length" class="flex flex-wrap gap-2 mb-6">
                <span v-for="label in task.labels" :key="label.id"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :style="`background-color: ${label.color}20; color: ${label.color}`">
                  {{ label.name }}
                </span>
              </div>

              <!-- Progress -->
              <div class="mb-6">
                <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                  <span>Progress</span>
                  <span>{{ task.progress || 0 }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                       :style="`width: ${task.progress || 0}%`"></div>
                </div>
              </div>

              <!-- Subtasks -->
              <div v-if="subtasks.length > 0" class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Subtasks</h3>
                <div class="space-y-2">
                  <div v-for="subtask in subtasks" :key="subtask.id" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                      <input type="checkbox" :checked="subtask.status === 'done'" 
                             @change="toggleSubtaskStatus(subtask)"
                             class="rounded border-gray-300 text-blue-600">
                      <Link :href="route('tasks.show', subtask.id)" 
                            class="text-sm font-medium text-gray-900 hover:text-blue-600">
                        {{ subtask.title }}
                      </Link>
                    </div>
                    <span :class="getStatusClass(subtask.status)" 
                          class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                      {{ subtask.status.replace('_', ' ') }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Dependencies -->
              <div v-if="task.dependencies && task.dependencies.length > 0" class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Dependencies</h3>
                <div class="space-y-2">
                  <div v-for="dep in task.dependencies" :key="dep.id" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                      <span class="text-xs text-gray-500 capitalize">{{ dep.type.replace('_', ' ') }}</span>
                      <Link :href="route('tasks.show', dep.depends_on_task.id)" 
                            class="text-sm font-medium text-gray-900 hover:text-blue-600">
                        {{ dep.depends_on_task.title }}
                      </Link>
                    </div>
                    <span :class="getStatusClass(dep.depends_on_task.status)" 
                          class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                      {{ dep.depends_on_task.status.replace('_', ' ') }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Comments -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Comments</h3>
              
              <!-- Add Comment -->
              <div class="mb-6">
                <textarea v-model="newComment" 
                          rows="3" 
                          placeholder="Add a comment..."
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                <div class="mt-2 flex justify-end">
                  <button @click="addComment" :disabled="!newComment.trim()"
                          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50">
                    Add Comment
                  </button>
                </div>
              </div>

              <!-- Comments List -->
              <div class="space-y-4">
                <div v-for="comment in task.comments" :key="comment.id" 
                     class="flex space-x-3">
                  <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-sm font-medium">
                    {{ comment.user.name.charAt(0) }}
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-1">
                      <span class="text-sm font-medium text-gray-900">{{ comment.user.name }}</span>
                      <span class="text-xs text-gray-500">{{ formatDate(comment.created_at) }}</span>
                    </div>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ comment.comment }}</p>
                  </div>
                </div>
                
                <div v-if="!task.comments || task.comments.length === 0" 
                     class="text-center py-8 text-gray-500">
                  No comments yet. Be the first to comment!
                </div>
              </div>
            </div>

            <!-- Time Entries -->
            <div class="bg-white rounded-lg shadow p-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Time Entries</h3>
                <span class="text-sm text-gray-500">
                  Total: {{ task.actual_hours || 0 }}h / {{ task.estimated_hours || 0 }}h estimated
                </span>
              </div>
              
              <div class="space-y-3">
                <div v-for="entry in task.time_entries" :key="entry.id" 
                     class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ entry.description }}</div>
                    <div class="text-xs text-gray-500">
                      {{ entry.user.name }} • {{ formatDate(entry.entry_date) }}
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-sm font-medium text-gray-900">{{ entry.hours }}h</div>
                    <div class="text-xs text-gray-500 capitalize">{{ entry.type }}</div>
                  </div>
                </div>
                
                <div v-if="!task.time_entries || task.time_entries.length === 0" 
                     class="text-center py-8 text-gray-500">
                  No time entries yet.
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Task Info -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Task Information</h3>
              <dl class="space-y-3">
                <div>
                  <dt class="text-sm font-medium text-gray-500">Assignee</dt>
                  <dd class="mt-1 text-sm text-gray-900">
                    <div v-if="task.assignee" class="flex items-center space-x-2">
                      <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center text-xs font-medium">
                        {{ task.assignee.name.charAt(0) }}
                      </div>
                      <span>{{ task.assignee.name }}</span>
                    </div>
                    <span v-else class="text-gray-400">Unassigned</span>
                  </dd>
                </div>
                
                <div>
                  <dt class="text-sm font-medium text-gray-500">Reporter</dt>
                  <dd class="mt-1 text-sm text-gray-900">
                    <div class="flex items-center space-x-2">
                      <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center text-xs font-medium">
                        {{ task.reporter.name.charAt(0) }}
                      </div>
                      <span>{{ task.reporter.name }}</span>
                    </div>
                  </dd>
                </div>

                <div v-if="task.start_date">
                  <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ formatDate(task.start_date) }}</dd>
                </div>

                <div v-if="task.due_date">
                  <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                  <dd class="mt-1 text-sm text-gray-900" :class="{ 'text-red-600': isOverdue(task) }">
                    {{ formatDate(task.due_date) }}
                    <span v-if="isOverdue(task)" class="text-xs">(Overdue)</span>
                  </dd>
                </div>

                <div>
                  <dt class="text-sm font-medium text-gray-500">Created</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ formatDate(task.created_at) }}</dd>
                </div>

                <div>
                  <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                  <dd class="mt-1 text-sm text-gray-900">{{ formatDate(task.updated_at) }}</dd>
                </div>
              </dl>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
              <div class="space-y-2">
                <button @click="updateStatus('in_progress')" 
                        v-if="task.status === 'todo'"
                        class="w-full text-left px-3 py-2 text-sm text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100">
                  Start Task
                </button>
                <button @click="updateStatus('review')" 
                        v-if="task.status === 'in_progress'"
                        class="w-full text-left px-3 py-2 text-sm text-purple-600 bg-purple-50 rounded-lg hover:bg-purple-100">
                  Mark for Review
                </button>
                <button @click="updateStatus('done')" 
                        v-if="['in_progress', 'review'].includes(task.status)"
                        class="w-full text-left px-3 py-2 text-sm text-green-600 bg-green-50 rounded-lg hover:bg-green-100">
                  Mark as Done
                </button>
                <button @click="updateStatus('blocked')" 
                        v-if="task.status !== 'blocked' && task.status !== 'done'"
                        class="w-full text-left px-3 py-2 text-sm text-red-600 bg-red-50 rounded-lg hover:bg-red-100">
                  Mark as Blocked
                </button>
                <Link :href="route('tasks.create', { project_id: task.project_id, parent_task_id: task.id })"
                      class="block w-full text-left px-3 py-2 text-sm text-gray-600 bg-gray-50 rounded-lg hover:bg-gray-100">
                  Create Subtask
                </Link>
              </div>
            </div>

            <!-- Attachments -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Attachments</h3>
              <div class="space-y-2">
                <div v-for="attachment in task.attachments" :key="attachment.id" 
                     class="flex items-center justify-between p-2 bg-gray-50 rounded">
                  <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-gray-900">{{ attachment.filename }}</span>
                  </div>
                  <a :href="attachment.download_url" 
                     class="text-xs text-blue-600 hover:text-blue-500">
                    Download
                  </a>
                </div>
                
                <div v-if="!task.attachments || task.attachments.length === 0" 
                     class="text-center py-4 text-gray-500 text-sm">
                  No attachments
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Log Time Modal -->
    <LogTimeModal 
      :show="showLogTimeModal"
      :task="task"
      @close="showLogTimeModal = false"
      @logged="handleTimeLogged"
    />
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import LogTimeModal from '@/Components/Tasks/LogTimeModal.vue'

const props = defineProps({
  taskId: [String, Number]
})

const task = ref(null)
const subtasks = ref([])
const loading = ref(true)
const newComment = ref('')
const showLogTimeModal = ref(false)
const timerRunning = ref(false)
const timerStart = ref(null)
const elapsedTime = ref(0)
const timerInterval = ref(null)

onMounted(async () => {
  await fetchTask()
  await fetchSubtasks()
})

onUnmounted(() => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
  }
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
    }
  } catch (error) {
    console.error('Failed to fetch task:', error)
  } finally {
    loading.value = false
  }
}

const fetchSubtasks = async () => {
  try {
    const response = await fetch(`/api/v1/tasks?parent_task_id=${props.taskId}`, {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      subtasks.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch subtasks:', error)
  }
}

const addComment = async () => {
  if (!newComment.value.trim()) return
  
  try {
    const response = await fetch(`/api/v1/tasks/${props.taskId}/comments`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        comment: newComment.value
      })
    })
    
    if (response.ok) {
      newComment.value = ''
      await fetchTask()
    }
  } catch (error) {
    console.error('Failed to add comment:', error)
  }
}

const updateStatus = async (status) => {
  try {
    const response = await fetch(`/api/v1/tasks/${props.taskId}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ status })
    })
    
    if (response.ok) {
      await fetchTask()
    }
  } catch (error) {
    console.error('Failed to update status:', error)
  }
}

const toggleSubtaskStatus = async (subtask) => {
  const newStatus = subtask.status === 'done' ? 'todo' : 'done'
  
  try {
    const response = await fetch(`/api/v1/tasks/${subtask.id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ status: newStatus })
    })
    
    if (response.ok) {
      await fetchSubtasks()
    }
  } catch (error) {
    console.error('Failed to update subtask:', error)
  }
}

const startTimer = () => {
  timerRunning.value = true
  timerStart.value = Date.now()
  elapsedTime.value = 0
  
  timerInterval.value = setInterval(() => {
    elapsedTime.value = Date.now() - timerStart.value
  }, 1000)
}

const stopTimer = () => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
  }
  
  const hours = elapsedTime.value / (1000 * 60 * 60)
  
  // Auto-log the time
  logTime(hours, 'Timer session')
  
  timerRunning.value = false
  timerStart.value = null
  elapsedTime.value = 0
}

const logTime = async (hours, description) => {
  try {
    const response = await fetch('/api/v1/time-entries', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        task_id: props.taskId,
        hours: hours,
        description: description,
        entry_date: new Date().toISOString().split('T')[0]
      })
    })
    
    if (response.ok) {
      await fetchTask()
    }
  } catch (error) {
    console.error('Failed to log time:', error)
  }
}

const handleTimeLogged = () => {
  showLogTimeModal.value = false
  fetchTask()
}

const formatTime = (milliseconds) => {
  const seconds = Math.floor(milliseconds / 1000)
  const minutes = Math.floor(seconds / 60)
  const hours = Math.floor(minutes / 60)
  
  if (hours > 0) {
    return `${hours}:${(minutes % 60).toString().padStart(2, '0')}:${(seconds % 60).toString().padStart(2, '0')}`
  }
  return `${minutes}:${(seconds % 60).toString().padStart(2, '0')}`
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

const isOverdue = (task) => {
  if (!task.due_date) return false
  return new Date(task.due_date) < new Date() && task.status !== 'done'
}

const getStatusClass = (status) => {
  const classes = {
    todo: 'bg-gray-100 text-gray-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    review: 'bg-blue-100 text-blue-800',
    done: 'bg-green-100 text-green-800',
    blocked: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityClass = (priority) => {
  const classes = {
    low: 'bg-green-100 text-green-800',
    medium: 'bg-yellow-100 text-yellow-800',
    high: 'bg-orange-100 text-orange-800',
    urgent: 'bg-red-100 text-red-800'
  }
  return classes[priority] || 'bg-gray-100 text-gray-800'
}

const getTypeClass = (type) => {
  const classes = {
    task: 'bg-blue-100 text-blue-800',
    bug: 'bg-red-100 text-red-800',
    feature: 'bg-green-100 text-green-800',
    epic: 'bg-purple-100 text-purple-800',
    story: 'bg-indigo-100 text-indigo-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}
</script>