<template>
  <section class="py-16" :style="{ backgroundColor: content.background_color || 'transparent' }">
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
      <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-8">
        <div 
          v-for="i in 4" 
          :key="i" 
          class="text-center animate-pulse"
        >
          <div class="h-16 w-16 bg-gray-300 rounded-full mx-auto mb-4"></div>
          <div class="h-8 bg-gray-300 rounded mb-2"></div>
          <div class="h-4 bg-gray-300 rounded w-3/4 mx-auto"></div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-8">
        <div 
          v-for="stat in displayStats" 
          :key="stat.key"
          class="text-center group"
        >
          <!-- Icon -->
          <div 
            v-if="stat.icon"
            class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center transition-transform duration-300 group-hover:scale-110"
            :style="{ backgroundColor: stat.color || tenant?.settings?.primary_color || '#3b82f6' }"
          >
            <component 
              :is="getIconComponent(stat.icon)" 
              class="w-8 h-8 text-white"
            />
          </div>
          
          <!-- Number -->
          <div 
            class="text-4xl md:text-5xl font-bold mb-2 transition-colors duration-300"
            :style="{ color: stat.color || tenant?.settings?.primary_color || '#1f2937' }"
          >
            <CountUp 
              :end-val="stat.value" 
              :duration="2"
              :suffix="stat.suffix || ''"
              :prefix="stat.prefix || ''"
            />
          </div>
          
          <!-- Label -->
          <p class="text-lg font-medium text-gray-700">
            {{ stat.label }}
          </p>
          
          <!-- Description -->
          <p v-if="stat.description" class="text-sm text-gray-500 mt-1">
            {{ stat.description }}
          </p>
        </div>
      </div>

      <!-- Additional Content -->
      <div v-if="content.description" class="text-center mt-12">
        <p class="text-lg text-gray-600 max-w-4xl mx-auto">
          {{ content.description }}
        </p>
      </div>
    </div>
  </section>
</template>

<script setup>
interface Stat {
  key: string
  label: string
  value: number
  icon?: string
  color?: string
  suffix?: string
  prefix?: string
  description?: string
}

interface Content {
  title?: string
  subtitle?: string
  description?: string
  background_color?: string
  title_color?: string
  stats?: Stat[]
  show_company_stats?: boolean
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
const companyStats = ref<Record<string, number>>({})
const loading = ref(true)

// Fetch company statistics if enabled
const fetchCompanyStats = async () => {
  if (!props.content.show_company_stats) {
    loading.value = false
    return
  }

  try {
    loading.value = true
    
    const response = await $fetch('/api/v1/cms/erp/stats', {
      headers: {
        'X-Tenant-ID': props.tenant?.id || '',
        'X-Tenant-Slug': props.tenant?.slug || ''
      }
    })
    
    companyStats.value = response.data || {}
  } catch (err) {
    console.error('Failed to fetch company stats:', err)
    // Use mock data in development
    if (process.dev) {
      companyStats.value = getMockCompanyStats()
    }
  } finally {
    loading.value = false
  }
}

// Mock company stats for development
const getMockCompanyStats = () => ({
  total_products: 150,
  total_employees: 25,
  total_projects: 45,
  total_customers: 320,
  years_experience: 8,
  satisfied_clients: 98
})

// Compute display stats
const displayStats = computed(() => {
  if (props.content.stats && props.content.stats.length > 0) {
    return props.content.stats
  }

  // Default company stats
  const stats: Stat[] = []
  
  if (companyStats.value.total_products) {
    stats.push({
      key: 'products',
      label: 'Products',
      value: companyStats.value.total_products,
      icon: 'package',
      suffix: '+'
    })
  }
  
  if (companyStats.value.total_employees) {
    stats.push({
      key: 'employees',
      label: 'Team Members',
      value: companyStats.value.total_employees,
      icon: 'users'
    })
  }
  
  if (companyStats.value.total_projects) {
    stats.push({
      key: 'projects',
      label: 'Projects Completed',
      value: companyStats.value.total_projects,
      icon: 'briefcase',
      suffix: '+'
    })
  }
  
  if (companyStats.value.total_customers) {
    stats.push({
      key: 'customers',
      label: 'Happy Customers',
      value: companyStats.value.total_customers,
      icon: 'heart',
      suffix: '+'
    })
  }
  
  if (companyStats.value.years_experience) {
    stats.push({
      key: 'experience',
      label: 'Years Experience',
      value: companyStats.value.years_experience,
      icon: 'calendar',
      suffix: '+'
    })
  }
  
  if (companyStats.value.satisfied_clients) {
    stats.push({
      key: 'satisfaction',
      label: 'Client Satisfaction',
      value: companyStats.value.satisfied_clients,
      icon: 'star',
      suffix: '%'
    })
  }

  return stats.slice(0, 4) // Show max 4 stats
})

// Icon components mapping
const getIconComponent = (iconName: string) => {
  const icons = {
    package: 'IconPackage',
    users: 'IconUsers',
    briefcase: 'IconBriefcase',
    heart: 'IconHeart',
    calendar: 'IconCalendar',
    star: 'IconStar',
    chart: 'IconChart',
    trophy: 'IconTrophy',
    target: 'IconTarget',
    clock: 'IconClock'
  }
  
  return icons[iconName as keyof typeof icons] || 'IconStar'
}

// Initialize
onMounted(() => {
  fetchCompanyStats()
})
</script>

<script>
// Icon components
const IconPackage = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
    </svg>
  `
}

const IconUsers = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a4 4 0 11-8 0 4 4 0 018 0z"/>
    </svg>
  `
}

const IconBriefcase = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m8 0H8m8 0v6a2 2 0 01-2 2H10a2 2 0 01-2-2V6m8 0V4a2 2 0 00-2-2H10a2 2 0 00-2 2v2"/>
    </svg>
  `
}

const IconHeart = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
    </svg>
  `
}

const IconCalendar = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
  `
}

const IconStar = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
    </svg>
  `
}

const IconChart = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>
  `
}

const IconTrophy = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
    </svg>
  `
}

const IconTarget = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
  `
}

const IconClock = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
  `
}

// Simple CountUp component
const CountUp = {
  props: {
    endVal: { type: Number, required: true },
    duration: { type: Number, default: 2 },
    suffix: { type: String, default: '' },
    prefix: { type: String, default: '' }
  },
  data() {
    return {
      displayValue: 0
    }
  },
  mounted() {
    this.animateValue()
  },
  methods: {
    animateValue() {
      const startTime = Date.now()
      const startValue = 0
      const endValue = this.endVal
      const duration = this.duration * 1000

      const animate = () => {
        const now = Date.now()
        const elapsed = now - startTime
        const progress = Math.min(elapsed / duration, 1)
        
        // Easing function
        const easeOutQuart = 1 - Math.pow(1 - progress, 4)
        
        this.displayValue = Math.floor(startValue + (endValue - startValue) * easeOutQuart)
        
        if (progress < 1) {
          requestAnimationFrame(animate)
        } else {
          this.displayValue = endValue
        }
      }
      
      animate()
    }
  },
  template: `<span>{{ prefix }}{{ displayValue.toLocaleString() }}{{ suffix }}</span>`
}
</script>