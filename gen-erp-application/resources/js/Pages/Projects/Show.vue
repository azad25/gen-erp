<template>
  <AppLayout :title="project.name">
    <template #header>
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <Link :href="route('projects.index')" 
                class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
            </svg>
          </Link>
          <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
              {{ project.name }}
            </h2>
            <div class="flex items-center space-x-4 mt-1">
              <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full" :class="getStatusColor(project.status)"></div>
                <span class="text-sm text-gray-600 capitalize">{{ project.status.replace('_', ' ') }}</span>
              </div>
              <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full" :class="getPriorityColor(project.priority)"></div>
                <span class="text-sm text-gray-600 capitalize">{{ project.priority }} Priority</span>
              </div>
            </div>
          </div>
        </div>
        <div class="flex items-center space-x-3">
          <Link :href="route('projects.edit', project.id)" 
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Edit Project
          </Link>
          <button @click="archiveProject" 
                  class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Archive
          </button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Main Content -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Project Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Project Overview</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Description</h4>
                    <p class="text-gray-900">{{ project.description || 'No description provided' }}</p>
                  </div>
                  <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Client</h4>
                    <p class="text-gray-900">{{ project.client_name || 'Internal Project' }}</p>
                    <p v-if="project.client_email" class="text-sm text-gray-600">{{ project.client_email }}</p>
                    <p v-if="project.client_phone" class="text-sm text-gray-600">{{ project.client_phone }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Progress & Statistics -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Progress & Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Overall Progress</h4>
                    <div class="flex items-center space-x-3">
                      <div class="flex-1 bg-gray-200 rounded-full h-3">
                        <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" 
                             :style="`width: ${project.progress_percentage}%`"></div>
                      </div>
                      <span class="text-sm font-medium text-gray-900">{{ project.progress_percentage }}%</span>
                    </div>
                  </div>
                  <div v-if="statistics">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Tasks</h4>
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ statistics.completed_tasks }}/{{ statistics.total_tasks }}
                    </div>
                    <p class="text-sm text-gray-600">Completed</p>
                  </div>
                  <div v-if="statistics">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Time Logged</h4>
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ Math.round(statistics.total_time_logged) }}h
                    </div>
                    <p class="text-sm text-gray-600">
                      of {{ project.estimated_hours || 'N/A' }}h estimated
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Recent Tasks -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-medium text-gray-900">Recent Tasks</h3>
                  <Link :href="route('tasks.create', { project_id: project.id })" 
                        class="text-sm text-blue-600 hover:text-blue-500">
                    Add Task
                  </Link>
                </div>
                <div v-if="recentTasks.length === 0" class="text-center py-8">
                  <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                  </svg>
                  <h3 class="mt-2 text-sm font-medium text-gray-900">No tasks yet</h3>
                  <p class="mt-1 text-sm text-gray-500">Get started by creating your first task.</p>
                </div>
                <div v-else class="space-y-3">
                  <div v-for="task in recentTasks" :key="task.id" 
                       class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div class="flex items-center space-x-3">
                      <div class="w-3 h-3 rounded-full" :class="getTaskStatusColor(task.status)"></div>
                      <div>
                        <p class="text-sm font-medium text-gray-900">{{ task.title }}</p>
                        <div class="flex items-center space-x-4 text-xs text-gray-500">
                          <span v-if="task.assignee">{{ task.assignee.first_name }} {{ task.assignee.last_name }}</span>
                          <span v-if="task.due_date">Due: {{ formatDate(task.due_date) }}</span>
                          <span class="capitalize">{{ task.priority }} priority</span>
                        </div>
                      </div>
                    </div>
                    <Link :href="route('tasks.show', task.id)" 
                          class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                      View
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Project Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Project Details</h3>
                <dl class="space-y-4">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Project Manager</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ project.project_manager ? 
                         `${project.project_manager.first_name} ${project.project_manager.last_name}` : 
                         'Not assigned' }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ project.start_date ? formatDate(project.start_date) : 'Not set' }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">End Date</dt>
                    <dd class="mt-1 text-sm text-gray-900" :class="{ 'text-red-600': isOverdue }">
                      {{ project.end_date ? formatDate(project.end_date) : 'Not set' }}
                      <span v-if="isOverdue" class="ml-1 text-xs">(Overdue)</span>
                    </dd>
                  </div>
                  <div v-if="project.budget">
                    <dt class="text-sm font-medium text-gray-500">Budget</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ formatCurrency(project.budget, project.currency) }}
                    </dd>
                  </div>
                  <div v-if="project.hourly_rate">
                    <dt class="text-sm font-medium text-gray-500">Hourly Rate</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ formatCurrency(project.hourly_rate, project.currency) }}/hour
                    </dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Team Members -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-medium text-gray-900">Team Members</h3>
                  <button @click="showAddMemberModal = true" 
                          class="text-sm text-blue-600 hover:text-blue-500">
                    Add Member
                  </button>
                </div>
                <div v-if="project.members && project.members.length === 0" class="text-center py-4">
                  <p class="text-sm text-gray-500">No team members assigned</p>
                </div>
                <div v-else class="space-y-3">
                  <div v-for="member in project.members" :key="member.id" 
                       class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                      <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                        <span class="text-xs font-medium text-gray-700">
                          {{ member.first_name[0] }}{{ member.last_name[0] }}
                        </span>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-900">
                          {{ member.first_name }} {{ member.last_name }}
                        </p>
                        <p class="text-xs text-gray-500 capitalize">{{ member.pivot.role }}</p>
                      </div>
                    </div>
                    <button @click="removeMember(member.id)" 
                            class="text-red-600 hover:text-red-500 text-xs">
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                  <Link :href="route('tasks.create', { project_id: project.id })" 
                        class="block w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-md">
                    Create Task
                  </Link>
                  <Link :href="route('projects.board', project.id)" 
                        class="block w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded-md">
                    View Kanban Board
                  </Link>
                  <button @click="duplicateProject" 
                          class="block w-full text-left px-3 py-2 text-sm text-purple-600 hover:bg-purple-50 rounded-md">
                    Duplicate Project
                  </button>
                  <Link :href="route('projects.reports', project.id)" 
                        class="block w-full text-left px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-md">
                    View Reports
                  </Link>
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
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  projectId: {
    type: [String, Number],
    required: true
  }
})

