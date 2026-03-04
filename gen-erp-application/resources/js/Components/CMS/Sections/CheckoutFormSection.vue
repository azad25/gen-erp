<template>
  <div class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-6">
      <h1 class="text-3xl font-bold text-gray-900 mb-8">Checkout</h1>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Checkout Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-6">Billing Information</h2>
          
          <form class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md" :disabled="isEditing" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md" :disabled="isEditing" />
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
              <input type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md" :disabled="isEditing" />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
              <input type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md" :disabled="isEditing" />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
              <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md" :disabled="isEditing" />
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md" :disabled="isEditing" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md" :disabled="isEditing" />
              </div>
            </div>
          </form>
        </div>
        
        <!-- Order Summary -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-6">Order Summary</h2>
          
          <div class="space-y-4 mb-6">
            <div v-for="i in 2" :key="i" class="flex justify-between items-center">
              <span class="text-gray-600">Sample Product {{ i }}</span>
              <span class="font-semibold">$99.99</span>
            </div>
          </div>
          
          <div class="border-t border-gray-200 pt-4 space-y-2">
            <div class="flex justify-between">
              <span>Subtotal:</span>
              <span>$199.98</span>
            </div>
            <div class="flex justify-between">
              <span>Shipping:</span>
              <span>$10.00</span>
            </div>
            <div class="flex justify-between font-semibold text-lg border-t border-gray-200 pt-2">
              <span>Total:</span>
              <span>$209.98</span>
            </div>
          </div>
          
          <button
            class="w-full mt-6 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200"
            :class="{ 'pointer-events-none opacity-50': isEditing }"
          >
            Place Order
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