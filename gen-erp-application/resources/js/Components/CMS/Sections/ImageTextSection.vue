<template>
  <div class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-6">
      <div
        :class="{
          'lg:flex-row': content.image_position === 'left',
          'lg:flex-row-reverse': content.image_position === 'right'
        }"
        class="flex flex-col lg:flex-row items-center gap-12"
      >
        <!-- Image -->
        <div class="lg:w-1/2">
          <div class="aspect-w-16 aspect-h-9 bg-gray-200 rounded-lg overflow-hidden">
            <img
              v-if="content.image"
              :src="content.image"
              :alt="content.heading"
              class="w-full h-64 object-cover"
            />
            <div
              v-else
              class="w-full h-64 bg-gray-200 flex items-center justify-center"
            >
              <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
        </div>
        
        <!-- Content -->
        <div class="lg:w-1/2">
          <h2 class="text-3xl font-bold text-gray-900 mb-6">
            {{ content.heading || 'Feature Heading' }}
          </h2>
          
          <div
            v-if="content.body"
            class="prose prose-lg text-gray-600 mb-6"
            v-html="content.body"
          ></div>
          
          <div v-if="content.cta_text && content.cta_link">
            <a
              :href="content.cta_link"
              class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200"
              :class="{ 'pointer-events-none': isEditing }"
            >
              {{ content.cta_text }}
            </a>
          </div>
        </div>
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
</script>