<template>
  <div class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">
          {{ content.heading || 'Meet The Team' }}
        </h2>
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
          class="text-center"
        >
          <!-- Photo -->
          <div class="mb-4">
            <div class="w-32 h-32 mx-auto rounded-full overflow-hidden bg-gray-200">
              <img
                v-if="member.photo"
                :src="member.photo"
                :alt="member.full_name"
                class="w-full h-full object-cover"
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
          <p
            v-if="content.show_role && member.position"
            class="text-sm text-blue-600 mb-2"
          >
            {{ member.position }}
          </p>
          
          <!-- Department -->
          <p
            v-if="member.department"
            class="text-sm text-gray-500 mb-3"
          >
            {{ member.department.name }}
          </p>
          
          <!-- Bio -->
          <p
            v-if="content.show_bio && member.bio"
            class="text-sm text-gray-600 line-clamp-3"
          >
            {{ member.bio }}
          </p>
          
          <!-- Social Links -->
          <div
            v-if="member.social_links"
            class="flex justify-center space-x-3 mt-4"
          >
            <a
              v-for="(link, platform) in member.social_links"
              :key="platform"
              :href="link"
              target="_blank"
              class="text-gray-400 hover:text-gray-600"
              :class="{ 'pointer-events-none': isEditing }"
            >
              <span class="sr-only">{{ platform }}</span>
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd" />
              </svg>
            </a>
          </div>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-else-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600">Loading team members...</p>
      </div>
      
      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No team members found</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ isEditing ? 'Team members will appear here when available' : 'Check back later' }}
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

const teamMembers = ref([])
const loading = ref(false)

const fetchTeamMembers = async () => {
  if (props.isEditing) {
    // In editing mode, show mock data
    teamMembers.value = [
      {
        id: 1,
        first_name: 'John',
        last_name: 'Doe',
        full_name: 'John Doe',
        position: 'CEO & Founder',
        department: { name: 'Executive' },
        photo: null,
        bio: 'Experienced leader with 15+ years in the industry.',
        social_links: {
          linkedin: 'https://linkedin.com/in/johndoe',
          twitter: 'https://twitter.com/johndoe'
        }
      },
      {
        id: 2,
        first_name: 'Jane',
        last_name: 'Smith',
        full_name: 'Jane Smith',
        position: 'CTO',
        department: { name: 'Technology' },
        photo: null,
        bio: 'Tech enthusiast and innovation driver.',
        social_links: {
          linkedin: 'https://linkedin.com/in/janesmith'
        }
      }
    ]
    return
  }
  
  loading.value = true
  
  try {
    const params = {
      limit: props.content.limit || 12,
      show_on_website: true,
      sort_by: 'first_name',
      sort_order: 'asc'
    }
    
    if (props.content.department_id) {
      params.department_id = props.content.department_id
    }
    
    const response = await axios.get('/api/v1/cms/erp/team', { params })
    teamMembers.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching team members:', error)
    teamMembers.value = []
  } finally {
    loading.value = false
  }
}

// Watch for content changes
watch(() => props.content, fetchTeamMembers, { deep: true })

onMounted(() => {
  fetchTeamMembers()
})
</script>