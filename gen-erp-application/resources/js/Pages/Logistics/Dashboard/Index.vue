<template>
  <AppLayout title="Logistics Dashboard">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Logistics Dashboard
        </h2>
        <div class="flex items-center space-x-3">
          <Link :href="route('logistics.shipments.create')" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Create Shipment
          </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                  </svg>
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ stats.total_shipments }}</div>
                  <div class="text-sm text-gray-600">Total Shipments</div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ stats.delivered_shipments }}</div>
                  <div class="text-sm text-gray-600">Delivered</div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ stats.pending_shipments }}</div>
                  <div class="text-sm text-gray-600">In Transit</div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                  </svg>
                </div>
                <div class="ml-4">
                  <div class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.total_cod_amount) }}</div>
                  <div class="text-sm text-gray-600">COD Amount</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <Link :href="route('logistics.shipments.create')" 
                    class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="h-6 w-6 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <div>
                  <div class="font-medium text-gray-900">Create Shipment</div>
                  <div class="text-sm text-gray-600">Add new shipment</div>
                </div>
              </Link>

              <Link :href="route('logistics.tracking.index')" 
                    class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="h-6 w-6 text-green-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <div>
                  <div class="font-medium text-gray-900">Track Shipment</div>
                  <div class="text-sm text-gray-600">Track shipment status</div>
                </div>
              </Link>

              <Link :href="route('logistics.returns.create')" 
                    class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="h-6 w-6 text-orange-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
                <div>
                  <div class="font-medium text-gray-900">Process Return</div>
                  <div class="text-sm text-gray-600">Handle return request</div>
                </div>
              </Link>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Recent Shipments -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Recent Shipments</h3>
                <Link :href="route('logistics.shipments.index')" 
                      class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                  View All
                </Link>
              </div>
              
              <div v-if="loading" class="text-center py-4">
                <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
              </div>
              
              <div v-else-if="recentShipments.length === 0" class="text-center py-8 text-gray-500">
                No recent shipments
              </div>
              
              <div v-else class="space-y-3">
                <div v-for="shipment in recentShipments" :key="shipment.id" 
                     class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                  <div>
                    <div class="font-medium text-gray-900">{{ shipment.tracking_number }}</div>
                    <div class="text-sm text-gray-600">{{ shipment.recipient_name }}</div>
                  </div>
                  <div class="text-right">
                    <span :class="getStatusBadgeClass(shipment.status)" 
                          class="px-2 py-1 text-xs font-semibold rounded-full">
                      {{ formatStatus(shipment.status) }}
                    </span>
                    <div class="text-xs text-gray-500 mt-1">{{ formatDate(shipment.created_at) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pending Returns -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Pending Returns</h3>
                <Link :href="route('logistics.returns.index')" 
                      class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                  View All
                </Link>
              </div>
              
              <div v-if="loading" class="text-center py-4">
                <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
              </div>
              
              <div v-else-if="pendingReturns.length === 0" class="text-center py-8 text-gray-500">
                No pending returns
              </div>
              
              <div v-else class="space-y-3">
                <div v-for="returnItem in pendingReturns" :key="returnItem.id" 
                     class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                  <div>
                    <div class="font-medium text-gray-900">{{ returnItem.return_number }}</div>
                    <div class="text-sm text-gray-600">{{ returnItem.reason }}</div>
                  </div>
                  <div class="text-right">
                    <span :class="getStatusBadgeClass(returnItem.status)" 
                          class="px-2 py-1 text-xs font-semibold rounded-full">
                      {{ formatStatus(returnItem.status) }}
                    </span>
                    <div class="text-xs text-gray-500 mt-1">{{ formatDate(returnItem.created_at) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const loading = ref(true)
const stats = ref({
  total_shipments: 0,
  delivered_shipments: 0,
  pending_shipments: 0,
  total_cod_amount: 0
})
const recentShipments = ref([])
const pendingReturns = ref([])

onMounted(() => {
  fetchDashboardData()
})

const fetchDashboardData = async () => {
  loading.value = true
  try {
    // Fetch statistics
    const statsResponse = await fetch('/api/v1/logistics/shipments/statistics', {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (statsResponse.ok) {
      const statsData = await statsResponse.json()
      stats.value = statsData.data
    }

    // Fetch recent shipments
    const shipmentsResponse = await fetch('/api/v1/logistics/shipments?per_page=5&sort_by=created_at&sort_order=desc', {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (shipmentsResponse.ok) {
      const shipmentsData = await shipmentsResponse.json()
      recentShipments.value = shipmentsData.data
    }

    // Fetch pending returns
    const returnsResponse = await fetch('/api/v1/logistics/returns?status=pending&per_page=5', {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Accept': 'application/json',
      }
    })
    
    if (returnsResponse.ok) {
      const returnsData = await returnsResponse.json()
      pendingReturns.value = returnsData.data
    }
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
  } finally {
    loading.value = false
  }
}

const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    returned: 'bg-orange-100 text-orange-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'BDT',
    minimumFractionDigits: 0
  }).format(amount || 0)
}
</script>