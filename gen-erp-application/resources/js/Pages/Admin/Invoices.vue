<template>
  
    
      <AppLayout>
        <div class="space-y-6">
          <!-- Page Header -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-black dark:text-white">
                Subscription Invoices
              </h1>
              <p class="text-sm text-gray-1 dark:text-gray-400">
                View and manage subscription invoices
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
                  placeholder="Search by company name or invoice number..."
                  class="w-full border rounded-lg px-3 py-2"
                />
              </div>
              <select v-model="statusFilter" class="border rounded-lg px-3 py-2">
                <option value="">All Status</option>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="overdue">Overdue</option>
              </select>
              <select v-model="planFilter" class="border rounded-lg px-3 py-2">
                <option value="">All Plans</option>
                <option value="free">Free</option>
                <option value="pro">Pro</option>
                <option value="enterprise">Enterprise</option>
              </select>
            </div>
          </Card>

          <!-- Invoices Table -->
          <Card>
            <div v-if="loading" class="flex items-center justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b">
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Invoice #</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Company</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Plan</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Amount</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Period</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Status</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Paid Date</th>
                    <th class="text-left p-4 font-semibold text-black dark:text-white">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr 
                    v-for="invoice in filteredInvoices" 
                    :key="invoice.id"
                    class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50"
                  >
                    <td class="p-4">
                      <p class="font-medium text-black dark:text-white">{{ invoice.invoice_number }}</p>
                    </td>
                    <td class="p-4">
                      <div>
                        <p class="font-medium text-black dark:text-white">{{ invoice.company?.name }}</p>
                        <p class="text-sm text-gray-1">{{ invoice.company?.email }}</p>
                      </div>
                    </td>
                    <td class="p-4">
                      <p class="font-medium text-black dark:text-white">{{ invoice.plan?.name }}</p>
                      <p class="text-sm text-gray-1">{{ invoice.plan?.slug }}</p>
                    </td>
                    <td class="p-4">
                      <p class="font-semibold text-black dark:text-white">
                        <span class="font-bangla">৳</span>{{ formatPrice(invoice.amount) }}
                      </p>
                    </td>
                    <td class="p-4">
                      <p class="text-sm text-black dark:text-white">
                        {{ formatDate(invoice.period_start) }} - {{ formatDate(invoice.period_end) }}
                      </p>
                    </td>
                    <td class="p-4">
                      <Badge :variant="getStatusVariant(invoice.status)">
                        {{ invoice.status }}
                      </Badge>
                    </td>
                    <td class="p-4">
                      <p class="text-sm text-black dark:text-white">{{ formatDate(invoice.paid_at) }}</p>
                    </td>
                    <td class="p-4">
                      <div class="flex items-center gap-2">
                        <Button variant="ghost" size="sm" @click="viewDetails(invoice)">
                          View
                        </Button>
                        <Button variant="secondary" size="sm" @click="downloadInvoice(invoice)">
                          Download
                        </Button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-if="filteredInvoices.length === 0" class="text-center py-12">
                <p class="text-gray-1">No invoices found</p>
              </div>
            </div>
          </Card>
        </div>

        <!-- View Details Modal -->
        <Modal v-if="showDetailsModal" @close="showDetailsModal = false" title="Invoice Details" size="lg">
          <div v-if="selectedInvoice" class="space-y-6">
            <!-- Invoice Header -->
            <div class="border-b pb-4">
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="text-xl font-bold text-black dark:text-white">
                    Invoice #{{ selectedInvoice.invoice_number }}
                  </h3>
                  <p class="text-sm text-gray-1">
                    {{ formatDate(selectedInvoice.period_start) }} - {{ formatDate(selectedInvoice.period_end) }}
                  </p>
                </div>
                <Badge :variant="getStatusVariant(selectedInvoice.status)">
                  {{ selectedInvoice.status }}
                </Badge>
              </div>
            </div>

            <!-- Company Info -->
            <div>
              <h3 class="font-semibold mb-2">Bill To</h3>
              <div class="space-y-1">
                <p class="font-medium text-black dark:text-white">{{ selectedInvoice.company?.name }}</p>
                <p class="text-sm text-gray-1">{{ selectedInvoice.company?.email }}</p>
                <p class="text-sm text-gray-1">{{ selectedInvoice.company?.phone }}</p>
                <p class="text-sm text-gray-1">{{ selectedInvoice.company?.address_line1 }}</p>
                <p class="text-sm text-gray-1">{{ selectedInvoice.company?.city }}, {{ selectedInvoice.company?.district }}</p>
                <p class="text-sm text-gray-1">{{ selectedInvoice.company?.country }}</p>
              </div>
            </div>

            <!-- Invoice Details -->
            <div>
              <h3 class="font-semibold mb-2">Invoice Details</h3>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-1">Plan</span>
                  <span class="font-semibold text-black dark:text-white">{{ selectedInvoice.plan?.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-1">Billing Cycle</span>
                  <span class="font-semibold text-black dark:text-white capitalize">{{ selectedInvoice.billing_cycle }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-1">Period</span>
                  <span class="font-semibold text-black dark:text-white">
                    {{ formatDate(selectedInvoice.period_start) }} - {{ formatDate(selectedInvoice.period_end) }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-1">Paid Date</span>
                  <span class="font-semibold text-black dark:text-white">{{ formatDate(selectedInvoice.paid_at) }}</span>
                </div>
              </div>
            </div>

            <!-- Total -->
            <div class="border-t pt-4">
              <div class="flex justify-between items-center">
                <span class="text-lg font-semibold text-black dark:text-white">Total</span>
                <span class="text-2xl font-bold text-black dark:text-white">
                  <span class="font-bangla">৳</span>{{ formatPrice(selectedInvoice.amount) }}
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
              <Button variant="primary" @click="downloadInvoice(selectedInvoice)">
                Download PDF
              </Button>
              <Button variant="secondary" @click="sendInvoice(selectedInvoice)">
                Send to Customer
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
const selectedInvoice = ref(null)

const invoices = ref([])

const filteredInvoices = computed(() => {
  return invoices.value.filter(inv => {
    const matchesSearch = !searchQuery.value || 
      inv.company?.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      inv.invoice_number?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchesStatus = !statusFilter.value || inv.status === statusFilter.value
    const matchesPlan = !planFilter.value || inv.plan?.slug === planFilter.value
    
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
    'paid': 'success',
    'pending': 'warning',
    'overdue': 'danger'
  }
  return variants[status] || 'secondary'
}

const viewDetails = (invoice) => {
  selectedInvoice.value = invoice
  showDetailsModal.value = true
}

const downloadInvoice = (invoice) => {
  alert(`Download invoice ${invoice.invoice_number} coming soon!`)
}

const sendInvoice = (invoice) => {
  alert(`Send invoice ${invoice.invoice_number} to customer coming soon!`)
}

const exportData = () => {
  alert('Export coming soon!')
}

const loadInvoices = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (statusFilter.value) params.append('status', statusFilter.value)
    if (planFilter.value) params.append('plan', planFilter.value)
    
    const response = await axios.get(`/api/v1/admin/subscription/invoices?${params}`)
    invoices.value = response.data.data
  } catch (error) {
    console.error('Failed to load invoices:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadInvoices()
})
</script>
