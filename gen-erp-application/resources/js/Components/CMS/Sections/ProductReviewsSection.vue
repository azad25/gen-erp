<template>
  <div class="py-12 bg-white">
    <div class="max-w-4xl mx-auto px-6">
      <h2 class="text-2xl font-bold text-gray-900 mb-8">Customer Reviews</h2>
      
      <!-- Review Summary -->
      <div class="bg-gray-50 rounded-lg p-6 mb-8">
        <div class="flex items-center space-x-4">
          <div class="text-center">
            <div class="text-3xl font-bold text-gray-900">4.5</div>
            <div class="flex text-yellow-400 justify-center">
              <svg v-for="i in 5" :key="i" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </div>
            <div class="text-sm text-gray-600">Based on 24 reviews</div>
          </div>
          
          <div class="flex-1">
            <div v-for="rating in [5, 4, 3, 2, 1]" :key="rating" class="flex items-center space-x-2 mb-1">
              <span class="text-sm text-gray-600 w-8">{{ rating }}</span>
              <div class="flex-1 bg-gray-200 rounded-full h-2">
                <div class="bg-yellow-400 h-2 rounded-full" :style="{ width: `${Math.random() * 100}%` }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Individual Reviews -->
      <div class="space-y-6">
        <div v-for="i in 3" :key="i" class="border-b border-gray-200 pb-6">
          <div class="flex items-start space-x-4">
            <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
            <div class="flex-1">
              <div class="flex items-center space-x-2 mb-2">
                <span class="font-semibold text-gray-900">Customer {{ i }}</span>
                <div class="flex text-yellow-400">
                  <svg v-for="j in 5" :key="j" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </div>
                <span class="text-sm text-gray-500">2 days ago</span>
              </div>
              <p class="text-gray-600">
                Great product! Really happy with the quality and fast shipping. Would definitely recommend to others.
              </p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Write Review Button -->
      <div class="mt-8 text-center">
        <button
          class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200"
          :class="{ 'pointer-events-none opacity-50': isEditing }"
        >
          Write a Review
        </button>
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