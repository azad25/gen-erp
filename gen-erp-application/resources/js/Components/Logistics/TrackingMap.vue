<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Shipment Tracking</h3>
        <div class="flex items-center space-x-3">
          <button
            @click="refreshTracking"
            :disabled="loading"
            class="text-sm text-indigo-600 hover:text-indigo-500"
          >
            {{ loading ? 'Refreshing...' : 'Refresh' }}
          </button>
          <button
            @click="toggleFullscreen"
            class="text-sm text-gray-600 hover:text-gray-500"
          >
            {{ isFullscreen ? 'Exit Fullscreen' : 'Fullscreen' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Shipment Info -->
    <div v-if="shipment" class="px-4 py-3 bg-gray-50 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div>
          <h4 class="text-sm font-medium text-gray-900">{{ shipment.tracking_number }}</h4>
          <p class="text-xs text-gray-500">{{ shipment.carrier?.name }} - {{ shipment.service_type }}</p>
        </div>
        <div class="text-right">
          <span
            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
            :class="getStatusClass(shipment.status)"
          >
            {{ shipment.status }}
          </span>
          <p class="text-xs text-gray-500 mt-1">
            ETA: {{ formatDate(shipment.estimated_delivery) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Map Container -->
    <div class="relative">
      <div
        ref="mapContainer"
        class="w-full bg-gray-100"
        :class="isFullscreen ? 'h-screen' : 'h-96'"
      >
        <!-- Loading Overlay -->
        <div
          v-if="loading"
          class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10"
        >
          <div class="text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
            <p class="text-sm text-gray-600 mt-2">Loading tracking data...</p>
          </div>
        </div>

        <!-- Map will be rendered here -->
        <div id="tracking-map" class="w-full h-full"></div>
      </div>

      <!-- Map Controls -->
      <div class="absolute top-4 right-4 z-20 space-y-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-2">
          <button
            @click="zoomIn"
            class="block w-8 h-8 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded"
          >
            <PlusIcon class="h-4 w-4 mx-auto" />
          </button>
          <button
            @click="zoomOut"
            class="block w-8 h-8 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded"
          >
            <MinusIcon class="h-4 w-4 mx-auto" />
          </button>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-2">
          <button
            @click="centerMap"
            class="block w-8 h-8 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded"
            title="Center Map"
          >
            <MapIcon class="h-4 w-4 mx-auto" />
          </button>
        </div>
      </div>
    </div>

    <!-- Tracking Timeline -->
    <div v-if="trackingEvents.length > 0" class="p-4">
      <h4 class="text-sm font-medium text-gray-900 mb-4">Tracking History</h4>
      
      <div class="flow-root">
        <ul class="-mb-8">
          <li
            v-for="(event, index) in trackingEvents"
            :key="event.id"
            class="relative pb-8"
          >
            <div v-if="index !== trackingEvents.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></div>
            
            <div class="relative flex space-x-3">
              <div>
                <span
                  class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white"
                  :class="getEventIconClass(event.status)"
                >
                  <component :is="getEventIcon(event.status)" class="h-4 w-4 text-white" />
                </span>
              </div>
              
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <p class="text-sm font-medium text-gray-900">{{ event.description }}</p>
                  <time class="text-xs text-gray-500">{{ formatDateTime(event.timestamp) }}</time>
                </div>
                <div class="mt-1 text-sm text-gray-600">
                  <p v-if="event.location">📍 {{ event.location }}</p>
                  <p v-if="event.notes" class="text-xs text-gray-500 mt-1">{{ event.notes }}</p>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Route Details -->
    <div v-if="routeDetails" class="px-4 py-3 bg-gray-50 border-t border-gray-200">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div>
          <span class="text-gray-500">Distance:</span>
          <div class="font-medium">{{ routeDetails.total_distance }} km</div>
        </div>
        <div>
          <span class="text-gray-500">Duration:</span>
          <div class="font-medium">{{ routeDetails.estimated_duration }}</div>
        </div>
        <div>
          <span class="text-gray-500">Stops:</span>
          <div class="font-medium">{{ routeDetails.stops_count }}</div>
        </div>
        <div>
          <span class="text-gray-500">Progress:</span>
          <div class="font-medium">{{ routeDetails.progress_percentage }}%</div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="p-4 text-center">
      <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-red-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">Tracking Error</h3>
      <p class="mt-1 text-sm text-gray-500">{{ error }}</p>
      <button
        @click="refreshTracking"
        class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-3 rounded-md"
      >
        Retry
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import {
  PlusIcon,
  MinusIcon,
  MapIcon,
  ExclamationTriangleIcon,
  TruckIcon,
  CheckCircleIcon,
  ClockIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  shipmentId: {
    type: [String, Number],
    required: true
  },
  trackingNumber: {
    type: String,
    default: ''
  }
})

const { get, loading } = useApi()
const { showError } = useToast()

// Reactive data
const mapContainer = ref(null)
const shipment = ref(null)
const trackingEvents = ref([])
const routeDetails = ref(null)
const error = ref(null)
const isFullscreen = ref(false)
const map = ref(null)
const markers = ref([])
const routeLine = ref(null)

// Methods
const initializeMap = async () => {
  await nextTick()
  
  try {
    // Initialize map (using Leaflet as example - replace with your preferred map library)
    if (typeof L !== 'undefined') {
      map.value = L.map('tracking-map').setView([23.8103, 90.4125], 10) // Default to Dhaka
      
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(map.value)
      
      // Add custom controls
      addMapControls()
    } else {
      // Fallback to Google Maps or other map service
      initializeGoogleMap()
    }
  } catch (err) {
    console.error('Failed to initialize map:', err)
    error.value = 'Failed to load map'
  }
}

const initializeGoogleMap = () => {
  // Google Maps implementation
  if (typeof google !== 'undefined' && google.maps) {
    map.value = new google.maps.Map(document.getElementById('tracking-map'), {
      zoom: 10,
      center: { lat: 23.8103, lng: 90.4125 }
    })
  }
}

const fetchTrackingData = async () => {
  try {
    const data = await get(`/api/v1/logistics/shipments/${props.shipmentId}/tracking`)
    
    shipment.value = data.data.shipment
    trackingEvents.value = data.data.events || []
    routeDetails.value = data.data.route
    
    if (data.data.locations && data.data.locations.length > 0) {
      updateMapWithLocations(data.data.locations)
    }
    
    error.value = null
  } catch (err) {
    console.error('Failed to fetch tracking data:', err)
    error.value = 'Failed to load tracking information'
  }
}

const updateMapWithLocations = (locations) => {
  if (!map.value) return
  
  // Clear existing markers and routes
  clearMapElements()
  
  const bounds = []
  
  // Add markers for each location
  locations.forEach((location, index) => {
    const isCurrentLocation = index === locations.length - 1
    const marker = createMarker(location, isCurrentLocation)
    markers.value.push(marker)
    bounds.push([location.latitude, location.longitude])
  })
  
  // Draw route line
  if (locations.length > 1) {
    const routeCoordinates = locations.map(loc => [loc.latitude, loc.longitude])
    routeLine.value = L.polyline(routeCoordinates, {
      color: '#3b82f6',
      weight: 4,
      opacity: 0.8
    }).addTo(map.value)
  }
  
  // Fit map to show all locations
  if (bounds.length > 0) {
    map.value.fitBounds(bounds, { padding: [20, 20] })
  }
}

const createMarker = (location, isCurrent = false) => {
  const icon = L.divIcon({
    className: 'custom-marker',
    html: `
      <div class="w-8 h-8 rounded-full flex items-center justify-center ${
        isCurrent ? 'bg-green-500' : 'bg-blue-500'
      } text-white shadow-lg">
        ${isCurrent ? '🚚' : '📍'}
      </div>
    `,
    iconSize: [32, 32],
    iconAnchor: [16, 16]
  })
  
  const marker = L.marker([location.latitude, location.longitude], { icon })
    .addTo(map.value)
    .bindPopup(`
      <div class="text-sm">
        <strong>${location.description || 'Location'}</strong><br>
        ${location.address || ''}<br>
        <small>${formatDateTime(location.timestamp)}</small>
      </div>
    `)
  
  return marker
}

const clearMapElements = () => {
  // Remove existing markers
  markers.value.forEach(marker => {
    map.value.removeLayer(marker)
  })
  markers.value = []
  
  // Remove existing route line
  if (routeLine.value) {
    map.value.removeLayer(routeLine.value)
    routeLine.value = null
  }
}

const addMapControls = () => {
  // Custom map controls can be added here
}

const refreshTracking = () => {
  fetchTrackingData()
}

const zoomIn = () => {
  if (map.value) {
    map.value.zoomIn()
  }
}

const zoomOut = () => {
  if (map.value) {
    map.value.zoomOut()
  }
}

const centerMap = () => {
  if (map.value && markers.value.length > 0) {
    const bounds = markers.value.map(marker => marker.getLatLng())
    map.value.fitBounds(bounds, { padding: [20, 20] })
  }
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
  
  // Trigger map resize after DOM update
  nextTick(() => {
    if (map.value) {
      map.value.invalidateSize()
    }
  })
}

const getStatusClass = (status) => {
  const classes = {
    'pending': 'bg-gray-100 text-gray-800',
    'picked_up': 'bg-blue-100 text-blue-800',
    'in_transit': 'bg-yellow-100 text-yellow-800',
    'out_for_delivery': 'bg-orange-100 text-orange-800',
    'delivered': 'bg-green-100 text-green-800',
    'failed': 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getEventIconClass = (status) => {
  const classes = {
    'pending': 'bg-gray-500',
    'picked_up': 'bg-blue-500',
    'in_transit': 'bg-yellow-500',
    'out_for_delivery': 'bg-orange-500',
    'delivered': 'bg-green-500',
    'failed': 'bg-red-500'
  }
  return classes[status] || 'bg-gray-500'
}

const getEventIcon = (status) => {
  const icons = {
    'pending': ClockIcon,
    'picked_up': TruckIcon,
    'in_transit': TruckIcon,
    'out_for_delivery': TruckIcon,
    'delivered': CheckCircleIcon,
    'failed': XCircleIcon
  }
  return icons[status] || ClockIcon
}

const formatDate = (date) => {
  if (!date) return 'Not available'
  return new Date(date).toLocaleDateString()
}

const formatDateTime = (date) => {
  if (!date) return 'Not available'
  return new Date(date).toLocaleString()
}

// Lifecycle
onMounted(async () => {
  await initializeMap()
  await fetchTrackingData()
  
  // Set up auto-refresh for active shipments
  if (shipment.value && ['picked_up', 'in_transit', 'out_for_delivery'].includes(shipment.value.status)) {
    const refreshInterval = setInterval(() => {
      fetchTrackingData()
    }, 30000) // Refresh every 30 seconds
    
    onUnmounted(() => {
      clearInterval(refreshInterval)
    })
  }
})

onUnmounted(() => {
  // Cleanup map
  if (map.value) {
    map.value.remove()
  }
})
</script>

<style scoped>
.custom-marker {
  background: transparent;
  border: none;
}

#tracking-map {
  z-index: 1;
}
</style>