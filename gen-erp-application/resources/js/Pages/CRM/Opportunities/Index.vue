<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Opportunity Management</h1>
          <p class="mt-1 text-sm text-gray-600">
            Track and manage sales opportunities through your pipeline
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="$router.push('/crm/opportunities/create')"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Create Opportunity
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ChartBarIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.total_opportunities }}</div>
            <div class="text-sm text-gray-600">Total Opportunities</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CurrencyDollarIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">৳{{ formatNumber(stats.total_value) }}</div>
            <div class="text-sm text-gray-600">Total Value</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">৳{{ formatNumber(stats.won_value) }}</div>
            <div class="text-sm text-gray-600">Won Value</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ClockIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">৳{{ formatNumber(stats.open_value) }}</div>
            <div class="text-sm text-gray-600">Open Value</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ArrowTrendingUpIcon class="h-8 w-8 text-purple-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.conversion_rate }}%</div>
            <div class="text-sm text-gray-600">Conversion Rate</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="filters.status" @change="fetchOpportunities" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Statuses</option>
            <option value="open">Open</option>
            <option value="won">Won</option>
            <option value="lost">Lost</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Pipeline</label>
          <select v-model="filters.pipeline_id" @change="fetchOpportunities" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Pipelines</option>
            <option v-for="pipeline in pipelines" :key="pipeline.id" :value="pipeline.id">
              {{ pipeline.name }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Assigned To</label>
          <select v-model="filters.assigned_to" @change="fetchOpportunities" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Users</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Value Range</label>
          <select v-model="filters.value_range" @change="fetchOpportunities" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Values</option>
            <option value="0-50000">৳0 - ৳50,000</option>
            <option value="50000-100000">৳50,000 - ৳1,00,000</option>
            <option value="100000-500000">৳1,00,000 - ৳5,00,000</option>
            <option value="500000+">৳5,00,000+</option>
          </select>
        </div>
        
        <div class="flex items-end">
          <button
            @click="clearFilters"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Clear Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Opportunities Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">Opportunities</h3>
          <div class="flex items-center space-x-2">
            <input
              v-model="searchQuery"
              @input="debounceSearch"
              type="text"
              placeholder="Search opportunities..."
              class="rounded-md border-gray-300 text-sm"
            />
          </div>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <input
                  type="checkbox"
                  @change="toggleSelectAll"
                  :checked="selectedOpportunities.length === opportunities.length && opportunities.length > 0"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Opportunity
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Lead/Contact
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Pipeline/Stage
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Value
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Probability
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Close Date
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="opportunity in opportunities" :key="opportunity.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <input
                  type="checkbox"
                  :value="opportunity.id"
                  v-model="selectedOpportunities"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ opportunity.name }}</div>
                <div class="text-sm text-gray-500">{{ opportunity.description }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="opportunity.lead" class="text-sm text-gray-900">
                  {{ opportunity.lead.name }}
                  <div class="text-xs text-gray-500">{{ opportunity.lead.company_name }}</div>
                </div>
                <div v-else class="text-sm text-gray-400">No lead</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ opportunity.pipeline?.name }}</div>
                <div class="text-sm text-gray-500">{{ opportunity.stage?.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">৳{{ formatNumber(opportunity.amount) }}</div>
                <div class="text-sm text-gray-500">{{ opportunity.currency }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                    <div
                      :class="getProbabilityBarClass(opportunity.probability)"
                      class="h-2 rounded-full transition-all duration-300"
                      :style="{ width: `${opportunity.probability}%` }"
                    ></div>
                  </div>
                  <span class="text-sm font-medium text-gray-900">{{ opportunity.probability }}%</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(opportunity.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ formatStatus(opportunity.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ opportunity.expected_close_date ? formatDate(opportunity.expected_close_date) : '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <button
                    @click="$router.push(`/crm/opportunities/${opportunity.uuid}`)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    View
                  </button>
                  <button
                    @click="$router.push(`/crm/opportunities/${opportunity.uuid}/edit`)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Edit
                  </button>
                  <button
                    v-if="opportunity.status === 'open'"
                    @click="markAsWon(opportunity)"
                    class="text-purple-600 hover:text-purple-900"
                  >
                    Mark Won
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Bulk Actions -->
      <div v-if="selectedOpportunities.length > 0" class="bg-gray-50 px-6 py-3 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-700">{{ selectedOpportunities.length }} opportunities selected</span>
          <div class="flex space-x-2">
            <button
              @click="bulkAssign"
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm"
            >
              Assign
            </button>
            <button
              @click="bulkMoveStage"
              class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
            >
              Move Stage
            </button>
            <button
              @click="bulkDelete"
              class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
      
      <!-- Pagination -->
      <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="page in pagination.links"
              :key="page.label"
              @click="changePage(page.url)"
              :disabled="!page.url"
              :class="[
                'px-3 py-2 text-sm font-medium rounded-md',
                page.active
                  ? 'bg-indigo-600 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
              ]"
              v-html="page.label"
            ></button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import {
  ChartBarIcon,
  CurrencyDollarIcon,
  CheckCircleIcon,
  ClockIcon,
  ArrowTrendingUpIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'

const { showToast } = useToast()

// Reactive data
const opportunities = ref([])
const pipelines = ref([])
const users = ref([])
const pagination = ref({})
const stats = ref({
  total_opportunities: 0,
  total_value: 0,
  won_value: 0,
  open_value: 0,
  conversion_rate: 0
})

const selectedOpportunities = ref([])
const searchQuery = ref('')
const searchTimeout = ref(null)

const filters = reactive({
  status: '',
  pipeline_id: '',
  assigned_to: '',
  value_range: ''
})

// Methods
const fetchOpportunities = async (page = 1) => {
  try {
    const params = new URLSearchParams({
      page,
      per_page: 15,
      search: searchQuery.value,
      ...filters
    })
    
    const response = await fetch(`/api/v1/crm/opportunities?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      opportunities.value = data.data
      pagination.value = data.meta
    }
  } catch (error) {
    console.error('Failed to fetch opportunities:', error)
    showToast('Failed to load opportunities', 'error')
  }
}

const fetchStats = async () => {
  try {
    const response = await fetch('/api/v1/crm/opportunities/statistics', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      stats.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch stats:', error)
  }
}

const fetchPipelines = async () => {
  try {
    const response = await fetch('/api/v1/crm/pipelines', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      pipelines.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch pipelines:', error)
  }
}

const fetchUsers = async () => {
  try {
    const response = await fetch('/api/v1/users', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      users.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch users:', error)
  }
}

const debounceSearch = () => {
  clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(() => {
    fetchOpportunities()
  }, 300)
}

const clearFilters = () => {
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  searchQuery.value = ''
  fetchOpportunities()
}

const toggleSelectAll = () => {
  if (selectedOpportunities.value.length === opportunities.value.length) {
    selectedOpportunities.value = []
  } else {
    selectedOpportunities.value = opportunities.value.map(opp => opp.id)
  }
}

const markAsWon = async (opportunity) => {
  if (!confirm('Mark this opportunity as won?')) return
  
  try {
    const response = await fetch(`/api/v1/crm/opportunities/${opportunity.uuid}/won`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      showToast('Opportunity marked as won', 'success')
      fetchOpportunities()
      fetchStats()
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to mark opportunity as won', 'error')
    }
  } catch (error) {
    console.error('Failed to mark opportunity as won:', error)
    showToast('Failed to mark opportunity as won', 'error')
  }
}

const bulkAssign = () => {
  // Implementation for bulk assign
  showToast('Bulk assign feature coming soon', 'info')
}

const bulkMoveStage = () => {
  // Implementation for bulk move stage
  showToast('Bulk move stage feature coming soon', 'info')
}

const bulkDelete = () => {
  if (!confirm('Are you sure you want to delete selected opportunities?')) return
  showToast('Bulk delete feature coming soon', 'info')
}

const changePage = (url) => {
  if (!url) return
  const page = new URL(url).searchParams.get('page')
  fetchOpportunities(page)
}

// Utility functions
const getStatusBadgeClass = (status) => {
  const classes = {
    open: 'bg-blue-100 text-blue-800',
    won: 'bg-green-100 text-green-800',
    lost: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getProbabilityBarClass = (probability) => {
  if (probability >= 80) return 'bg-green-500'
  if (probability >= 50) return 'bg-yellow-500'
  return 'bg-red-500'
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatNumber = (num) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num?.toLocaleString() || '0'
}

// Lifecycle
onMounted(() => {
  fetchOpportunities()
  fetchStats()
  fetchPipelines()
  fetchUsers()
})
</script>