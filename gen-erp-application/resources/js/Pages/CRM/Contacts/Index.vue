<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Contact Management</h1>
          <p class="mt-1 text-sm text-gray-600">
            Manage your business contacts and relationships
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="$router.push('/crm/contacts/create')"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Add Contact
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <UserGroupIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.total_contacts }}</div>
            <div class="text-sm text-gray-600">Total Contacts</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.active_contacts }}</div>
            <div class="text-sm text-gray-600">Active Contacts</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <BuildingOfficeIcon class="h-8 w-8 text-purple-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.companies_count }}</div>
            <div class="text-sm text-gray-600">Companies</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CalendarIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.recent_activities }}</div>
            <div class="text-sm text-gray-600">Recent Activities</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Type</label>
          <select v-model="filters.type" @change="fetchContacts" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Types</option>
            <option value="person">Person</option>
            <option value="company">Company</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="filters.is_active" @change="fetchContacts" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Statuses</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">City</label>
          <select v-model="filters.city" @change="fetchContacts" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Cities</option>
            <option v-for="city in cities" :key="city" :value="city">
              {{ city }}
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

    <!-- Contacts Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">Contacts</h3>
          <div class="flex items-center space-x-2">
            <input
              v-model="searchQuery"
              @input="debounceSearch"
              type="text"
              placeholder="Search contacts..."
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
                  :checked="selectedContacts.length === contacts.length && contacts.length > 0"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Contact
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Type
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Company
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Location
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
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
            <tr v-for="contact in contacts" :key="contact.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <input
                  type="checkbox"
                  :value="contact.id"
                  v-model="selectedContacts"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                      <span class="text-sm font-medium text-gray-700">
                        {{ getInitials(contact.name) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ contact.name }}</div>
                    <div class="text-sm text-gray-500">{{ contact.email }}</div>
                    <div class="text-sm text-gray-500">{{ contact.phone }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getTypeBadgeClass(contact.type)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ formatType(contact.type) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ contact.company_name || '-' }}</div>
                <div class="text-sm text-gray-500">{{ contact.job_title || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ contact.city || '-' }}</div>
                <div class="text-sm text-gray-500">{{ contact.country || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(contact.is_active)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ contact.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ contact.last_activity_at ? formatRelativeTime(contact.last_activity_at) : 'No activity' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <button
                    @click="$router.push(`/crm/contacts/${contact.uuid}`)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    View
                  </button>
                  <button
                    @click="$router.push(`/crm/contacts/${contact.uuid}/edit`)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Edit
                  </button>
                  <button
                    @click="createActivity(contact)"
                    class="text-purple-600 hover:text-purple-900"
                  >
                    Activity
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Bulk Actions -->
      <div v-if="selectedContacts.length > 0" class="bg-gray-50 px-6 py-3 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-700">{{ selectedContacts.length }} contacts selected</span>
          <div class="flex space-x-2">
            <button
              @click="bulkActivate"
              class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
            >
              Activate
            </button>
            <button
              @click="bulkDeactivate"
              class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-sm"
            >
              Deactivate
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
  UserGroupIcon,
  CheckCircleIcon,
  BuildingOfficeIcon,
  CalendarIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'

const { showToast } = useToast()

// Reactive data
const contacts = ref([])
const cities = ref([])
const pagination = ref({})
const stats = ref({
  total_contacts: 0,
  active_contacts: 0,
  companies_count: 0,
  recent_activities: 0
})

const selectedContacts = ref([])
const searchQuery = ref('')
const searchTimeout = ref(null)

const filters = reactive({
  type: '',
  is_active: '',
  city: ''
})

// Methods
const fetchContacts = async (page = 1) => {
  try {
    const params = new URLSearchParams({
      page,
      per_page: 15,
      search: searchQuery.value,
      ...filters
    })
    
    const response = await fetch(`/api/v1/crm/contacts?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      contacts.value = data.data
      pagination.value = data.meta
    }
  } catch (error) {
    console.error('Failed to fetch contacts:', error)
    showToast('Failed to load contacts', 'error')
  }
}

const fetchStats = async () => {
  try {
    const response = await fetch('/api/v1/crm/contacts/statistics', {
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

const fetchCities = async () => {
  try {
    const response = await fetch('/api/v1/crm/contacts/cities', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      cities.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch cities:', error)
  }
}

const debounceSearch = () => {
  clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(() => {
    fetchContacts()
  }, 300)
}

const clearFilters = () => {
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  searchQuery.value = ''
  fetchContacts()
}

const toggleSelectAll = () => {
  if (selectedContacts.value.length === contacts.value.length) {
    selectedContacts.value = []
  } else {
    selectedContacts.value = contacts.value.map(contact => contact.id)
  }
}

const createActivity = (contact) => {
  // Navigate to create activity with pre-filled contact
  const params = new URLSearchParams({
    subject_type: 'contact',
    subject_id: contact.id,
    subject_name: contact.name
  })
  window.open(`/crm/activities/create?${params}`, '_blank')
}

const bulkActivate = async () => {
  if (!confirm('Activate selected contacts?')) return
  
  try {
    const response = await fetch('/api/v1/crm/contacts/bulk-activate', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        contact_ids: selectedContacts.value
      })
    })
    
    if (response.ok) {
      showToast('Contacts activated successfully', 'success')
      selectedContacts.value = []
      fetchContacts()
      fetchStats()
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to activate contacts', 'error')
    }
  } catch (error) {
    console.error('Failed to activate contacts:', error)
    showToast('Failed to activate contacts', 'error')
  }
}

const bulkDeactivate = async () => {
  if (!confirm('Deactivate selected contacts?')) return
  
  try {
    const response = await fetch('/api/v1/crm/contacts/bulk-deactivate', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        contact_ids: selectedContacts.value
      })
    })
    
    if (response.ok) {
      showToast('Contacts deactivated successfully', 'success')
      selectedContacts.value = []
      fetchContacts()
      fetchStats()
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to deactivate contacts', 'error')
    }
  } catch (error) {
    console.error('Failed to deactivate contacts:', error)
    showToast('Failed to deactivate contacts', 'error')
  }
}

const bulkDelete = () => {
  if (!confirm('Are you sure you want to delete selected contacts?')) return
  showToast('Bulk delete feature coming soon', 'info')
}

const changePage = (url) => {
  if (!url) return
  const page = new URL(url).searchParams.get('page')
  fetchContacts(page)
}

// Utility functions
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getTypeBadgeClass = (type) => {
  const classes = {
    person: 'bg-blue-100 text-blue-800',
    company: 'bg-purple-100 text-purple-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getStatusBadgeClass = (isActive) => {
  return isActive
    ? 'bg-green-100 text-green-800'
    : 'bg-red-100 text-red-800'
}

const formatType = (type) => {
  return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
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
  fetchContacts()
  fetchStats()
  fetchCities()
})
</script>