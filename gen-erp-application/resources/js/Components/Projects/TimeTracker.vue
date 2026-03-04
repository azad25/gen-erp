<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">Time Tracker</h3>
        <div class="flex items-center space-x-2">
          <button
            v-if="!isTracking"
            @click="showManualEntryModal = true"
            class="text-xs text-gray-500 hover:text-gray-700"
          >
            Manual Entry
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

    <!-- Timer Content -->
    <div v-if="!collapsed" class="p-4">
      <!-- Current Timer -->
      <div class="text-center mb-6">
        <!-- Timer Display -->
        <div class="text-3xl font-mono font-bold text-gray-900 mb-2">
          {{ formatTime(currentTime) }}
        </div>
        
        <!-- Current Task -->
        <div v-if="currentTask" class="text-sm text-gray-600 mb-4">
          <div class="font-medium">{{ currentTask.title }}</div>
          <div class="text-xs">{{ currentTask.project?.name }}</div>
        </div>
        
        <!-- Timer Controls -->
        <div class="flex items-center justify-center space-x-3">
          <button
            v-if="!isTracking"
            @click="startTimer"
            :disabled="!selectedTask"
            class="bg-green-600 hover:bg-green-700 disabled:bg-gray-300 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center"
          >
            <PlayIcon class="h-4 w-4 mr-2" />
            Start
          </button>
          
          <button
            v-if="isTracking"
            @click="pauseTimer"
            class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center"
          >
            <PauseIcon class="h-4 w-4 mr-2" />
            Pause
          </button>
          
          <button
            v-if="isTracking || isPaused"
            @click="stopTimer"
            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center"
          >
            <StopIcon class="h-4 w-4 mr-2" />
            Stop
          </button>
        </div>
      </div>

      <!-- Task Selection -->
      <div v-if="!isTracking" class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Task</label>
        <div class="space-y-2">
          <select
            v-model="selectedProjectId"
            @change="fetchProjectTasks"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">Select Project</option>
            <option v-for="project in projects" :key="project.id" :value="project.id">
              {{ project.name }}
            </option>
          </select>
          
          <select
            v-model="selectedTask"
            :disabled="!selectedProjectId"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">Select Task</option>
            <option v-for="task in availableTasks" :key="task.id" :value="task">
              {{ task.title }}
            </option>
          </select>
        </div>
      </div>

      <!-- Description -->
      <div v-if="isTracking || isPaused" class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
        <textarea
          v-model="timeEntryDescription"
          rows="2"
          placeholder="What are you working on?"
          class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
        ></textarea>
      </div>

      <!-- Today's Time Summary -->
      <div class="border-t border-gray-200 pt-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Today's Time</h4>
        <div class="space-y-2">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Total Time</span>
            <span class="font-medium">{{ formatTime(todayStats.total_time) }}</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Billable Time</span>
            <span class="font-medium text-green-600">{{ formatTime(todayStats.billable_time) }}</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Tasks Worked</span>
            <span class="font-medium">{{ todayStats.tasks_count }}</span>
          </div>
        </div>
      </div>

      <!-- Recent Entries -->
      <div v-if="recentEntries.length > 0" class="border-t border-gray-200 pt-4 mt-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Entries</h4>
        <div class="space-y-2 max-h-32 overflow-y-auto">
          <div
            v-for="entry in recentEntries"
            :key="entry.id"
            class="flex items-center justify-between p-2 bg-gray-50 rounded text-xs"
          >
            <div class="flex-1 min-w-0">
              <div class="font-medium text-gray-900 truncate">{{ entry.task?.title }}</div>
              <div class="text-gray-500 truncate">{{ entry.description || 'No description' }}</div>
            </div>
            <div class="flex items-center space-x-2 ml-2">
              <span class="font-medium">{{ formatTime(entry.duration) }}</span>
              <button
                @click="editTimeEntry(entry)"
                class="text-gray-400 hover:text-gray-600"
              >
                <PencilIcon class="h-3 w-3" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Manual Time Entry Modal -->
    <ManualTimeEntryModal
      v-if="showManualEntryModal"
      :projects="projects"
      @close="showManualEntryModal = false"
      @saved="handleManualEntrySaved"
    />

    <!-- Edit Time Entry Modal -->
    <EditTimeEntryModal
      v-if="editingEntry"
      :entry="editingEntry"
      @close="editingEntry = null"
      @saved="handleTimeEntrySaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  PlayIcon,
  PauseIcon,
  StopIcon,
  PencilIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'
import ManualTimeEntryModal from './ManualTimeEntryModal.vue'
import EditTimeEntryModal from './EditTimeEntryModal.vue'

