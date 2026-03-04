<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Shipment Management</h1>
          <p class="mt-1 text-sm text-gray-600">
            Track and manage all your shipments across different carriers
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="showCreateModal = true"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Create Shipment
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
            <div class="text-2xl font-bold text-gray-900">{{ stats.total_shipments }}</div>
            <div class="text-sm text-gray-600">Total Shipments</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ClockIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.in_transit }}</div>
            <div class="text-sm text-gray-600">In Transit</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.delivered }}</div>
            <div class="text-sm text-gray-600">Delivered</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CurrencyDollarIcon class="h-8 w-8 text-purple-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.cod_pending }}</div>
            <div class="text-sm text-gray-600">COD Pending</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="filters.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="picked_up">Picked Up</option>
            <option value="in_transit">In Transit</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Carrier</label>
          <select v-model="filters.carrier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Carriers</option>
            <option v-for="carrier in carriers" :key="carrier.id" :value="carrier.id">
              {{ carrier.name }}
            </option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Date Range</label>
          <input
            v-model="filters.date_from"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          />
        </div>
        
        <div class="flex items-end">
          <button
            @click="fetchShipments"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Apply Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Shipments Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Shipments</h3>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tracking Number
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Recipient
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Carrier
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                COD Amount
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Created
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="shipment in shipments" :key="shipment.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ shipment.tracking_number }}</div>
                <div class="text-sm text-gray-500">{{ shipment.uuid }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ shipment.recipient_name }}</div>
                <div class="text-sm text-gray-500">{{ shipment.recipient_phone }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ shipment.carrier?.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(shipment.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ formatStatus(shipment.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <span v-if="shipment.cod_amount">৳{{ shipment.cod_amount }}</span>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(shipment.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <button
                    @click="viewShipment(shipment)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    View
                  </button>
                  <button
                    @click="trackShipment(shipment)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Track
                  </button>
                  <button
                    v-if="shipment.can_cancel"
                    @click="cancelShipment(shipment)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Cancel
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
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

    <!-- Create Shipment Modal -->
    <CreateShipmentModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="handleShipmentCreated"
    />

    <!-- View Shipment Modal -->
    <ViewShipmentModal
      v-if="selectedShipment"
      :shipment="selectedShipment"
      @close="selectedShipment = null"
    />

    <!-- Track Shipment Modal -->
    <TrackShipmentModal
      v-if="trackingShipment"
      :shipment="trackingShipment"
      @close="trackingShipment = null"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import {
  TruckIcon,
  ClockIcon,
  CheckCircleIcon,
  CurrencyDollarIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import { useCompany } from '@/Composables/useCompany'
import CreateShipmentModal from './CreateShipmentModal.vue'
import ViewShipmentModal from './ViewShipmentModal.vue'
import TrackShipmentModal from './TrackShipmentModal.vue'

const { showToast } = useToast()
const { currentCompany } = useCompany()

// Reactive data
const shipments = ref([])
const carriers = ref([])
const pagination = ref({})
const stats = ref({
  total_shipments: 0,
  in_transit: 0,
  delivered: 0,
  cod_pending: 0
})

const showCreateModal = ref(false)
const selectedShipment = ref(null)
const trackingShipment = ref(null)

const filters = reactive({
  status: '',
  carrier_id: '',
  date_from: '',
  date_to: ''
})

// Methods
const fetchShipments = async (page = 1) => {
  try {
    const params = new URLSearchParams({
      page,
      per_page: 15,
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
      shipments.value = data.data
      pagination.value = data.meta
    }
  } catch (error) {
    console.error('Failed to fetch shipments:', error)
    showToast('Failed to load shipments', 'error')
  }
}

const fetchStats = async () => {
  try {
    const response = await fetch('/api/v1/logistics/shipments/statistics', {
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

const viewShipment = (shipment) => {
  selectedShipment.value = shipment
}

const trackShipment = (shipment) => {
  trackingShipment.value = shipment
}

const cancelShipment = async (shipment) => {
  if (!confirm('Are you sure you want to cancel this shipment?')) return
  
  try {
    const response = await fetch(`/api/v1/logistics/shipments/${shipment.uuid}/cancel`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      showToast('Shipment cancelled successfully', 'success')
      fetchShipments()
      fetchStats()
    } else {
      const error = await response.json()
      showToast(error.message || 'Failed to cancel shipment', 'error')
    }
  } catch (error) {
    console.error('Failed to cancel shipment:', error)
    showToast('Failed to cancel shipment', 'error')
  }
}

const handleShipmentCreated = () => {
  showCreateModal.value = false
  fetchShipments()
  fetchStats()
  showToast('Shipment created successfully', 'success')
}

const changePage = (url) => {
  if (!url) return
  const page = new URL(url).searchParams.get('page')
  fetchShipments(page)
}

const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    picked_up: 'bg-blue-100 text-blue-800',
    in_transit: 'bg-indigo-100 text-indigo-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    returned: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  fetchShipments()
  fetchStats()
  fetchCarriers()
})
</script>