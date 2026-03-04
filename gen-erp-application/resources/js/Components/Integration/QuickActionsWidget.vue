<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <h3 class="text-sm font-medium text-gray-900">Quick Actions</h3>
    </div>

    <!-- Actions Grid -->
    <div class="p-4">
      <div class="grid grid-cols-2 gap-3">
        <!-- CRM Actions -->
        <button
          @click="createLead"
          class="flex items-center space-x-2 p-3 text-left border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors"
        >
          <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
            <UserPlusIcon class="h-4 w-4 text-white" />
          </div>
          <div>
            <div class="text-sm font-medium text-gray-900">New Lead</div>
            <div class="text-xs text-gray-500">Add prospect</div>
          </div>
        </button>

        <button
          @click="createOpportunity"
          class="flex items-center space-x-2 p-3 text-left border border-gray-200 rounded-lg hover:border-green-300 hover:bg-green-50 transition-colors"
        >
          <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
            <BriefcaseIcon class="h-4 w-4 text-white" />
          </div>
          <div>
            <div class="text-sm font-medium text-gray-900">New Opportunity</div>
            <div class="text-xs text-gray-500">Track deal</div>
          </div>
        </button>

        <!-- Logistics Actions -->
        <button
          @click="createShipment"
          class="flex items-center space-x-2 p-3 text-left border border-gray-200 rounded-lg hover:border-purple-300 hover:bg-purple-50 transition-colors"
        >
          <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center">
            <TruckIcon class="h-4 w-4 text-white" />
          </div>
          <div>
            <div class="text-sm font-medium text-gray-900">New Shipment</div>
            <div class="text-xs text-gray-500">Ship order</div>
          </div>
        </button>

        <button
          @click="trackShipment"
          class="flex items-center space-x-2 p-3 text-left border border-gray-200 rounded-lg hover:border-orange-300 hover:bg-orange-50 transition-colors"
        >
          <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center">
            <MagnifyingGlassIcon class="h-4 w-4 text-white" />
          </div>
          <div>
            <div class="text-sm font-medium text-gray-900">Track Shipment</div>
            <div class="text-xs text-gray-500">Check status</div>
          </div>
        </button>

        <!-- Project Actions -->
        <button
          @click="createProject"
          class="flex items-center space-x-2 p-3 text-left border border-gray-200 rounded-lg hover:border-teal-300 hover:bg-teal-50 transition-colors"
        >
          <div class="w-8 h-8 bg-teal-500 rounded-lg flex items-center justify-center">
            <FolderPlusIcon class="h-4 w-4 text-white" />
          </div>
          <div>
            <div class="text-sm font-medium text-gray-900">New Project</div>
            <div class="text-xs text-gray-500">Start project</div>
          </div>
        </button>

        <button
          @click="createTask"
          class="flex items-center space-x-2 p-3 text-left border border-gray-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition-colors"
        >
          <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center">
            <PlusIcon class="h-4 w-4 text-white" />
          </div>
          <div>
            <div class="text-sm font-medium text-gray-900">New Task</div>
            <div class="text-xs text-gray-500">Add to-do</div>
          </div>
        </button>
      </div>

      <!-- Quick Search -->
      <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Quick search across all domains..."
            class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
            @keydown.enter="performSearch"
          />
        </div>
        
        <!-- Search Results -->
        <div v-if="searchResults.length > 0" class="mt-2 max-h-48 overflow-y-auto">
          <div class="space-y-1">
            <button
              v-for="result in searchResults"
              :key="`${result.type}-${result.id}`"
              @click="navigateToResult(result)"
              class="w-full text-left p-2 hover:bg-gray-50 rounded text-sm"
            >
              <div class="flex items-center justify-between">
                <span class="font-medium">{{ result.title }}</span>
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="getDomainColor(result.domain)"
                >
                  {{ result.domain }}
                </span>
              </div>
              <div class="text-xs text-gray-500 mt-1">{{ result.description }}</div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modals -->
  <TrackingModal
    v-if="showTrackingModal"
    @close="showTrackingModal = false"
    @track="handleTrackShipment"
  />
</template>

<script setup>
import { ref } from 'vue'
import {
  UserPlusIcon,
  BriefcaseIcon,
  TruckIcon,
  MagnifyingGlassIcon,
  FolderPlusIcon,
  PlusIcon
} from '@heroicons/vue/24/outline'
import { useRouter } from 'vue-router'
import { useApi } from '@/Composables/useApi'
import TrackingModal from './TrackingModal.vue'

const router = useRouter()
const { get } = useApi()

// Reactive data
const searchQuery = ref('')
const searchResults = ref([])
const showTrackingModal = ref(false)

// Methods
const createLead = () => {
  router.push('/crm/leads/create')
}

const createOpportunity = () => {
  router.push('/crm/opportunities/create')
}

const createShipment = () => {
  router.push('/logistics/shipments/create')
}

const trackShipment = () => {
  showTrackingModal.value = true
}

const createProject = () => {
  router.push('/projects/create')
}

const createTask = () => {
  router.push('/projects/tasks/create')
}

const performSearch = async () => {
  if (!searchQuery.value.trim()) {
    searchResults.value = []
    return
  }

  try {
    const response = await get('/api/v1/integration/search', {
      q: searchQuery.value,
      limit: 10
    })
    searchResults.value = response.data
  } catch (err) {
    console.error('Search failed:', err)
    searchResults.value = []
  }
}

const navigateToResult = (result) => {
  const routes = {
    'lead': `/crm/leads/${result.id}`,
    'opportunity': `/crm/opportunities/${result.id}`,
    'shipment': `/logistics/shipments/${result.id}`,
    'project': `/projects/${result.id}`,
    'task': `/projects/tasks/${result.id}`
  }
  
  const route = routes[result.type]
  if (route) {
    router.push(route)
    searchQuery.value = ''
    searchResults.value = []
  }
}

const handleTrackShipment = (trackingNumber) => {
  router.push(`/logistics/tracking/${trackingNumber}`)
  showTrackingModal.value = false
}

const getDomainColor = (domain) => {
  const colors = {
    'CRM': 'bg-blue-100 text-blue-800',
    'Logistics': 'bg-purple-100 text-purple-800',
    'Projects': 'bg-teal-100 text-teal-800'
  }
  return colors[domain] || 'bg-gray-100 text-gray-800'
}
</script>