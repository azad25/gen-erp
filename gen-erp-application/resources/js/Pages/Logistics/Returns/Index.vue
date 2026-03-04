<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Return Management</h1>
          <p class="mt-1 text-sm text-gray-600">
            Manage customer return requests and process refunds
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="showCreateModal = true"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Create Return
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ArrowUturnLeftIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.total_returns }}</div>
            <div class="text-sm text-gray-600">Total Returns</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ClockIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.pending_returns }}</div>
            <div class="text-sm text-gray-600">Pending Review</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">{{ stats.approved_returns }}</div>
            <div class="text-sm text-gray-600">Approved</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CurrencyDollarIcon class="h-8 w-8 text-purple-600" />
          </div>
          <div class="ml-4">
            <div class="text-2xl font-bold text-gray-900">৳{{ formatNumber(stats.refund_amount) }}</div>
            <div class="text-sm text-gray-600">Total Refunds</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="filters.status" @change="fetchReturns" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Statuses</option>
            <option value="requested">Requested</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="received">Received</option>
            <option value="refunded">Refunded</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Reason</label>
          <select v-model="filters.reason" @change="fetchReturns" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">All Reasons</option>
            <option value="damaged">Damaged</option>
            <option value="defective">Defective</option>
            <option value="wrong_item">Wrong Item</option>
            <option value="not_as_described">Not as Described</option>
            <option value="changed_mind">Changed Mind</option>
            <option value="other">Other</option>
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
            @click="fetchReturns"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Apply Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Returns Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Return Requests</h3>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Return Number
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Shipment
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Customer
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Reason
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Refund Amount
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Requested
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="returnItem in returns" :key="returnItem.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ returnItem.return_number }}</div>
                <div class="text-sm text-gray-500">{{ returnItem.uuid }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ returnItem.shipment?.tracking_number }}</div>
                <div class="text-sm text-gray-500">{{ returnItem.shipment?.recipient_name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ returnItem.customer_name }}</div>
                <div class="text-sm text-gray-500">{{ returnItem.customer_phone }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ formatReason(returnItem.reason) }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(returnItem.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ formatStatus(returnItem.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <span v-if="returnItem.refund_amount">৳{{ returnItem.refund_amount }}</span>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(returnItem.requested_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <button
                    @click="viewReturn(returnItem)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    View
                  </button>
                  <button
                    v-if="returnItem.status === 'requested'"
                    @click="approveReturn(returnItem)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Approve
                  </button>
                  <button
                    v-if="returnItem.status === 'requested'"
                    @click="rejectReturn(returnItem)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Reject
                  </button>
                  <button
                    v-if="returnItem.status === 'approved'"
                    @click="processRefund(returnItem)"
                    class="text-purple-600 hover:text-purple-900"
                  >
                    Refund
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

    <!-- Create Return Modal -->
    <CreateReturnModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="handleReturnCreated"
    />

    <!-- View Return Modal -->
    <ViewReturnModal
      v-if="selectedReturn"
      :return-item="selectedReturn"
      @close="selectedReturn = null"
      @updated="handleReturnUpdated"
    />

    <!-- Approve Return Modal -->
    <ApproveReturnModal
      v-if="approveReturnItem"
      :return-item="approveReturnItem"
      @close="approveReturnItem = null"
      @approved="handleReturnApproved"
    />

    <!-- Reject Return Modal -->
    <RejectReturnModal
      v-if="rejectReturnItem"
      :return-item="rejectReturnItem"
      @close="rejectReturnItem = null"
      @rejected="handleReturnRejected"
    />

    <!-- Process Refund Modal -->
    <ProcessRefundModal
      v-if="refundReturnItem"
      :return-item="refundReturnItem"
      @close="refundReturnItem = null"
      @processed="handleRefundProcessed"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import {
  ArrowUturnLeftIcon,
  ClockIcon,
  CheckCircleIcon,
  CurrencyDollarIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import CreateReturnModal from './CreateReturnModal.vue'
import ViewReturnModal from './ViewReturnModal.vue'
import ApproveReturnModal from './ApproveReturnModal.vue'
import RejectReturnModal from './RejectReturnModal.vue'
import ProcessRefundModal from './ProcessRefundModal.vue'

const { showToast } = useToast()

// Reactive data
const returns = ref([])
const pagination = ref({})
const stats = ref({
  total_returns: 0,
  pending_returns: 0,
  approved_returns: 0,
  refund_amount: 0
})

const showCreateModal = ref(false)
const selectedReturn = ref(null)
const approveReturnItem = ref(null)
const rejectReturnItem = ref(null)
const refundReturnItem = ref(null)

const filters = reactive({
  status: '',
  reason: '',
  date_from: '',
  date_to: ''
})

// Methods
const fetchReturns = async (page = 1) => {
  try {
    const params = new URLSearchParams({
      page,
      per_page: 15,
      ...filters
    })
    
    const response = await fetch(`/api/v1/logistics/returns?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      returns.value = data.data
      pagination.value = data.meta
    }
  } catch (error) {
    console.error('Failed to fetch returns:', error)
    showToast('Failed to load returns', 'error')
  }
}

const fetchStats = async () => {
  try {
    const response = await fetch('/api/v1/logistics/returns/statistics', {
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

const viewReturn = (returnItem) => {
  selectedReturn.value = returnItem
}

const approveReturn = (returnItem) => {
  approveReturnItem.value = returnItem
}

const rejectReturn = (returnItem) => {
  rejectReturnItem.value = returnItem
}

const processRefund = (returnItem) => {
  refundReturnItem.value = returnItem
}

const handleReturnCreated = () => {
  showCreateModal.value = false
  fetchReturns()
  fetchStats()
  showToast('Return request created successfully', 'success')
}

const handleReturnUpdated = () => {
  selectedReturn.value = null
  fetchReturns()
  showToast('Return updated successfully', 'success')
}

const handleReturnApproved = () => {
  approveReturnItem.value = null
  fetchReturns()
  fetchStats()
  showToast('Return approved successfully', 'success')
}

const handleReturnRejected = () => {
  rejectReturnItem.value = null
  fetchReturns()
  fetchStats()
  showToast('Return rejected', 'success')
}

const handleRefundProcessed = () => {
  refundReturnItem.value = null
  fetchReturns()
  fetchStats()
  showToast('Refund processed successfully', 'success')
}

const changePage = (url) => {
  if (!url) return
  const page = new URL(url).searchParams.get('page')
  fetchReturns(page)
}

const getStatusBadgeClass = (status) => {
  const classes = {
    requested: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    received: 'bg-blue-100 text-blue-800',
    refunded: 'bg-purple-100 text-purple-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatReason = (reason) => {
  return reason.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
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
  fetchReturns()
  fetchStats()
})
</script>