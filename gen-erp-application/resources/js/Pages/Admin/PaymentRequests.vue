<template>
  
    
      <AppLayout>
        <div class="space-y-6">
          <!-- Page Header -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-black dark:text-white">
                Payment Requests
              </h1>
              <p class="text-sm text-gray-1 dark:text-gray-400">
                Manage subscription payment requests
              </p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">
                Export
              </Button>
            </div>
          </div>

          <!-- Filters -->
          <Card>
            <div class="flex items-center gap-4 p-4">
              <div class="flex-1">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search by company name or email..."
                  class="w-full border rounded-lg px-3 py-2"
                />
              </div>
              <select v-model="statusFilter" class="border rounded-lg px-3 py-2">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="verified">Verified</option>
                <option value="rejected">Rejected</option>
              </select>
              <select v-model="planFilter" class="border rounded-lg px-3 py-2">
                <option value="">All Plans</option>
                <option value="free">Free</option>
                <option value="pro">Pro</option>
                <option value="enterprise">Enterprise</option>
              </select>
            </div>
          </Card>

          <!-- Payment Requests Table -->
          <Card>
            <div v-if="loading" class="flex items-center justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b">
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Company</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Plan</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Amount</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Billing</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Status</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Date</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr 
                    v-for="request in filteredRequests" 
                    :key="request.id"
                    class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50"
                  >
                    <td class="p-4">
                      <div>
                        <p class="font-medium text-black dark:text-white">{{ request.company?.name }}</p>
                        <p class="text-sm text-gray-1">{{ request.company?.email }}</p>
                      </div>
                    </td>
                    <td class="p-4">
                      <p class="font-medium text-black dark:text-white">{{ request.plan?.name }}</p>
                      <p class="text-sm text-gray-1">{{ request.plan?.slug }}</p>
                    </td>
                    <td class="p-4">
                      <p class="font-semibold text-black dark:text-white">
                        <span class="font-bangla">৳</span>{{ formatPrice(request.amount) }}
                      </p>
                    </td>
                    <td class="p-4">
                      <p class="text-sm text-black dark:text-white capitalize">{{ request.billing_cycle }}</p>
                    </td>
                    <td class="p-4">
                      <Badge :variant="getStatusVariant(request.status)">
                        {{ request.status }}
                      </Badge>
                    </td>
                    <td class="p-4">
                      <p class="text-sm text-black dark:text-white">{{ formatDate(request.created_at) }}</p>
                    </td>
                    <td class="p-4">
                      <div class="flex items-center gap-2">
                        <Button 
                          v-if="request.status === 'pending'"
                          variant="ghost" 
                          size="sm"
                          @click="viewDetails(request)"
                        >
                          View
                        </Button>
                        <Button 
                          v-if="request.status === 'pending'"
                          variant="primary" 
                          size="sm"
                          @click="verifyPayment(request)"
                        >
                          Verify
                        </Button>
                        <Button 
                          v-if="request.status === 'pending'"
                          variant="danger" 
                          size="sm"
                          @click="rejectPayment(request)"
                        >
                          Reject
                        </Button>
                        <Badge 
                          v-if="request.status === 'verified'"
                          variant="success"
                        >
                          Verified
                        </Badge>
                        <Badge 
                          v-if="request.status === 'rejected'"
                          variant="danger"
                        >
                          Rejected
                        </Badge>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-if="filteredRequests.length === 0" class="text-center py-12">
                <p class="text-gray-1">No payment requests found</p>
              </div>
            </div>
          </Card>
        </div>

        <!-- View Details Modal -->
        <Modal v-if="showDetailsModal" @close="showDetailsModal = false" title="Payment Request Details" size="lg">
          <div v-if="selectedRequest" class="space-y-6">
            <!-- Company Info -->
            <div>
              <h3 class="font-semibold mb-2">Company Information</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-1">Company Name</p>
                  <p class="font-semibold text-black dark:text-white">{{ selectedRequest.company?.name }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Email</p>
                  <p class="font-semibold text-black dark:text-white">{{ selectedRequest.company?.email }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Phone</p>
                  <p class="font-semibold text-black dark:text-white">{{ selectedRequest.company?.phone }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Location</p>
                  <p class="font-semibold text-black dark:text-white">{{ selectedRequest.company?.city }}, {{ selectedRequest.company?.country }}</p>
                </div>
              </div>
            </div>

            <!-- Payment Info -->
            <div>
              <h3 class="font-semibold mb-2">Payment Information</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-1">Plan</p>
                  <p class="font-semibold text-black dark:text-white">{{ selectedRequest.plan?.name }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Billing Cycle</p>
                  <p class="font-semibold text-black dark:text-white capitalize">{{ selectedRequest.billing_cycle }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Amount</p>
                  <p class="font-semibold text-black dark:text-white">
                    <span class="font-bangla">৳</span>{{ formatPrice(selectedRequest.amount) }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Status</p>
                  <Badge :variant="getStatusVariant(selectedRequest.status)">
                    {{ selectedRequest.status }}
                  </Badge>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Created</p>
                  <p class="font-semibold text-black dark:text-white">{{ formatDate(selectedRequest.created_at) }}</p>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div v-if="selectedRequest.status === 'pending'" class="flex gap-2">
              <Button variant="primary" @click="verifyPayment(selectedRequest)">
                Verify Payment
              </Button>
              <Button variant="danger" @click="rejectPayment(selectedRequest)">
                Reject Payment
              </Button>
            </div>
          </div>
        </Modal>
      </AppLayout>
    
  
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue"
import Card from '@/Components/UI/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/UI/Badge.vue'
import Modal from '@/Components/UI/Modal.vue'

const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref('')
const planFilter = ref('')
const showDetailsModal = ref(false)
const selectedRequest = ref(null)

const paymentRequests = ref([])

const filteredRequests = computed(() => {
  return paymentRequests.value.filter(req => {
    const matchesSearch = !searchQuery.value || 
      req.company?.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      req.company?.email?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchesStatus = !statusFilter.value || req.status === statusFilter.value
    const matchesPlan = !planFilter.value || req.plan?.slug === planFilter.value
    
    return matchesSearch && matchesStatus && matchesPlan
  })
})

const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('en-BD').format(price / 100)
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-BD', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusVariant = (status) => {
  const variants = {
    'pending': 'warning',
    'verified': 'success',
    'rejected': 'danger'
  }
  return variants[status] || 'secondary'
}

const viewDetails = (request) => {
  selectedRequest.value = request
  showDetailsModal.value = true
}

const verifyPayment = async (request) => {
  if (!confirm(`Verify payment from ${request.company?.name} for ৳${formatPrice(request.amount)}?`)) return
  try {
    await axios.post(`/api/v1/admin/subscription/payment-requests/${request.id}/verify`)
    alert('Payment verified successfully')
    showDetailsModal.value = false
    await loadPaymentRequests()
  } catch (error) {
    console.error('Failed to verify payment:', error)
    alert('Failed to verify payment')
  }
}

const rejectPayment = async (request) => {
  const note = prompt('Enter rejection reason:')
  if (!note) return
  
  try {
    await axios.post(`/api/v1/admin/subscription/payment-requests/${request.id}/reject`, { note })
    alert('Payment rejected successfully')
    showDetailsModal.value = false
    await loadPaymentRequests()
  } catch (error) {
    console.error('Failed to reject payment:', error)
    alert('Failed to reject payment')
  }
}

const exportData = () => {
  alert('Export coming soon!')
}

const loadPaymentRequests = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (statusFilter.value) params.append('status', statusFilter.value)
    if (planFilter.value) params.append('plan', planFilter.value)
    
    const response = await axios.get(`/api/v1/admin/subscription/payment-requests?${params}`)
    paymentRequests.value = response.data.data
  } catch (error) {
    console.error('Failed to load payment requests:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadPaymentRequests()
})
</script>