const project = ref({})
const statistics = ref(null)
const recentTasks = ref([])
const loading = ref(true)
const showAddMemberModal = ref(false)

onMounted(async () => {
  await Promise.all([
    loadProject(),
    loadStatistics(),
    loadRecentTasks()
  ])
  loading.value = false
})

const loadProject = async () => {
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
    console.error('Failed to load project:', error)
  }
}

const loadStatistics = async () => {
  try {
    const response = await fetch(`/api/v1/projects/${props.projectId}/statistics`, {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      statistics.value = data.data
    }
  } catch (error) {
    console.error('Failed to load statistics:', error)
  }
}

const loadRecentTasks = async () => {
  try {
    const response = await fetch(`/api/v1/projects/${props.projectId}/tasks?per_page=5&sort_by=created_at&sort_order=desc`, {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      recentTasks.value = data.data.data
    }
  } catch (error) {
    console.error('Failed to load recent tasks:', error)
  }
}

const isOverdue = computed(() => {
  if (!project.value.end_date) return false
  return new Date(project.value.end_date) < new Date() && 
         !['completed', 'cancelled'].includes(project.value.status)
})

const archiveProject = async () => {
  if (!confirm('Are you sure you want to archive this project?')) return
  
  try {
    const response = await fetch(`/api/v1/projects/${props.projectId}/archive`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      await loadProject()
    }
  } catch (error) {
    console.error('Failed to archive project:', error)
  }
}

const duplicateProject = async () => {
  const newName = prompt('Enter name for the duplicated project:', `${project.value.name} (Copy)`)
  if (!newName) return
  
  try {
    const response = await fetch(`/api/v1/projects/${props.projectId}/duplicate`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ name: newName })
    })
    
    if (response.ok) {
      const data = await response.json()
      window.location.href = `/projects/${data.data.id}`
    }
  } catch (error) {
    console.error('Failed to duplicate project:', error)
  }
}

const removeMember = async (memberId) => {
  if (!confirm('Are you sure you want to remove this member?')) return
  
  try {
    const response = await fetch(`/api/v1/projects/${props.projectId}/members/${memberId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      await loadProject()
    }
  } catch (error) {
    console.error('Failed to remove member:', error)
  }
}

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

const getTaskStatusColor = (status) => {
  const colors = {
    todo: 'bg-gray-400',
    in_progress: 'bg-blue-400',
    in_review: 'bg-yellow-400',
    testing: 'bg-purple-400',
    completed: 'bg-green-400',
    cancelled: 'bg-red-400'
  }
  return colors[status] || 'bg-gray-400'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

const formatCurrency = (amount, currency = 'USD') => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency
  }).format(amount)
}
</script>