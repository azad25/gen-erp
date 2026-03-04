<template>
  <section class="py-16" :style="{ backgroundColor: content.background_color || '#f9fafb' }">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div v-if="content.title || content.subtitle" class="text-center mb-12">
        <h2 
          v-if="content.title" 
          class="text-3xl md:text-4xl font-bold mb-4"
          :style="{ color: content.title_color || tenant?.settings?.primary_color || '#1f2937' }"
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
          v-for="i in 3" 
          :key="i" 
          class="bg-white rounded-lg shadow-md p-6 animate-pulse"
        >
          <div class="flex items-center mb-4">
            <div class="w-12 h-12 bg-gray-300 rounded-full mr-4"></div>
            <div>
              <div class="h-4 bg-gray-300 rounded w-24 mb-2"></div>
              <div class="h-3 bg-gray-300 rounded w-32"></div>
            </div>
          </div>
          <div class="space-y-2">
            <div class="h-3 bg-gray-300 rounded"></div>
            <div class="h-3 bg-gray-300 rounded w-5/6"></div>
            <div class="h-3 bg-gray-300 rounded w-4/6"></div>
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
        <p class="text-gray-600">Failed to load testimonials</p>
      </div>

      <!-- Testimonials Grid -->
      <div v-else-if="displayTestimonials.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <article 
          v-for="testimonial in displayTestimonials" 
          :key="testimonial.id"
          class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300 relative"
        >
          <!-- Quote Icon -->
          <div 
            class="absolute -top-4 left-6 w-8 h-8 rounded-full flex items-center justify-center"
            :style="{ backgroundColor: tenant?.settings?.primary_color || '#3b82f6' }"
          >
            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
            </svg>
          </div>

          <!-- Rating -->
          <div v-if="testimonial.rating" class="flex items-center mb-4 mt-2">
            <div class="flex">
              <svg 
                v-for="star in 5" 
                :key="star"
                class="w-5 h-5"
                :class="star <= testimonial.rating ? 'text-yellow-400' : 'text-gray-300'"
                fill="currentColor" 
                viewBox="0 0 24 24"
              >
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
            </div>
            <span class="ml-2 text-sm text-gray-600">({{ testimonial.rating }}/5)</span>
          </div>

          <!-- Testimonial Content -->
          <blockquote class="text-gray-700 mb-6 leading-relaxed">
            "{{ testimonial.content }}"
          </blockquote>

          <!-- Author Info -->
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <NuxtImg
                v-if="testimonial.author_image"
                :src="testimonial.author_image"
                :alt="testimonial.author_name"
                class="w-12 h-12 rounded-full object-cover"
                loading="lazy"
                format="webp"
                quality="80"
              />
              <div 
                v-else
                class="w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold"
                :style="{ backgroundColor: tenant?.settings?.primary_color || '#3b82f6' }"
              >
                {{ getInitials(testimonial.author_name) }}
              </div>
            </div>
            <div class="ml-4">
              <h4 class="text-lg font-semibold text-gray-900">
                {{ testimonial.author_name }}
              </h4>
              <p class="text-sm text-gray-600">
                {{ testimonial.author_title }}
                <span v-if="testimonial.company_name" class="text-gray-400">
                  at {{ testimonial.company_name }}
                </span>
              </p>
              <p v-if="testimonial.project_name" class="text-xs text-gray-500 mt-1">
                Project: {{ testimonial.project_name }}
              </p>
            </div>
          </div>

          <!-- Date -->
          <div v-if="testimonial.created_at" class="text-xs text-gray-400 mt-4 text-right">
            {{ formatDate(testimonial.created_at) }}
          </div>
        </article>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <div class="text-gray-400 mb-4">
          <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
          </svg>
        </div>
        <p class="text-gray-600">No testimonials available</p>
      </div>

      <!-- View All Button -->
      <div v-if="content.show_view_all && displayTestimonials.length >= content.limit" class="text-center mt-12">
        <NuxtLink
          to="/testimonials"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white transition-colors duration-200"
          :style="{ backgroundColor: tenant?.settings?.primary_color || '#3b82f6' }"
        >
          View All Testimonials
          <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
          </svg>
        </NuxtLink>
      </div>
    </div>
  </section>
</template>

<script setup>
interface Testimonial {
  id: string
  content: string
  author_name: string
  author_title: string
  author_image?: string
  company_name?: string
  project_name?: string
  rating?: number
  created_at?: string
}

