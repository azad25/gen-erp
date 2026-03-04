<template>
  <AppLayout title="Projects">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Projects
        </h2>
        <Link :href="route('projects.create')" 
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
          Create Project
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Filters -->
        <div class="bg-white shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input v-model="filters.search" 
                       type="text" 
                       placeholder="Search projects..."
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       @input="debouncedSearch">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select v-model="filters.status" 
                        @change="applyFilters"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                  <option value="">All Statuses</option>
                  <option value="planning">Planning</option>
                  <option value="active">Active</option>
                  <option value="on_hold">On Hold</option>
                  <option value="completed">Completed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select v-model="filters.priority" 
                        @change="applyFilters"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                  <option value="">All Priorities</option>
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                <select v-model="filters.sort_by" 
                        @change="applyFilters"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                  <option value="created_at">Created Date</option>
                  <option value="name">Name</option>
                  <option value="end_date">Due Date</option>
                  <option value="progress_percentage">Progress</option>
                </select>
              </div>
            </div>
            <div class="mt-4 flex items-center space-x-4">
              <label class="flex items-center">
                <input v-model="filters.overdue" 
                       type="checkbox" 
                       @change="applyFilters"
                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700">Show overdue only</span>
              </label>
              <button @click="clearFilters" 
                      class="text-sm text-gray-500 hover:text-gray-700">
                Clear filters
              </button>
            </div>
          </div>
        </div>

        <!-- Projects Grid -->
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-500">Loading projects...</p>
        </div>

        <div v-else-if="projects.data.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No projects found</h3>
          <p class="mt-1 text-sm text-gray-500">Get started by creating a new project.</p>
          <div class="mt-6">
            <Link :href="route('projects.create')" 
                  class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
              Create Project
            </Link>
          </div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="project in projects.data" :key="project.id" 
               class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
            <div class="p-6">
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 rounded-full" :class="getStatusColor(project.status)"></div>
                  <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                    {{ project.status.replace('_', ' ') }}
                  </span>
                </div>
                <div class="flex items-center space-x-1">
                  <div class="w-2 h-2 rounded-full" :class="getPriorityColor(project.priority)"></div>
                  <span class="text-xs text-gray-500 capitalize">{{ project.priority }}</span>
                </div>
              </div>

              <div class="mb-4">
                <h3 class="text-lg font-medium text-gray-900 mb-1">
                  <Link :href="route('projects.show', project.id)" 
                        class="hover:text-blue-600">
                    {{ project.name }}
                  </Link>
                </h3>
                <p class="text-sm text-gray-600 line-clamp-2">{{ project.description }}</p>
              </div>

              <div class="mb-4">
                <div class="flex items-center justify-between text-sm text-gray-500 mb-1">
                  <span>Progress</span>
                  <span>{{ project.progress_percentage }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                       :style="`width: ${project.progress_percentage}%`"></div>
                </div>
              </div>

              <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                <div v-if="project.client_name">
                  <span class="font-medium">Client:</span> {{ project.client_name }}
                </div>
                <div v-else>
                  <span class="text-gray-400">Internal Project</span>
                </div>
                <div v-if="project.end_date">
                  <span class="font-medium">Due:</span> 
                  <span :class="{ 'text-red-600': isOverdue(project) }">
                    {{ formatDate(project.end_date) }}
                  </span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div v-if="project.project_manager" class="flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    {{ project.project_manager.first_name }} {{ project.project_manager.last_name }}
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <Link :href="route('projects.show', project.id)" 
                        class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                    View
                  </Link>
                  <Link :href="route('projects.edit', project.id)" 
                        class="text-gray-600 hover:text-gray-500 text-sm font-medium">
                    Edit
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="projects.data.length > 0" class="mt-8">
          <nav class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
              <button v-if="projects.prev_page_url" 
                      @click="loadPage(projects.current_page - 1)"
                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Previous
              </button>
              <button v-if="projects.next_page_url" 
                      @click="loadPage(projects.current_page + 1)"
                      class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Next
              </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Showing {{ projects.from }} to {{ projects.to }} of {{ projects.total }} results
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                  <button v-for="page in visiblePages" :key="page"
                          @click="loadPage(page)"
                          :class="[
                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                            page === projects.current_page
                              ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                              : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                          ]">
                    {{ page }}
                  </button>
                </nav>
              </div>
            </div>
          </nav>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { debounce } from 'lodash'

const projects = ref({
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
  prev_page_url: null,
  next_page_url: null
})

const loading = ref(true)

const filters = ref({
  search: '',
  status: '',
  priority: '',
  sort_by: 'created_at',
  sort_order: 'desc',
  overdue: false
})

onMounted(() => {
  loadProjects()
})

const loadProjects = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      ...Object.fromEntries(
        Object.entries(filters.value).filter(([_, value]) => value !== '' && value !== false)
      )
    })

    const response = await fetch(`/api/v1/projects?${params}`, {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      projects.value = data.data
    }
  } catch (error) {
    console.error('Failed to load projects:', error)
  } finally {
    loading.value = false
  }
}

const loadPage = (page) => {
  loadProjects(page)
}

const applyFilters = () => {
  loadProjects(1)
}

const debouncedSearch = debounce(() => {
  applyFilters()
}, 300)

const clearFilters = () => {
  filters.value = {
    search: '',
    status: '',
    priority: '',
    sort_by: 'created_at',
    sort_order: 'desc',
    overdue: false
  }
  applyFilters()
}

const visiblePages = computed(() => {
  const current = projects.value.current_page
  const last = projects.value.last_page
  const pages = []
  
  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i)
  }
  
  return pages
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

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

const isOverdue = (project) => {
  if (!project.end_date) return false
  return new Date(project.end_date) < new Date() && 
         !['completed', 'cancelled'].includes(project.status)
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>