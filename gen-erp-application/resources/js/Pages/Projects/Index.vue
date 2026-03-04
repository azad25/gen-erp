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
          <ProjectCard
            v-for="project in projects.data"
            :key="project.id"
            :project="project"
            @updated="loadProjects"
            @deleted="handleProjectDeleted"
          />
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
import ProjectCard from '@/Components/Projects/ProjectCard.vue'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'
import { debounce } from 'lodash'

const { get, loading, error } = useApi()
const { showToast } = useToast()

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
    const params = {
      page: page.toString(),
      ...Object.fromEntries(
        Object.entries(filters.value).filter(([_, value]) => value !== '' && value !== false)
      )
    }

    const data = await get('/api/v1/projects', params)
    projects.value = data.data
  } catch (err) {
    console.error('Failed to load projects:', err)
    showToast('Failed to load projects', 'error')
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

const handleProjectDeleted = (projectId) => {
  projects.value.data = projects.value.data.filter(p => p.id !== projectId)
  projects.value.total -= 1
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
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>