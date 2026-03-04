<template>
  <div class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">
          {{ content.heading || 'Our Work' }}
        </h2>
      </div>
      
      <!-- Portfolio Grid -->
      <div
        v-if="projects.length > 0"
        :class="{
          'columns-1': content.layout === '1-col',
          'columns-2': content.layout === '2-col',
          'columns-3': content.layout === '3-col'
        }"
        class="columns-2 md:columns-3 gap-6 space-y-6"
      >
        <div
          v-for="project in projects"
          :key="project.id"
          class="break-inside-avoid bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-200"
          :class="{ 'pointer-events-none': isEditing }"
        >
          <!-- Project Image -->
          <div class="aspect-w-16 aspect-h-9 bg-gray-200">
            <img
              v-if="project.featured_image"
              :src="project.featured_image"
              :alt="project.name"
              class="w-full h-48 object-cover"
            />
            <div
              v-else
              class="w-full h-48 bg-gray-200 flex items-center justify-center"
            >
              <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
          </div>
          
          <!-- Project Info -->
          <div class="p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              {{ project.name }}
            </h3>
            
            <p
              v-if="project.description"
              class="text-sm text-gray-600 mb-3 line-clamp-3"
            >
              {{ project.description }}
            </p>
            
            <div class="flex items-center justify-between text-sm text-gray-500">
              <span v-if="project.completed_at">
                Completed {{ formatDate(project.completed_at) }}
              </span>
              <span v-if="project.budget" class="font-medium">
                ${{ formatCurrency(project.budget) }}
              </span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-else-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600">Loading projects...</p>
      </div>
      
      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No projects found</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ isEditing ? 'Portfolio projects will appear here when available' : 'Check back later for our latest work' }}
        </p>
      </div>
    </div>
    
    <!-- Editing Overlay -->
    <div
      v-if="isEditing"
      class="absolute inset-0 bg-blue-500 bg-opacity-5 border border-blue-300 rounded"
    ></div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  content: {
    type: Object,
    default: () => ({})
  },
  isEditing: {
    type: Boolean,
    default: false
  }
})

const projects = ref([])
const loading = ref(false)

const fetchProjects = async () => {
  if (props.isEditing) {
    // In editing mode, show mock data
    projects.value = [
      {
        id: 1,
        name: 'E-commerce Platform',
        description: 'A modern e-commerce platform built with Laravel and Vue.js',
        featured_image: null,
        completed_at: '2024-01-15',
        budget: 25000
      },
      {
        id: 2,
        name: 'Mobile App Development',
        description: 'Cross-platform mobile application for iOS and Android',
        featured_image: null,
        completed_at: '2024-02-20',
        budget: 35000
      }
    ]
    return
  }
  
  loading.value = true
  
  try {
    const params = {
      limit: props.content.limit || 6,
      sort_by: 'completed_at',
      sort_order: 'desc'
    }
    
    if (props.content.category) {
      params.category = props.content.category
    }
    
    if (props.content.client_id) {
      params.client_id = props.content.client_id
    }
    
    const response = await axios.get('/api/v1/cms/erp/projects', { params })
    projects.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching projects:', error)
    projects.value = []
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US').format(amount)
}

// Watch for content changes
watch(() => props.content, fetchProjects, { deep: true })

onMounted(() => {
  fetchProjects()
})
</script>