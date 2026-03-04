<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">COD Management</h1>
          <p class="mt-1 text-sm text-gray-600">
            Track and manage Cash on Delivery payments and settlements
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="exportCODReport"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Export Report
          </button>
          <button
            @click="showSettlementModal = true"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Process Settlement
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CurrencyDollarIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">৳{{ formatNumber(stats.total_cod_amount) }}</div>
            <div class="text-sm text-gray-600">Total COD</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ClockIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">৳{{ formatNumber(stats.pending_cod_amount) }}</div>
            <div class="text-sm text-gray-600">Pending</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">৳{{ formatNumber(stats.collected_cod_amount) }}</div>
            <div class="text-sm text-gray-600">Collected</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <BanknotesIcon class="h-8 w-8 text-purple-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">৳{{ formatNumber(stats.settled_cod_amount) }}</div>
            <div class="text-sm text-gray-600">Settled</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ChartBarIcon class="h-8 w-8 text-indigo-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.collection_rate }}%</div>
            <div class="text-sm text-gray-600">Collection Rate</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">COD Status</label>
          <select v-model="filters.cod_status" @change="fetchCODShipments" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="collected">Collected</option>
            <option value="settled">Settled</option>
            <option value="failed">Failed</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Carrier</label>
          <select v-model="filters.carrier_id" @change="fetchCODShipments" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Carriers</option>
            <option v-for="carrier in carriers" :key="carrier.id" :value="carrier.id">
              {{ carrier.name }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Date From</label>
          <input
            v-model="filters.date_from"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Date To</label>
          <input
            v-model="filters.date_to"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          />
        </div>
        
        <div class="flex items-end">
          <button
            @click="fetchCODShipments"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Apply Filters
          </button>
        </div>
      </div>
    </div>

    <!-- COD Shipments Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">COD Shipments</h3>
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">
              Total: ৳{{ formatNumber(codShipments.reduce((sum, s) => sum + (s.cod_amount || 0), 0)) }}
            </span>
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
                  :checked="selectedShipments.length === codShipments.length && codShipments.length > 0"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tracking Number
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Customer
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Carrier
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                COD Amount
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                COD Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Delivery Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Collection Date
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="shipment in codShipments" :key="shipment.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <input
                  type="checkbox"
                  :value="shipment.id"
                  v-model="selectedShipments"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ shipment.tracking_number }}</div>
                <div class="text-sm text-gray-500">{{ shipment.uuid }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ shipment.recipient_name }}</div>
                <div class="text-sm text-gray-500">{{ shipment.recipient_phone }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ shipment.carrier?.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-gray-900">৳{{ shipment.cod_amount }}</div>
                <div v-if="shipment.cod_fee" class="text-xs text-gray-500">
                  Fee: ৳{{ shipment.cod_fee }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getCODStatusBadgeClass(shipment.cod_status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ formatCODStatus(shipment.cod_status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getDeliveryStatusBadgeClass(shipment.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ formatStatus(shipment.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ shipment.cod_collected_at ? formatDate(shipment.cod_collected_at) : '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <button
                    @click="viewCODDetails(shipment)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    View
                  </button>
                  <button
                    v-if="shipment.cod_status === 'collected' && !shipment.cod_settled_at"
                    @click="markAsSettled(shipment)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Settle
                  </button>
                  <button
                    v-if="shipment.cod_status === 'pending'"
                    @click="markAsFailed(shipment)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Mark Failed
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Bulk Actions -->
      <div v-if="selectedShipments.length > 0" class="bg-gray-50 px-6 py-3 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-700">
            {{ selectedShipments.length }} shipments selected 
            (Total: ৳{{ formatNumber(getSelectedTotal()) }})
          </span>
          <div class="flex space-x-2">
            <button
              @click="bulkSettle"
              class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm"
            >
              Bulk Settle
            </button>
            <button
              @click="bulkExport"
              class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm"
            >
              Export Selected
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

    <!-- Settlement Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent Settlements -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Settlements</h3>
        <div class="space-y-3">
          <div v-for="settlement in recentSettlements" :key="settlement.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
              <div class="text-sm font-medium text-gray-900">{{ settlement.carrier_name }}</div>
              <div class="text-xs text-gray-500">{{ formatDate(settlement.settled_at) }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm font-semibold text-gray-900">৳{{ formatNumber(settlement.amount) }}</div>
              <div class="text-xs text-gray-500">{{ settlement.shipment_count }} shipments</div>
            </div>
          </div>
        </div>
      </div>

      <!-- COD Performance -->
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">COD Performance</h3>
        <div class="space-y-4">
          <div v-for="carrier in codPerformance" :key="carrier.carrier_id" class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="text-sm font-medium text-gray-900">{{ carrier.carrier_name }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm font-semibold text-gray-900">{{ carrier.collection_rate }}%</div>
              <div class="text-xs text-gray-500">
                ৳{{ formatNumber(carrier.collected_amount) }} / ৳{{ formatNumber(carrier.total_amount) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ProcessSettlementModal
      v-if="showSettlementModal"
      @close="showSettlementModal = false"
      @processed="handleSettlementProcessed"
    />

    <ViewCODDetailsModal
      v-if="selectedCODShipment"
      :shipment="selectedCODShipment"
      @close="selectedCODShipment = null"
      @updated="handleCODUpdated"
    />

    <BulkSettlementModal
      v-if="showBulkSettlementModal"
      :selected-shipments="selectedShipments"
      @close="showBulkSettlementModal = false"
      @processed="handleBulkSettlementProcessed"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue'
import {
  CurrencyDollarIcon,
  ClockIcon,
  CheckCircleIcon,
  BanknotesIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import ProcessSettlementModal from './ProcessSettlementModal.vue'
import ViewCODDetailsModal from './ViewCODDetailsModal.vue'
import BulkSettlementModal from './BulkSettlementModal.vue'

const { showToast } = useToast()

// Reactive data
const codShipments = ref([])
const carriers = ref([])
const recentSettlements = ref([])
const codPerformance = ref([])
const pagination = ref({})
const stats = ref({
  total_cod_amount: 0,
  pending_cod_amount: 0,
  collected_cod_amount: 0,
  settled_cod_amount: 0,
  collection_rate: 0
})

const selectedShipments = ref([])
const selectedCODShipment = ref(null)

const showSettlementModal = ref(false)
const showBulkSettlementModal = ref(false)

const filters = reactive({
  cod_status: '',
  carrier_id: '',
  date_from: '',
  date_to: ''
})

// Computed
const getSelectedTotal = computed(() => {
  return codShipments.value
    .filter(s => selectedShipments.value.includes(s.id))
    .reduce((sum, s) => sum + (s.cod_amount || 0), 0)
})

// Methods
const fetchCODShipments = async (page = 1) => {
  try {
    const params = new URLSearchParams({
      page,
      per_page: 15,
      has_cod: 'true',
      ...filters
    })
    
    const response = await fetch(`/api/v1/logistics/shipments?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      codShipments.value = data.data
      pagination.value = data.meta
    }
  } catch (error) {
    console.error('Failed to fetch COD shipments:', error)
    showToast('Failed to load COD shipments', 'error')
  }
}

const fetchStats = async () => {
  try {
    const response = await fetch('/api/v1/logistics/cod/statistics', {
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
    console.error('Failed to fetch COD stats:', error)
  }
}

const fetchCarriers = async () => {
  try {
    const response = await fetch('/api/v1/logistics/carriers', {
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
  }
}

const fetchRecentSettlements = async () => {
  try {
    const response = await fetch('/api/v1/logistics/cod/settlements?limit=5', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      recentSettlements.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch recent settlements:', error)
  }
}

const fetchCODPerformance = async () => {
  try {
    const response = await fetch('/api/v1/logistics/cod/performance', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      codPerformance.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch COD performance:', error)
  }
}

const toggleSelectAll = () => {
  if (selectedShipments.value.length === codShipments.value.length) {
    selectedShipments.value = []
  } else {
    selectedShipments.value = codShipments.value.map(shipment => shipment.id)
  }
}

const viewCODDetails = (shipment) => {
  selectedCODShipment.value = shipment
}

const markAsSettled = async (shipment) => {
  if (!confirm('Mark this COD as settled?')) return
  
  try {
    const response = await fetch(`/api/v1/logistics/cod/${shipment.uuid}/settle`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      showToast('COD marked as settled', 'success')
      fetchCODShipments()
      fetchStats()
    }
  } catch (error) {
    console.error('Failed to settle COD:', error)
    showToast('Failed to settle COD', 'error')
  }
}

const markAsFailed = async (shipment) => {
  if (!confirm('Mark this COD collection as failed?')) return
  
  try {
    const response = await fetch(`/api/v1/logistics/cod/${shipment.uuid}/fail`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      showToast('COD marked as failed', 'success')
      fetchCODShipments()
      fetchStats()
    }
  } catch (error) {
    console.error('Failed to mark COD as failed:', error)
    showToast('Failed to mark COD as failed', 'error')
  }
}

const bulkSettle = () => {
  showBulkSettlementModal.value = true
}

const bulkExport = async () => {
  try {
    const response = await fetch('/api/v1/logistics/cod/export', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        shipment_ids: selectedShipments.value
      })
    })
    
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `cod-report-${new Date().toISOString().split('T')[0]}.xlsx`
      a.click()
      window.URL.revokeObjectURL(url)
      
      showToast('COD report exported successfully', 'success')
    }
  } catch (error) {
    console.error('Failed to export COD report:', error)
    showToast('Failed to export COD report', 'error')
  }
}

const exportCODReport = async () => {
  try {
    const params = new URLSearchParams(filters)
    const response = await fetch(`/api/v1/logistics/cod/export?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `cod-report-${new Date().toISOString().split('T')[0]}.xlsx`
      a.click()
      window.URL.revokeObjectURL(url)
      
      showToast('COD report exported successfully', 'success')
    }
  } catch (error) {
    console.error('Failed to export COD report:', error)
    showToast('Failed to export COD report', 'error')
  }
}

const handleSettlementProcessed = () => {
  showSettlementModal.value = false
  fetchCODShipments()
  fetchStats()
  fetchRecentSettlements()
  showToast('Settlement processed successfully', 'success')
}

const handleCODUpdated = () => {
  selectedCODShipment.value = null
  fetchCODShipments()
  fetchStats()
}

const handleBulkSettlementProcessed = () => {
  showBulkSettlementModal.value = false
  selectedShipments.value = []
  fetchCODShipments()
  fetchStats()
  fetchRecentSettlements()
  showToast('Bulk settlement processed successfully', 'success')
}

const changePage = (url) => {
  if (!url) return
  const page = new URL(url).searchParams.get('page')
  fetchCODShipments(page)
}

// Utility functions
const getCODStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    collected: 'bg-green-100 text-green-800',
    settled: 'bg-blue-100 text-blue-800',
    failed: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getDeliveryStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    picked_up: 'bg-blue-100 text-blue-800',
    in_transit: 'bg-indigo-100 text-indigo-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatCODStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
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
  fetchCODShipments()
  fetchStats()
  fetchCarriers()
  fetchRecentSettlements()
  fetchCODPerformance()
})
</script>