<template>
  <AppLayout title="Project Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Project Dashboard
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Total Projects</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ dashboardData.total_projects }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Active Projects</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ dashboardData.active_projects }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Overdue Projects</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ dashboardData.overdue_projects }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Completed Projects</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ dashboardData.completed_projects }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Recent Projects -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Recent Projects</h3>
                <Link :href="route('projects.index')" class="text-sm text-blue-600 hover:text-blue-500">
                  View all
                </Link>
              </div>
              <div class="space-y-4">
                <div v-for="project in dashboardData.recent_projects" :key="project.id" 
                     class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                  <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 rounded-full" :class="getStatusColor(project.status)"></div>
                    <div>
                      <p class="text-sm font-medium text-gray-900">{{ project.name }}</p>
                      <p class="text-xs text-gray-500">{{ project.client_name || 'Internal Project' }}</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-sm text-gray-900">{{ project.progress_percentage }}%</p>
                    <div class="w-16 bg-gray-200 rounded-full h-2 mt-1">
                      <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${project.progress_percentage}%`"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Projects by Status Chart -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Projects by Status</h3>
              <div class="space-y-3">
                <div v-for="(count, status) in dashboardData.projects_by_status" :key="status" 
                     class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full" :class="getStatusColor(status)"></div>
                    <span class="text-sm text-gray-700 capitalize">{{ status.replace('_', ' ') }}</span>
                  </div>
                  <span class="text-sm font-medium text-gray-900">{{ count }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Projects by Priority Chart -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Projects by Priority</h3>
              <div class="space-y-3">
                <div v-for="(count, priority) in dashboardData.projects_by_priority" :key="priority" 
                     class="flex items-center justify-between">
                  <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full" :class="getPriorityColor(priority)"></div>
                    <span class="text-sm text-gray-700 capitalize">{{ priority }}</span>
                  </div>
                  <span class="text-sm font-medium text-gray-900">{{ count }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
              <div class="space-y-3">
                <Link :href="route('projects.create')" 
                      class="flex items-center p-3 text-sm text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100">
                  <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                  </svg>
                  Create New Project
                </Link>
                <Link :href="route('projects.index', { status: 'active' })" 
                      class="flex items-center p-3 text-sm text-green-600 bg-green-50 rounded-lg hover:bg-green-100">
                  <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  View Active Projects
                </Link>
                <Link :href="route('projects.index', { overdue: true })" 
                      class="flex items-center p-3 text-sm text-red-600 bg-red-50 rounded-lg hover:bg-red-100">
                  <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  View Overdue Projects
                </Link>
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

const dashboardData = ref({
  total_projects: 0,
  active_projects: 0,
  completed_projects: 0,
  overdue_projects: 0,
  recent_projects: [],
  projects_by_status: {},
  projects_by_priority: {}
})

const loading = ref(true)

onMounted(async () => {
  try {
    const response = await fetch('/api/v1/projects/dashboard', {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      dashboardData.value = data.data
    }
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
  } finally {
    loading.value = false
  }
})

const getStatusColor = (status) => {
  const colors = {
    planning: 'bg-gray-400',
    active: 'bg-green-400',
    on_hold: 'bg-yellow-400',
    completed: 'bg-blue-400',
    cancelled: 'bg-red-400'
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
</script>