const props = defineProps({
  taskId: {
    type: [String, Number],
    default: null
  },
  projectId: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['time-logged', 'timer-started', 'timer-stopped'])

const { get, post, put } = useApi()
const { showToast } = useToast()

// Reactive data
const collapsed = ref(false)
const isTracking = ref(false)
const isPaused = ref(false)
const currentTime = ref(0)
const startTime = ref(null)
const pausedTime = ref(0)
const timerInterval = ref(null)

const projects = ref([])
const availableTasks = ref([])
const selectedProjectId = ref(props.projectId)
const selectedTask = ref(null)
const currentTask = ref(null)
const timeEntryDescription = ref('')

const recentEntries = ref([])
const todayStats = ref({
  total_time: 0,
  billable_time: 0,
  tasks_count: 0
})

const showManualEntryModal = ref(false)
const editingEntry = ref(null)

// Computed properties
const formattedCurrentTime = computed(() => {
  return formatTime(currentTime.value)
})

// Methods
const fetchProjects = async () => {
  try {
    const data = await get('/api/v1/projects', { with_tasks: true })
    projects.value = data.data
    
    if (props.projectId) {
      selectedProjectId.value = props.projectId
      await fetchProjectTasks()
    }
  } catch (err) {
    console.error('Failed to fetch projects:', err)
  }
}

const fetchProjectTasks = async () => {
  if (!selectedProjectId.value) {
    availableTasks.value = []
    return
  }
  
  try {
    const data = await get(`/api/v1/projects/${selectedProjectId.value}/tasks`)
    availableTasks.value = data.data
    
    if (props.taskId) {
      selectedTask.value = availableTasks.value.find(t => t.id == props.taskId)
    }
  } catch (err) {
    console.error('Failed to fetch tasks:', err)
  }
}

const fetchTodayStats = async () => {
  try {
    const data = await get('/api/v1/time-tracking/today-stats')
    todayStats.value = data.data
  } catch (err) {
    console.error('Failed to fetch today stats:', err)
  }
}

const fetchRecentEntries = async () => {
  try {
    const data = await get('/api/v1/time-tracking/recent', { limit: 5 })
    recentEntries.value = data.data
  } catch (err) {
    console.error('Failed to fetch recent entries:', err)
  }
}

const startTimer = async () => {
  if (!selectedTask.value) {
    showToast('Please select a task first', 'error')
    return
  }
  
  try {
    const data = await post('/api/v1/time-tracking/start', {
      task_id: selectedTask.value.id,
      description: timeEntryDescription.value
    })
    
    isTracking.value = true
    isPaused.value = false
    currentTask.value = selectedTask.value
    startTime.value = new Date()
    currentTime.value = 0
    pausedTime.value = 0
    
    // Start the timer interval
    timerInterval.value = setInterval(() => {
      if (isTracking.value && !isPaused.value) {
        currentTime.value = Math.floor((new Date() - startTime.value) / 1000) + pausedTime.value
      }
    }, 1000)
    
    showToast('Timer started', 'success')
    emit('timer-started', { task: selectedTask.value, entry: data.data })
  } catch (err) {
    console.error('Failed to start timer:', err)
    showToast('Failed to start timer', 'error')
  }
}

const pauseTimer = async () => {
  try {
    await post('/api/v1/time-tracking/pause')
    
    isPaused.value = true
    pausedTime.value = currentTime.value
    
    showToast('Timer paused', 'info')
  } catch (err) {
    console.error('Failed to pause timer:', err)
    showToast('Failed to pause timer', 'error')
  }
}

const stopTimer = async () => {
  try {
    const data = await post('/api/v1/time-tracking/stop', {
      description: timeEntryDescription.value
    })
    
    // Clear timer
    if (timerInterval.value) {
      clearInterval(timerInterval.value)
      timerInterval.value = null
    }
    
    isTracking.value = false
    isPaused.value = false
    currentTime.value = 0
    pausedTime.value = 0
    currentTask.value = null
    timeEntryDescription.value = ''
    selectedTask.value = null
    
    // Refresh data
    await Promise.all([
      fetchTodayStats(),
      fetchRecentEntries()
    ])
    
    showToast(`Time logged: ${formatTime(data.data.duration)}`, 'success')
    emit('timer-stopped', data.data)
    emit('time-logged', data.data)
  } catch (err) {
    console.error('Failed to stop timer:', err)
    showToast('Failed to stop timer', 'error')
  }
}

const editTimeEntry = (entry) => {
  editingEntry.value = entry
}

const handleManualEntrySaved = () => {
  showManualEntryModal.value = false
  fetchTodayStats()
  fetchRecentEntries()
  showToast('Time entry saved', 'success')
}

const handleTimeEntrySaved = () => {
  editingEntry.value = null
  fetchTodayStats()
  fetchRecentEntries()
  showToast('Time entry updated', 'success')
}

// Utility functions
const formatTime = (seconds) => {
  if (!seconds) return '00:00:00'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchProjects(),
    fetchTodayStats(),
    fetchRecentEntries()
  ])
  
  // Check if there's an active timer
  try {
    const data = await get('/api/v1/time-tracking/active')
    if (data.data) {
      isTracking.value = true
      isPaused.value = data.data.is_paused
      currentTask.value = data.data.task
      timeEntryDescription.value = data.data.description || ''
      
      // Calculate current time
      const startedAt = new Date(data.data.started_at)
      const pausedDuration = data.data.paused_duration || 0
      currentTime.value = Math.floor((new Date() - startedAt) / 1000) - pausedDuration
      
      if (!isPaused.value) {
        // Start the timer interval
        timerInterval.value = setInterval(() => {
          if (isTracking.value && !isPaused.value) {
            currentTime.value = Math.floor((new Date() - startedAt) / 1000) - pausedDuration
          }
        }, 1000)
      }
    }
  } catch (err) {
    console.error('Failed to check active timer:', err)
  }
})

onUnmounted(() => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
  }
})
</script>