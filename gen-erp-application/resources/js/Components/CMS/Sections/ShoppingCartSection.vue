<template>
  <div class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-6">
      <h1 class="text-3xl font-bold text-gray-900 mb-8">Shopping Cart</h1>
      
      <div class="bg-white rounded-lg shadow-md p-6">
        <!-- Cart Items -->
        <div class="space-y-4 mb-6">
          <div v-for="i in 2" :key="i" class="flex items-center space-x-4 py-4 border-b border-gray-200">
            <div class="w-16 h-16 bg-gray-200 rounded-lg"></div>
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900">Sample Product {{ i }}</h3>
              <p class="text-sm text-gray-600">$99.99</p>
            </div>
            <div class="flex items-center space-x-2">
              <button class="px-2 py-1 border border-gray-300 rounded" :disabled="isEditing">-</button>
              <span class="px-3">1</span>
              <button class="px-2 py-1 border border-gray-300 rounded" :disabled="isEditing">+</button>
            </div>
            <button class="text-red-600 hover:text-red-800" :disabled="isEditing">Remove</button>
          </div>
        </div>
        
        <!-- Cart Summary -->
        <div class="border-t border-gray-200 pt-6">
          <div class="flex justify-between items-center mb-4">
            <span class="text-lg font-semibold">Total: $199.98</span>
          </div>
          <button
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200"
            :class="{ 'pointer-events-none opacity-50': isEditing }"
          >
            Proceed to Checkout
          </button>
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