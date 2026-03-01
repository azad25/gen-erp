<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Purchase Returns</h1>
              <p class="text-sm text-gray-1">Manage purchase returns for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Return</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="returns"
            :pagination="pagination"
            placeholder="Search returns..."
            @search="handleSearch"
          >
            <template #cell-return_number="{ row }">
              <span class="font-mono text-sm">{{ row.return_number }}</span>
            </template>

            <template #cell-supplier="{ row }">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold">
                  {{ row.supplier?.name?.charAt(0) || 'S' }}
                </div>
                <span class="text-sm">{{ row.supplier?.name || '—' }}</span>
              </div>
            </template>

            <template #cell-status="{ row }">
              <Badge :variant="getStatusVariant(row.status)">{{ row.status }}</Badge>
            </template>

            <template #cell-amount="{ row }">
              <span class="font-semibold">{{ formatCurrency(row.amount) }}</span>
            </template>

            <template #cell-return_date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.return_date) }}</span>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewReturn(row)">View</Button>
              <Button variant="ghost" size="sm" @click="editReturn(row)" :disabled="row.status !== 'draft'">Edit</Button>
              <Button variant="ghost" size="sm" @click="approveReturn(row)" :disabled="row.status !== 'pending'">Approve</Button>
              <Button variant="ghost" size="sm" @click="rejectReturn(row)" :disabled="row.status !== 'pending'">Reject</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteReturn(row)" :disabled="row.status !== 'draft'">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" :title="isEditing ? 'Edit Purchase Return' : 'New Purchase Return'">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Supplier *</label>
                <select v-model="form.supplier_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Supplier</option>
                  <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                    {{ supplier.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Purchase Order (Optional)</label>
                <select v-model="form.purchase_order_id" class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Purchase Order</option>
                  <option v-for="order in purchaseOrders" :key="order.id" :value="order.id">
                    {{ order.order_number }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Return Date *</label>
                <input type="date" v-model="form.return_date" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Amount *</label>
                <input type="number" v-model="form.amount" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Reason</label>
                <textarea v-model="form.reason" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
              <Button type="submit">{{ isEditing ? 'Update' : 'Create' }}</Button>
            </div>
          </form>
        </Modal>

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Purchase Return Details" size="lg">
          <div v-if="selectedReturn" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Return Number</p>
                <p class="font-semibold">{{ selectedReturn.return_number }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="getStatusVariant(selectedReturn.status)">{{ selectedReturn.status }}</Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Supplier</p>
                <p class="font-semibold">{{ selectedReturn.supplier?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Purchase Order</p>
                <p class="font-semibold">{{ selectedReturn.purchase_order?.order_number || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Return Date</p>
                <p class="font-semibold">{{ formatDate(selectedReturn.return_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Amount</p>
                <p class="font-semibold text-lg">{{ formatCurrency(selectedReturn.amount) }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Reason</p>
                <p class="font-semibold">{{ selectedReturn.reason || '—' }}</p>
              </div>
            </div>
          </div>
        </Modal>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import DataTable from '@/Components/UI/DataTable.vue'
import Badge from '@/Components/UI/Badge.vue'
import Modal from '@/Components/UI/Modal.vue'

const returns = ref([])
const suppliers = ref([])
const purchaseOrders = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditing = ref(false)
const selectedReturn = ref(null)
const searchQuery = ref('')

const form = ref({
  supplier_id: '',
  purchase_order_id: '',
  return_date: new Date().toISOString().split('T')[0],
  amount: 0,
  reason: ''
})

const columns = [
  { key: 'return_number', label: 'Return #' },
  { key: 'supplier', label: 'Supplier' },
  { key: 'status', label: 'Status' },
  { key: 'amount', label: 'Amount', right: true },
  { key: 'return_date', label: 'Date' }
]

const fetchReturns = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/purchase-returns', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    returns.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch returns:', error)
  } finally {
    loading.value = false
  }
}

const fetchSuppliers = async () => {
  try {
    const response = await axios.get('/api/v1/suppliers', { params: { per_page: 100 } })
    suppliers.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch suppliers:', error)
  }
}

const fetchPurchaseOrders = async () => {
  try {
    const response = await axios.get('/api/v1/purchase-orders', { params: { per_page: 100 } })
    purchaseOrders.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch purchase orders:', error)
  }
}

const handleSearch = (query) => {
  searchQuery.value = query
  fetchReturns(1)
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = {
    supplier_id: '',
    purchase_order_id: '',
    return_date: new Date().toISOString().split('T')[0],
    amount: 0,
    reason: ''
  }
  showModal.value = true
}

const editReturn = (returnItem) => {
  isEditing.value = true
  selectedReturn.value = returnItem
  form.value = {
    supplier_id: returnItem.supplier_id,
    purchase_order_id: returnItem.purchase_order_id,
    return_date: returnItem.return_date,
    amount: returnItem.amount,
    reason: returnItem.reason
  }
  showModal.value = true
}

const viewReturn = (returnItem) => {
  selectedReturn.value = returnItem
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/v1/purchase-returns/${selectedReturn.value.id}`, form.value)
    } else {
      await axios.post('/api/v1/purchase-returns', form.value)
    }
    closeModal()
    fetchReturns(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save return:', error)
  }
}

const approveReturn = async (returnItem) => {
  if (!confirm('Are you sure you want to approve this return?')) return
  try {
    await axios.post(`/api/v1/purchase-returns/${returnItem.id}/approve`)
    fetchReturns(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to approve return:', error)
  }
}

const rejectReturn = async (returnItem) => {
  if (!confirm('Are you sure you want to reject this return?')) return
  try {
    await axios.post(`/api/v1/purchase-returns/${returnItem.id}/reject`)
    fetchReturns(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to reject return:', error)
  }
}

const deleteReturn = async (returnItem) => {
  if (!confirm('Are you sure you want to delete this return?')) return
  try {
    await axios.delete(`/api/v1/purchase-returns/${returnItem.id}`)
    fetchReturns(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete return:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  selectedReturn.value = null
}

const exportData = () => {
  window.print()
}

const getStatusVariant = (status) => {
  const variants = {
    draft: 'secondary',
    pending: 'default',
    approved: 'default',
    rejected: 'destructive',
    processed: 'default'
  }
  return variants[status] || 'secondary'
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount / 100)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  fetchReturns()
  fetchSuppliers()
  fetchPurchaseOrders()
})
</script>
