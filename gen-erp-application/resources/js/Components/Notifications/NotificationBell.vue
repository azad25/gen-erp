<template>
  <div class="relative">
    <!-- Bell Icon Button -->
    <button
      @click="toggleDropdown"
      class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-full"
    >
      <span class="sr-only">View notifications</span>
      <BellIcon class="h-6 w-6" />
      
      <!-- Unread Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 h-5 w-5 bg-red-500 text-white text-xs font-medium rounded-full flex items-center justify-center"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown Panel -->
    <div
      v-if="showDropdown"
      v-click-outside="closeDropdown"
      class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50"
    >
      <!-- Header -->
      <div class="px-4 py-3 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
          <div class="flex items-center space-x-2">
            <button
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              class="text-xs text-indigo-600 hover:text-indigo-900 font-medium"
            >
              Mark all read
            </button>
            <router-link
              to="/notifications"
              @click="closeDropdown"
              class="text-xs text-indigo-600 hover:text-indigo-900 font-medium"
            >
              View all
            </router-link>
          </div>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="max-h-96 overflow-y-auto">
        <div v-if="notifications.length === 0" class="px-4 py-8 text-center">
          <BellSlashIcon class="mx-auto h-8 w-8 text-gray-400" />
          <p class="mt-2 text-sm text-gray-500">No new notifications</p>
        </div>
        
        <div v-else class="divide-y divide-gray-200">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            :class="[
              'px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors',
              !notification.read_at ? 'bg-blue-50' : ''
            ]"
            @click="handleNotificationClick(notification)"
          >
            <div class="flex items-start space-x-3">
              <!-- Icon -->
              <div class="flex-shrink-0">
                <div :class="getNotificationIconClass(notification.type)" class="w-8 h-8 rounded-full flex items-center justify-center">
                  <component :is="getNotificationIcon(notification.type)" class="w-4 h-4 text-white" />
                </div>
              </div>
              
              <!-- Content -->
              <div class="flex-1 min-w-0">
                <p :class="[
                  'text-sm font-medium',
                  !notification.read_at ? 'text-gray-900' : 'text-gray-700'
                ]">
                  {{ notification.title }}
                </p>
                <p :class="[
                  'text-xs mt-1',
                  !notification.read_at ? 'text-gray-700' : 'text-gray-500'
                ]">
                  {{ notification.message }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                  {{ formatRelativeTime(notification.created_at) }}
                </p>
              </div>
              
              <!-- Unread Indicator -->
              <div v-if="!notification.read_at" class="flex-shrink-0">
                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
        <router-link
          to="/notifications"
          @click="closeDropdown"
          class="block text-center text-sm text-indigo-600 hover:text-indigo-900 font-medium"
        >
          View all notifications
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import {
  BellIcon,
  BellSlashIcon,
  InformationCircleIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'

const { showToast } = useToast()

// Reactive data
const showDropdown = ref(false)
const notifications = ref([])
const unreadCount = ref(0)
const eventSource = ref(null)

// Methods
const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
  if (showDropdown.value) {
    fetchNotifications()
  }
}

const closeDropdown = () => {
  showDropdown.value = false
}

const fetchNotifications = async () => {
  try {
    const response = await fetch('/api/v1/notifications?per_page=10&unread_first=true', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      notifications.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
  }
}

const fetchUnreadCount = async () => {
  try {
    const response = await fetch('/api/v1/notifications/unread-count', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      unreadCount.value = data.data.count
    }
  } catch (error) {
    console.error('Failed to fetch unread count:', error)
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
      unreadCount.value = 0
      showToast('All notifications marked as read', 'success')
    }
  } catch (error) {
    console.error('Failed to mark all as read:', error)
    showToast('Failed to mark all notifications as read', 'error')
  }
}

const handleNotificationClick = async (notification) => {
  // Mark as read if unread
  if (!notification.read_at) {
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
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (error) {
      console.error('Failed to mark as read:', error)
    }
  }
  
  // Navigate to action URL if available
  if (notification.action_url) {
    closeDropdown()
    window.location.href = notification.action_url
  }
}

const setupRealTimeNotifications = () => {
  // Set up Server-Sent Events for real-time notifications
  const token = localStorage.getItem('token')
  if (!token) return
  
  eventSource.value = new EventSource(`/api/v1/notifications/stream?token=${token}`)
  
  eventSource.value.onmessage = (event) => {
    const notification = JSON.parse(event.data)
    
    // Add to notifications list
    notifications.value.unshift(notification)
    if (notifications.value.length > 10) {
      notifications.value.pop()
    }
    
    // Update unread count
    unreadCount.value++
    
    // Show toast notification
    showToast(notification.title, notification.type || 'info')
    
    // Play sound if enabled
    playNotificationSound()
    
    // Show browser notification if enabled and permission granted
    showBrowserNotification(notification)
  }
  
  eventSource.value.onerror = (error) => {
    console.error('Notification stream error:', error)
    // Reconnect after 5 seconds
    setTimeout(() => {
      if (eventSource.value?.readyState === EventSource.CLOSED) {
        setupRealTimeNotifications()
      }
    }, 5000)
  }
}

const playNotificationSound = () => {
  // Check if sound is enabled in user preferences
  const soundEnabled = localStorage.getItem('notification_sound') !== 'false'
  if (soundEnabled) {
    const audio = new Audio('/sounds/notification.mp3')
    audio.volume = 0.3
    audio.play().catch(() => {
      // Ignore errors if sound can't be played
    })
  }
}

const showBrowserNotification = (notification) => {
  // Check if browser notifications are enabled
  const browserNotificationsEnabled = localStorage.getItem('browser_notifications') !== 'false'
  
  if (browserNotificationsEnabled && 'Notification' in window && Notification.permission === 'granted') {
    new Notification(notification.title, {
      body: notification.message,
      icon: '/favicon.ico',
      tag: notification.uuid
    })
  }
}

const requestNotificationPermission = async () => {
  if ('Notification' in window && Notification.permission === 'default') {
    await Notification.requestPermission()
  }
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
  const diffInMinutes = Math.floor((now - past) / (1000 * 60))
  
  if (diffInMinutes < 1) return 'Just now'
  if (diffInMinutes < 60) return `${diffInMinutes}m ago`
  
  const diffInHours = Math.floor(diffInMinutes / 60)
  if (diffInHours < 24) return `${diffInHours}h ago`
  
  const diffInDays = Math.floor(diffInHours / 24)
  if (diffInDays < 7) return `${diffInDays}d ago`
  
  return `${Math.floor(diffInDays / 7)}w ago`
}

// Click outside directive
const vClickOutside = {
  beforeMount(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value()
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
}

// Lifecycle
onMounted(() => {
  fetchUnreadCount()
  setupRealTimeNotifications()
  requestNotificationPermission()
})

onUnmounted(() => {
  if (eventSource.value) {
    eventSource.value.close()
  }
})
</script>