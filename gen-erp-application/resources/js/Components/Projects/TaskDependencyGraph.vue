<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">Task Dependencies</h3>
        <div class="flex items-center space-x-2">
          <button
            @click="showAddDependencyModal = true"
            class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-2 py-1 rounded-md"
          >
            Add Dependency
          </button>
          <button
            @click="toggleView"
            class="text-xs text-gray-500 hover:text-gray-700"
          >
            {{ viewMode === 'graph' ? 'List View' : 'Graph View' }}
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

    <!-- Content -->
    <div v-if="!collapsed" class="p-4">
      <!-- Graph View -->
      <div v-if="viewMode === 'graph'" class="relative">
        <div
          ref="graphContainer"
          class="w-full h-96 border border-gray-200 rounded-lg bg-gray-50 overflow-hidden"
        >
          <svg
            ref="svgElement"
            class="w-full h-full"
            @mousedown="startPan"
            @mousemove="handlePan"
            @mouseup="endPan"
            @wheel="handleZoom"
          >
            <!-- Grid Pattern -->
            <defs>
              <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                <path d="M 20 0 L 0 0 0 20" fill="none" stroke="#e5e7eb" stroke-width="1"/>
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />
            
            <!-- Dependency Lines -->
            <g :transform="`translate(${panX}, ${panY}) scale(${zoom})`">
              <line
                v-for="dependency in dependencies"
                :key="`${dependency.predecessor_id}-${dependency.successor_id}`"
                :x1="getTaskPosition(dependency.predecessor_id).x + 100"
                :y1="getTaskPosition(dependency.predecessor_id).y + 25"
                :x2="getTaskPosition(dependency.successor_id).x"
                :y2="getTaskPosition(dependency.successor_id).y + 25"
                stroke="#6366f1"
                stroke-width="2"
                marker-end="url(#arrowhead)"
                :class="getDependencyLineClass(dependency)"
              />
              
              <!-- Arrow marker -->
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
              
              <!-- Task Nodes -->
              <g
                v-for="task in tasks"
                :key="task.id"
                :transform="`translate(${getTaskPosition(task.id).x}, ${getTaskPosition(task.id).y})`"
                class="cursor-pointer"
                @click="selectTask(task)"
                @mousedown="startDrag(task, $event)"
              >
                <rect
                  width="200"
                  height="50"
                  rx="8"
                  :fill="getTaskColor(task)"
                  :stroke="selectedTask?.id === task.id ? '#6366f1' : '#d1d5db'"
                  stroke-width="2"
                  class="drop-shadow-sm"
                />
                <text
                  x="10"
                  y="20"
                  class="text-sm font-medium fill-gray-900"
                  text-anchor="start"
                >
                  {{ truncateText(task.title, 25) }}
                </text>
                <text
                  x="10"
                  y="35"
                  class="text-xs fill-gray-600"
                  text-anchor="start"
                >
                  {{ task.status }} • {{ task.priority }}
                </text>
                
                <!-- Connection Points -->
                <circle cx="0" cy="25" r="4" fill="#6366f1" class="opacity-50" />
                <circle cx="200" cy="25" r="4" fill="#6366f1" class="opacity-50" />
              </g>
            </g>
          </svg>
        </div>
        
        <!-- Graph Controls -->
        <div class="absolute top-2 right-2 flex flex-col space-y-1">
          <button
            @click="zoomIn"
            class="bg-white border border-gray-300 rounded p-1 hover:bg-gray-50"
          >
            <PlusIcon class="h-4 w-4" />
          </button>
          <button
            @click="zoomOut"
            class="bg-white border border-gray-300 rounded p-1 hover:bg-gray-50"
          >
            <MinusIcon class="h-4 w-4" />
          </button>
          <button
            @click="resetView"
            class="bg-white border border-gray-300 rounded p-1 hover:bg-gray-50"
          >
            <ArrowsPointingOutIcon class="h-4 w-4" />
          </button>
        </div>
      </div>

      <!-- List View -->
      <div v-else class="space-y-4">
        <!-- Dependencies List -->
        <div v-if="dependencies.length > 0">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Current Dependencies</h4>
          <div class="space-y-2">
            <div
              v-for="dependency in dependencies"
              :key="`${dependency.predecessor_id}-${dependency.successor_id}`"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
            >
              <div class="flex items-center space-x-3">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                  <span class="text-sm font-medium text-gray-900">
                    {{ getTaskById(dependency.predecessor_id)?.title }}
                  </span>
                </div>
                <ArrowRightIcon class="h-4 w-4 text-gray-400" />
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                  <span class="text-sm font-medium text-gray-900">
                    {{ getTaskById(dependency.successor_id)?.title }}
                  </span>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <span
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                  :class="getDependencyTypeClass(dependency.type)"
                >
                  {{ dependency.type }}
                </span>
                <button
                  @click="removeDependency(dependency)"
                  class="text-gray-400 hover:text-red-600"
                >
                  <XMarkIcon class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Critical Path -->
        <div v-if="criticalPath.length > 0">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Critical Path</h4>
          <div class="flex items-center space-x-2 p-3 bg-red-50 rounded-lg">
            <ExclamationTriangleIcon class="h-5 w-5 text-red-500" />
            <div class="flex-1">
              <div class="flex items-center space-x-2">
                <span
                  v-for="(task, index) in criticalPath"
                  :key="task.id"
                  class="flex items-center space-x-1"
                >
                  <span class="text-sm font-medium text-red-800">{{ task.title }}</span>
                  <ArrowRightIcon
                    v-if="index < criticalPath.length - 1"
                    class="h-3 w-3 text-red-500"
                  />
                </span>
              </div>
              <p class="text-xs text-red-600 mt-1">
                Total duration: {{ calculateCriticalPathDuration() }} days
              </p>
            </div>
          </div>
        </div>

        <!-- Task Details -->
        <div v-if="selectedTask">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Task Details</h4>
          <div class="p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
              <h5 class="font-medium text-blue-900">{{ selectedTask.title }}</h5>
              <button
                @click="selectedTask = null"
                class="text-blue-400 hover:text-blue-600"
              >
                <XMarkIcon class="h-4 w-4" />
              </button>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-blue-700">Status:</span>
                <span class="ml-1 text-blue-900">{{ selectedTask.status }}</span>
              </div>
              <div>
                <span class="text-blue-700">Priority:</span>
                <span class="ml-1 text-blue-900">{{ selectedTask.priority }}</span>
              </div>
              <div>
                <span class="text-blue-700">Dependencies:</span>
                <span class="ml-1 text-blue-900">{{ getTaskDependencies(selectedTask.id).length }}</span>
              </div>
              <div>
                <span class="text-blue-700">Dependents:</span>
                <span class="ml-1 text-blue-900">{{ getTaskDependents(selectedTask.id).length }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="dependencies.length === 0" class="text-center py-8">
        <ShareIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No dependencies</h3>
        <p class="mt-1 text-sm text-gray-500">Create task dependencies to visualize project flow.</p>
        <div class="mt-6">
          <button
            @click="showAddDependencyModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
          >
            <ShareIcon class="h-4 w-4 mr-2" />
            Add Dependency
          </button>
        </div>
      </div>
    </div>

    <!-- Add Dependency Modal -->
    <AddDependencyModal
      v-if="showAddDependencyModal"
      :project-id="projectId"
      :tasks="tasks"
      @close="showAddDependencyModal = false"
      @saved="handleDependencyAdded"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  PlusIcon,
  MinusIcon,
  ArrowsPointingOutIcon,
  ArrowRightIcon,
  XMarkIcon,
  ExclamationTriangleIcon,
  ShareIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'
import AddDependencyModal from './AddDependencyModal.vue'

const props = defineProps({
  projectId: {
    type: [String, Number],
    required: true
  },
  taskId: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['dependency-added', 'dependency-removed'])

const { get, delete: del } = useApi()
const { showSuccess, showError } = useToast()

// Reactive data
const collapsed = ref(false)
const viewMode = ref('graph')
const tasks = ref([])
const dependencies = ref([])
const selectedTask = ref(null)
const showAddDependencyModal = ref(false)

// Graph state
const panX = ref(0)
const panY = ref(0)
const zoom = ref(1)
const isPanning = ref(false)
const isDragging = ref(false)
const dragTask = ref(null)
const lastMousePos = ref({ x: 0, y: 0 })
const taskPositions = ref(new Map())

// Computed properties
const criticalPath = computed(() => {
  return calculateCriticalPath()
})

// Methods
const fetchData = async () => {
  try {
    const [tasksData, dependenciesData] = await Promise.all([
      get(`/api/v1/projects/${props.projectId}/tasks`),
      get(`/api/v1/projects/${props.projectId}/dependencies`)
    ])
    
    tasks.value = tasksData.data
    dependencies.value = dependenciesData.data
    
    // Initialize task positions
    initializeTaskPositions()
  } catch (err) {
    console.error('Failed to fetch dependency data:', err)
  }
}

const initializeTaskPositions = () => {
  const positions = new Map()
  const cols = Math.ceil(Math.sqrt(tasks.value.length))
  
  tasks.value.forEach((task, index) => {
    const row = Math.floor(index / cols)
    const col = index % cols
    positions.set(task.id, {
      x: col * 250 + 50,
      y: row * 100 + 50
    })
  })
  
  taskPositions.value = positions
}

const getTaskPosition = (taskId) => {
  return taskPositions.value.get(taskId) || { x: 0, y: 0 }
}

const getTaskById = (taskId) => {
  return tasks.value.find(task => task.id === taskId)
}

const getTaskColor = (task) => {
  const colors = {
    todo: '#f3f4f6',
    in_progress: '#dbeafe',
    review: '#fef3c7',
    completed: '#d1fae5'
  }
  return colors[task.status] || '#f3f4f6'
}

const getDependencyLineClass = (dependency) => {
  return {
    'stroke-red-500': dependency.type === 'critical',
    'stroke-yellow-500': dependency.type === 'warning',
    'stroke-gray-400': dependency.type === 'normal'
  }
}

const getDependencyTypeClass = (type) => {
  const classes = {
    'finish-to-start': 'bg-blue-100 text-blue-800',
    'start-to-start': 'bg-green-100 text-green-800',
    'finish-to-finish': 'bg-yellow-100 text-yellow-800',
    'start-to-finish': 'bg-purple-100 text-purple-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getTaskDependencies = (taskId) => {
  return dependencies.value.filter(dep => dep.successor_id === taskId)
}

const getTaskDependents = (taskId) => {
  return dependencies.value.filter(dep => dep.predecessor_id === taskId)
}

const calculateCriticalPath = () => {
  // Simplified critical path calculation
  // In a real implementation, this would use proper CPM algorithm
  const visited = new Set()
  const path = []
  
  const findLongestPath = (taskId, currentPath) => {
    if (visited.has(taskId)) return currentPath
    
    visited.add(taskId)
    const task = getTaskById(taskId)
    if (!task) return currentPath
    
    const newPath = [...currentPath, task]
    const dependents = getTaskDependents(taskId)
    
    if (dependents.length === 0) {
      return newPath
    }
    
    let longestPath = newPath
    for (const dep of dependents) {
      const path = findLongestPath(dep.successor_id, newPath)
      if (path.length > longestPath.length) {
        longestPath = path
      }
    }
    
    return longestPath
  }
  
  // Find starting tasks (no dependencies)
  const startingTasks = tasks.value.filter(task => 
    getTaskDependencies(task.id).length === 0
  )
  
  let criticalPath = []
  for (const task of startingTasks) {
    const path = findLongestPath(task.id, [])
    if (path.length > criticalPath.length) {
      criticalPath = path
    }
  }
  
  return criticalPath
}

const calculateCriticalPathDuration = () => {
  return criticalPath.value.reduce((total, task) => {
    return total + (task.estimated_hours || 8) / 8 // Convert hours to days
  }, 0)
}

const toggleView = () => {
  viewMode.value = viewMode.value === 'graph' ? 'list' : 'graph'
}

const selectTask = (task) => {
  selectedTask.value = selectedTask.value?.id === task.id ? null : task
}

const removeDependency = async (dependency) => {
  if (!confirm('Are you sure you want to remove this dependency?')) return
  
  try {
    await del(`/api/v1/dependencies/${dependency.id}`)
    
    const index = dependencies.value.findIndex(d => d.id === dependency.id)
    if (index > -1) {
      dependencies.value.splice(index, 1)
    }
    
    showSuccess('Dependency removed successfully')
    emit('dependency-removed', dependency)
  } catch (err) {
    console.error('Failed to remove dependency:', err)
    showError('Failed to remove dependency')
  }
}

const handleDependencyAdded = (dependency) => {
  dependencies.value.push(dependency)
  showAddDependencyModal.value = false
  showSuccess('Dependency added successfully')
  emit('dependency-added', dependency)
}

// Graph interaction methods
const startPan = (event) => {
  if (event.target.tagName === 'svg') {
    isPanning.value = true
    lastMousePos.value = { x: event.clientX, y: event.clientY }
  }
}

const handlePan = (event) => {
  if (isPanning.value) {
    const deltaX = event.clientX - lastMousePos.value.x
    const deltaY = event.clientY - lastMousePos.value.y
    
    panX.value += deltaX
    panY.value += deltaY
    
    lastMousePos.value = { x: event.clientX, y: event.clientY }
  }
  
  if (isDragging.value && dragTask.value) {
    const rect = event.currentTarget.getBoundingClientRect()
    const x = (event.clientX - rect.left - panX.value) / zoom.value
    const y = (event.clientY - rect.top - panY.value) / zoom.value
    
    taskPositions.value.set(dragTask.value.id, { x, y })
  }
}

const endPan = () => {
  isPanning.value = false
  isDragging.value = false
  dragTask.value = null
}

const startDrag = (task, event) => {
  event.stopPropagation()
  isDragging.value = true
  dragTask.value = task
}

const handleZoom = (event) => {
  event.preventDefault()
  const delta = event.deltaY > 0 ? 0.9 : 1.1
  zoom.value = Math.max(0.1, Math.min(3, zoom.value * delta))
}

const zoomIn = () => {
  zoom.value = Math.min(3, zoom.value * 1.2)
}

const zoomOut = () => {
  zoom.value = Math.max(0.1, zoom.value * 0.8)
}

const resetView = () => {
  panX.value = 0
  panY.value = 0
  zoom.value = 1
}

const truncateText = (text, maxLength) => {
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
}

// Lifecycle
onMounted(() => {
  fetchData()
})
</script>