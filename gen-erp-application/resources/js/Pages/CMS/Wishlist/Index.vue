<template>
  <div>
    <Head title="Customer Wishlists" />
    
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Customer Wishlists</h1>
              <div class="flex space-x-2">
                <button
                  @click="exportWishlists"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                  Export Data
                </button>
                <button
                  @click="sendWishlistReminders"
                  class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                  Send Reminders
                </button>
              </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
              <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">{{ totalWishlists }}</div>
                <div class="text-sm text-blue-600">Total Wishlists</div>
              </div>
              <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ activeWishlists }}</div>
                <div class="text-sm text-green-600">Active Wishlists</div>
              </div>
              <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600">{{ totalItems }}</div>
                <div class="text-sm text-yellow-600">Total Items</div>
              </div>
              <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-600">{{ averageItems }}</div>
                <div class="text-sm text-purple-600">Avg Items/Wishlist</div>
              </div>
            </div>

            <div class="mb-4 flex space-x-4">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by customer name or email..."
                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
              />
              
              <select
                v-model="sortBy"
                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="updated_at">Recently Updated</option>
                <option value="created_at">Recently Created</option>
                <option value="items_count">Most Items</option>
                <option value="customer_name">Customer Name</option>
              </select>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Customer
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Items
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Total Value
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Last Updated
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="wishlist in filteredWishlists" :key="wishlist.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                          <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-700">
                              {{ wishlist.customer_name.charAt(0).toUpperCase() }}
                            </span>
                          </div>
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900">
                            {{ wishlist.customer_name }}
                          </div>
                          <div class="text-sm text-gray-500">
                            {{ wishlist.customer_email }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">{{ wishlist.items_count }} items</div>
                      <div class="text-sm text-gray-500">
                        <span v-if="wishlist.recent_items.length > 0">
                          Recent: {{ wishlist.recent_items.slice(0, 2).map(item => item.name).join(', ') }}
                          <span v-if="wishlist.recent_items.length > 2">...</span>
                        </span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">
                        ${{ wishlist.total_value.toFixed(2) }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(wishlist.updated_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex space-x-2">
                        <button
                          @click="viewWishlist(wishlist)"
                          class="text-blue-600 hover:text-blue-900"
                        >
                          View
                        </button>
                        <button
                          @click="sendReminder(wishlist)"
                          class="text-green-600 hover:text-green-900"
                        >
                          Remind
                        </button>
                        <button
                          @click="deleteWishlist(wishlist.id)"
                          class="text-red-600 hover:text-red-900"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="filteredWishlists.length === 0" class="text-center py-12">
              <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No wishlists</h3>
                <p class="mt-1 text-sm text-gray-500">Customer wishlists will appear here when they save items.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const searchQuery = ref('')
const sortBy = ref('updated_at')

const wishlists = ref([
  {
    id: 1,
    customer_name: 'Sarah Wilson',
    customer_email: 'sarah@example.com',
    items_count: 5,
    total_value: 299.95,
    updated_at: '2024-01-15T10:30:00Z',
    recent_items: [
      { name: 'Premium Widget' },
      { name: 'Deluxe Gadget' },
      { name: 'Super Tool' }
    ]
  },
  {
    id: 2,
    customer_name: 'Mike Johnson',
    customer_email: 'mike@example.com',
    items_count: 3,
    total_value: 149.97,
    updated_at: '2024-01-14T14:20:00Z',
    recent_items: [
      { name: 'Standard Widget' },
      { name: 'Basic Tool' }
    ]
  }
])

const totalWishlists = computed(() => wishlists.value.length)
const activeWishlists = computed(() => wishlists.value.filter(w => w.items_count > 0).length)
const totalItems = computed(() => wishlists.value.reduce((sum, w) => sum + w.items_count, 0))
const averageItems = computed(() => {
  if (wishlists.value.length === 0) return 0
  return Math.round(totalItems.value / wishlists.value.length * 10) / 10
})

const filteredWishlists = computed(() => {
  let filtered = wishlists.value.filter(wishlist => {
    const searchMatch = !searchQuery.value || 
      wishlist.customer_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      wishlist.customer_email.toLowerCase().includes(searchQuery.value.toLowerCase())
    return searchMatch
  })

  // Sort the results
  filtered.sort((a, b) => {
    switch (sortBy.value) {
      case 'updated_at':
        return new Date(b.updated_at) - new Date(a.updated_at)
      case 'created_at':
        return new Date(b.created_at) - new Date(a.created_at)
      case 'items_count':
        return b.items_count - a.items_count
      case 'customer_name':
        return a.customer_name.localeCompare(b.customer_name)
      default:
        return 0
    }
  })

  return filtered
})

const viewWishlist = (wishlist) => {
  // TODO: Implement wishlist viewing modal
  alert(`View wishlist for: ${wishlist.customer_name}`)
}

const sendReminder = (wishlist) => {
  // TODO: Implement reminder sending
  alert(`Send reminder to: ${wishlist.customer_email}`)
}

const deleteWishlist = (wishlistId) => {
  if (confirm('Are you sure you want to delete this wishlist?')) {
    // TODO: Implement wishlist deletion
    alert('Wishlist deletion functionality will be implemented')
  }
}

const exportWishlists = () => {
  // TODO: Implement wishlist export
  alert('Wishlist export functionality will be implemented')
}

const sendWishlistReminders = () => {
  // TODO: Implement bulk reminder sending
  alert('Bulk reminder functionality will be implemented')
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}
</script>