<template>
  <div
    :class="{
      'bg-white': content.background === 'light',
      'bg-gray-900 text-white': content.background === 'dark',
      'bg-blue-600 text-white': content.background === 'brand'
    }"
    class="py-12"
  >
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-12">
        <h2
          :class="{
            'text-gray-900': content.background === 'light',
            'text-white': content.background === 'dark' || content.background === 'brand'
          }"
          class="text-3xl font-bold mb-4"
        >
          {{ content.heading || 'Our Numbers' }}
        </h2>
      </div>
      
      <div
        v-if="content.items && content.items.length > 0"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8"
      >
        <div
          v-for="(item, index) in content.items"
          :key="index"
          class="text-center"
        >
          <div
            :class="{
              'text-gray-900': content.background === 'light',
              'text-white': content.background === 'dark' || content.background === 'brand'
            }"
            class="text-4xl font-bold mb-2"
          >
            {{ item.number }}
          </div>
          <div
            :class="{
              'text-gray-600': content.background === 'light',
              'text-gray-300': content.background === 'dark' || content.background === 'brand'
            }"
            class="text-lg"
          >
            {{ item.label }}
          </div>
        </div>
      </div>
      
      <!-- Empty State for Editing -->
      <div
        v-else-if="isEditing"
        class="text-center py-12"
      >
        <div
          :class="{
            'text-gray-400 border-gray-300': content.background === 'light',
            'text-gray-500 border-gray-600': content.background === 'dark' || content.background === 'brand'
          }"
          class="border-2 border-dashed rounded-lg py-8"
        >
          <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          <h3 class="text-lg font-medium mb-2">No statistics added</h3>
          <p class="text-sm">Add statistics using the properties panel</p>
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