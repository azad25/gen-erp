<template>
  <div class="py-12 bg-white">
    <div class="max-w-3xl mx-auto px-6">
      <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">
          {{ content.heading || 'Frequently Asked Questions' }}
        </h2>
      </div>
      
      <div v-if="content.items && content.items.length > 0" class="space-y-4">
        <div
          v-for="(item, index) in content.items"
          :key="index"
          class="border border-gray-200 rounded-lg"
        >
          <button
            @click="toggleItem(index)"
            class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50"
            :class="{ 'pointer-events-none': isEditing }"
          >
            <span class="font-medium text-gray-900">{{ item.question }}</span>
            <svg
              :class="{ 'rotate-180': openItems.includes(index) }"
              class="w-5 h-5 text-gray-500 transform transition-transform"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div
            v-show="openItems.includes(index) || isEditing"
            class="px-6 pb-4 text-gray-600"
          >
            {{ item.answer }}
          </div>
        </div>
      </div>
      
      <!-- Empty State for Editing -->
      <div
        v-else-if="isEditing"
        class="text-center py-12 border-2 border-dashed border-gray-300 rounded-lg"
      >
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No FAQ items added</h3>
        <p class="text-sm text-gray-500">Add FAQ items using the properties panel</p>
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
import { ref } from 'vue'

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

const openItems = ref([])

const toggleItem = (index) => {
  const itemIndex = openItems.value.indexOf(index)
  if (itemIndex > -1) {
    openItems.value.splice(itemIndex, 1)
  } else {
    openItems.value.push(index)
  }
}
</script>