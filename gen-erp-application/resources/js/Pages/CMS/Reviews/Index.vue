<template>
  <div>
    <Head title="Product Reviews" />
    
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-semibold text-gray-900">Product Reviews</h1>
              <div class="flex space-x-2">
                <button
                  @click="approveAllPending"
                  class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                  Approve All Pending
                </button>
                <button
                  @click="exportReviews"
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                  Export Reviews
                </button>
              </div>
            </div>

            <div class="mb-4 flex space-x-4">
              <select
                v-model="selectedStatus"
                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
              
              <select
                v-model="selectedRating"
                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">All Ratings</option>
                <option value="5">5 Stars</option>
                <option value="4">4 Stars</option>
                <option value="3">3 Stars</option>
                <option value="2">2 Stars</option>
                <option value="1">1 Star</option>
              </select>

              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search reviews..."
                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Review
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Product
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Rating
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="review in filteredReviews" :key="review.id" :class="{ 'bg-yellow-50': review.status === 'pending' }">
                    <td class="px-6 py-4">
                      <div>
                        <div class="text-sm font-medium text-gray-900">
                          {{ review.customer_name }}
                        </div>
                        <div class="text-sm text-gray-500">
                          {{ review.customer_email }}
                        </div>
                        <div class="text-sm text-gray-900 mt-1">
                          "{{ review.comment }}"
                        </div>
                        <div v-if="review.is_verified_purchase" class="mt-1">
                          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Verified Purchase
                          </span>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">{{ review.product_name }}</div>
                      <div class="text-sm text-gray-500">SKU: {{ review.product_sku }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex">
                          <svg
                            v-for="star in 5"
                            :key="star"
                            :class="[
                              star <= review.rating ? 'text-yellow-400' : 'text-gray-300',
                              'h-4 w-4'
                            ]"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                          >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                          </svg>
                        </div>
                        <span class="ml-2 text-sm text-gray-600">{{ review.rating }}/5</span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        :class="{
                          'bg-yellow-100 text-yellow-800': review.status === 'pending',
                          'bg-green-100 text-green-800': review.status === 'approved',
                          'bg-red-100 text-red-800': review.status === 'rejected'
                        }"
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                      >
                        {{ review.status }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(review.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex space-x-2">
                        <button
                          v-if="review.status === 'pending'"
                          @click="approveReview(review.id)"
                          class="text-green-600 hover:text-green-900"
                        >
                          Approve
                        </button>
                        <button
                          v-if="review.status === 'pending'"
                          @click="rejectReview(review.id)"
                          class="text-red-600 hover:text-red-900"
                        >
                          Reject
                        </button>
                        <button
                          @click="deleteReview(review.id)"
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

            <div v-if="filteredReviews.length === 0" class="text-center py-12">
              <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No reviews</h3>
                <p class="mt-1 text-sm text-gray-500">Product reviews will appear here when customers submit them.</p>
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

const selectedStatus = ref('')
const selectedRating = ref('')
const searchQuery = ref('')

const reviews = ref([
  {
    id: 1,
    customer_name: 'Alice Johnson',
    customer_email: 'alice@example.com',
    product_name: 'Premium Widget',
    product_sku: 'PWD-001',
    rating: 5,
    comment: 'Excellent product! Highly recommend it to everyone.',
    status: 'approved',
    is_verified_purchase: true,
    created_at: '2024-01-15T10:30:00Z'
  },
  {
    id: 2,
    customer_name: 'Bob Smith',
    customer_email: 'bob@example.com',
    product_name: 'Standard Widget',
    product_sku: 'SWD-001',
    rating: 3,
    comment: 'Good product but could be better. The quality is okay.',
    status: 'pending',
    is_verified_purchase: false,
    created_at: '2024-01-14T14:20:00Z'
  }
])

const filteredReviews = computed(() => {
  return reviews.value.filter(review => {
    const statusMatch = !selectedStatus.value || review.status === selectedStatus.value
    const ratingMatch = !selectedRating.value || review.rating.toString() === selectedRating.value
    const searchMatch = !searchQuery.value || 
      review.customer_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      review.product_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      review.comment.toLowerCase().includes(searchQuery.value.toLowerCase())
    return statusMatch && ratingMatch && searchMatch
  })
})

const approveReview = (reviewId) => {
  // TODO: Implement review approval
  alert('Review approval functionality will be implemented')
}

const rejectReview = (reviewId) => {
  // TODO: Implement review rejection
  alert('Review rejection functionality will be implemented')
}

const deleteReview = (reviewId) => {
  if (confirm('Are you sure you want to delete this review?')) {
    // TODO: Implement review deletion
    alert('Review deletion functionality will be implemented')
  }
}

const approveAllPending = () => {
  if (confirm('Are you sure you want to approve all pending reviews?')) {
    // TODO: Implement bulk approval
    alert('Bulk approval functionality will be implemented')
  }
}

const exportReviews = () => {
  // TODO: Implement review export
  alert('Review export functionality will be implemented')
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}
</script>