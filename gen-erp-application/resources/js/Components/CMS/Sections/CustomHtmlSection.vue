<template>
  <div class="py-12">
    <div v-if="content.html" v-html="content.html"></div>
    
    <!-- Empty State for Editing -->
    <div
      v-else-if="isEditing"
      class="max-w-4xl mx-auto px-6 text-center py-12 border-2 border-dashed border-gray-300 rounded-lg"
    >
      <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No custom HTML added</h3>
      <p class="text-sm text-gray-500">Add custom HTML code using the properties panel</p>
    </div>
    
    <!-- Editing Overlay -->
    <div
      v-if="isEditing"
      class="absolute inset-0 bg-blue-500 bg-opacity-5 border border-blue-300 rounded"
    ></div>
  </div>
</template>

<script setup>
defineProps({
  content: {
    type: Object,
    default: () => ({})
  },
  isEditing: {
    type: Boolean,
    default: false
  }
})
</script>