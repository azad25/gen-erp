<template>
  <div class="bg-white overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200 rounded-lg border border-gray-200">
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 rounded-full" :class="getStatusColor(project.status)"></div>
          <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">
            {{ formatStatus(project.status) }}
          </span>
        </div>
        <div class="flex items-center space-x-1">
          <div class="w-2 h-2 rounded-full" :class="getPriorityColor(project.priority)"></div>
          <span class="text-xs text-gray-500 capitalize">{{ project.priority }}</span>
        </div>
      </div>

      <!-- Project Title and Description -->
      <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-1">
          <Link :href="route('projects.show', project.id)" class="hover:text-indigo-600 transition-colors">
            {{ project.name }}
          </Link>
        </h3>
        <p class="text-sm text-gray-600 line-clamp-2">{{ project.description || 'No description provided' }}</p>
      </div>

      <!-- Progress Bar -->
      <div class="mb-4">
        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
          <span>Progress</span>
          <span class="font-medium">{{ project.progress_percentage || 0 }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div 
            class="bg-indigo-600 h-2 rounded-full transition-all duration-300" 
            :style="`width: ${project.progress_percentage || 0}%`"
          ></div>
        </div>
      </div>

      <!-- Project Details -->
      <div class="space-y-2 mb-4">
        <!-- Client -->
        <div v-if="project.client_name" class="flex items-center text-sm text-gray-600">
          <BuildingOfficeIcon class="h-4 w-4 mr-2 text-gray-400" />
          <span>{{ project.client_name }}</span>
        </div>
        
        <!-- Due Date -->
        <div v-if="project.end_date" class="flex items-center text-sm">
          <CalendarIcon class="h-4 w-4 mr-2 text-gray-400" />
          <span :class="{ 'text-red-600 font-medium': isOverdue(project), 'text-gray-600': !isOverdue(project) }">
            Due {{ formatDate(project.end_date) }}
          </span>
        </div>

        <!-- Budget -->
        <div v-if="project.budget" class="flex items-center text-sm text-gray-600">
          <CurrencyDollarIcon class="h-4 w-4 mr-2 text-gray-400" />
          <span>${{ formatCurrency(project.budget) }}</span>
        </div>

        <!-- Team Size -->
        <div v-if="project.team_members_count" class="flex items-center text-sm text-gray-600">
          <UsersIcon class="h-4 w-4 mr-2 text-gray-400" />
          <span>{{ project.team_members_count }} member{{ project.team_members_count !== 1 ? 's' : '' }}</span>
        </div>
      </div>

      <!-- Project Manager -->
      <div v-if="project.project_manager" class="flex items-center mb-4">
        <div class="flex-shrink-0 h-8 w-8">
          <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
            <span class="text-xs font-medium text-gray-700">
              {{ getInitials(project.project_manager.first_name, project.project_manager.last_name) }}
            </span>
          </div>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-gray-900">
            {{ project.project_manager.first_name }} {{ project.project_manager.last_name }}
          </p>
          <p class="text-xs text-gray-500">Project Manager</p>
        </div>
      </div>

      <!-- Task Summary -->
      <div v-if="project.tasks_summary" class="grid grid-cols-3 gap-2 mb-4 p-3 bg-gray-50 rounded-lg">
        <div class="text-center">
          <div class="text-lg font-semibold text-gray-900">{{ project.tasks_summary.total || 0 }}</div>
          <div class="text-xs text-gray-500">Total</div>
        </div>
        <div class="text-center">
          <div class="text-lg font-semibold text-green-600">{{ project.tasks_summary.completed || 0 }}</div>
          <div class="text-xs text-gray-500">Done</div>
        </div>
        <div class="text-center">
          <div class="text-lg font-semibold text-yellow-600">{{ project.tasks_summary.in_progress || 0 }}</div>
          <div class="text-xs text-gray-500">Active</div>
        </div>
      </div>

      <!-- Tags -->
      <div v-if="project.tags && project.tags.length > 0" class="mb-4">
        <div class="flex flex-wrap gap-1">
          <span
            v-for="tag in project.tags.slice(0, 3)"
            :key="tag"
            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
          >
            {{ tag }}
          </span>
          <span
            v-if="project.tags.length > 3"
            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600"
          >
            +{{ project.tags.length - 3 }}
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-4 border-t border-gray-100">
        <div class="flex items-center space-x-2">
          <Link 
            :href="route('projects.show', project.id)" 
            class="text-indigo-600 hover:text-indigo-500 text-sm font-medium transition-colors"
          >
            View
          </Link>
          <Link 
            :href="route('projects.edit', project.id)" 
            class="text-gray-600 hover:text-gray-500 text-sm font-medium transition-colors"
          >
            Edit
          </Link>
          <Link 
            :href="route('projects.board', project.id)" 
            class="text-green-600 hover:text-green-500 text-sm font-medium transition-colors"
          >
            Board
          </Link>
        </div>
        
        <!-- Quick Actions -->
        <div class="flex items-center space-x-1">
          <button
            @click="toggleFavorite"
            :class="[
              'p-1 rounded-full transition-colors',
              project.is_favorite 
                ? 'text-yellow-500 hover:text-yellow-600' 
                : 'text-gray-400 hover:text-gray-500'
            ]"
            :title="project.is_favorite ? 'Remove from favorites' : 'Add to favorites'"
          >
            <StarIcon class="h-4 w-4" :class="{ 'fill-current': project.is_favorite }" />
          </button>
          
          <div class="relative" ref="dropdownRef">
            <button
              @click="showDropdown = !showDropdown"
              class="p-1 rounded-full text-gray-400 hover:text-gray-500 transition-colors"
            >
              <EllipsisVerticalIcon class="h-4 w-4" />
            </button>
            
            <!-- Dropdown Menu -->
            <div
              v-if="showDropdown"
              class="absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200"
            >
              <div class="py-1">
                <button
                  @click="duplicateProject"
                  class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                  Duplicate Project
                </button>
                <button
                  @click="archiveProject"
                  class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                  {{ project.status === 'archived' ? 'Unarchive' : 'Archive' }}
                </button>
                <button
                  @click="exportProject"
                  class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                  Export
                </button>
                <div class="border-t border-gray-100"></div>
                <button
                  @click="deleteProject"
                  class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                >
                  Delete Project
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
import { Link } from '@inertiajs/vue3'
import {
  BuildingOfficeIcon,
  CalendarIcon,
  CurrencyDollarIcon,
  UsersIcon,
  StarIcon,
  EllipsisVerticalIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  project: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['updated', 'deleted'])

