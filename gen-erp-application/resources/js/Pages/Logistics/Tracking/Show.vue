<template>
  <AppLayout title="Track Shipment">
    <div class="p-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
        Track Shipment: {{ trackingNumber }}
      </h1>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div v-if="trackingInfo">
          <h3 class="text-lg font-semibold mb-4">Tracking Information</h3>
          <pre class="text-sm bg-gray-100 dark:bg-gray-700 p-4 rounded">{{ JSON.stringify(trackingInfo, null, 2) }}</pre>
        </div>
        <div v-else>
          <p class="text-gray-600 dark:text-gray-400">
            No tracking information available for: {{ trackingNumber }}
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
  trackingNumber: {
    type: String,
    required: true
  },
  trackingInfo: {
    type: Object,
    default: () => ({})
  }
})
</script>