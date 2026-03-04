<template>
  <div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Track Your Shipment</h1>
        <p class="mt-2 text-lg text-gray-600">
          Enter your tracking number to get real-time updates
        </p>
      </div>

      <!-- Search Form -->
      <div class="bg-white shadow rounded-lg p-6 mb-8">
        <form @submit.prevent="trackShipment" class="flex flex-col sm:flex-row gap-4">
          <div class="flex-1">
            <label for="tracking_number" class="sr-only">Tracking Number</label>
            <input
              id="tracking_number"
              v-model="trackingNumber"
              type="text"
              placeholder="Enter tracking number (e.g., TRK123456789)"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-lg py-3"
              required
            />
          </div>
          <button
            type="submit"
            :disabled="loading"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-md text-lg font-medium disabled:opacity-50"
          >
            {{ loading ? 'Tracking...' : 'Track Shipment' }}
          </button>
        </form>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
        <div class="flex">
          <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mt-0.5" />
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Tracking Error</h3>
            <p class="mt-1 text-sm text-red-700">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- Shipment Details -->
      <div v-if="shipment" class="space-y-8">
        <!-- Basic Info -->
        <div class="bg-white shadow rounded-lg p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 class="text-lg font-medium text-gray-900 mb-4">Shipment Details</h3>
              <dl class="space-y-2">
                <div>
                  <dt class="text-sm font-medium text-gray-500">Tracking Number</dt>
                  <dd class="text-sm text-gray-900 font-mono">{{ shipment.tracking_number }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Status</dt>
                  <dd>
                    <span :class="getStatusBadgeClass(shipment.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                      {{ formatStatus(shipment.status) }}
                    </span>
                  </dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Carrier</dt>
                  <dd class="text-sm text-gray-900">{{ shipment.carrier?.name }}</dd>
                </div>
                <div v-if="shipment.estimated_delivery">
                  <dt class="text-sm font-medium text-gray-500">Estimated Delivery</dt>
                  <dd class="text-sm text-gray-900">{{ formatDate(shipment.estimated_delivery) }}</dd>
                </div>
              </dl>
            </div>
            
            <div>
              <h3 class="text-lg font-medium text-gray-900 mb-4">Delivery Information</h3>
              <dl class="space-y-2">
                <div>
                  <dt class="text-sm font-medium text-gray-500">Recipient</dt>
                  <dd class="text-sm text-gray-900">{{ shipment.recipient_name }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Phone</dt>
                  <dd class="text-sm text-gray-900">{{ shipment.recipient_phone }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Delivery Address</dt>
                  <dd class="text-sm text-gray-900">
                    {{ shipment.delivery_address }}<br>
                    {{ shipment.delivery_city }}
                    <span v-if="shipment.delivery_postal_code">- {{ shipment.delivery_postal_code }}</span>
                  </dd>
                </div>
                <div v-if="shipment.cod_amount">
                  <dt class="text-sm font-medium text-gray-500">COD Amount</dt>
                  <dd class="text-sm text-gray-900 font-semibold">৳{{ shipment.cod_amount }}</dd>
                </div>
              </dl>
            </div>
          </div>
        </div>

        <!-- Progress Timeline -->
        <div class="bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-6">Tracking Timeline</h3>
          
          <div class="flow-root">
            <ul class="-mb-8">
              <li v-for="(event, index) in trackingEvents" :key="event.id" class="relative pb-8">
                <div v-if="index !== trackingEvents.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></div>
                
                <div class="relative flex space-x-3">
                  <div>
                    <span :class="getEventIconClass(event.status)" class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white">
                      <component :is="getEventIcon(event.status)" class="h-5 w-5 text-white" />
                    </span>
                  </div>
                  
                  <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                    <div>
                      <p class="text-sm text-gray-900 font-medium">{{ formatEventStatus(event.status) }}</p>
                      <p v-if="event.description" class="text-sm text-gray-500">{{ event.description }}</p>
                      <p v-if="event.location" class="text-sm text-gray-500">📍 {{ event.location }}</p>
                    </div>
                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                      <time :datetime="event.event_time">{{ formatDateTime(event.event_time) }}</time>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- Package Items -->
        <div v-if="shipment.items?.length" class="bg-white shadow rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Package Contents</h3>
          
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in shipment.items" :key="item.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.name }}</td>
                  <td class="px-6 py-4 text-sm text-gray-500">{{ item.description || '-' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.quantity }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span v-if="item.value">৳{{ item.value }}</span>
                    <span v-else class="text-gray-400">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Contact Support -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
          <div class="flex">
            <InformationCircleIcon class="h-5 w-5 text-blue-400 mt-0.5" />
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800">Need Help?</h3>
              <p class="mt-1 text-sm text-blue-700">
                If you have any questions about your shipment, please contact our support team with your tracking number.
              </p>
              <div class="mt-3 text-sm text-blue-700">
                <p>📞 Support: +880-1234-567890</p>
                <p>✉️ Email: support@yourcompany.com</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sample Tracking Numbers -->
      <div v-if="!shipment && !loading" class="bg-gray-100 rounded-lg p-6 mt-8">
        <h3 class="text-sm font-medium text-gray-900 mb-2">Sample Tracking Numbers</h3>
        <p class="text-sm text-gray-600 mb-3">Try these sample tracking numbers to see the tracking system in action:</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="sample in sampleTrackingNumbers"
            :key="sample"
            @click="trackingNumber = sample; trackShipment()"
            class="text-xs bg-white border border-gray-300 rounded px-3 py-1 hover:bg-gray-50 font-mono"
          >
            {{ sample }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import {
  ExclamationTriangleIcon,
  InformationCircleIcon,
  TruckIcon,
  CheckCircleIcon,
  ClockIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'

// Reactive data
const trackingNumber = ref('')
const shipment = ref(null)
const trackingEvents = ref([])
const loading = ref(false)
const error = ref('')

const sampleTrackingNumbers = [
  'TRK123456789',
  'TRK987654321',
  'TRK555666777'
]

// Methods
const trackShipment = async () => {
  if (!trackingNumber.value.trim()) return
  
  loading.value = true
  error.value = ''
  shipment.value = null
  trackingEvents.value = []
  
  try {
    const response = await fetch(`/api/v1/logistics/tracking/${trackingNumber.value}`, {
      headers: {
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      shipment.value = data.data.shipment
      trackingEvents.value = data.data.events || []
    } else if (response.status === 404) {
      error.value = 'Shipment not found. Please check your tracking number and try again.'
    } else {
      const errorData = await response.json()
      error.value = errorData.message || 'Failed to track shipment. Please try again.'
    }
  } catch (err) {
    console.error('Tracking failed:', err)
    error.value = 'Network error. Please check your connection and try again.'
  } finally {
    loading.value = false
  }
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

const getEventIconClass = (status) => {
  const classes = {
    pending: 'bg-yellow-500',
    picked_up: 'bg-blue-500',
    in_transit: 'bg-indigo-500',
    delivered: 'bg-green-500',
    cancelled: 'bg-red-500',
    returned: 'bg-gray-500'
  }
  return classes[status] || 'bg-gray-500'
}

const getEventIcon = (status) => {
  const icons = {
    pending: ClockIcon,
    picked_up: TruckIcon,
    in_transit: TruckIcon,
    delivered: CheckCircleIcon,
    cancelled: XCircleIcon,
    returned: XCircleIcon
  }
  return icons[status] || ClockIcon
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatEventStatus = (status) => {
  const statusMap = {
    pending: 'Shipment Created',
    picked_up: 'Package Picked Up',
    in_transit: 'In Transit',
    out_for_delivery: 'Out for Delivery',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
    returned: 'Returned to Sender'
  }
  return statusMap[status] || formatStatus(status)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>