<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Project Gantt Chart</h3>
        <div class="flex items-center space-x-3">
          <!-- View Controls -->
          <div class="flex items-center space-x-2">
            <label class="text-sm text-gray-700">View:</label>
            <select
              v-model="viewMode"
              @change="updateView"
              class="text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="day">Day</option>
              <option value="week">Week</option>
              <option value="month">Month</option>
              <option value="quarter">Quarter</option>
            </select>
          </div>
          
          <!-- Zoom Controls -->
          <div class="flex items-center space-x-1">
            <button
              @click="zoomOut"
              class="p-1 text-gray-400 hover:text-gray-600"
              title="Zoom Out"
            >
              <MinusIcon class="h-4 w-4" />
            </button>
            <button
              @click="zoomIn"
              class="p-1 text-gray-400 hover:text-gray-600"
              title="Zoom In"
            >
              <PlusIcon class="h-4 w-4" />
            </button>
          </div>

          <!-- Export -->
          <button
            @click="exportGantt"
            class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-1 px-3 rounded-md"
          >
            Export
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      <span class="ml-2 text-gray-600">Loading Gantt chart...</span>
    </div>

    <!-- Gantt Chart -->
    <div v-else class="overflow-auto">
      <div class="min-w-full">
        <!-- Timeline Header -->
        <div class="sticky top-0 bg-gray-50 border-b border-gray-200 z-10">
          <div class="flex">
            <!-- Task Names Column -->
            <div class="w-80 px-4 py-3 border-r border-gray-200">
              <h4 class="text-sm font-medium text-gray-900">Tasks</h4>
            </div>
            
            <!-- Timeline -->
            <div class="flex-1 overflow-x-auto">
              <div class="flex" :style="{ width: timelineWidth + 'px' }">
                <div
                  v-for="period in timelinePeriods"
                  :key="period.key"
                  class="border-r border-gray-200 text-center py-3"
                  :style="{ width: periodWidth + 'px' }"
                >
                  <div class="text-xs font-medium text-gray-900">
                    {{ formatPeriod(period) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Gantt Rows -->
        <div class="divide-y divide-gray-200">
          <div
            v-for="task in tasks"
            :key="task.id"
            class="flex hover:bg-gray-50"
            :class="{ 'bg-blue-50': selectedTask?.id === task.id }"
          >
            <!-- Task Info -->
            <div class="w-80 px-4 py-3 border-r border-gray-200">
              <div class="flex items-center space-x-2">
                <button
                  v-if="task.subtasks && task.subtasks.length > 0"
                  @click="toggleTaskExpansion(task.id)"
                  class="text-gray-400 hover:text-gray-600"
                >
                  <ChevronRightIcon
                    v-if="!expandedTasks.includes(task.id)"
                    class="h-4 w-4"
                  />
                  <ChevronDownIcon v-else class="h-4 w-4" />
                </button>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">
                    {{ task.title }}
                  </p>
                  <div class="flex items-center space-x-2 mt-1">
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                      :class="getStatusClass(task.status)"
                    >
                      {{ task.status }}
                    </span>
                    <span class="text-xs text-gray-500">
                      {{ task.assignee?.name || 'Unassigned' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Timeline Bar -->
            <div class="flex-1 relative py-3" :style="{ width: timelineWidth + 'px' }">
              <!-- Task Bar -->
              <div
                v-if="task.start_date && task.due_date"
                class="absolute h-6 rounded cursor-pointer"
                :class="getTaskBarClass(task)"
                :style="getTaskBarStyle(task)"
                @click="selectTask(task)"
                @mousedown="startDrag(task, $event)"
              >
                <!-- Progress Bar -->
                <div
                  v-if="task.progress > 0"
                  class="h-full bg-green-500 rounded"
                  :style="{ width: task.progress + '%' }"
                ></div>
                
                <!-- Task Title -->
                <div class="absolute inset-0 flex items-center px-2">
                  <span class="text-xs font-medium text-white truncate">
                    {{ task.title }}
                  </span>
                </div>

                <!-- Resize Handles -->
                <div
                  class="absolute left-0 top-0 w-2 h-full cursor-w-resize opacity-0 hover:opacity-100"
                  @mousedown.stop="startResize(task, 'start', $event)"
                ></div>
                <div
                  class="absolute right-0 top-0 w-2 h-full cursor-e-resize opacity-0 hover:opacity-100"
                  @mousedown.stop="startResize(task, 'end', $event)"
                ></div>
              </div>

              <!-- Dependencies -->
              <svg
                v-if="task.dependencies && task.dependencies.length > 0"
                class="absolute inset-0 pointer-events-none"
                :width="timelineWidth"
                height="100%"
              >
                <path
                  v-for="dep in getVisibleDependencies(task)"
                  :key="`${task.id}-${dep.id}`"
                  :d="getDependencyPath(task, dep)"
                  stroke="#6366f1"
                  stroke-width="2"
                  fill="none"
                  marker-end="url(#arrowhead)"
                />
              </svg>

              <!-- Milestone -->
              <div
                v-if="task.is_milestone"
                class="absolute w-4 h-4 bg-yellow-500 transform rotate-45"
                :style="getMilestoneStyle(task)"
                :title="task.title"
              ></div>
            </div>
          </div>

          <!-- Subtasks -->
          <template v-for="task in tasks" :key="`subtasks-${task.id}`">
            <div
              v-if="expandedTasks.includes(task.id) && task.subtasks"
              v-for="subtask in task.subtasks"
              :key="subtask.id"
              class="flex hover:bg-gray-50 bg-gray-25"
            >
              <!-- Subtask Info -->
              <div class="w-80 px-4 py-2 border-r border-gray-200">
                <div class="flex items-center space-x-2 ml-6">
                  <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-700 truncate">
                      {{ subtask.title }}
                    </p>
                    <div class="flex items-center space-x-2 mt-1">
                      <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="getStatusClass(subtask.status)"
                      >
                        {{ subtask.status }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Subtask Timeline Bar -->
              <div class="flex-1 relative py-2" :style="{ width: timelineWidth + 'px' }">
                <div
                  v-if="subtask.start_date && subtask.due_date"
                  class="absolute h-4 rounded cursor-pointer opacity-75"
                  :class="getTaskBarClass(subtask)"
                  :style="getTaskBarStyle(subtask)"
                  @click="selectTask(subtask)"
                >
                  <div
                    v-if="subtask.progress > 0"
                    class="h-full bg-green-400 rounded"
                    :style="{ width: subtask.progress + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Today Line -->
    <div
      class="absolute top-0 bottom-0 w-0.5 bg-red-500 pointer-events-none z-20"
      :style="{ left: todayLinePosition + 'px' }"
    >
      <div class="absolute -top-2 -left-2 w-4 h-4 bg-red-500 rounded-full"></div>
    </div>

    <!-- SVG Definitions -->
    <svg class="absolute" width="0" height="0">
      <defs>
        <marker
          id="arrowhead"
          markerWidth="10"
          markerHeight="7"
          refX="9"
          refY="3.5"
          orient="auto"
        >
          <polygon points="0 0, 10 3.5, 0 7" fill="#6366f1" />
        </marker>
      </defs>
    </svg>

    <!-- Task Details Modal -->
    <div
      v-if="selectedTask"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click="selectedTask = null"
    >
      <div
        class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"
        @click.stop
      >
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Task Details</h3>
          <button
            @click="selectedTask = null"
            class="text-gray-400 hover:text-gray-600"
          >
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        
        <div class="space-y-3">
          <div>
            <label class="text-sm font-medium text-gray-700">Title</label>
            <p class="text-sm text-gray-900">{{ selectedTask.title }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700">Status</label>
            <span
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
              :class="getStatusClass(selectedTask.status)"
            >
              {{ selectedTask.status }}
            </span>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700">Duration</label>
            <p class="text-sm text-gray-900">
              {{ formatDate(selectedTask.start_date) }} - {{ formatDate(selectedTask.due_date) }}
            </p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700">Progress</label>
            <div class="flex items-center space-x-2">
              <div class="flex-1 bg-gray-200 rounded-full h-2">
                <div
                  class="bg-green-500 h-2 rounded-full"
                  :style="{ width: selectedTask.progress + '%' }"
                ></div>
              </div>
              <span class="text-sm text-gray-600">{{ selectedTask.progress }}%</span>
            </div>
          </div>
          <div v-if="selectedTask.assignee">
            <label class="text-sm font-medium text-gray-700">Assignee</label>
            <p class="text-sm text-gray-900">{{ selectedTask.assignee.name }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import {
  PlusIcon,
  MinusIcon,
  ChevronRightIcon,
  ChevronDownIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

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

const emit = defineEmits(['task-updated'])

const { get, put, loading } = useApi()
const { showSuccess, showError } = useToast()

// Reactive data
const viewMode = ref('week')
const zoomLevel = ref(1)
const selectedTask = ref(null)
const expandedTasks = ref([])
const dragState = ref(null)

// Timeline configuration
const periodWidth = computed(() => {
  const baseWidth = {
    day: 30,
    week: 100,
    month: 120,
    quarter: 200
  }
  return baseWidth[viewMode.value] * zoomLevel.value
})

const timelineWidth = computed(() => {
  return timelinePeriods.value.length * periodWidth.value
})

const timelinePeriods = computed(() => {
  if (!props.tasks.length) return []
  
  const startDate = new Date(Math.min(...props.tasks.map(t => new Date(t.start_date || Date.now()))))
  const endDate = new Date(Math.max(...props.tasks.map(t => new Date(t.due_date || Date.now()))))
  
  // Add buffer
  startDate.setDate(startDate.getDate() - 7)
  endDate.setDate(endDate.getDate() + 7)
  
  const periods = []
  const current = new Date(startDate)
  
  while (current <= endDate) {
    periods.push(new Date(current))
    
    switch (viewMode.value) {
      case 'day':
        current.setDate(current.getDate() + 1)
        break
      case 'week':
        current.setDate(current.getDate() + 7)
        break
      case 'month':
        current.setMonth(current.getMonth() + 1)
        break
      case 'quarter':
        current.setMonth(current.getMonth() + 3)
        break
    }
  }
  
  return periods.map((date, index) => ({
    key: `${date.getTime()}-${index}`,
    date,
    start: date,
    end: getNextPeriod(date)
  }))
})

const todayLinePosition = computed(() => {
  if (!timelinePeriods.value.length) return 0
  
  const today = new Date()
  const startDate = timelinePeriods.value[0].date
  const daysDiff = Math.floor((today - startDate) / (1000 * 60 * 60 * 24))
  
  return 320 + (daysDiff * periodWidth.value / 7) // 320px for task names column
})

// Methods
const getNextPeriod = (date) => {
  const next = new Date(date)
  switch (viewMode.value) {
    case 'day':
      next.setDate(next.getDate() + 1)
      break
    case 'week':
      next.setDate(next.getDate() + 7)
      break
    case 'month':
      next.setMonth(next.getMonth() + 1)
      break
    case 'quarter':
      next.setMonth(next.getMonth() + 3)
      break
  }
  return next
}

const formatPeriod = (period) => {
  const options = {
    day: { month: 'short', day: 'numeric' },
    week: { month: 'short', day: 'numeric' },
    month: { month: 'short', year: 'numeric' },
    quarter: { month: 'short', year: 'numeric' }
  }
  
  return period.date.toLocaleDateString('en-US', options[viewMode.value])
}

const getTaskBarStyle = (task) => {
  if (!task.start_date || !task.due_date || !timelinePeriods.value.length) {
    return { display: 'none' }
  }
  
  const startDate = new Date(task.start_date)
  const endDate = new Date(task.due_date)
  const timelineStart = timelinePeriods.value[0].date
  
  const startOffset = Math.floor((startDate - timelineStart) / (1000 * 60 * 60 * 24))
  const duration = Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1
  
  const left = (startOffset * periodWidth.value) / 7
  const width = Math.max((duration * periodWidth.value) / 7, 20)
  
  return {
    left: left + 'px',
    width: width + 'px',
    top: '8px'
  }
}

const getMilestoneStyle = (task) => {
  if (!task.due_date || !timelinePeriods.value.length) {
    return { display: 'none' }
  }
  
  const date = new Date(task.due_date)
  const timelineStart = timelinePeriods.value[0].date
  const offset = Math.floor((date - timelineStart) / (1000 * 60 * 60 * 24))
  
  return {
    left: (offset * periodWidth.value) / 7 + 'px',
    top: '10px'
  }
}

const getTaskBarClass = (task) => {
  const baseClass = 'bg-indigo-500 hover:bg-indigo-600'
  const statusClasses = {
    'completed': 'bg-green-500 hover:bg-green-600',
    'in_progress': 'bg-blue-500 hover:bg-blue-600',
    'review': 'bg-yellow-500 hover:bg-yellow-600',
    'todo': 'bg-gray-500 hover:bg-gray-600'
  }
  
  return statusClasses[task.status] || baseClass
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

const toggleTaskExpansion = (taskId) => {
  const index = expandedTasks.value.indexOf(taskId)
  if (index > -1) {
    expandedTasks.value.splice(index, 1)
  } else {
    expandedTasks.value.push(taskId)
  }
}

const selectTask = (task) => {
  selectedTask.value = task
}

const updateView = () => {
  // Trigger reactivity
  nextTick()
}

const zoomIn = () => {
  if (zoomLevel.value < 3) {
    zoomLevel.value += 0.5
  }
}

const zoomOut = () => {
  if (zoomLevel.value > 0.5) {
    zoomLevel.value -= 0.5
  }
}

const startDrag = (task, event) => {
  dragState.value = {
    task,
    startX: event.clientX,
    originalStart: new Date(task.start_date),
    originalEnd: new Date(task.due_date)
  }
  
  document.addEventListener('mousemove', handleDrag)
  document.addEventListener('mouseup', endDrag)
}

const handleDrag = (event) => {
  if (!dragState.value) return
  
  const deltaX = event.clientX - dragState.value.startX
  const daysDelta = Math.round((deltaX * 7) / periodWidth.value)
  
  const newStart = new Date(dragState.value.originalStart)
  const newEnd = new Date(dragState.value.originalEnd)
  
  newStart.setDate(newStart.getDate() + daysDelta)
  newEnd.setDate(newEnd.getDate() + daysDelta)
  
  // Update task dates temporarily for visual feedback
  dragState.value.task.start_date = newStart.toISOString().split('T')[0]
  dragState.value.task.due_date = newEnd.toISOString().split('T')[0]
}

const endDrag = async () => {
  if (!dragState.value) return
  
  try {
    await put(`/api/v1/tasks/${dragState.value.task.id}`, {
      start_date: dragState.value.task.start_date,
      due_date: dragState.value.task.due_date
    })
    
    showSuccess('Task dates updated')
    emit('task-updated', dragState.value.task)
  } catch (err) {
    // Revert changes
    dragState.value.task.start_date = dragState.value.originalStart.toISOString().split('T')[0]
    dragState.value.task.due_date = dragState.value.originalEnd.toISOString().split('T')[0]
    showError('Failed to update task dates')
  }
  
  dragState.value = null
  document.removeEventListener('mousemove', handleDrag)
  document.removeEventListener('mouseup', endDrag)
}

const startResize = (task, handle, event) => {
  event.stopPropagation()
  // Implement resize logic similar to drag
}

const getVisibleDependencies = (task) => {
  if (!task.dependencies) return []
  return task.dependencies.filter(dep => 
    props.tasks.some(t => t.id === dep.id)
  )
}

const getDependencyPath = (fromTask, toTask) => {
  // Calculate SVG path for dependency arrow
  const fromStyle = getTaskBarStyle(fromTask)
  const toStyle = getTaskBarStyle(toTask)
  
  if (fromStyle.display === 'none' || toStyle.display === 'none') return ''
  
  const fromX = parseInt(fromStyle.left) + parseInt(fromStyle.width)
  const fromY = 20
  const toX = parseInt(toStyle.left)
  const toY = 20
  
  return `M ${fromX} ${fromY} L ${toX} ${toY}`
}

const exportGantt = () => {
  // Implement Gantt chart export
  showSuccess('Gantt chart export feature coming soon')
}

const formatDate = (date) => {
  if (!date) return 'Not set'
  return new Date(date).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  // Initialize expanded tasks for parent tasks
  expandedTasks.value = props.tasks
    .filter(task => task.subtasks && task.subtasks.length > 0)
    .map(task => task.id)
})
</script>

<style scoped>
.bg-gray-25 {
  background-color: #fafafa;
}
</style>