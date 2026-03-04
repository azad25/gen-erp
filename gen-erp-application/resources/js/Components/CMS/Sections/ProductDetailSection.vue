<template>
  <div class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-6">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Product Images -->
        <div>
          <div class="aspect-w-1 aspect-h-1 bg-gray-200 rounded-lg overflow-hidden mb-4">
            <div class="w-full h-96 bg-gray-200 flex items-center justify-center">
              <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          
          <!-- Thumbnail Gallery -->
          <div class="grid grid-cols-4 gap-2">
            <div v-for="i in 4" :key="i" class="aspect-w-1 aspect-h-1 bg-gray-200 rounded-lg">
              <div class="w-full h-20 bg-gray-200"></div>
            </div>
          </div>
        </div>
        
        <!-- Product Info -->
        <div>
          <h1 class="text-3xl font-bold text-gray-900 mb-4">Sample Product</h1>
          
          <div class="flex items-center mb-4">
            <div class="flex text-yellow-400 mr-2">
              <svg v-for="i in 5" :key="i" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <span class="text-sm text-gray-600">(24 reviews)</span>
          </div>
          
          <div class="mb-6">
            <span class="text-3xl font-bold text-gray-900">$99.99</span>
            <span class="text-lg text-gray-500 line-through ml-2">$129.99</span>
          </div>
          
          <p class="text-gray-600 mb-6">
            This is a sample product description. In a real implementation, this would be populated with actual product data from the ERP system.
          </p>
          
          <!-- Add to Cart -->
          <div class="flex items-center space-x-4 mb-6">
            <div class="flex items-center border border-gray-300 rounded-lg">
              <button class="px-3 py-2 text-gray-600 hover:text-gray-800" :disabled="isEditing">-</button>
              <span class="px-4 py-2 border-l border-r border-gray-300">1</span>
              <button class="px-3 py-2 text-gray-600 hover:text-gray-800" :disabled="isEditing">+</button>
            </div>
            <button
              class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200"
              :class="{ 'pointer-events-none opacity-50': isEditing }"
            >
              Add to Cart
            </button>
          </div>
          
          <!-- Product Details -->
          <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Product Details</h3>
            <dl class="space-y-2">
              <div class="flex">
                <dt class="w-1/3 text-sm font-medium text-gray-500">SKU:</dt>
                <dd class="text-sm text-gray-900">SAMPLE-001</dd>
              </div>
              <div class="flex">
                <dt class="w-1/3 text-sm font-medium text-gray-500">Category:</dt>
                <dd class="text-sm text-gray-900">Sample Category</dd>
              </div>
              <div class="flex">
                <dt class="w-1/3 text-sm font-medium text-gray-500">Stock:</dt>
                <dd class="text-sm text-green-600">In Stock</dd>
              </div>
            </dl>
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