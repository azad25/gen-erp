<template>
  <div class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">
          {{ content.heading || 'Gallery' }}
        </h2>
      </div>
      
      <div
        v-if="content.images && content.images.length > 0"
        :class="{
          'grid-cols-2': content.columns === 2,
          'grid-cols-3': content.columns === 3,
          'grid-cols-4': content.columns === 4
        }"
        class="grid gap-4"
      >
        <div
          v-for="(image, index) in content.images"
          :key="index"
          class="aspect-w-1 aspect-h-1 bg-gray-200 rounded-lg overflow-hidden hover:opacity-75 transition-opacity cursor-pointer"
          :class="{ 'pointer-events-none': isEditing }"
          @click="openLightbox(index)"
        >
          <img
            :src="image.url"
            :alt="image.alt || `Gallery image ${index + 1}`"
            class="w-full h-full object-cover"
          />
        </div>
      </div>
      
      <!-- Empty State for Editing -->
      <div
        v-else-if="isEditing"
        class="text-center py-12 border-2 border-dashed border-gray-300 rounded-lg"
      >
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No images added</h3>
        <p class="text-sm text-gray-500">Add images using the properties panel</p>
      </div>
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

const openLightbox = (index) => {
  // Lightbox functionality would be implemented here
  console.log('Open lightbox for image', index)
}
</script>