<template>
  
    
      <AppLayout>
        <div class="space-y-6">
          <!-- Page Header -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-black dark:text-white">
                Manage Subscriptions
              </h1>
              <p class="text-sm text-gray-1 dark:text-gray-400">
                View and manage all customer subscriptions
              </p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">
                Export
              </Button>
              <Button size="sm" @click="openDashboard">
                Dashboard
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
                <option value="active">Active</option>
                <option value="trialing">Trial</option>
                <option value="grace">Grace</option>
                <option value="expired">Expired</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <select v-model="planFilter" class="border rounded-lg px-3 py-2">
                <option value="">All Plans</option>
                <option value="free">Free</option>
                <option value="pro">Pro</option>
                <option value="enterprise">Enterprise</option>
              </select>
            </div>
          </Card>

          <!-- Subscriptions Table -->
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
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Status</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Billing</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Period</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Revenue</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr 
                    v-for="subscription in filteredSubscriptions" 
                    :key="subscription.id"
                    class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50"
                  >
                    <td class="p-4">
                      <div>
                        <p class="font-medium text-black dark:text-white">{{ subscription.company?.name }}</p>
                        <p class="text-sm text-gray-1">{{ subscription.company?.email }}</p>
                      </div>
                    </td>
                    <td class="p-4">
                      <p class="font-medium text-black dark:text-white">{{ subscription.plan?.name }}</p>
                      <p class="text-sm text-gray-1">{{ subscription.plan?.slug }}</p>
                    </td>
                    <td class="p-4">
                      <Badge :variant="getStatusVariant(subscription.status)">
                        {{ subscription.status }}
                      </Badge>
                    </td>
                    <td class="p-4">
                      <p class="text-sm text-black dark:text-white capitalize">{{ subscription.billing_cycle }}</p>
                    </td>
                    <td class="p-4">
                      <p class="text-sm text-black dark:text-white">
                        {{ formatDate(subscription.starts_at) }} - {{ formatDate(subscription.ends_at) }}
                      </p>
                    </td>
                    <td class="p-4">
                      <p class="font-semibold text-black dark:text-white">
                        <span class="font-bangla">৳</span>{{ formatPrice(subscription.amount) }}
                      </p>
                    </td>
                    <td class="p-4">
                      <div class="flex items-center gap-2">
                        <Button variant="ghost" size="sm" @click="viewDetails(subscription)">
                          View
                        </Button>
                        <Button 
                          v-if="subscription.status === 'active'"
                          variant="secondary" 
                          size="sm"
                          @click="pauseSubscription(subscription)"
                        >
                          Pause
                        </Button>
                        <Button 
                          v-if="subscription.status === 'grace'"
                          variant="primary" 
                          size="sm"
                          @click="activateSubscription(subscription)"
                        >
                          Activate
                        </Button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-if="filteredSubscriptions.length === 0" class="text-center py-12">
                <p class="text-gray-1">No subscriptions found</p>
              </div>
            </div>
          </Card>
        </div>

        <!-- View Details Modal -->
        <Modal v-if="showDetailsModal" @close="showDetailsModal = false" title="Subscription Details" size="lg">
          <div v-if="selectedSubscription" class="space-y-6">
            <!-- Company Info -->
            <div>
              <h3 class="font-semibold mb-2">Company Information</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-1">Company Name</p>
                  <p class="font-semibold text-black dark:text-white">{{ selectedSubscription.company?.name }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Email</p>
                  <p class="font-semibold text-black dark:text-white">{{ selectedSubscription.company?.email }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Phone</p>
                  <p class="font-semibold text-black dark:text-white">{{ selectedSubscription.company?.phone }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Location</p>
                  <p class="font-semibold text-black dark:text-white">{{ selectedSubscription.company?.city }}, {{ selectedSubscription.company?.country }}</p>
                </div>
              </div>
            </div>

            <!-- Subscription Info -->
            <div>
              <h3 class="font-semibold mb-2">Subscription Information</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-1">Plan</p>
                  <p class="font-semibold text-black dark:text-white">{{ selectedSubscription.plan?.name }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Status</p>
                  <Badge :variant="getStatusVariant(selectedSubscription.status)">
                    {{ selectedSubscription.status }}
                  </Badge>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Billing Cycle</p>
                  <p class="font-semibold text-black dark:text-white capitalize">{{ selectedSubscription.billing_cycle }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Amount</p>
                  <p class="font-semibold text-black dark:text-white">
                    <span class="font-bangla">৳</span>{{ formatPrice(selectedSubscription.amount) }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Started</p>
                  <p class="font-semibold text-black dark:text-white">{{ formatDate(selectedSubscription.starts_at) }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Ends</p>
                  <p class="font-semibold text-black dark:text-white">{{ formatDate(selectedSubscription.ends_at) }}</p>
                </div>
              </div>
            </div>

            <!-- Usage Info -->
            <div>
              <h3 class="font-semibold mb-2">Usage</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-1">Products</p>
                  <p class="font-semibold text-black dark:text-white">
                    {{ selectedSubscription.usage?.products }} / {{ selectedSubscription.usage?.products_limit }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Users</p>
                  <p class="font-semibold text-black dark:text-white">
                    {{ selectedSubscription.usage?.users }} / {{ selectedSubscription.usage?.users_limit }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Branches</p>
                  <p class="font-semibold text-black dark:text-white">
                    {{ selectedSubscription.usage?.branches }} / {{ selectedSubscription.usage?.branches_limit }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-1">Storage</p>
                  <p class="font-semibold text-black dark:text-white">
                    {{ formatStorage(selectedSubscription.usage?.storage) }} / {{ formatStorage(selectedSubscription.usage?.storage_limit) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
              <Button variant="primary" @click="editSubscription">
                Edit Subscription
              </Button>
              <Button variant="secondary" @click="viewInvoices">
                View Invoices
              </Button>
              <Button variant="danger" @click="cancelSubscription">
                Cancel Subscription
              </Button>
            </div>
          </div>
        </Modal>
      </AppLayout>
    
  
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
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
const selectedSubscription = ref(null)

const subscriptions = ref([])

const filteredSubscriptions = computed(() => {
  return subscriptions.value.filter(sub => {
    const matchesSearch = !searchQuery.value || 
      sub.company?.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      sub.company?.email?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchesStatus = !statusFilter.value || sub.status === statusFilter.value
    const matchesPlan = !planFilter.value || sub.plan?.slug === planFilter.value
    
    return matchesSearch && matchesStatus && matchesPlan
  })
})

const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('en-BD').format(price / 100)
}

const formatStorage = (bytes) => {
  if (!bytes) return '0 MB'
  const gb = bytes / (1024 * 1024 * 1024)
  return `${gb.toFixed(1)} GB`
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
    'active': 'success',
    'trialing': 'info',
    'grace': 'warning',
    'expired': 'danger',
    'cancelled': 'secondary'
  }
  return variants[status] || 'secondary'
}

const viewDetails = (subscription) => {
  selectedSubscription.value = subscription
  showDetailsModal.value = true
}

const pauseSubscription = async (subscription) => {
  if (!confirm(`Pause subscription for ${subscription.company?.name}?`)) return
  try {
    await axios.post(`/api/v1/admin/subscription/subscriptions/${subscription.id}/pause`)
    alert('Subscription paused successfully')
    await loadSubscriptions()
  } catch (error) {
    console.error('Failed to pause subscription:', error)
    alert('Failed to pause subscription')
  }
}

const activateSubscription = async (subscription) => {
  if (!confirm(`Activate subscription for ${subscription.company?.name}?`)) return
  try {
    await axios.post(`/api/v1/admin/subscription/subscriptions/${subscription.id}/activate`)
    alert('Subscription activated successfully')
    await loadSubscriptions()
  } catch (error) {
    console.error('Failed to activate subscription:', error)
    alert('Failed to activate subscription')
  }
}

const cancelSubscription = async () => {
  if (!confirm('Are you sure you want to cancel this subscription?')) return
  try {
    await axios.delete(`/api/v1/admin/subscription/subscriptions/${selectedSubscription.value.id}`)
    alert('Subscription cancelled successfully')
    showDetailsModal.value = false
    await loadSubscriptions()
  } catch (error) {
    console.error('Failed to cancel subscription:', error)
    alert('Failed to cancel subscription')
  }
}

const editSubscription = () => {
  alert('Edit subscription coming soon!')
}

const viewInvoices = () => {
  alert('View invoices coming soon!')
}

const exportData = () => {
  alert('Export coming soon!')
}

const openDashboard = () => {
  router.visit('/admin/subscription/dashboard')
}

const loadSubscriptions = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (statusFilter.value) params.append('status', statusFilter.value)
    if (planFilter.value) params.append('plan', planFilter.value)
    
    const response = await axios.get(`/api/v1/admin/subscription/subscriptions?${params}`)
    subscriptions.value = response.data.data
  } catch (error) {
    console.error('Failed to load subscriptions:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadSubscriptions()
})
</script>