interface Content {
  title?: string
  subtitle?: string
  background_color?: string
  title_color?: string
  limit?: number
  show_view_all?: boolean
  testimonials?: Testimonial[]
  source?: 'custom' | 'erp' | 'mixed'
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
const erpTestimonials = ref<Testimonial[]>([])
const loading = ref(true)
const error = ref(false)

// Fetch ERP testimonials if needed
const fetchERPTestimonials = async () => {
  if (props.content.source === 'custom') {
    loading.value = false
    return
  }

  try {
    loading.value = true
    error.value = false
    
    const params = new URLSearchParams()
    if (props.content.limit) params.append('limit', props.content.limit.toString())
    
    const response = await $fetch(`/api/v1/cms/erp/testimonials?${params.toString()}`, {
      headers: {
        'X-Tenant-ID': props.tenant?.id || '',
        'X-Tenant-Slug': props.tenant?.slug || ''
      }
    })
    
    erpTestimonials.value = response.data || []
  } catch (err) {
    console.error('Failed to fetch testimonials:', err)
    error.value = true
    // Use mock data in development
    if (process.dev) {
      erpTestimonials.value = getMockTestimonials()
    }
  } finally {
    loading.value = false
  }
}

// Mock testimonials for development
const getMockTestimonials = (): Testimonial[] => [
  {
    id: '1',
    content: 'Working with this team was an absolute pleasure. They delivered our e-commerce platform on time and exceeded our expectations. The attention to detail and customer service was outstanding.',
    author_name: 'Sarah Johnson',
    author_title: 'CEO',
    company_name: 'Fashion House BD',
    project_name: 'E-commerce Platform',
    rating: 5,
    created_at: '2024-02-15'
  },
  {
    id: '2',
    content: 'The mobile banking app they developed for us has been a game-changer. Our customers love the user-friendly interface and the security features. Highly recommended!',
    author_name: 'Ahmed Rahman',
    author_title: 'CTO',
    company_name: 'Digital Bank Ltd',
    project_name: 'Mobile Banking App',
    rating: 5,
    created_at: '2024-01-20'
  },
  {
    id: '3',
    content: 'Professional, reliable, and innovative. They transformed our healthcare management system and made it much more efficient. Our staff productivity has increased significantly.',
    author_name: 'Dr. Maria Garcia',
    author_title: 'Director',
    company_name: 'City Hospital',
    project_name: 'Healthcare Management System',
    rating: 4,
    created_at: '2024-01-10'
  },
  {
    id: '4',
    content: 'Excellent communication throughout the project. They understood our requirements perfectly and delivered a solution that fits our business needs exactly.',
    author_name: 'John Smith',
    author_title: 'Operations Manager',
    company_name: 'Tech Solutions Inc',
    rating: 5,
    created_at: '2024-01-05'
  },
  {
    id: '5',
    content: 'The team\'s expertise in Laravel and Vue.js is impressive. They built us a robust inventory management system that has streamlined our operations significantly.',
    author_name: 'Lisa Chen',
    author_title: 'Founder',
    company_name: 'Retail Plus',
    rating: 4,
    created_at: '2023-12-20'
  },
  {
    id: '6',
    content: 'Outstanding work quality and great support even after project completion. They continue to help us with updates and maintenance. True professionals!',
    author_name: 'Michael Brown',
    author_title: 'IT Director',
    company_name: 'Manufacturing Corp',
    rating: 5,
    created_at: '2023-12-15'
  }
]

// Compute display testimonials
const displayTestimonials = computed(() => {
  let testimonials: Testimonial[] = []
  
  if (props.content.source === 'custom' && props.content.testimonials) {
    testimonials = props.content.testimonials
  } else if (props.content.source === 'erp') {
    testimonials = erpTestimonials.value
  } else {
    // Mixed: combine custom and ERP testimonials
    testimonials = [
      ...(props.content.testimonials || []),
      ...erpTestimonials.value
    ]
  }
  
  // Apply limit
  if (props.content.limit && props.content.limit > 0) {
    testimonials = testimonials.slice(0, props.content.limit)
  }
  
  return testimonials
})

// Helper functions
const getInitials = (name: string) => {
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Initialize
onMounted(() => {
  fetchERPTestimonials()
})
</script>

<style scoped>
blockquote {
  position: relative;
}

blockquote::before {
  content: '';
  position: absolute;
  left: -1rem;
  top: 0;
  bottom: 0;
  width: 4px;
  background: linear-gradient(to bottom, var(--primary-color, #3b82f6), var(--accent-color, #60a5fa));
  border-radius: 2px;
}
</style>