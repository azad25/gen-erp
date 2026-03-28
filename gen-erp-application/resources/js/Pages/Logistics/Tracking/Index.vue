<template>
  <AppLayout title="Tracking">
    <div class="p-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
        Shipment Tracking
      </h1>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <p class="text-gray-600 dark:text-gray-400">
          Shipment tracking functionality will be implemented here.
        </p>
        <div v-if="trackingData" class="mt-4">
          <pre class="text-sm">{{ JSON.stringify(trackingData, null, 2) }}</pre>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
  trackingData: {
    type: Object,
    default: () => ({})
  }
})
</script>