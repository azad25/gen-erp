<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Activity Timeline</h1>
          <p class="mt-1 text-sm text-gray-600">
            Track and manage all your CRM activities and interactions
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="showCreateModal = true"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Schedule Activity
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CalendarIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.total_activities }}</div>
            <div class="text-sm text-gray-600">Total Activities</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ClockIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.pending_activities }}</div>
            <div class="text-sm text-gray-600">Pending</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="h-8 w-8 text-red-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.overdue_activities }}</div>
            <div class="text-sm text-gray-600">Overdue</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.completed_activities }}</div>
            <div class="text-sm text-gray-600">Completed</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Type</label>
          <select v-model="filters.type" @change="fetchActivities" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Types</option>
            <option value="call">Call</option>
            <option value="email">Email</option>
            <option value="meeting">Meeting</option>
            <option value="task">Task</option>
            <option value="note">Note</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="filters.status" @change="fetchActivities" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Statuses</option>
            <option value="scheduled">Scheduled</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Assigned To</label>
          <select v-model="filters.user_id" @change="fetchActivities" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Users</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Date Range</label>
          <select v-model="filters.date_range" @change="fetchActivities" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Time</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="overdue">Overdue</option>
          </select>
        </div>
        
        <div class="flex items-end">
          <button
            @click="clearFilters"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Clear Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Timeline View -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900">Activity Timeline</h3>
        <div class="flex items-center space-x-2">
          <button
            :class="[
              'px-3 py-1 text-sm font-medium rounded-md',
              viewMode === 'timeline' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'
            ]"
            @click="viewMode = 'timeline'"
          >
            Timeline
          </button>
          <button
            :class="[
              'px-3 py-1 text-sm font-medium rounded-md',
              viewMode === 'list' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'
            ]"
            @click="viewMode = 'list'"
          >
            List
          </button>
        </div>
      </div>

      <!-- Timeline View -->
      <div v-if="viewMode === 'timeline'" class="flow-root">
        <ul class="-mb-8">
          <li v-for="(activity, index) in activities" :key="activity.id" class="relative pb-8">
            <div v-if="index !== activities.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></div>
            
            <div class="relative flex space-x-3">
              <!-- Icon -->
              <div>
                <span :class="getActivityIconClass(activity)" class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white">
                  <component :is="getActivityIcon(activity.type)" class="h-5 w-5 text-white" />
                </span>
              </div>
              
              <!-- Content -->
              <div class="min-w-0 flex-1 pt-1.5">
                <div class="flex justify-between space-x-4">
                  <div class="flex-1">
                    <p class="text-sm text-gray-900 font-medium">
                      {{ activity.title }}
                      <span :class="getStatusBadgeClass(activity.status)" class="ml-2 px-2 py-1 text-xs font-semibold rounded-full">
                        {{ formatStatus(activity.status) }}
                      </span>
                    </p>
                    <p v-if="activity.description" class="mt-1 text-sm text-gray-500">
                      {{ activity.description }}
                    </p>
                    
                    <!-- Subject Info -->
                    <div v-if="activity.subject" class="mt-2 flex items-center space-x-2 text-xs text-gray-500">
                      <UserIcon class="w-3 h-3" />
                      <span>{{ activity.subject.name }}</span>
                      <span v-if="activity.subject.company_name" class="text-gray-400">
                        at {{ activity.subject.company_name }}
                      </span>
                    </div>
                    
                    <!-- Activity Details -->
                    <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                      <span class="flex items-center">
                        <UserCircleIcon class="w-3 h-3 mr-1" />
                        {{ activity.user?.name }}
                      </span>
                      <span v-if="activity.scheduled_at" class="flex items-center">
                        <CalendarIcon class="w-3 h-3 mr-1" />
                        {{ formatDateTime(activity.scheduled_at) }}
                      </span>
                      <span v-if="activity.duration_minutes" class="flex items-center">
                        <ClockIcon class="w-3 h-3 mr-1" />
                        {{ activity.duration_minutes }}min
                      </span>
                    </div>
                    
                    <!-- Outcome -->
                    <div v-if="activity.outcome" class="mt-2 p-2 bg-gray-50 rounded text-xs">
                      <strong>Outcome:</strong> {{ activity.outcome }}
                      <p v-if="activity.outcome_notes" class="mt-1 text-gray-600">
                        {{ activity.outcome_notes }}
                      </p>
                    </div>
                  </div>
                  
                  <!-- Actions -->
                  <div class="flex items-start space-x-2">
                    <button
                      v-if="activity.status === 'scheduled'"
                      @click="startActivity(activity)"
                      class="text-green-600 hover:text-green-900 text-xs font-medium"
                    >
                      Start
                    </button>
                    <button
                      v-if="activity.status === 'in_progress'"
                      @click="completeActivity(activity)"
                      class="text-blue-600 hover:text-blue-900 text-xs font-medium"
                    >
                      Complete
                    </button>
                    <button
                      @click="editActivity(activity)"
                      class="text-indigo-600 hover:text-indigo-900 text-xs font-medium"
                    >
                      Edit
                    </button>
                    <button
                      v-if="activity.status === 'scheduled'"
                      @click="rescheduleActivity(activity)"
                      class="text-yellow-600 hover:text-yellow-900 text-xs font-medium"
                    >
                      Reschedule
                    </button>
                  </div>
                </div>
                
                <div class="mt-2 text-xs text-gray-500">
                  {{ formatRelativeTime(activity.created_at) }}
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- List View -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Activity
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Type
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Subject
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Assigned To
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Scheduled
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="activity in activities" :key="activity.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ activity.title }}</div>
                <div v-if="activity.description" class="text-sm text-gray-500">{{ activity.description }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <component :is="getActivityIcon(activity.type)" class="w-4 h-4 mr-2 text-gray-400" />
                  <span class="text-sm text-gray-900">{{ formatActivityType(activity.type) }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="activity.subject" class="text-sm text-gray-900">
                  {{ activity.subject.name }}
                  <div class="text-xs text-gray-500">{{ activity.subject.company_name }}</div>
                </div>
                <div v-else class="text-sm text-gray-400">No subject</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(activity.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ formatStatus(activity.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ activity.user?.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ activity.scheduled_at ? formatDateTime(activity.scheduled_at) : '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <button
                    v-if="activity.status === 'scheduled'"
                    @click="startActivity(activity)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Start
                  </button>
                  <button
                    v-if="activity.status === 'in_progress'"
                    @click="completeActivity(activity)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Complete
                  </button>
                  <button
                    @click="editActivity(activity)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    Edit
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="activities.length === 0" class="text-center py-12">
        <CalendarIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No activities</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by scheduling your first activity.</p>
        <div class="mt-6">
          <button
            @click="showCreateModal = true"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Schedule Activity
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
        </div>
        <div class="flex space-x-2">
          <button
            v-for="page in pagination.links"
            :key="page.label"
            @click="changePage(page.url)"
            :disabled="!page.url"
            :class="[
              'px-3 py-2 text-sm font-medium rounded-md',
              page.active
                ? 'bg-indigo-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
            ]"
            v-html="page.label"
          ></button>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <CreateActivityModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="handleActivityCreated"
    />

    <EditActivityModal
      v-if="selectedActivity && showEditModal"
      :activity="selectedActivity"
      @close="showEditModal = false; selectedActivity = null"
      @updated="handleActivityUpdated"
    />

    <CompleteActivityModal
      v-if="selectedActivity && showCompleteModal"
      :activity="selectedActivity"
      @close="showCompleteModal = false; selectedActivity = null"
      @completed="handleActivityCompleted"
    />

    <RescheduleActivityModal
      v-if="selectedActivity && showRescheduleModal"
      :activity="selectedActivity"
      @close="showRescheduleModal = false; selectedActivity = null"
      @rescheduled="handleActivityRescheduled"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import {
  CalendarIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  UserIcon,
  UserCircleIcon,
  PhoneIcon,
  EnvelopeIcon,
  VideoCameraIcon,
  DocumentTextIcon,
  ChatBubbleLeftIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import CreateActivityModal from './CreateActivityModal.vue'
import EditActivityModal from './EditActivityModal.vue'
import CompleteActivityModal from './CompleteActivityModal.vue'
import RescheduleActivityModal from './RescheduleActivityModal.vue'

const { showToast } = useToast()

// Reactive data
const activities = ref([])
const users = ref([])
const pagination = ref({})
const stats = ref({
  total_activities: 0,
  pending_activities: 0,
  overdue_activities: 0,
  completed_activities: 0
})

const selectedActivity = ref(null)
const viewMode = ref('timeline')

const showCreateModal = ref(false)
const showEditModal = ref(false)
const showCompleteModal = ref(false)
const showRescheduleModal = ref(false)

const filters = reactive({
  type: '',
  status: '',
  user_id: '',
  date_range: ''
})

// Methods
const fetchActivities = async (page = 1) => {
  try {
    const params = new URLSearchParams({
      page,
      per_page: 20,
      ...filters
    })
    
    const response = await fetch(`/api/v1/crm/activities?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      activities.value = data.data
      pagination.value = data.meta
    }
  } catch (error) {
    console.error('Failed to fetch activities:', error)
    showToast('Failed to load activities', 'error')
  }
}

const fetchStats = async () => {
  try {
    const response = await fetch('/api/v1/crm/activities/statistics', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      stats.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch stats:', error)
  }
}

const fetchUsers = async () => {
  try {
    const response = await fetch('/api/v1/users', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
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

const clearFilters = () => {
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  fetchActivities()
}

const startActivity = async (activity) => {
  try {
    const response = await fetch(`/api/v1/crm/activities/${activity.uuid}/start`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      showToast('Activity started', 'success')
      fetchActivities()
      fetchStats()
    }
  } catch (error) {
    console.error('Failed to start activity:', error)
    showToast('Failed to start activity', 'error')
  }
}

const completeActivity = (activity) => {
  selectedActivity.value = activity
  showCompleteModal.value = true
}

const editActivity = (activity) => {
  selectedActivity.value = activity
  showEditModal.value = true
}

const rescheduleActivity = (activity) => {
  selectedActivity.value = activity
  showRescheduleModal.value = true
}

const handleActivityCreated = () => {
  showCreateModal.value = false
  fetchActivities()
  fetchStats()
  showToast('Activity scheduled successfully', 'success')
}

const handleActivityUpdated = () => {
  showEditModal.value = false
  selectedActivity.value = null
  fetchActivities()
  showToast('Activity updated successfully', 'success')
}

const handleActivityCompleted = () => {
  showCompleteModal.value = false
  selectedActivity.value = null
  fetchActivities()
  fetchStats()
  showToast('Activity completed successfully', 'success')
}

const handleActivityRescheduled = () => {
  showRescheduleModal.value = false
  selectedActivity.value = null
  fetchActivities()
  showToast('Activity rescheduled successfully', 'success')
}

const changePage = (url) => {
  if (!url) return
  const page = new URL(url).searchParams.get('page')
  fetchActivities(page)
}

// Utility functions
const getActivityIcon = (type) => {
  const icons = {
    call: PhoneIcon,
    email: EnvelopeIcon,
    meeting: VideoCameraIcon,
    task: DocumentTextIcon,
    note: ChatBubbleLeftIcon
  }
  return icons[type] || CalendarIcon
}

const getActivityIconClass = (activity) => {
  const baseClasses = {
    call: 'bg-blue-500',
    email: 'bg-green-500',
    meeting: 'bg-purple-500',
    task: 'bg-yellow-500',
    note: 'bg-gray-500'
  }
  
  let bgClass = baseClasses[activity.type] || 'bg-gray-500'
  
  // Modify based on status
  if (activity.status === 'completed') {
    bgClass = 'bg-green-600'
  } else if (activity.status === 'cancelled') {
    bgClass = 'bg-red-500'
  } else if (activity.status === 'overdue') {
    bgClass = 'bg-red-600'
  }
  
  return bgClass
}

const getStatusBadgeClass = (status) => {
  const classes = {
    scheduled: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatActivityType = (type) => {
  return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatRelativeTime = (date) => {
  const now = new Date()
  const past = new Date(date)
  const diffInHours = Math.floor((now - past) / (1000 * 60 * 60))
  
  if (diffInHours < 1) return 'Just now'
  if (diffInHours < 24) return `${diffInHours}h ago`
  if (diffInHours < 168) return `${Math.floor(diffInHours / 24)}d ago`
  return `${Math.floor(diffInHours / 168)}w ago`
}

// Lifecycle
onMounted(() => {
  fetchActivities()
  fetchStats()
  fetchUsers()
})
</script>