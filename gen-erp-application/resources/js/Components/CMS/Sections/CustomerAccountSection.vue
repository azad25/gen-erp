<template>
  <div class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-6">
      <h1 class="text-3xl font-bold text-gray-900 mb-8">My Account</h1>
      
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Account Navigation -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-md p-6">
            <nav class="space-y-2">
              <a href="#" class="block px-3 py-2 text-blue-600 bg-blue-50 rounded-md font-medium" :class="{ 'pointer-events-none': isEditing }">
                Profile
              </a>
              <a href="#" class="block px-3 py-2 text-gray-700 hover:bg-gray-50 rounded-md" :class="{ 'pointer-events-none': isEditing }">
                Order History
              </a>
              <a href="#" class="block px-3 py-2 text-gray-700 hover:bg-gray-50 rounded-md" :class="{ 'pointer-events-none': isEditing }">
                Wishlist
              </a>
              <a href="#" class="block px-3 py-2 text-gray-700 hover:bg-gray-50 rounded-md" :class="{ 'pointer-events-none': isEditing }">
                Addresses
              </a>
              <a href="#" class="block px-3 py-2 text-gray-700 hover:bg-gray-50 rounded-md" :class="{ 'pointer-events-none': isEditing }">
                Settings
              </a>
            </nav>
          </div>
        </div>
        
        <!-- Account Content -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Profile Information</h2>
            
            <form class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                  <input type="text" value="John" class="w-full px-3 py-2 border border-gray-300 rounded-md" :disabled="isEditing" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                  <input type="text" value="Doe" class="w-full px-3 py-2 border border-gray-300 rounded-md" :disabled="isEditing" />
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" value="john@example.com" class="w-full px-3 py-2 border border-gray-300 rounded-md" :disabled="isEditing" />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                <input type="tel" value="+1 (555) 123-4567" class="w-full px-3 py-2 border border-gray-300 rounded-md" :disabled="isEditing" />
              </div>
              
              <div class="pt-4">
                <button
                  type="submit"
                  class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200"
                  :class="{ 'pointer-events-none opacity-50': isEditing }"
                >
                  Update Profile
                </button>
              </div>
            </form>
          </div>
          
          <!-- Account Stats -->
          <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow-md p-4 text-center">
              <div class="text-2xl font-bold text-blue-600">12</div>
              <div class="text-sm text-gray-600">Total Orders</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-4 text-center">
              <div class="text-2xl font-bold text-green-600">$1,234</div>
              <div class="text-sm text-gray-600">Total Spent</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-4 text-center">
              <div class="text-2xl font-bold text-purple-600">5</div>
              <div class="text-sm text-gray-600">Wishlist Items</div>
            </div>
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