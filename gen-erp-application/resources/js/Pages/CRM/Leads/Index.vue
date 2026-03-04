<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Lead Management</h1>
          <p class="mt-1 text-sm text-gray-600">
            Manage and track your sales leads through the qualification process
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="showImportModal = true"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Import Leads
          </button>
          <button
            @click="showCreateModal = true"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Add Lead
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <UserPlusIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.total_leads }}</div>
            <div class="text-sm text-gray-600">Total Leads</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ClockIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.new_leads }}</div>
            <div class="text-sm text-gray-600">New Leads</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.qualified_leads }}</div>
            <div class="text-sm text-gray-600">Qualified</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ArrowTrendingUpIcon class="h-8 w-8 text-purple-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.converted_leads }}</div>
            <div class="text-sm text-gray-600">Converted</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ChartBarIcon class="h-8 w-8 text-indigo-600" />
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
          <select v-model="filters.status" @change="fetchLeads" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Statuses</option>
            <option value="new">New</option>
            <option value="contacted">Contacted</option>
            <option value="qualified">Qualified</option>
            <option value="unqualified">Unqualified</option>
            <option value="converted">Converted</option>
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
            <option value="cold_call">Cold Call</option>
            <option value="trade_show">Trade Show</option>
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
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Score Range</label>
          <select v-model="filters.score_range" @change="fetchLeads" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Scores</option>
            <option value="hot">Hot (80-100)</option>
            <option value="warm">Warm (50-79)</option>
            <option value="cold">Cold (0-49)</option>
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

    <!-- Leads Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">Leads</h3>
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
                Company
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Score
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Source
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Assigned To
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
                    <div class="text-sm text-gray-500">{{ lead.phone }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ lead.company_name || '-' }}</div>
                <div class="text-sm text-gray-500">{{ lead.job_title || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(lead.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ formatStatus(lead.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                    <div
                      :class="getScoreBarClass(lead.score)"
                      class="h-2 rounded-full transition-all duration-300"
                      :style="{ width: `${lead.score}%` }"
                    ></div>
                  </div>
                  <span class="text-sm font-medium text-gray-900">{{ lead.score }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatSource(lead.source) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ lead.assigned_user?.name || 'Unassigned' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ lead.last_activity_at ? formatRelativeTime(lead.last_activity_at) : 'No activity' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <button
                    @click="viewLead(lead)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    View
                  </button>
                  <button
                    @click="editLead(lead)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Edit
                  </button>
                  <button
                    v-if="lead.status !== 'converted'"
                    @click="convertLead(lead)"
                    class="text-purple-600 hover:text-purple-900"
                  >
                    Convert
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
              @click="bulkAssign"
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm"
            >
              Assign
            </button>
            <button
              @click="bulkUpdateStatus"
              class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
            >
              Update Status
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

    <!-- Modals -->
    <CreateLeadModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="handleLeadCreated"
    />

    <EditLeadModal
      v-if="selectedLead && showEditModal"
      :lead="selectedLead"
      @close="showEditModal = false; selectedLead = null"
      @updated="handleLeadUpdated"
    />

    <ViewLeadModal
      v-if="selectedLead && showViewModal"
      :lead="selectedLead"
      @close="showViewModal = false; selectedLead = null"
    />

    <ImportLeadsModal
      v-if="showImportModal"
      @close="showImportModal = false"
      @imported="handleLeadsImported"
    />

    <BulkActionModal
      v-if="showBulkModal"
      :action="bulkAction"
      :selected-leads="selectedLeads"
      @close="showBulkModal = false"
      @completed="handleBulkActionCompleted"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import {
  UserPlusIcon,
  ClockIcon,
  CheckCircleIcon,
  ArrowTrendingUpIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import { useCompany } from '@/Composables/useCompany'
import CreateLeadModal from './CreateLeadModal.vue'
import EditLeadModal from './EditLeadModal.vue'
import ViewLeadModal from './ViewLeadModal.vue'
import ImportLeadsModal from './ImportLeadsModal.vue'
import BulkActionModal from './BulkActionModal.vue'

const { showToast } = useToast()
const { currentCompany } = useCompany()

// Reactive data
const leads = ref([])
const users = ref([])
const pagination = ref({})
const stats = ref({
  total_leads: 0,
  new_leads: 0,
  qualified_leads: 0,
  converted_leads: 0,
  conversion_rate: 0
})

const selectedLeads = ref([])
const selectedLead = ref(null)
const searchQuery = ref('')
const searchTimeout = ref(null)

const showCreateModal = ref(false)
const showEditModal = ref(false)
const showViewModal = ref(false)
const showImportModal = ref(false)
const showBulkModal = ref(false)
const bulkAction = ref('')

const filters = reactive({
  status: '',
  source: '',
  assigned_to: '',
  score_range: ''
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
    const response = await fetch('/api/v1/crm/leads/statistics', {
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

const viewLead = (lead) => {
  selectedLead.value = lead
  showViewModal.value = true
}

const editLead = (lead) => {
  selectedLead.value = lead
  showEditModal.value = true
}

const convertLead = async (lead) => {
  if (!confirm('Are you sure you want to convert this lead to a customer?')) return
  
  try {
    const response = await fetch(`/api/v1/crm/leads/${lead.uuid}/convert`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      showToast('Lead converted successfully', 'success')
      fetchLeads()
      fetchStats()
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to convert lead', 'error')
    }
  } catch (error) {
    console.error('Failed to convert lead:', error)
    showToast('Failed to convert lead', 'error')
  }
}

const bulkAssign = () => {
  bulkAction.value = 'assign'
  showBulkModal.value = true
}

const bulkUpdateStatus = () => {
  bulkAction.value = 'status'
  showBulkModal.value = true
}

const bulkDelete = () => {
  bulkAction.value = 'delete'
  showBulkModal.value = true
}

const handleLeadCreated = () => {
  showCreateModal.value = false
  fetchLeads()
  fetchStats()
  showToast('Lead created successfully', 'success')
}

const handleLeadUpdated = () => {
  showEditModal.value = false
  selectedLead.value = null
  fetchLeads()
  showToast('Lead updated successfully', 'success')
}

const handleLeadsImported = () => {
  showImportModal.value = false
  fetchLeads()
  fetchStats()
  showToast('Leads imported successfully', 'success')
}

const handleBulkActionCompleted = () => {
  showBulkModal.value = false
  selectedLeads.value = []
  fetchLeads()
  fetchStats()
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

const getScoreBarClass = (score) => {
  if (score >= 80) return 'bg-green-500'
  if (score >= 50) return 'bg-yellow-500'
  return 'bg-red-500'
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatSource = (source) => {
  return source.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
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