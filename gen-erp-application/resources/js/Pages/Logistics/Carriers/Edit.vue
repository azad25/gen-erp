<template>
  <AppLayout title="Edit Carrier">
    <div class="p-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
        Edit Carrier
      </h1>
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <p class="text-gray-600 dark:text-gray-400">
          Edit carrier with ID: {{ carrierId }}
        </p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
  carrierId: {
    type: [String, Number],
    required: true
  }
})
</script>