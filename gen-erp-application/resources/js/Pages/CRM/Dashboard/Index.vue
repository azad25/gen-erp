<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">CRM Dashboard</h1>
          <p class="mt-1 text-sm text-gray-600">
            Overview of your sales pipeline and customer relationships
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <select v-model="selectedPeriod" @change="fetchData" class="rounded-md border-gray-300 text-sm">
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
            <option value="365">Last year</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <UserGroupIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ metrics.total_leads }}</div>
            <div class="text-sm text-gray-600">Total Leads</div>
            <div class="text-xs" :class="metrics.leads_change >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ metrics.leads_change >= 0 ? '+' : '' }}{{ metrics.leads_change }}% from last period
            </div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ChartBarIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ metrics.total_opportunities }}</div>
            <div class="text-sm text-gray-600">Active Opportunities</div>
            <div class="text-xs" :class="metrics.opportunities_change >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ metrics.opportunities_change >= 0 ? '+' : '' }}{{ metrics.opportunities_change }}% from last period
            </div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CurrencyDollarIcon class="h-8 w-8 text-purple-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">৳{{ formatNumber(metrics.pipeline_value) }}</div>
            <div class="text-sm text-gray-600">Pipeline Value</div>
            <div class="text-xs" :class="metrics.pipeline_change >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ metrics.pipeline_change >= 0 ? '+' : '' }}{{ metrics.pipeline_change }}% from last period
            </div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <TrophyIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ metrics.conversion_rate }}%</div>
            <div class="text-sm text-gray-600">Conversion Rate</div>
            <div class="text-xs" :class="metrics.conversion_change >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ metrics.conversion_change >= 0 ? '+' : '' }}{{ metrics.conversion_change }}% from last period
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Sales Pipeline Chart -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Sales Pipeline</h3>
        <div class="space-y-4">
          <div v-for="stage in pipelineData" :key="stage.id" class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: stage.color }"></div>
              <span class="text-sm font-medium text-gray-900">{{ stage.name }}</span>
            </div>
            <div class="text-right">
              <div class="text-sm font-semibold text-gray-900">{{ stage.opportunities_count }}</div>
              <div class="text-xs text-gray-500">৳{{ formatNumber(stage.total_value) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Lead Sources Chart -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Lead Sources</h3>
        <div class="space-y-3">
          <div v-for="source in leadSources" :key="source.source" class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-3 h-3 rounded-full bg-blue-500"></div>
              <span class="text-sm text-gray-900">{{ formatLeadSource(source.source) }}</span>
            </div>
            <div class="text-right">
              <div class="text-sm font-semibold text-gray-900">{{ source.count }}</div>
              <div class="text-xs text-gray-500">{{ source.percentage }}%</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activities and Top Performers -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent Activities -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Recent Activities</h3>
          <router-link to="/crm/activities" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
            View all
          </router-link>
        </div>
        <div class="space-y-4">
          <div v-for="activity in recentActivities" :key="activity.id" class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <div :class="getActivityIconClass(activity.type)" class="w-8 h-8 rounded-full flex items-center justify-center">
                <component :is="getActivityIcon(activity.type)" class="w-4 h-4 text-white" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-900">
                <span class="font-medium">{{ activity.user?.name }}</span>
                {{ getActivityDescription(activity) }}
              </p>
              <p class="text-xs text-gray-500">{{ formatRelativeTime(activity.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Performers -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Top Performers</h3>
          <select v-model="performanceMetric" @change="fetchTopPerformers" class="text-sm rounded-md border-gray-300">
            <option value="deals_won">Deals Won</option>
            <option value="revenue">Revenue</option>
            <option value="activities">Activities</option>
          </select>
        </div>
        <div class="space-y-4">
          <div v-for="(performer, index) in topPerformers" :key="performer.user_id" class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                  <span class="text-sm font-medium text-gray-700">{{ index + 1 }}</span>
                </div>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ performer.user_name }}</p>
                <p class="text-xs text-gray-500">{{ performer.role || 'Sales Rep' }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-sm font-semibold text-gray-900">
                <span v-if="performanceMetric === 'revenue'">৳{{ formatNumber(performer.value) }}</span>
                <span v-else>{{ performer.value }}</span>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Upcoming Tasks and Overdue Items -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Upcoming Tasks -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Upcoming Tasks</h3>
          <router-link to="/crm/activities" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
            View all
          </router-link>
        </div>
        <div class="space-y-3">
          <div v-for="task in upcomingTasks" :key="task.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center space-x-3">
              <div :class="getActivityIconClass(task.type)" class="w-6 h-6 rounded-full flex items-center justify-center">
                <component :is="getActivityIcon(task.type)" class="w-3 h-3 text-white" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ task.title }}</p>
                <p class="text-xs text-gray-500">{{ formatDateTime(task.scheduled_at) }}</p>
              </div>
            </div>
            <span :class="getPriorityBadgeClass(task.priority)" class="px-2 py-1 text-xs font-semibold rounded-full">
              {{ task.priority }}
            </span>
          </div>
        </div>
      </div>

      <!-- Overdue Items -->
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Overdue Items</h3>
          <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
            {{ overdueItems.length }}
          </span>
        </div>
        <div class="space-y-3">
          <div v-for="item in overdueItems" :key="item.id" class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
            <div class="flex items-center space-x-3">
              <ExclamationTriangleIcon class="w-5 h-5 text-red-500" />
              <div>
                <p class="text-sm font-medium text-gray-900">{{ item.title }}</p>
                <p class="text-xs text-red-600">Due {{ formatRelativeTime(item.due_date) }}</p>
              </div>
            </div>
            <button
              @click="markAsComplete(item)"
              class="text-red-600 hover:text-red-900 text-sm font-medium"
            >
              Mark Complete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  UserGroupIcon,
  ChartBarIcon,
  CurrencyDollarIcon,
  TrophyIcon,
  PhoneIcon,
  EnvelopeIcon,
  CalendarIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import { useCompany } from '@/Composables/useCompany'

const { showToast } = useToast()
const { currentCompany } = useCompany()

// Reactive data
const selectedPeriod = ref(30)
const performanceMetric = ref('deals_won')

const metrics = ref({
  total_leads: 0,
  total_opportunities: 0,
  pipeline_value: 0,
  conversion_rate: 0,
  leads_change: 0,
  opportunities_change: 0,
  pipeline_change: 0,
  conversion_change: 0
})

const pipelineData = ref([])
const leadSources = ref([])
const recentActivities = ref([])
const topPerformers = ref([])
const upcomingTasks = ref([])
const overdueItems = ref([])

// Methods
const fetchData = async () => {
  await Promise.all([
    fetchMetrics(),
    fetchPipelineData(),
    fetchLeadSources(),
    fetchRecentActivities(),
    fetchTopPerformers(),
    fetchUpcomingTasks(),
    fetchOverdueItems()
  ])
}

const fetchMetrics = async () => {
  try {
    const response = await fetch(`/api/v1/crm/dashboard/metrics?period=${selectedPeriod.value}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      metrics.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch metrics:', error)
  }
}

const fetchPipelineData = async () => {
  try {
    const response = await fetch('/api/v1/crm/pipelines/statistics', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      pipelineData.value = data.data.stages || []
    }
  } catch (error) {
    console.error('Failed to fetch pipeline data:', error)
  }
}

const fetchLeadSources = async () => {
  try {
    const response = await fetch(`/api/v1/crm/leads/sources?period=${selectedPeriod.value}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      leadSources.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch lead sources:', error)
  }
}

const fetchRecentActivities = async () => {
  try {
    const response = await fetch('/api/v1/crm/activities?limit=5&sort_by=created_at&sort_order=desc', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      recentActivities.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch recent activities:', error)
  }
}

const fetchTopPerformers = async () => {
  try {
    const response = await fetch(`/api/v1/crm/dashboard/top-performers?metric=${performanceMetric.value}&period=${selectedPeriod.value}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      topPerformers.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch top performers:', error)
  }
}

const fetchUpcomingTasks = async () => {
  try {
    const response = await fetch('/api/v1/crm/activities/upcoming?limit=5', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      upcomingTasks.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch upcoming tasks:', error)
  }
}

const fetchOverdueItems = async () => {
  try {
    const response = await fetch('/api/v1/crm/activities/overdue?limit=5', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      overdueItems.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch overdue items:', error)
  }
}

const markAsComplete = async (item) => {
  try {
    const response = await fetch(`/api/v1/crm/activities/${item.uuid}/complete`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      showToast('Activity marked as complete', 'success')
      fetchOverdueItems()
    }
  } catch (error) {
    console.error('Failed to mark as complete:', error)
    showToast('Failed to mark as complete', 'error')
  }
}

// Utility functions
const formatNumber = (num) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}

const formatLeadSource = (source) => {
  return source.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
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

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getActivityIcon = (type) => {
  const icons = {
    call: PhoneIcon,
    email: EnvelopeIcon,
    meeting: CalendarIcon
  }
  return icons[type] || CalendarIcon
}

const getActivityIconClass = (type) => {
  const classes = {
    call: 'bg-blue-500',
    email: 'bg-green-500',
    meeting: 'bg-purple-500'
  }
  return classes[type] || 'bg-gray-500'
}

const getActivityDescription = (activity) => {
  const descriptions = {
    call: `called ${activity.subject?.name || 'contact'}`,
    email: `sent email to ${activity.subject?.name || 'contact'}`,
    meeting: `had meeting with ${activity.subject?.name || 'contact'}`
  }
  return descriptions[activity.type] || `completed ${activity.title}`
}

const getPriorityBadgeClass = (priority) => {
  const classes = {
    high: 'bg-red-100 text-red-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800'
  }
  return classes[priority] || 'bg-gray-100 text-gray-800'
}

// Lifecycle
onMounted(() => {
  fetchData()
})
</script>