<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Carrier Configuration</h1>
          <p class="mt-1 text-sm text-gray-600">
            Manage and configure shipping carriers for your logistics operations
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="$router.push('/logistics/carriers/create')"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Add Carrier
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <TruckIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.total_carriers }}</div>
            <div class="text-sm text-gray-600">Total Carriers</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.active_carriers }}</div>
            <div class="text-sm text-gray-600">Active Carriers</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CogIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.configured_carriers }}</div>
            <div class="text-sm text-gray-600">Configured</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ChartBarIcon class="h-8 w-8 text-purple-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.total_shipments }}</div>
            <div class="text-sm text-gray-600">Total Shipments</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="filters.is_active" @change="fetchCarriers" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Statuses</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Type</label>
          <select v-model="filters.code" @change="fetchCarriers" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Types</option>
            <option value="pathao">Pathao</option>
            <option value="paperfly">PaperFly</option>
            <option value="steadfast">SteadFast</option>
            <option value="redx">RedX</option>
            <option value="custom">Custom</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Configuration</label>
          <select v-model="filters.configured" @change="fetchCarriers" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All</option>
            <option value="1">Configured</option>
            <option value="0">Not Configured</option>
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

    <!-- Carriers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="carrier in carriers"
        :key="carrier.id"
        class="bg-white shadow rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-200"
      >
        <!-- Carrier Header -->
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <div :class="getCarrierIconClass(carrier.code)" class="w-12 h-12 rounded-lg flex items-center justify-center">
                  <component :is="getCarrierIcon(carrier.code)" class="w-6 h-6 text-white" />
                </div>
              </div>
              <div>
                <h3 class="text-lg font-medium text-gray-900">{{ carrier.name }}</h3>
                <p class="text-sm text-gray-500">{{ formatCarrierType(carrier.code) }}</p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span :class="getStatusBadgeClass(carrier.is_active)" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ carrier.is_active ? 'Active' : 'Inactive' }}
              </span>
              <span :class="getConfigBadgeClass(carrier.is_configured)" class="px-2 py-1 text-xs font-semibold rounded-full">
                {{ carrier.is_configured ? 'Configured' : 'Not Configured' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Carrier Details -->
        <div class="p-6">
          <div class="space-y-4">
            <!-- Configuration Status -->
            <div>
              <h4 class="text-sm font-medium text-gray-900 mb-2">Configuration</h4>
              <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">API Endpoint</span>
                  <span :class="carrier.api_endpoint ? 'text-green-600' : 'text-red-600'">
                    {{ carrier.api_endpoint ? '✓ Set' : '✗ Missing' }}
                  </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">API Key</span>
                  <span :class="carrier.api_key ? 'text-green-600' : 'text-red-600'">
                    {{ carrier.api_key ? '✓ Set' : '✗ Missing' }}
                  </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Secret Key</span>
                  <span :class="carrier.secret_key ? 'text-green-600' : 'text-red-600'">
                    {{ carrier.secret_key ? '✓ Set' : '✗ Missing' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Pricing -->
            <div>
              <h4 class="text-sm font-medium text-gray-900 mb-2">Pricing</h4>
              <div class="space-y-1">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Base Rate</span>
                  <span class="text-gray-900">৳{{ carrier.base_rate || '0' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Per KG Rate</span>
                  <span class="text-gray-900">৳{{ carrier.per_kg_rate || '0' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">COD Charge</span>
                  <span class="text-gray-900">{{ carrier.cod_charge || '0' }}%</span>
                </div>
              </div>
            </div>

            <!-- Coverage -->
            <div>
              <h4 class="text-sm font-medium text-gray-900 mb-2">Coverage</h4>
              <div class="space-y-1">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Inside Dhaka</span>
                  <span :class="carrier.supports_inside_dhaka ? 'text-green-600' : 'text-red-600'">
                    {{ carrier.supports_inside_dhaka ? '✓ Yes' : '✗ No' }}
                  </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Outside Dhaka</span>
                  <span :class="carrier.supports_outside_dhaka ? 'text-green-600' : 'text-red-600'">
                    {{ carrier.supports_outside_dhaka ? '✓ Yes' : '✗ No' }}
                  </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">COD Support</span>
                  <span :class="carrier.supports_cod ? 'text-green-600' : 'text-red-600'">
                    {{ carrier.supports_cod ? '✓ Yes' : '✗ No' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Statistics -->
            <div>
              <h4 class="text-sm font-medium text-gray-900 mb-2">Statistics</h4>
              <div class="space-y-1">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Total Shipments</span>
                  <span class="text-gray-900">{{ carrier.shipments_count || 0 }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Success Rate</span>
                  <span class="text-gray-900">{{ carrier.success_rate || 0 }}%</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Avg. Delivery Time</span>
                  <span class="text-gray-900">{{ carrier.avg_delivery_time || 0 }} days</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <div class="flex space-x-2">
              <button
                @click="$router.push(`/logistics/carriers/${carrier.uuid}/edit`)"
                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
              >
                Configure
              </button>
              <button
                @click="testConnection(carrier)"
                :disabled="!carrier.is_configured"
                class="text-green-600 hover:text-green-900 text-sm font-medium disabled:text-gray-400"
              >
                Test Connection
              </button>
            </div>
            <div class="flex space-x-2">
              <button
                @click="toggleStatus(carrier)"
                :class="carrier.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
                class="text-sm font-medium"
              >
                {{ carrier.is_active ? 'Deactivate' : 'Activate' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="carriers.length === 0" class="bg-white shadow rounded-lg p-12">
      <div class="text-center">
        <TruckIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No carriers found</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by adding your first carrier.</p>
        <div class="mt-6">
          <button
            @click="$router.push('/logistics/carriers/create')"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Add Carrier
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import {
  TruckIcon,
  CheckCircleIcon,
  CogIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'

const { showToast } = useToast()

// Reactive data
const carriers = ref([])
const stats = ref({
  total_carriers: 0,
  active_carriers: 0,
  configured_carriers: 0,
  total_shipments: 0
})

const filters = reactive({
  is_active: '',
  code: '',
  configured: ''
})

// Methods
const fetchCarriers = async () => {
  try {
    const params = new URLSearchParams(filters)
    const response = await fetch(`/api/v1/logistics/carriers?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      carriers.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch carriers:', error)
    showToast('Failed to load carriers', 'error')
  }
}

const fetchStats = async () => {
  try {
    const response = await fetch('/api/v1/logistics/carriers/statistics', {
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

const clearFilters = () => {
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  fetchCarriers()
}

const toggleStatus = async (carrier) => {
  const action = carrier.is_active ? 'deactivate' : 'activate'
  if (!confirm(`Are you sure you want to ${action} this carrier?`)) return
  
  try {
    const response = await fetch(`/api/v1/logistics/carriers/${carrier.uuid}/${action}`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      showToast(`Carrier ${action}d successfully`, 'success')
      fetchCarriers()
      fetchStats()
    } else {
      const error = await response.json()
      showToast(error.message || `Failed to ${action} carrier`, 'error')
    }
  } catch (error) {
    console.error(`Failed to ${action} carrier:`, error)
    showToast(`Failed to ${action} carrier`, 'error')
  }
}

const testConnection = async (carrier) => {
  try {
    showToast('Testing connection...', 'info')
    
    const response = await fetch(`/api/v1/logistics/carriers/${carrier.uuid}/test`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    const data = await response.json()
    
    if (response.ok) {
      showToast('Connection test successful', 'success')
    } else {
      showToast(data.message || 'Connection test failed', 'error')
    }
  } catch (error) {
    console.error('Failed to test connection:', error)
    showToast('Connection test failed', 'error')
  }
}

// Utility functions
const getCarrierIcon = (code) => {
  return TruckIcon // Default icon for all carriers
}

const getCarrierIconClass = (code) => {
  const classes = {
    pathao: 'bg-red-500',
    paperfly: 'bg-blue-500',
    steadfast: 'bg-green-500',
    redx: 'bg-purple-500',
    custom: 'bg-gray-500'
  }
  return classes[code] || 'bg-gray-500'
}

const getStatusBadgeClass = (isActive) => {
  return isActive
    ? 'bg-green-100 text-green-800'
    : 'bg-red-100 text-red-800'
}

const getConfigBadgeClass = (isConfigured) => {
  return isConfigured
    ? 'bg-blue-100 text-blue-800'
    : 'bg-yellow-100 text-yellow-800'
}

const formatCarrierType = (code) => {
  const types = {
    pathao: 'Pathao Courier',
    paperfly: 'PaperFly',
    steadfast: 'SteadFast Courier',
    redx: 'RedX',
    custom: 'Custom Carrier'
  }
  return types[code] || 'Unknown'
}

// Lifecycle
onMounted(() => {
  fetchCarriers()
  fetchStats()
})
</script>