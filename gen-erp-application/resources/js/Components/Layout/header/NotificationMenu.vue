<template>
  <div class="relative" ref="dropdownRef">
    <button
      class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
      @click="toggleDropdown"
    >
      <span
        :class="{ hidden: !hasUnreadNotifications, flex: hasUnreadNotifications }"
        class="absolute right-0 top-0.5 z-1 h-2 w-2 rounded-full bg-orange-400"
      >
        <span
          class="absolute inline-flex w-full h-full bg-orange-400 rounded-full opacity-75 -z-1 animate-ping"
        ></span>
      </span>
      <svg
        class="fill-current"
        width="20"
        height="20"
        viewBox="0 0 20 20"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          fill-rule="evenodd"
          clip-rule="evenodd"
          d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
          fill=""
        />
      </svg>
      <!-- Unread count badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown Start -->
    <div
      v-if="dropdownOpen"
      class="absolute -right-[240px] mt-[17px] flex h-[480px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark sm:w-[361px] lg:right-0"
    >
      <div
        class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100 dark:border-gray-800"
      >
        <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">
          Notifications
          <span v-if="unreadCount > 0" class="ml-2 text-sm text-gray-500">({{ unreadCount }} unread)</span>
        </h5>

        <button @click="closeDropdown" class="text-gray-500 dark:text-gray-400">
          <svg
            class="fill-current"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
              fill=""
            />
          </svg>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center h-32">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Notifications List -->
      <ul v-else class="flex flex-col h-auto overflow-y-auto custom-scrollbar">
        <li v-for="notification in notifications" :key="notification.id" @click="handleNotificationClick(notification)">
          <div
            :class="[
              'flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5 cursor-pointer transition-colors',
              !notification.read_at ? 'bg-blue-50 dark:bg-blue-900/20' : ''
            ]"
          >
            <!-- Notification Icon -->
            <span class="relative block w-10 h-10 rounded-full flex-shrink-0">
              <div :class="getNotificationIconClass(notification.type)" class="w-10 h-10 rounded-full flex items-center justify-center">
                <component :is="getNotificationIcon(notification.type)" class="w-5 h-5 text-white" />
              </div>
              <span
                v-if="!notification.read_at"
                class="absolute bottom-0 right-0 z-10 h-2.5 w-2.5 rounded-full bg-blue-500 border-[1.5px] border-white dark:border-gray-900"
              ></span>
            </span>

            <!-- Notification Content -->
            <span class="block flex-1 min-w-0">
              <span class="mb-1.5 block text-theme-sm">
                <span :class="[
                  'font-medium',
                  !notification.read_at ? 'text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300'
                ]">
                  {{ notification.title }}
                </span>
              </span>
              
              <p :class="[
                'text-theme-xs mb-2 line-clamp-2',
                !notification.read_at ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400'
              ]">
                {{ notification.message }}
              </p>

              <span class="flex items-center gap-2 text-gray-500 text-theme-xs dark:text-gray-400">
                <span class="capitalize">{{ notification.type }}</span>
                <span class="w-1 h-1 bg-gray-400 rounded-full"></span>
                <span>{{ formatRelativeTime(notification.created_at) }}</span>
              </span>
            </span>
          </div>
        </li>
        
        <!-- Empty State -->
        <li v-if="notifications.length === 0" class="p-8 text-center">
          <div class="text-gray-400 dark:text-gray-500">
            <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 7H4l5-5v5zm6 10V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2h6a2 2 0 002-2z" />
            </svg>
            <p class="text-sm font-medium text-gray-900 dark:text-white">No notifications</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">You're all caught up!</p>
          </div>
        </li>
      </ul>

      <!-- Footer Actions -->
      <div class="mt-3 space-y-2">
        <button
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          class="w-full flex justify-center rounded-lg border border-gray-300 bg-white p-2 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
        >
          Mark All Read
        </button>
        
        <Link
          href="/notifications"
          class="flex justify-center rounded-lg border border-gray-300 bg-white p-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
          @click="handleViewAllClick"
        >
          View All Notifications
        </Link>
      </div>
    </div>
    <!-- Dropdown End -->
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  InformationCircleIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'

const dropdownOpen = ref(false)
const dropdownRef = ref(null)
const loading = ref(false)
const notifications = ref([])
const unreadCount = ref(0)

// Computed
const hasUnreadNotifications = computed(() => unreadCount.value > 0)

// Methods
const fetchNotifications = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/v1/notifications?per_page=10', {
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
  } finally {
    loading.value = false
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

const markAsRead = async (notification) => {
  if (notification.read_at) return
  
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
    }
  } catch (error) {
    console.error('Failed to mark all as read:', error)
  }
}

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value
  if (dropdownOpen.value) {
    fetchNotifications()
  }
}

const closeDropdown = () => {
  dropdownOpen.value = false
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    closeDropdown()
  }
}

const handleNotificationClick = (notification) => {
  markAsRead(notification)
  
  if (notification.action_url) {
    window.open(notification.action_url, '_blank')
  }
  
  closeDropdown()
}

const handleViewAllClick = () => {
  closeDropdown()
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
  document.addEventListener('click', handleClickOutside)
  fetchUnreadCount()
  
  // Refresh unread count every 30 seconds
  const interval = setInterval(fetchUnreadCount, 30000)
  
  onUnmounted(() => {
    clearInterval(interval)
  })
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
