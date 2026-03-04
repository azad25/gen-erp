<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-2/3 max-w-4xl shadow-lg rounded-md bg-white">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
          <img
            class="h-12 w-12 rounded-full"
            :src="member.user?.avatar || '/default-avatar.png'"
            :alt="member.user?.name"
          />
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ member.user?.name }}</h3>
            <p class="text-sm text-gray-500">{{ member.user?.email }}</p>
          </div>
        </div>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Member Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Basic Info -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Member Information</h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Role:</span>
              <span
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                :class="getRoleClass(member.role)"
              >
                {{ member.role }}
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Status:</span>
              <span class="flex items-center space-x-1">
                <div
                  class="w-2 h-2 rounded-full"
                  :class="member.is_online ? 'bg-green-400' : 'bg-gray-400'"
                ></div>
                <span>{{ member.is_online ? 'Online' : 'Offline' }}</span>
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Joined:</span>
              <span>{{ formatDate(member.joined_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Last Activity:</span>
              <span>{{ formatDate(member.last_activity) }}</span>
            </div>
          </div>
        </div>

        <!-- Statistics -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Statistics</h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-900">{{ memberStats.total_tasks }}</div>
              <div class="text-xs text-gray-500">Total Tasks</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600">{{ memberStats.completed_tasks }}</div>
              <div class="text-xs text-gray-500">Completed</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600">{{ memberStats.active_tasks }}</div>
              <div class="text-xs text-gray-500">Active</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-900">{{ memberStats.hours_logged }}</div>
              <div class="text-xs text-gray-500">Hours</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Permissions -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Permissions</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="flex items-center space-x-2">
            <CheckCircleIcon
              v-if="member.permissions?.can_edit_tasks"
              class="h-5 w-5 text-green-500"
            />
            <XCircleIcon v-else class="h-5 w-5 text-red-500" />
            <span class="text-sm text-gray-700">Edit Tasks</span>
          </div>
          <div class="flex items-center space-x-2">
            <CheckCircleIcon
              v-if="member.permissions?.can_create_tasks"
              class="h-5 w-5 text-green-500"
            />
            <XCircleIcon v-else class="h-5 w-5 text-red-500" />
            <span class="text-sm text-gray-700">Create Tasks</span>
          </div>
          <div class="flex items-center space-x-2">
            <CheckCircleIcon
              v-if="member.permissions?.can_delete_tasks"
              class="h-5 w-5 text-green-500"
            />
            <XCircleIcon v-else class="h-5 w-5 text-red-500" />
            <span class="text-sm text-gray-700">Delete Tasks</span>
          </div>
          <div class="flex items-center space-x-2">
            <CheckCircleIcon
              v-if="member.permissions?.can_manage_members"
              class="h-5 w-5 text-green-500"
            />
            <XCircleIcon v-else class="h-5 w-5 text-red-500" />
            <span class="text-sm text-gray-700">Manage Members</span>
          </div>
        </div>
      </div>

      <!-- Recent Tasks -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Tasks</h4>
        <div v-if="recentTasks.length > 0" class="space-y-2">
          <div
            v-for="task in recentTasks"
            :key="task.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
          >
            <div class="flex items-center space-x-3">
              <div
                class="w-3 h-3 rounded-full"
                :class="getTaskStatusColor(task.status)"
              ></div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ task.title }}</p>
                <p class="text-xs text-gray-500">
                  {{ task.status }} • Updated {{ formatDate(task.updated_at) }}
                </p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                :class="getPriorityClass(task.priority)"
              >
                {{ task.priority }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-4 text-gray-500 text-sm">
          No recent tasks
        </div>
      </div>

      <!-- Activity Timeline -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Activity</h4>
        <div v-if="recentActivity.length > 0" class="space-y-3">
          <div
            v-for="activity in recentActivity"
            :key="activity.id"
            class="flex items-start space-x-3"
          >
            <div class="flex-shrink-0">
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center"
                :class="getActivityIconClass(activity.type)"
              >
                <component :is="getActivityIcon(activity.type)" class="h-4 w-4" />
              </div>
            </div>
            <div class="flex-1">
              <p class="text-sm text-gray-900">{{ activity.description }}</p>
              <p class="text-xs text-gray-500">{{ formatDate(activity.created_at) }}</p>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-4 text-gray-500 text-sm">
          No recent activity
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-3">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  XMarkIcon,
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon,
  CheckIcon,
  PencilIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'

const props = defineProps({
  member: {
    type: Object,
    required: true
  },
  projectId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const { get } = useApi()

// Reactive data
const memberStats = ref({
  total_tasks: 0,
  completed_tasks: 0,
  active_tasks: 0,
  hours_logged: 0
})
const recentTasks = ref([])
const recentActivity = ref([])

// Methods
const fetchMemberDetails = async () => {
  try {
    const data = await get(`/api/v1/projects/${props.projectId}/members/${props.member.id}/details`)
    memberStats.value = data.data.stats
    recentTasks.value = data.data.recent_tasks
    recentActivity.value = data.data.recent_activity
  } catch (err) {
    console.error('Failed to fetch member details:', err)
  }
}

const getRoleClass = (role) => {
  const classes = {
    'manager': 'bg-purple-100 text-purple-800',
    'developer': 'bg-blue-100 text-blue-800',
    'designer': 'bg-green-100 text-green-800',
    'tester': 'bg-yellow-100 text-yellow-800',
    'viewer': 'bg-gray-100 text-gray-800'
  }
  return classes[role] || 'bg-gray-100 text-gray-800'
}

const getTaskStatusColor = (status) => {
  const colors = {
    'todo': 'bg-gray-400',
    'in_progress': 'bg-blue-500',
    'review': 'bg-yellow-500',
    'completed': 'bg-green-500'
  }
  return colors[status] || 'bg-gray-400'
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

const getActivityIcon = (type) => {
  const icons = {
    'task_created': PencilIcon,
    'task_completed': CheckIcon,
    'task_updated': PencilIcon,
    'task_deleted': TrashIcon,
    'comment_added': PencilIcon
  }
  return icons[type] || ClockIcon
}

const getActivityIconClass = (type) => {
  const classes = {
    'task_created': 'bg-blue-100 text-blue-600',
    'task_completed': 'bg-green-100 text-green-600',
    'task_updated': 'bg-yellow-100 text-yellow-600',
    'task_deleted': 'bg-red-100 text-red-600',
    'comment_added': 'bg-purple-100 text-purple-600'
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

const formatDate = (dateString) => {
  if (!dateString) return 'Never'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) {
    return 'Yesterday'
  } else if (diffDays < 7) {
    return `${diffDays} days ago`
  } else {
    return date.toLocaleDateString()
  }
}

// Lifecycle
onMounted(() => {
  fetchMemberDetails()
})
</script>