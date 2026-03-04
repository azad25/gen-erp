<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">Project Reports</h3>
        <div class="flex items-center space-x-2">
          <select
            v-model="selectedReportType"
            @change="generateReport"
            class="text-xs border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="overview">Overview</option>
            <option value="progress">Progress</option>
            <option value="time_tracking">Time Tracking</option>
            <option value="team_performance">Team Performance</option>
            <option value="budget">Budget Analysis</option>
          </select>
          <button
            @click="exportReport"
            :disabled="loading"
            class="text-xs bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white px-2 py-1 rounded-md"
          >
            Export
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
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Overview Report -->
      <div v-else-if="selectedReportType === 'overview'" class="space-y-6">
        <!-- Key Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-blue-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ reportData.total_tasks }}</div>
            <div class="text-xs text-blue-800">Total Tasks</div>
          </div>
          <div class="bg-green-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ reportData.completed_tasks }}</div>
            <div class="text-xs text-green-800">Completed</div>
          </div>
          <div class="bg-yellow-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ reportData.in_progress_tasks }}</div>
            <div class="text-xs text-yellow-800">In Progress</div>
          </div>
          <div class="bg-red-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ reportData.overdue_tasks }}</div>
            <div class="text-xs text-red-800">Overdue</div>
          </div>
        </div>

        <!-- Progress Chart -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Project Progress</h4>
          <div class="h-64">
            <LineChart
              :data="progressChartData"
              :options="chartOptions"
            />
          </div>
        </div>

        <!-- Task Distribution -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Tasks by Status</h4>
            <div class="h-48">
              <PieChart
                :data="statusChartData"
                :options="pieChartOptions"
              />
            </div>
          </div>
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Tasks by Priority</h4>
            <div class="h-48">
              <BarChart
                :data="priorityChartData"
                :options="barChartOptions"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Progress Report -->
      <div v-else-if="selectedReportType === 'progress'" class="space-y-6">
        <!-- Progress Summary -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Progress Summary</h4>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Overall Progress</span>
              <div class="flex items-center space-x-2">
                <div class="w-32 bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-indigo-600 h-2 rounded-full"
                    :style="{ width: `${reportData.overall_progress}%` }"
                  ></div>
                </div>
                <span class="text-sm font-medium">{{ reportData.overall_progress }}%</span>
              </div>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">On Schedule</span>
              <span class="text-sm font-medium" :class="reportData.on_schedule ? 'text-green-600' : 'text-red-600'">
                {{ reportData.on_schedule ? 'Yes' : 'No' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Days Remaining</span>
              <span class="text-sm font-medium">{{ reportData.days_remaining }}</span>
            </div>
          </div>
        </div>

        <!-- Milestone Progress -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Milestone Progress</h4>
          <div class="space-y-3">
            <div
              v-for="milestone in reportData.milestones"
              :key="milestone.id"
              class="flex items-center justify-between p-3 bg-white rounded border"
            >
              <div>
                <div class="text-sm font-medium text-gray-900">{{ milestone.name }}</div>
                <div class="text-xs text-gray-500">Due: {{ formatDate(milestone.due_date) }}</div>
              </div>
              <div class="flex items-center space-x-2">
                <div class="w-24 bg-gray-200 rounded-full h-2">
                  <div
                    class="h-2 rounded-full"
                    :class="milestone.progress === 100 ? 'bg-green-500' : 'bg-blue-500'"
                    :style="{ width: `${milestone.progress}%` }"
                  ></div>
                </div>
                <span class="text-xs font-medium">{{ milestone.progress }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Time Tracking Report -->
      <div v-else-if="selectedReportType === 'time_tracking'" class="space-y-6">
        <!-- Time Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-blue-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ formatHours(reportData.total_hours) }}</div>
            <div class="text-xs text-blue-800">Total Hours</div>
          </div>
          <div class="bg-green-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ formatHours(reportData.billable_hours) }}</div>
            <div class="text-xs text-green-800">Billable Hours</div>
          </div>
          <div class="bg-yellow-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ formatHours(reportData.estimated_hours) }}</div>
            <div class="text-xs text-yellow-800">Estimated Hours</div>
          </div>
          <div class="bg-purple-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-purple-600">{{ reportData.efficiency }}%</div>
            <div class="text-xs text-purple-800">Efficiency</div>
          </div>
        </div>

        <!-- Time Tracking Chart -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Time Tracking Over Time</h4>
          <div class="h-64">
            <AreaChart
              :data="timeTrackingChartData"
              :options="chartOptions"
            />
          </div>
        </div>

        <!-- Team Time Distribution -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Time by Team Member</h4>
          <div class="space-y-2">
            <div
              v-for="member in reportData.team_time"
              :key="member.user_id"
              class="flex items-center justify-between p-2 bg-white rounded"
            >
              <div class="flex items-center space-x-3">
                <img
                  class="h-6 w-6 rounded-full"
                  :src="member.user?.avatar || '/default-avatar.png'"
                  :alt="member.user?.name"
                />
                <span class="text-sm font-medium text-gray-900">{{ member.user?.name }}</span>
              </div>
              <div class="flex items-center space-x-2">
                <div class="w-24 bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-indigo-600 h-2 rounded-full"
                    :style="{ width: `${(member.hours / reportData.max_hours) * 100}%` }"
                  ></div>
                </div>
                <span class="text-sm font-medium">{{ formatHours(member.hours) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Team Performance Report -->
      <div v-else-if="selectedReportType === 'team_performance'" class="space-y-6">
        <!-- Performance Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-blue-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ reportData.avg_completion_rate }}%</div>
            <div class="text-xs text-blue-800">Avg Completion Rate</div>
          </div>
          <div class="bg-green-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ reportData.avg_response_time }}h</div>
            <div class="text-xs text-green-800">Avg Response Time</div>
          </div>
          <div class="bg-yellow-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ reportData.quality_score }}</div>
            <div class="text-xs text-yellow-800">Quality Score</div>
          </div>
        </div>

        <!-- Team Member Performance -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Individual Performance</h4>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tasks</th>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">On Time</th>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Hours</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="member in reportData.team_performance"
                  :key="member.user_id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-3 py-2 whitespace-nowrap">
                    <div class="flex items-center space-x-2">
                      <img
                        class="h-6 w-6 rounded-full"
                        :src="member.user?.avatar || '/default-avatar.png'"
                        :alt="member.user?.name"
                      />
                      <span class="text-sm font-medium text-gray-900">{{ member.user?.name }}</span>
                    </div>
                  </td>
                  <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ member.total_tasks }}</td>
                  <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ member.completed_tasks }}</td>
                  <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ member.on_time_rate }}%</td>
                  <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ formatHours(member.hours) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Budget Report -->
      <div v-else-if="selectedReportType === 'budget'" class="space-y-6">
        <!-- Budget Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-blue-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">${{ formatCurrency(reportData.total_budget) }}</div>
            <div class="text-xs text-blue-800">Total Budget</div>
          </div>
          <div class="bg-green-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">${{ formatCurrency(reportData.spent_budget) }}</div>
            <div class="text-xs text-green-800">Spent</div>
          </div>
          <div class="bg-yellow-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-yellow-600">${{ formatCurrency(reportData.remaining_budget) }}</div>
            <div class="text-xs text-yellow-800">Remaining</div>
          </div>
          <div class="bg-purple-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-purple-600">{{ reportData.budget_utilization }}%</div>
            <div class="text-xs text-purple-800">Utilization</div>
          </div>
        </div>

        <!-- Budget Breakdown -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Budget Breakdown</h4>
          <div class="h-64">
            <PieChart
              :data="budgetChartData"
              :options="pieChartOptions"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import LineChart from '@/Components/Charts/LineChart.vue'
