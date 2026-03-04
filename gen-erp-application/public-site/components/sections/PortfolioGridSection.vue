<template>
  <section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div v-if="content.title || content.subtitle" class="text-center mb-12">
        <h2 
          v-if="content.title" 
          class="text-3xl md:text-4xl font-bold mb-4"
          :style="{ color: tenant?.settings?.primary_color || '#1f2937' }"
        >
          {{ content.title }}
        </h2>
        <p v-if="content.subtitle" class="text-xl text-gray-600 max-w-3xl mx-auto">
          {{ content.subtitle }}
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="i in 6" 
          :key="i" 
          class="bg-white rounded-lg shadow-md overflow-hidden animate-pulse"
        >
          <div class="h-48 bg-gray-300"></div>
          <div class="p-6">
            <div class="h-4 bg-gray-300 rounded mb-2"></div>
            <div class="h-3 bg-gray-300 rounded w-3/4 mb-4"></div>
            <div class="flex space-x-2">
              <div class="h-6 bg-gray-300 rounded w-16"></div>
              <div class="h-6 bg-gray-300 rounded w-20"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-500 mb-4">
          <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <p class="text-gray-600">Failed to load portfolio items</p>
      </div>

      <!-- Portfolio Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <article 
          v-for="item in portfolioItems" 
          :key="item.id"
          class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300"
        >
          <!-- Project Image -->
          <div class="relative h-48 overflow-hidden">
            <NuxtImg
              v-if="item.featured_image"
              :src="item.featured_image"
              :alt="item.title"
              class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
              loading="lazy"
              format="webp"
              quality="80"
            />
            <div v-else class="w-full h-full bg-gray-200 flex items-center justify-center">
              <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            
            <!-- Project Status Badge -->
            <div 
              v-if="item.status"
              class="absolute top-4 right-4 px-3 py-1 rounded-full text-sm font-medium"
              :class="getStatusClass(item.status)"
            >
              {{ formatStatus(item.status) }}
            </div>
          </div>

          <!-- Project Content -->
          <div class="p-6">
            <h3 class="text-xl font-semibold mb-2 text-gray-900">
              {{ item.title }}
            </h3>
            
            <p v-if="item.description" class="text-gray-600 mb-4 line-clamp-3">
              {{ item.description }}
            </p>

            <!-- Project Meta -->
            <div class="flex flex-wrap gap-2 mb-4">
              <span 
                v-if="item.client_name"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
              >
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                {{ item.client_name }}
              </span>
              
              <span 
                v-if="item.category"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
              >
                {{ item.category }}
              </span>
            </div>

            <!-- Project Technologies -->
            <div v-if="item.technologies && item.technologies.length" class="flex flex-wrap gap-1 mb-4">
              <span 
                v-for="tech in item.technologies.slice(0, 4)" 
                :key="tech"
                class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700"
              >
                {{ tech }}
              </span>
              <span 
                v-if="item.technologies.length > 4"
                class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700"
              >
                +{{ item.technologies.length - 4 }} more
              </span>
            </div>

            <!-- Project Actions -->
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-500">
                <time v-if="item.completed_at" :datetime="item.completed_at">
                  {{ formatDate(item.completed_at) }}
                </time>
                <span v-else-if="item.start_date">
                  Started {{ formatDate(item.start_date) }}
                </span>
              </div>
              
              <div class="flex space-x-2">
                <a 
                  v-if="item.demo_url"
                  :href="item.demo_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white transition-colors duration-200"
                  :style="{ backgroundColor: tenant?.settings?.primary_color || '#3b82f6' }"
                >
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                  </svg>
                  View Demo
                </a>
                
                <a 
                  v-if="item.github_url"
                  :href="item.github_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200"
                >
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                  </svg>
                  Code
                </a>
              </div>
            </div>
          </div>
        </article>
      </div>

      <!-- View All Button -->
      <div v-if="content.show_view_all && portfolioItems.length >= content.limit" class="text-center mt-12">
        <NuxtLink
          to="/portfolio"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white transition-colors duration-200"
          :style="{ backgroundColor: tenant?.settings?.primary_color || '#3b82f6' }"
        >
          View All Projects
          <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
          </svg>
        </NuxtLink>
      </div>
    </div>
  </section>
