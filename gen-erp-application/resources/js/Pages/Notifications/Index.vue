<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
          <p class="mt-1 text-sm text-gray-600">
            Stay updated with all your important notifications
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="markAllAsRead"
            :disabled="!hasUnreadNotifications"
            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Mark All Read
          </button>
          <button
            @click="showSettings = true"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Settings
          </button>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <BellIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.total_notifications }}</div>
            <div class="text-sm text-gray-600">Total Notifications</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ExclamationCircleIcon class="h-8 w-8 text-red-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.unread_notifications }}</div>
            <div class="text-sm text-gray-600">Unread</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ClockIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.today_notifications }}</div>
            <div class="text-sm text-gray-600">Today</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="filters.read" @change="fetchNotifications" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All</option>
            <option value="false">Unread</option>
            <option value="true">Read</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Type</label>
          <select v-model="filters.type" @change="fetchNotifications" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Types</option>
            <option value="info">Information</option>
            <option value="success">Success</option>
            <option value="warning">Warning</option>
            <option value="error">Error</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Date Range</label>
          <select v-model="filters.date_range" @change="fetchNotifications" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Time</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
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

    <!-- Notifications List -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Recent Notifications</h3>
      </div>
      
      <div class="divide-y divide-gray-200">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          :class="[
            'p-6 hover:bg-gray-50 cursor-pointer transition-colors',
            !notification.read_at ? 'bg-blue-50 border-l-4 border-l-blue-500' : ''
          ]"
          @click="handleNotificationClick(notification)"
        >
          <div class="flex items-start space-x-4">
            <!-- Icon -->
            <div class="flex-shrink-0">
              <div :class="getNotificationIconClass(notification.type)" class="w-10 h-10 rounded-full flex items-center justify-center">
                <component :is="getNotificationIcon(notification.type)" class="w-5 h-5 text-white" />
              </div>
            </div>
            
            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <h4 :class="[
                    'text-sm font-medium',
                    !notification.read_at ? 'text-gray-900' : 'text-gray-700'
                  ]">
                    {{ notification.title }}
                  </h4>
                  <p :class="[
                    'mt-1 text-sm',
                    !notification.read_at ? 'text-gray-700' : 'text-gray-500'
                  ]">
                    {{ notification.message }}
                  </p>
                  
                  <!-- Metadata -->
                  <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                    <span>{{ formatRelativeTime(notification.created_at) }}</span>
                    <span v-if="notification.category" class="capitalize">{{ notification.category }}</span>
                    <span v-if="notification.source" class="capitalize">{{ notification.source }}</span>
                  </div>
                </div>
                
                <!-- Actions -->
                <div class="flex items-center space-x-2 ml-4">
                  <button
                    v-if="!notification.read_at"
                    @click.stop="markAsRead(notification)"
                    class="text-indigo-600 hover:text-indigo-900 text-xs font-medium"
                  >
                    Mark Read
                  </button>
                  <button
                    @click.stop="deleteNotification(notification)"
                    class="text-red-600 hover:text-red-900 text-xs font-medium"
                  >
                    Delete
                  </button>
                </div>
              </div>
              
              <!-- Action Button -->
              <div v-if="notification.action_url" class="mt-3">
                <a
                  :href="notification.action_url"
                  class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200"
                >
                  {{ notification.action_label || 'View Details' }}
                  <ArrowTopRightOnSquareIcon class="ml-1 w-3 h-3" />
                </a>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Empty State -->
        <div v-if="notifications.length === 0" class="p-12 text-center">
          <BellSlashIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ hasActiveFilters ? 'No notifications match your current filters.' : 'You\'re all caught up!' }}
          </p>
        </div>
      </div>
      
      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
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
    </div>

    <!-- Settings Modal -->
    <NotificationSettingsModal
      v-if="showSettings"
      @close="showSettings = false"
      @updated="fetchStats"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue'
import {
  BellIcon,
  BellSlashIcon,
  ExclamationCircleIcon,
  ClockIcon,
  InformationCircleIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import NotificationSettingsModal from './NotificationSettingsModal.vue'

const { showToast } = useToast()

// Reactive data
const notifications = ref([])
const pagination = ref({})
const stats = ref({
  total_notifications: 0,
  unread_notifications: 0,
  today_notifications: 0
})

const showSettings = ref(false)

const filters = reactive({
  read: '',
  type: '',
  date_range: ''
})

// Computed
const hasUnreadNotifications = computed(() => stats.value.unread_notifications > 0)
const hasActiveFilters = computed(() => Object.values(filters).some(value => value !== ''))

// Methods
const fetchNotifications = async (page = 1) => {
  try {
    const params = new URLSearchParams({
      page,
      per_page: 15,
      ...filters
    })
    
    const response = await fetch(`/api/v1/notifications?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      notifications.value = data.data
      pagination.value = data.meta
    }
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
    showToast('Failed to load notifications', 'error')
  }
}

const fetchStats = async () => {
  try {
    const response = await fetch('/api/v1/notifications/statistics', {
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

const markAsRead = async (notification) => {
  try {
    const response = await fetch(`/api/v1/notifications/${notification.uuid}/mark-read`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      notification.read_at = new Date().toISOString()
      stats.value.unread_notifications = Math.max(0, stats.value.unread_notifications - 1)
    }
  } catch (error) {
    console.error('Failed to mark as read:', error)
    showToast('Failed to mark notification as read', 'error')
  }
}

const markAllAsRead = async () => {
  try {
    const response = await fetch('/api/v1/notifications/mark-all-read', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      notifications.value.forEach(notification => {
        if (!notification.read_at) {
          notification.read_at = new Date().toISOString()
        }
      })
      stats.value.unread_notifications = 0
      showToast('All notifications marked as read', 'success')
    }
  } catch (error) {
    console.error('Failed to mark all as read:', error)
    showToast('Failed to mark all notifications as read', 'error')
  }
}

const deleteNotification = async (notification) => {
  if (!confirm('Are you sure you want to delete this notification?')) return
  
  try {
    const response = await fetch(`/api/v1/notifications/${notification.uuid}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const index = notifications.value.findIndex(n => n.id === notification.id)
      if (index !== -1) {
        notifications.value.splice(index, 1)
      }
      
      if (!notification.read_at) {
        stats.value.unread_notifications = Math.max(0, stats.value.unread_notifications - 1)
      }
      stats.value.total_notifications = Math.max(0, stats.value.total_notifications - 1)
      
      showToast('Notification deleted', 'success')
    }
  } catch (error) {
    console.error('Failed to delete notification:', error)
    showToast('Failed to delete notification', 'error')
  }
}

const handleNotificationClick = (notification) => {
  if (!notification.read_at) {
    markAsRead(notification)
  }
  
  if (notification.action_url) {
    window.open(notification.action_url, '_blank')
  }
}

const clearFilters = () => {
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  fetchNotifications()
}

const changePage = (url) => {
  if (!url) return
  const page = new URL(url).searchParams.get('page')
  fetchNotifications(page)
}

const getNotificationIcon = (type) => {
  const icons = {
    info: InformationCircleIcon,
    success: CheckCircleIcon,
    warning: ExclamationTriangleIcon,
    error: XCircleIcon
  }
  return icons[type] || InformationCircleIcon
}

const getNotificationIconClass = (type) => {
  const classes = {
    info: 'bg-blue-500',
    success: 'bg-green-500',
    warning: 'bg-yellow-500',
    error: 'bg-red-500'
  }
  return classes[type] || 'bg-blue-500'
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
  fetchNotifications()
  fetchStats()
})
</script>