const { post, put, delete: del } = useApi()
const { showToast } = useToast()

const showDropdown = ref(false)
const dropdownRef = ref(null)

// Utility functions
const getStatusColor = (status) => {
  const colors = {
    planning: 'bg-gray-400',
    active: 'bg-green-400',
    on_hold: 'bg-yellow-400',
    completed: 'bg-blue-400',
    cancelled: 'bg-red-400',
    archived: 'bg-gray-300'
  }
  return colors[status] || 'bg-gray-400'
}

const getPriorityColor = (priority) => {
  const colors = {
    low: 'bg-green-400',
    medium: 'bg-yellow-400',
    high: 'bg-orange-400',
    urgent: 'bg-red-400'
  }
  return colors[priority] || 'bg-gray-400'
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (dateString) => {
  if (!dateString) return 'No due date'
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = date - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) {
    return `${Math.abs(diffDays)} days overdue`
  } else if (diffDays === 0) {
    return 'Due today'
  } else if (diffDays === 1) {
    return 'Due tomorrow'
  } else if (diffDays <= 7) {
    return `Due in ${diffDays} days`
  } else {
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  }
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US').format(amount)
}

const isOverdue = (project) => {
  if (!project.end_date) return false
  return new Date(project.end_date) < new Date() && 
         !['completed', 'cancelled', 'archived'].includes(project.status)
}

const getInitials = (firstName, lastName) => {
  return `${firstName?.[0] || ''}${lastName?.[0] || ''}`.toUpperCase()
}

// Actions
const toggleFavorite = async () => {
  try {
    await post(`/api/v1/projects/${props.project.id}/toggle-favorite`)
    emit('updated', { ...props.project, is_favorite: !props.project.is_favorite })
    showToast(
      props.project.is_favorite ? 'Removed from favorites' : 'Added to favorites',
      'success'
    )
  } catch (err) {
    console.error('Failed to toggle favorite:', err)
    showToast('Failed to update favorite status', 'error')
  }
}

const duplicateProject = async () => {
  try {
    await post(`/api/v1/projects/${props.project.id}/duplicate`)
    showToast('Project duplicated successfully', 'success')
    emit('updated')
  } catch (err) {
    console.error('Failed to duplicate project:', err)
    showToast('Failed to duplicate project', 'error')
  } finally {
    showDropdown.value = false
  }
}

const archiveProject = async () => {
  try {
    const action = props.project.status === 'archived' ? 'unarchive' : 'archive'
    await post(`/api/v1/projects/${props.project.id}/${action}`)
    showToast(`Project ${action}d successfully`, 'success')
    emit('updated')
  } catch (err) {
    console.error('Failed to archive/unarchive project:', err)
    showToast('Failed to update project status', 'error')
  } finally {
    showDropdown.value = false
  }
}

const exportProject = () => {
  // TODO: Implement project export
  showToast('Export functionality coming soon', 'info')
  showDropdown.value = false
}

const deleteProject = async () => {
  if (!confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
    showDropdown.value = false
    return
  }
  
  try {
    await del(`/api/v1/projects/${props.project.id}`)
    showToast('Project deleted successfully', 'success')
    emit('deleted', props.project.id)
  } catch (err) {
    console.error('Failed to delete project:', err)
    showToast('Failed to delete project', 'error')
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
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>