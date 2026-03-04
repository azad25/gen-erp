<template>
  <div 
    class="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer"
    :class="{ 'border-l-4': true, [getPriorityBorderColor(task.priority)]: true }"
    @click="$emit('click', task)"
  >
    <div class="p-4">
      <!-- Header -->
      <div class="flex items-start justify-between mb-3">
        <div class="flex-1">
          <h4 class="text-sm font-medium text-gray-900 line-clamp-2 mb-1">
            {{ task.title }}
          </h4>
          <p v-if="task.description" class="text-xs text-gray-600 line-clamp-2">
            {{ task.description }}
          </p>
        </div>
        
        <!-- Task ID -->
        <div class="ml-2 flex-shrink-0">
          <span class="text-xs text-gray-400 font-mono">
            #{{ task.id }}
          </span>
        </div>
      </div>

      <!-- Status and Priority -->
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center space-x-2">
          <span :class="getStatusBadgeClass(task.status)" class="px-2 py-1 text-xs font-medium rounded-full">
            {{ formatStatus(task.status) }}
          </span>
          <span :class="getPriorityBadgeClass(task.priority)" class="px-2 py-1 text-xs font-medium rounded-full">
            {{ task.priority }}
          </span>
        </div>
      </div>

      <!-- Progress Bar (if task has subtasks or progress) -->
      <div v-if="task.progress_percentage !== undefined" class="mb-3">
        <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
          <span>Progress</span>
          <span>{{ task.progress_percentage }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-1.5">
          <div 
            class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" 
            :style="`width: ${task.progress_percentage}%`"
          ></div>
        </div>
      </div>

      <!-- Assignee -->
      <div v-if="task.assignee" class="flex items-center mb-3">
        <div class="flex-shrink-0 h-6 w-6">
          <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center">
            <span class="text-xs font-medium text-gray-700">
              {{ getInitials(task.assignee.first_name, task.assignee.last_name) }}
            </span>
          </div>
        </div>
        <div class="ml-2">
          <p class="text-xs text-gray-600">
            {{ task.assignee.first_name }} {{ task.assignee.last_name }}
          </p>
        </div>
      </div>

      <!-- Due Date -->
      <div v-if="task.due_date" class="flex items-center mb-3">
        <CalendarIcon class="h-4 w-4 text-gray-400 mr-2" />
        <span 
          class="text-xs"
          :class="{ 
            'text-red-600 font-medium': isOverdue(task), 
            'text-yellow-600 font-medium': isDueSoon(task),
            'text-gray-600': !isOverdue(task) && !isDueSoon(task)
          }"
        >
          {{ formatDueDate(task.due_date) }}
        </span>
      </div>

      <!-- Tags -->
      <div v-if="task.tags && task.tags.length > 0" class="mb-3">
        <div class="flex flex-wrap gap-1">
          <span
            v-for="tag in task.tags.slice(0, 2)"
            :key="tag"
            class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700"
          >
            {{ tag }}
          </span>
          <span
            v-if="task.tags.length > 2"
            class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600"
          >
            +{{ task.tags.length - 2 }}
          </span>
        </div>
      </div>

      <!-- Task Metadata -->
      <div class="flex items-center justify-between text-xs text-gray-500">
        <div class="flex items-center space-x-3">
          <!-- Comments Count -->
          <div v-if="task.comments_count" class="flex items-center">
            <ChatBubbleLeftIcon class="h-3 w-3 mr-1" />
            <span>{{ task.comments_count }}</span>
          </div>
          
          <!-- Attachments Count -->
          <div v-if="task.attachments_count" class="flex items-center">
            <PaperClipIcon class="h-3 w-3 mr-1" />
            <span>{{ task.attachments_count }}</span>
          </div>
          
          <!-- Subtasks Count -->
          <div v-if="task.subtasks_count" class="flex items-center">
            <ListBulletIcon class="h-3 w-3 mr-1" />
            <span>{{ task.completed_subtasks_count || 0 }}/{{ task.subtasks_count }}</span>
          </div>
          
          <!-- Time Tracked -->
          <div v-if="task.time_tracked" class="flex items-center">
            <ClockIcon class="h-3 w-3 mr-1" />
            <span>{{ formatTimeTracked(task.time_tracked) }}</span>
          </div>
        </div>
        
        <!-- Created Date -->
        <div>
          {{ formatRelativeTime(task.created_at) }}
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
        <div class="flex items-center space-x-2">
          <button
            @click.stop="toggleTaskStatus"
            :class="[
              'p-1 rounded-full transition-colors',
              task.status === 'completed' 
                ? 'text-green-600 hover:text-green-700' 
                : 'text-gray-400 hover:text-gray-500'
            ]"
            :title="task.status === 'completed' ? 'Mark as incomplete' : 'Mark as complete'"
          >
            <CheckCircleIcon class="h-4 w-4" :class="{ 'fill-current': task.status === 'completed' }" />
          </button>
          
          <button
            @click.stop="startTimer"
            v-if="task.status !== 'completed'"
            class="p-1 rounded-full text-gray-400 hover:text-gray-500 transition-colors"
            title="Start timer"
          >
            <PlayIcon class="h-4 w-4" />
          </button>
        </div>
        
        <div class="flex items-center space-x-1">
          <button
            @click.stop="editTask"
            class="p-1 rounded-full text-gray-400 hover:text-gray-500 transition-colors"
            title="Edit task"
          >
            <PencilIcon class="h-4 w-4" />
          </button>
          
          <div class="relative" ref="dropdownRef">
            <button
              @click.stop="showDropdown = !showDropdown"
              class="p-1 rounded-full text-gray-400 hover:text-gray-500 transition-colors"
              title="More options"
            >
              <EllipsisVerticalIcon class="h-4 w-4" />
            </button>
            
            <!-- Dropdown Menu -->
            <div
              v-if="showDropdown"
              class="absolute right-0 bottom-full mb-1 w-40 bg-white rounded-md shadow-lg z-10 border border-gray-200"
            >
              <div class="py-1">
                <button
                  @click.stop="duplicateTask"
                  class="block w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-100"
                >
                  Duplicate
                </button>
                <button
                  @click.stop="moveTask"
                  class="block w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-100"
                >
                  Move to...
                </button>
                <button
                  @click.stop="convertToSubtask"
                  class="block w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-100"
                >
                  Convert to Subtask
                </button>
                <div class="border-t border-gray-100"></div>
                <button
                  @click.stop="deleteTask"
                  class="block w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import {
  CalendarIcon,
  ChatBubbleLeftIcon,
  PaperClipIcon,
  ListBulletIcon,
  ClockIcon,
  CheckCircleIcon,
  PlayIcon,
  PencilIcon,
  EllipsisVerticalIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  task: {
    type: Object,
    required: true
  },
  projectId: {
    type: [String, Number],
    required: false
  }
})

