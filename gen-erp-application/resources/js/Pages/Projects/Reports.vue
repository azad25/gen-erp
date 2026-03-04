<template>
  <AppLayout title="Project Reports">
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ project?.name }} - Reports
          </h2>
          <p class="text-sm text-gray-600 mt-1">Project analytics and insights</p>
        </div>
        <div class="flex space-x-3">
          <button @click="exportReport" 
                  class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Export PDF
          </button>
          <Link :href="route('projects.show', projectId)" 
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Back to Project
          </Link>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-500">Loading reports...</p>
        </div>

        <div v-else class="space-y-6">
          <!-- Project Overview -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Project Overview</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
              <div class="text-center">
                <div class="text-3xl font-bold text-blue-600">{{ reports.overview.progress }}%</div>
                <div class="text-sm text-gray-500">Progress</div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                  <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${reports.overview.progress}%`"></div>
                </div>
              </div>
              <div class="text-center">
                <div class="text-3xl font-bold text-green-600">{{ reports.overview.tasks_completed }}</div>
                <div class="text-sm text-gray-500">Tasks Completed</div>
                <div class="text-xs text-gray-400 mt-1">of {{ reports.overview.total_tasks }} total</div>
              </div>
              <div class="text-center">
                <div class="text-3xl font-bold text-purple-600">{{ reports.overview.hours_logged }}</div>
                <div class="text-sm text-gray-500">Hours Logged</div>
                <div class="text-xs text-gray-400 mt-1">of {{ reports.overview.estimated_hours }} estimated</div>
              </div>
              <div class="text-center">
                <div class="text-3xl font-bold text-orange-600">৳{{ reports.overview.cost_spent }}</div>
                <div class="text-sm text-gray-500">Cost Spent</div>
                <div class="text-xs text-gray-400 mt-1">of ৳{{ reports.overview.budget }} budget</div>
              </div>
            </div>
          </div>

          <!-- Time Tracking -->
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900">Time Tracking</h3>
              <select v-model="timeRange" @change="loadReports" 
                      class="text-sm border-gray-300 rounded-md">
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="quarter">This Quarter</option>
                <option value="all">All Time</option>
              </select>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Time by Team Member -->
              <div>
                <h4 class="font-medium text-gray-900 mb-3">Time by Team Member</h4>
                <div class="space-y-3">
                  <div v-for="member in reports.time_tracking.by_member" :key="member.user_id" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                      <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-sm font-medium">
                        {{ member.name.charAt(0) }}
                      </div>
                      <span class="font-medium text-gray-900">{{ member.name }}</span>
                    </div>
                    <div class="text-right">
                      <div class="font-semibold text-gray-900">{{ member.hours }}h</div>
                      <div class="text-xs text-gray-500">{{ member.percentage }}%</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Time by Task Type -->
              <div>
                <h4 class="font-medium text-gray-900 mb-3">Time by Task Type</h4>
                <div class="space-y-3">
                  <div v-for="type in reports.time_tracking.by_type" :key="type.type" 
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                      <div class="w-3 h-3 rounded-full" :class="getTypeColor(type.type)"></div>
                      <span class="font-medium text-gray-900 capitalize">{{ type.type }}</span>
                    </div>
                    <div class="text-right">
                      <div class="font-semibold text-gray-900">{{ type.hours }}h</div>
                      <div class="text-xs text-gray-500">{{ type.percentage }}%</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Task Analytics -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Task Analytics</h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Task Status Distribution -->
              <div>
                <h4 class="font-medium text-gray-900 mb-3">Task Status Distribution</h4>
                <div class="space-y-3">
                  <div v-for="status in reports.task_analytics.by_status" :key="status.status" 
                       class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                      <div class="w-3 h-3 rounded-full" :class="getStatusColor(status.status)"></div>
                      <span class="text-sm text-gray-700 capitalize">{{ status.status.replace('_', ' ') }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                      <span class="text-sm font-medium text-gray-900">{{ status.count }}</span>
                      <div class="w-20 bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full" :class="getStatusColor(status.status)" 
                             :style="`width: ${status.percentage}%`"></div>
                      </div>
                      <span class="text-xs text-gray-500 w-8">{{ status.percentage }}%</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Task Priority Distribution -->
              <div>
                <h4 class="font-medium text-gray-900 mb-3">Task Priority Distribution</h4>
                <div class="space-y-3">
                  <div v-for="priority in reports.task_analytics.by_priority" :key="priority.priority" 
                       class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                      <div class="w-3 h-3 rounded-full" :class="getPriorityColor(priority.priority)"></div>
                      <span class="text-sm text-gray-700 capitalize">{{ priority.priority }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                      <span class="text-sm font-medium text-gray-900">{{ priority.count }}</span>
                      <div class="w-20 bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full" :class="getPriorityColor(priority.priority)" 
                             :style="`width: ${priority.percentage}%`"></div>
                      </div>
                      <span class="text-xs text-gray-500 w-8">{{ priority.percentage }}%</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Budget Analysis -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Budget Analysis</h3>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">৳{{ reports.budget.allocated }}</div>
                <div class="text-sm text-gray-600">Budget Allocated</div>
              </div>
              <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-2xl font-bold text-green-600">৳{{ reports.budget.spent }}</div>
                <div class="text-sm text-gray-600">Amount Spent</div>
                <div class="text-xs text-gray-500 mt-1">{{ reports.budget.spent_percentage }}% of budget</div>
              </div>
              <div class="text-center p-4 bg-orange-50 rounded-lg">
                <div class="text-2xl font-bold text-orange-600">৳{{ reports.budget.remaining }}</div>
                <div class="text-sm text-gray-600">Remaining Budget</div>
                <div class="text-xs text-gray-500 mt-1">{{ reports.budget.remaining_percentage }}% remaining</div>
              </div>
            </div>
            
            <!-- Budget Progress Bar -->
            <div class="mt-6">
              <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                <span>Budget Utilization</span>
                <span>{{ reports.budget.spent_percentage }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="h-3 rounded-full transition-all duration-300"
                     :class="reports.budget.spent_percentage > 90 ? 'bg-red-500' : reports.budget.spent_percentage > 75 ? 'bg-yellow-500' : 'bg-green-500'"
                     :style="`width: ${reports.budget.spent_percentage}%`"></div>
              </div>
            </div>
          </div>

          <!-- Team Performance -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Team Performance</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Team Member
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Tasks Assigned
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Tasks Completed
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Completion Rate
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Hours Logged
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Avg. Task Time
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="member in reports.team_performance" :key="member.user_id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-sm font-medium mr-3">
                          {{ member.name.charAt(0) }}
                        </div>
                        <div class="text-sm font-medium text-gray-900">{{ member.name }}</div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ member.tasks_assigned }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ member.tasks_completed }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                          <div class="h-2 rounded-full" 
                               :class="member.completion_rate >= 80 ? 'bg-green-500' : member.completion_rate >= 60 ? 'bg-yellow-500' : 'bg-red-500'"
                               :style="`width: ${member.completion_rate}%`"></div>
                        </div>
                        <span class="text-sm text-gray-600">{{ member.completion_rate }}%</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ member.hours_logged }}h
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ member.avg_task_time }}h
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Project Timeline -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Project Timeline</h3>
            <div class="space-y-4">
              <div v-for="milestone in reports.timeline" :key="milestone.id" 
                   class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                  <div class="w-3 h-3 rounded-full mt-2" 
                       :class="milestone.completed ? 'bg-green-500' : 'bg-gray-300'"></div>
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <h4 class="text-sm font-medium text-gray-900">{{ milestone.name }}</h4>
                    <span class="text-xs text-gray-500">{{ formatDate(milestone.date) }}</span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">{{ milestone.description }}</p>
                  <div v-if="milestone.tasks_count" class="text-xs text-gray-500 mt-1">
                    {{ milestone.completed_tasks }}/{{ milestone.tasks_count }} tasks completed
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  projectId: [String, Number]
})

const project = ref(null)
const reports = ref({
  overview: {},
  time_tracking: { by_member: [], by_type: [] },
  task_analytics: { by_status: [], by_priority: [] },
  budget: {},
  team_performance: [],
  timeline: []
})
const loading = ref(true)
const timeRange = ref('month')

onMounted(async () => {
  await fetchProject()
  await loadReports()
})

const fetchProject = async () => {
  try {
    const response = await fetch(`/api/v1/projects/${props.projectId}`, {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      project.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch project:', error)
  }
}

const loadReports = async () => {
  loading.value = true
  try {
    const response = await fetch(`/api/v1/projects/${props.projectId}/report?time_range=${timeRange.value}`, {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      reports.value = data.data
    }
  } catch (error) {
    console.error('Failed to load reports:', error)
  } finally {
    loading.value = false
  }
}

const exportReport = async () => {
  try {
    const response = await fetch(`/api/v1/projects/${props.projectId}/report/export`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/pdf',
      },
      body: JSON.stringify({ time_range: timeRange.value })
    })
    
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `${project.value.name}-report.pdf`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
    }
  } catch (error) {
    console.error('Failed to export report:', error)
  }
}

const getStatusColor = (status) => {
  const colors = {
    todo: 'bg-gray-400',
    in_progress: 'bg-yellow-400',
    review: 'bg-blue-400',
    done: 'bg-green-400',
    blocked: 'bg-red-400'
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

const getTypeColor = (type) => {
  const colors = {
    development: 'bg-blue-400',
    design: 'bg-purple-400',
    testing: 'bg-green-400',
    meeting: 'bg-orange-400',
    documentation: 'bg-gray-400'
  }
  return colors[type] || 'bg-gray-400'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}
</script>