<template>
  <CrossDomainWidget
    title="Recent Activities"
    endpoint="/api/v1/integration/recent-activities"
    :auto-refresh="30"
  >
    <template #default="{ data }">
      <div v-if="data && data.length > 0" class="space-y-3">
        <div
          v-for="activity in data"
          :key="activity.id"
          class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg cursor-pointer"
          @click="viewActivity(activity)"
        >
          <!-- Activity Icon -->
          <div class="flex-shrink-0">
            <div
              class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-medium"
              :class="getActivityColor(activity.type)"
            >
              {{ getActivityIcon(activity.type) }}
            </div>
          </div>

          <!-- Activity Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ activity.title }}
              </p>
              <time class="text-xs text-gray-500">
                {{ formatTime(activity.created_at) }}
              </time>
            </div>
            <p class="text-xs text-gray-600 mt-1">
              {{ activity.description }}
            </p>
            <div class="flex items-center space-x-2 mt-2">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="getDomainColor(activity.domain)"
              >
                {{ activity.domain }}
              </span>
              <span v-if="activity.user" class="text-xs text-gray-500">
                by {{ activity.user.name }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-8">
        <ClockIcon class="mx-auto h-8 w-8 text-gray-400" />
        <p class="mt-2 text-sm text-gray-500">No recent activities</p>
      </div>
    </template>
  </CrossDomainWidget>
</template>

<script setup>
import { ClockIcon } from '@heroicons/vue/24/outline'
import CrossDomainWidget from './CrossDomainWidget.vue'

const emit = defineEmits(['activity-clicked'])

// Methods
const getActivityColor = (type) => {
  const colors = {
    'lead_created': 'bg-blue-500',
    'lead_updated': 'bg-blue-400',
    'opportunity_created': 'bg-green-500',
    'opportunity_won': 'bg-green-600',
    'shipment_created': 'bg-purple-500',
    'shipment_delivered': 'bg-purple-600',
    'notification_sent': 'bg-yellow-500',
    'task_completed': 'bg-indigo-500',
    'project_created': 'bg-teal-500'
  }
  return colors[type] || 'bg-gray-500'
}

const getActivityIcon = (type) => {
  const icons = {
    'lead_created': '👤',
    'lead_updated': '✏️',
    'opportunity_created': '💼',
    'opportunity_won': '🎉',
    'shipment_created': '📦',
    'shipment_delivered': '✅',
    'notification_sent': '🔔',
    'task_completed': '✓',
    'project_created': '📁'
  }
  return icons[type] || '📋'
}

const getDomainColor = (domain) => {
  const colors = {
    'CRM': 'bg-blue-100 text-blue-800',
    'Logistics': 'bg-purple-100 text-purple-800',
    'Projects': 'bg-teal-100 text-teal-800',
    'Notifications': 'bg-yellow-100 text-yellow-800',
    'HR': 'bg-green-100 text-green-800'
  }
  return colors[domain] || 'bg-gray-100 text-gray-800'
}

const formatTime = (timestamp) => {
  const now = new Date()
  const time = new Date(timestamp)
  const diffInMinutes = Math.floor((now - time) / (1000 * 60))
  
  if (diffInMinutes < 1) return 'Just now'
  if (diffInMinutes < 60) return `${diffInMinutes}m ago`
  if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`
  return time.toLocaleDateString()
}

const viewActivity = (activity) => {
  emit('activity-clicked', activity)
}
</script>