const emit = defineEmits(['click', 'updated', 'deleted', 'edit', 'move'])

const { post, put, delete: del } = useApi()
const { showToast } = useToast()

const showDropdown = ref(false)
const dropdownRef = ref(null)

// Utility functions
const getStatusBadgeClass = (status) => {
  const classes = {
    todo: 'bg-gray-100 text-gray-800',
    in_progress: 'bg-blue-100 text-blue-800',
    review: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    blocked: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityBadgeClass = (priority) => {
  const classes = {
    low: 'bg-green-100 text-green-800',
    medium: 'bg-yellow-100 text-yellow-800',
    high: 'bg-orange-100 text-orange-800',
    urgent: 'bg-red-100 text-red-800'
  }
  return classes[priority] || 'bg-gray-100 text-gray-800'
}

const getPriorityBorderColor = (priority) => {
  const colors = {
    low: 'border-l-green-400',
    medium: 'border-l-yellow-400',
    high: 'border-l-orange-400',
    urgent: 'border-l-red-400'
  }
  return colors[priority] || 'border-l-gray-400'
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getInitials = (firstName, lastName) => {
  return `${firstName?.[0] || ''}${lastName?.[0] || ''}`.toUpperCase()
}

const formatDueDate = (dateString) => {
  if (!dateString) return 'No due date'
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = date - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) {
    return `${Math.abs(diffDays)}d overdue`
  } else if (diffDays === 0) {
    return 'Due today'
  } else if (diffDays === 1) {
    return 'Due tomorrow'
  } else if (diffDays <= 7) {
    return `Due in ${diffDays}d`
  } else {
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  }
}

const formatRelativeTime = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = now - date
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return '1d ago'
  if (diffDays < 7) return `${diffDays}d ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`
  return `${Math.floor(diffDays / 30)}m ago`
}

const formatTimeTracked = (minutes) => {
  if (!minutes) return '0m'
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  if (hours > 0) {
    return `${hours}h ${mins}m`
  }
  return `${mins}m`
}

const isOverdue = (task) => {
  if (!task.due_date) return false
  return new Date(task.due_date) < new Date() && task.status !== 'completed'
}

const isDueSoon = (task) => {
  if (!task.due_date || task.status === 'completed') return false
  const dueDate = new Date(task.due_date)
  const now = new Date()
  const diffTime = dueDate - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays >= 0 && diffDays <= 2
}

// Actions
const toggleTaskStatus = async () => {
  try {
    const newStatus = props.task.status === 'completed' ? 'todo' : 'completed'
    await put(`/api/v1/tasks/${props.task.id}`, { status: newStatus })
    emit('updated', { ...props.task, status: newStatus })
    showToast(`Task marked as ${newStatus === 'completed' ? 'complete' : 'incomplete'}`, 'success')
  } catch (err) {
    console.error('Failed to update task status:', err)
    showToast('Failed to update task status', 'error')
  }
}

const startTimer = () => {
  // TODO: Implement time tracking
  showToast('Time tracking functionality coming soon', 'info')
}

const editTask = () => {
  emit('edit', props.task)
}

const duplicateTask = async () => {
  try {
    await post(`/api/v1/tasks/${props.task.id}/duplicate`)
    showToast('Task duplicated successfully', 'success')
    emit('updated')
  } catch (err) {
    console.error('Failed to duplicate task:', err)
    showToast('Failed to duplicate task', 'error')
  } finally {
    showDropdown.value = false
  }
}

const moveTask = () => {
  emit('move', props.task)
  showDropdown.value = false
}

const convertToSubtask = () => {
  // TODO: Implement convert to subtask
  showToast('Convert to subtask functionality coming soon', 'info')
  showDropdown.value = false
}

const deleteTask = async () => {
  if (!confirm('Are you sure you want to delete this task?')) {
    showDropdown.value = false
    return
  }
  
  try {
    await del(`/api/v1/tasks/${props.task.id}`)
    showToast('Task deleted successfully', 'success')
    emit('deleted', props.task.id)
  } catch (err) {
    console.error('Failed to delete task:', err)
    showToast('Failed to delete task', 'error')
  } finally {
    showDropdown.value = false
  }
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    showDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>