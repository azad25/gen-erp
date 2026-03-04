<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Lead Scoring & Qualification</h1>
          <p class="mt-1 text-sm text-gray-600">
            Analyze and score leads based on qualification criteria
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="showScoringRules = true"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Scoring Rules
          </button>
          <button
            @click="bulkScore"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Bulk Score
          </button>
        </div>
      </div>
    </div>

    <!-- Scoring Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <FireIcon class="h-8 w-8 text-red-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.hot_leads }}</div>
            <div class="text-sm text-gray-600">Hot Leads (80-100)</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <SunIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.warm_leads }}</div>
            <div class="text-sm text-gray-600">Warm Leads (50-79)</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CloudIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.cold_leads }}</div>
            <div class="text-sm text-gray-600">Cold Leads (0-49)</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ChartBarIcon class="h-8 w-8 text-purple-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.average_score }}</div>
            <div class="text-sm text-gray-600">Average Score</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Score Range</label>
          <select v-model="filters.score_range" @change="fetchLeads" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Scores</option>
            <option value="hot">Hot (80-100)</option>
            <option value="warm">Warm (50-79)</option>
            <option value="cold">Cold (0-49)</option>
            <option value="unscored">Unscored (0)</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="filters.status" @change="fetchLeads" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Statuses</option>
            <option value="new">New</option>
            <option value="contacted">Contacted</option>
            <option value="qualified">Qualified</option>
            <option value="unqualified">Unqualified</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Source</label>
          <select v-model="filters.source" @change="fetchLeads" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Sources</option>
            <option value="website">Website</option>
            <option value="social_media">Social Media</option>
            <option value="referral">Referral</option>
            <option value="advertisement">Advertisement</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Assigned To</label>
          <select v-model="filters.assigned_to" @change="fetchLeads" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Users</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
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

    <!-- Leads Scoring Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">Lead Scoring</h3>
          <div class="flex items-center space-x-2">
            <input
              v-model="searchQuery"
              @input="debounceSearch"
              type="text"
              placeholder="Search leads..."
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
                  :checked="selectedLeads.length === leads.length && leads.length > 0"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Lead
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Score
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Qualification
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Source
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Expected Value
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Last Activity
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="lead in leads" :key="lead.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <input
                  type="checkbox"
                  :value="lead.id"
                  v-model="selectedLeads"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                      <span class="text-sm font-medium text-gray-700">
                        {{ getInitials(lead.name) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ lead.name }}</div>
                    <div class="text-sm text-gray-500">{{ lead.email }}</div>
                    <div class="text-sm text-gray-500">{{ lead.company_name || 'No company' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-1 bg-gray-200 rounded-full h-3 mr-3">
                    <div
                      :class="getScoreBarClass(lead.score)"
                      class="h-3 rounded-full transition-all duration-300"
                      :style="{ width: `${lead.score}%` }"
                    ></div>
                  </div>
                  <span class="text-sm font-bold text-gray-900 min-w-[3rem]">{{ lead.score }}/100</span>
                </div>
                <div class="mt-1">
                  <span :class="getScoreLabelClass(lead.score)" class="px-2 py-1 text-xs font-semibold rounded-full">
                    {{ getScoreLabel(lead.score) }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(lead.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ formatStatus(lead.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatSource(lead.source) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ lead.expected_value ? `৳${formatNumber(lead.expected_value)}` : '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ lead.last_activity_at ? formatRelativeTime(lead.last_activity_at) : 'No activity' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <button
                    @click="scoreLead(lead)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    Score
                  </button>
                  <button
                    @click="qualifyLead(lead)"
                    v-if="lead.status !== 'qualified'"
                    class="text-green-600 hover:text-green-900"
                  >
                    Qualify
                  </button>
                  <button
                    @click="viewDetails(lead)"
                    class="text-purple-600 hover:text-purple-900"
                  >
                    Details
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Bulk Actions -->
      <div v-if="selectedLeads.length > 0" class="bg-gray-50 px-6 py-3 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-700">{{ selectedLeads.length }} leads selected</span>
          <div class="flex space-x-2">
            <button
              @click="bulkScore"
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm"
            >
              Bulk Score
            </button>
            <button
              @click="bulkQualify"
              class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
            >
              Bulk Qualify
            </button>
            <button
              @click="bulkAssign"
              class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-sm"
            >
              Assign
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

    <!-- Scoring Rules Modal -->
    <div v-if="showScoringRules" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Lead Scoring Rules</h3>
            <button @click="showScoringRules = false" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>
          
          <div class="space-y-4">
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-medium text-gray-900 mb-2">Demographic Information</h4>
              <ul class="text-sm text-gray-600 space-y-1">
                <li>• Job Title (Decision Maker): +20 points</li>
                <li>• Company Size (50+ employees): +15 points</li>
                <li>• Industry Match: +10 points</li>
                <li>• Location (Target Market): +5 points</li>
              </ul>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-medium text-gray-900 mb-2">Behavioral Scoring</h4>
              <ul class="text-sm text-gray-600 space-y-1">
                <li>• Website Visit: +5 points</li>
                <li>• Email Opened: +3 points</li>
                <li>• Email Clicked: +7 points</li>
                <li>• Form Submitted: +15 points</li>
                <li>• Downloaded Content: +10 points</li>
              </ul>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-medium text-gray-900 mb-2">Engagement Level</h4>
              <ul class="text-sm text-gray-600 space-y-1">
                <li>• Responded to Outreach: +20 points</li>
                <li>• Attended Demo/Meeting: +25 points</li>
                <li>• Requested Quote: +30 points</li>
                <li>• Multiple Touchpoints: +10 points</li>
              </ul>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-medium text-gray-900 mb-2">Negative Scoring</h4>
              <ul class="text-sm text-gray-600 space-y-1">
                <li>• Unsubscribed: -20 points</li>
                <li>• Bounced Email: -10 points</li>
                <li>• No Response (30+ days): -5 points</li>
                <li>• Wrong Contact Info: -15 points</li>
              </ul>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end">
            <button
              @click="showScoringRules = false"
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import {
  FireIcon,
  SunIcon,
  CloudIcon,
  ChartBarIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'

const { showToast } = useToast()

// Reactive data
const leads = ref([])
const users = ref([])
const pagination = ref({})
const stats = ref({
  hot_leads: 0,
  warm_leads: 0,
  cold_leads: 0,
  average_score: 0
})

const selectedLeads = ref([])
const searchQuery = ref('')
const searchTimeout = ref(null)
const showScoringRules = ref(false)

const filters = reactive({
  score_range: '',
  status: '',
  source: '',
  assigned_to: ''
})

// Methods
const fetchLeads = async (page = 1) => {
  try {
    const params = new URLSearchParams({
      page,
      per_page: 15,
      search: searchQuery.value,
      ...filters
    })
    
    const response = await fetch(`/api/v1/crm/leads?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      leads.value = data.data
      pagination.value = data.meta
    }
  } catch (error) {
    console.error('Failed to fetch leads:', error)
    showToast('Failed to load leads', 'error')
  }
}

const fetchStats = async () => {
  try {
    const response = await fetch('/api/v1/crm/leads/scoring-statistics', {
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
    fetchLeads()
  }, 300)
}

const clearFilters = () => {
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  searchQuery.value = ''
  fetchLeads()
}

const toggleSelectAll = () => {
  if (selectedLeads.value.length === leads.value.length) {
    selectedLeads.value = []
  } else {
    selectedLeads.value = leads.value.map(lead => lead.id)
  }
}

const scoreLead = async (lead) => {
  try {
    const response = await fetch(`/api/v1/crm/leads/${lead.uuid}/score`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      showToast(`Lead scored: ${data.data.score}/100`, 'success')
      fetchLeads()
      fetchStats()
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to score lead', 'error')
    }
  } catch (error) {
    console.error('Failed to score lead:', error)
    showToast('Failed to score lead', 'error')
  }
}

const qualifyLead = async (lead) => {
  if (!confirm('Mark this lead as qualified?')) return
  
  try {
    const response = await fetch(`/api/v1/crm/leads/${lead.uuid}/qualify`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      showToast('Lead qualified successfully', 'success')
      fetchLeads()
      fetchStats()
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to qualify lead', 'error')
    }
  } catch (error) {
    console.error('Failed to qualify lead:', error)
    showToast('Failed to qualify lead', 'error')
  }
}

const viewDetails = (lead) => {
  window.open(`/crm/leads/${lead.uuid}`, '_blank')
}

const bulkScore = async () => {
  if (selectedLeads.value.length === 0) {
    showToast('Please select leads to score', 'warning')
    return
  }
  
  if (!confirm(`Score ${selectedLeads.value.length} selected leads?`)) return
  
  try {
    const response = await fetch('/api/v1/crm/leads/bulk-score', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        lead_ids: selectedLeads.value
      })
    })
    
    if (response.ok) {
      showToast('Leads scored successfully', 'success')
      selectedLeads.value = []
      fetchLeads()
      fetchStats()
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to score leads', 'error')
    }
  } catch (error) {
    console.error('Failed to bulk score leads:', error)
    showToast('Failed to score leads', 'error')
  }
}

const bulkQualify = async () => {
  if (selectedLeads.value.length === 0) {
    showToast('Please select leads to qualify', 'warning')
    return
  }
  
  if (!confirm(`Qualify ${selectedLeads.value.length} selected leads?`)) return
  
  try {
    const response = await fetch('/api/v1/crm/leads/bulk-qualify', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        lead_ids: selectedLeads.value
      })
    })
    
    if (response.ok) {
      showToast('Leads qualified successfully', 'success')
      selectedLeads.value = []
      fetchLeads()
      fetchStats()
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to qualify leads', 'error')
    }
  } catch (error) {
    console.error('Failed to bulk qualify leads:', error)
    showToast('Failed to qualify leads', 'error')
  }
}

const bulkAssign = () => {
  showToast('Bulk assign feature coming soon', 'info')
}

const changePage = (url) => {
  if (!url) return
  const page = new URL(url).searchParams.get('page')
  fetchLeads(page)
}

// Utility functions
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getScoreBarClass = (score) => {
  if (score >= 80) return 'bg-red-500'
  if (score >= 50) return 'bg-yellow-500'
  return 'bg-blue-500'
}

const getScoreLabelClass = (score) => {
  if (score >= 80) return 'bg-red-100 text-red-800'
  if (score >= 50) return 'bg-yellow-100 text-yellow-800'
  return 'bg-blue-100 text-blue-800'
}

const getScoreLabel = (score) => {
  if (score >= 80) return 'Hot'
  if (score >= 50) return 'Warm'
  if (score > 0) return 'Cold'
  return 'Unscored'
}

const getStatusBadgeClass = (status) => {
  const classes = {
    new: 'bg-blue-100 text-blue-800',
    contacted: 'bg-yellow-100 text-yellow-800',
    qualified: 'bg-green-100 text-green-800',
    unqualified: 'bg-red-100 text-red-800',
    converted: 'bg-purple-100 text-purple-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatSource = (source) => {
  return source.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatNumber = (num) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num?.toLocaleString() || '0'
}

const formatRelativeTime = (date) => {
  const now = new Date()
  const past = new Date(date)
  const diffInHours = Math.floor((now - past) / (1000 * 60 * 60))
  
  if (diffInHours < 1) return 'Just now'
  if (diffInHours < 24) return `${diffInHours}h ago`
  if (diffInHours < 168) return `${Math.floor(diffInHours / 24)}d ago`
  return `${Math.floor(diffInHours / 168)}w ago`
}

// Lifecycle
onMounted(() => {
  fetchLeads()
  fetchStats()
  fetchUsers()
})
</script>