import BarChart from '@/Components/Charts/BarChart.vue'
import PieChart from '@/Components/Charts/PieChart.vue'
import AreaChart from '@/Components/Charts/AreaChart.vue'

const props = defineProps({
  projectId: {
    type: [String, Number],
    required: true
  }
})

const { get, loading } = useApi()

// Reactive data
const collapsed = ref(false)
const selectedReportType = ref('overview')
const reportData = ref({})

// Computed properties
const progressChartData = computed(() => {
  if (!reportData.value.progress_data) return { labels: [], datasets: [] }
  
  return {
    labels: reportData.value.progress_data.labels,
    datasets: [
      {
        label: 'Progress %',
        data: reportData.value.progress_data.values,
        borderColor: 'rgb(79, 70, 229)',
        backgroundColor: 'rgba(79, 70, 229, 0.1)',
        tension: 0.1,
        fill: true
      }
    ]
  }
})

const statusChartData = computed(() => {
  if (!reportData.value.status_distribution) return { labels: [], datasets: [] }
  
  return {
    labels: Object.keys(reportData.value.status_distribution),
    datasets: [
      {
        data: Object.values(reportData.value.status_distribution),
        backgroundColor: [
          '#EF4444', // red
          '#F59E0B', // yellow
          '#3B82F6', // blue
          '#10B981'  // green
        ]
      }
    ]
  }
})

const priorityChartData = computed(() => {
  if (!reportData.value.priority_distribution) return { labels: [], datasets: [] }
  
  return {
    labels: Object.keys(reportData.value.priority_distribution),
    datasets: [
      {
        label: 'Tasks',
        data: Object.values(reportData.value.priority_distribution),
        backgroundColor: 'rgba(79, 70, 229, 0.8)'
      }
    ]
  }
})

const timeTrackingChartData = computed(() => {
  if (!reportData.value.time_data) return { labels: [], datasets: [] }
  
  return {
    labels: reportData.value.time_data.labels,
    datasets: [
      {
        label: 'Hours Logged',
        data: reportData.value.time_data.values,
        backgroundColor: 'rgba(79, 70, 229, 0.3)',
        borderColor: 'rgb(79, 70, 229)',
        fill: true
      }
    ]
  }
})

const budgetChartData = computed(() => {
  if (!reportData.value.budget_breakdown) return { labels: [], datasets: [] }
  
  return {
    labels: Object.keys(reportData.value.budget_breakdown),
    datasets: [
      {
        data: Object.values(reportData.value.budget_breakdown),
        backgroundColor: [
          '#3B82F6', // blue
          '#10B981', // green
          '#F59E0B', // yellow
          '#EF4444'  // red
        ]
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  }
}

const pieChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
}

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true
    }
  }
}

// Methods
const generateReport = async () => {
  try {
    const data = await get(`/api/v1/projects/${props.projectId}/reports/${selectedReportType.value}`)
    reportData.value = data.data
  } catch (err) {
    console.error('Failed to generate report:', err)
  }
}

const exportReport = async () => {
  try {
    const response = await fetch(`/api/v1/projects/${props.projectId}/reports/${selectedReportType.value}/export`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('api_token')}`
      }
    })
    
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `project-${selectedReportType.value}-report.pdf`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
    }
  } catch (err) {
    console.error('Failed to export report:', err)
  }
}

const formatHours = (hours) => {
  return `${hours}h`
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US').format(amount)
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  generateReport()
})
</script>