</template>

<script setup>
interface PortfolioItem {
  id: string
  title: string
  description?: string
  featured_image?: string
  status: string
  client_name?: string
  category?: string
  technologies?: string[]
  demo_url?: string
  github_url?: string
  completed_at?: string
  start_date?: string
}

interface Content {
  title?: string
  subtitle?: string
  limit?: number
  category_filter?: string
  status_filter?: string
  show_view_all?: boolean
}

interface Tenant {
  id: string
  name: string
  slug: string
  settings: Record<string, any>
}

const props = defineProps<{
  content: Content
  tenant?: Tenant
}>()

const { $fetch } = useNuxtApp()
const portfolioItems = ref<PortfolioItem[]>([])
const loading = ref(true)
const error = ref(false)

// Fetch portfolio items
const fetchPortfolioItems = async () => {
  try {
    loading.value = true
    error.value = false
    
    const params = new URLSearchParams()
    if (props.content.limit) params.append('limit', props.content.limit.toString())
    if (props.content.category_filter) params.append('category', props.content.category_filter)
    if (props.content.status_filter) params.append('status', props.content.status_filter)
    
    const response = await $fetch(`/api/v1/cms/erp/portfolio?${params.toString()}`, {
      headers: {
        'X-Tenant-ID': props.tenant?.id || '',
        'X-Tenant-Slug': props.tenant?.slug || ''
      }
    })
    
    portfolioItems.value = response.data || []
  } catch (err) {
    console.error('Failed to fetch portfolio items:', err)
    error.value = true
    // Use mock data in development
    if (process.dev) {
      portfolioItems.value = getMockPortfolioItems()
    }
  } finally {
    loading.value = false
  }
}

// Mock data for development
const getMockPortfolioItems = (): PortfolioItem[] => [
  {
    id: '1',
    title: 'E-commerce Platform',
    description: 'A modern e-commerce platform built with Laravel and Vue.js, featuring advanced inventory management and payment processing.',
    featured_image: 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800&h=600&fit=crop',
    status: 'completed',
    client_name: 'Fashion House BD',
    category: 'Web Development',
    technologies: ['Laravel', 'Vue.js', 'MySQL', 'Stripe', 'AWS'],
    demo_url: 'https://demo.example.com',
    github_url: 'https://github.com/example/project',
    completed_at: '2024-02-15'
  },
  {
    id: '2',
    title: 'Mobile Banking App',
    description: 'Secure mobile banking application with biometric authentication and real-time transaction processing.',
    featured_image: 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=800&h=600&fit=crop',
    status: 'in_progress',
    client_name: 'Digital Bank Ltd',
    category: 'Mobile Development',
    technologies: ['React Native', 'Node.js', 'MongoDB', 'Firebase'],
    start_date: '2024-01-10'
  },
  {
    id: '3',
    title: 'Healthcare Management System',
    description: 'Comprehensive healthcare management system for hospitals and clinics with patient records and appointment scheduling.',
    featured_image: 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=800&h=600&fit=crop',
    status: 'completed',
    client_name: 'City Hospital',
    category: 'Healthcare',
    technologies: ['PHP', 'MySQL', 'Bootstrap', 'jQuery'],
    demo_url: 'https://healthcare-demo.example.com',
    completed_at: '2024-01-20'
  }
]

// Status formatting
const getStatusClass = (status: string) => {
  const classes = {
    completed: 'bg-green-100 text-green-800',
    in_progress: 'bg-blue-100 text-blue-800',
    on_hold: 'bg-yellow-100 text-yellow-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status: string) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Initialize
onMounted(() => {
  fetchPortfolioItems()
})
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>