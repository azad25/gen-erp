<template>
  <section class="section-padding bg-white">
    <div class="container-custom">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">
          {{ content.heading || 'Meet The Team' }}
        </h2>
        <p v-if="content.subheading" class="text-lg text-gray-600">
          {{ content.subheading }}
        </p>
      </div>
      
      <!-- Team Grid -->
      <div
        v-if="teamMembers.length > 0"
        :class="{
          'grid-cols-2': content.layout === '2-col',
          'grid-cols-3': content.layout === '3-col',
          'grid-cols-4': content.layout === '4-col'
        }"
        class="grid gap-8"
      >
        <div
          v-for="member in teamMembers"
          :key="member.id"
          class="text-center group"
        >
          <!-- Photo -->
          <div class="mb-4">
            <div class="w-32 h-32 mx-auto rounded-full overflow-hidden bg-gray-200 group-hover:shadow-lg transition-shadow duration-200">
              <NuxtImg
                v-if="member.photo"
                :src="member.photo"
                :alt="member.full_name"
                class="w-full h-full object-cover"
                loading="lazy"
                format="webp"
              />
              <div
                v-else
                class="w-full h-full bg-gray-300 flex items-center justify-center"
              >
                <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>
          </div>
          
          <!-- Name -->
          <h3 class="text-lg font-semibold text-gray-900 mb-1">
            {{ member.full_name || `${member.first_name} ${member.last_name}` }}
          </h3>
          
          <!-- Position -->
          <p class="text-blue-600 font-medium mb-2">
            {{ member.position || member.job_title }}
          </p>
          
          <!-- Department -->
          <p v-if="member.department" class="text-sm text-gray-500 mb-3">
            {{ member.department.name }}
          </p>
          
          <!-- Bio -->
          <p v-if="member.bio && content.show_bio" class="text-sm text-gray-600 mb-4 line-clamp-3">
            {{ member.bio }}
          </p>
          
          <!-- Social Links -->
          <div v-if="member.social_links && content.show_social" class="flex justify-center space-x-3">
            <a
              v-if="member.social_links.linkedin"
              :href="member.social_links.linkedin"
              target="_blank"
              rel="noopener noreferrer"
              class="text-gray-400 hover:text-blue-600 transition-colors"
            >
              <span class="sr-only">LinkedIn</span>
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
              </svg>
            </a>
            
            <a
              v-if="member.social_links.twitter"
              :href="member.social_links.twitter"
              target="_blank"
              rel="noopener noreferrer"
              class="text-gray-400 hover:text-blue-400 transition-colors"
            >
              <span class="sr-only">Twitter</span>
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
              </svg>
            </a>
            
            <a
              v-if="member.social_links.github"
              :href="member.social_links.github"
              target="_blank"
              rel="noopener noreferrer"
              class="text-gray-400 hover:text-gray-900 transition-colors"
            >
              <span class="sr-only">GitHub</span>
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
              </svg>
            </a>
            
            <a
              v-if="member.social_links.email"
              :href="`mailto:${member.social_links.email}`"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <span class="sr-only">Email</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </a>
          </div>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-else-if="loading" class="text-center py-12">
        <div class="spinner w-8 h-8 mx-auto mb-4"></div>
        <p class="text-gray-600">Loading team members...</p>
      </div>
      
      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No team members found</h3>
        <p class="mt-1 text-sm text-gray-500">Check back later</p>
      </div>
    </div>
  </section>
</template>

<script setup>
interface TeamGridContent {
  heading?: string
  subheading?: string
  layout?: '2-col' | '3-col' | '4-col'
  limit?: number
  department_id?: string
  show_bio?: boolean
  show_social?: boolean
}

interface TeamMember {
  id: string
  first_name: string
  last_name: string
  full_name?: string
  position?: string
  job_title?: string
  department?: {
    name: string
  }
  photo?: string
  bio?: string
  social_links?: {
    linkedin?: string
    twitter?: string
    github?: string
    email?: string
  }
}

const props = defineProps<{
  content: TeamGridContent
  tenant?: any
}>()

const config = useRuntimeConfig()
const teamMembers = ref<TeamMember[]>([])
const loading = ref(true)

const fetchTeamMembers = async () => {
  try {
    loading.value = true
    
    const params = new URLSearchParams({
      limit: (props.content.limit || 12).toString(),
      show_on_website: 'true',
      sort_by: 'first_name',
      sort_order: 'asc'
    })
    
    if (props.content.department_id) {
      params.append('department_id', props.content.department_id)
    }
    
    const headers: Record<string, string> = {}
    if (props.tenant?.id) {
      headers['X-Tenant-ID'] = props.tenant.id
    }
    
    const response = await $fetch<{ data: TeamMember[] }>(`${config.public.apiBase}/public/team?${params}`, {
      headers
    })
    
    teamMembers.value = response.data || []
  } catch (error) {
    console.error('Error fetching team members:', error)
    teamMembers.value = []
  } finally {
    loading.value = false
  }
}

// Fetch team members on mount
onMounted(() => {
  fetchTeamMembers()
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