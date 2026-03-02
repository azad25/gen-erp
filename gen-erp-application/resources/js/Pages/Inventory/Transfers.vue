<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Stock Transfers</h1>
              <p class="text-sm text-gray-1">Manage stock transfers between warehouses</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Transfer</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="transfers"
            :pagination="pagination"
            placeholder="Search transfers..."
            @search="handleSearch"
          >
            <template #cell-transfer_number="{ row }">
              <span class="font-mono text-sm">{{ row.transfer_number }}</span>
            </template>

            <template #cell-from="{ row }">
              <div class="flex items-center gap-2">
                <span class="text-sm">{{ row.from_warehouse?.name || '—' }}</span>
                <span class="text-gray-400">→</span>
                <span class="text-sm">{{ row.to_warehouse?.name || '—' }}</span>
              </div>
            </template>

            <template #cell-product="{ row }">
              <span class="text-sm">{{ row.product?.name || '—' }}</span>
            </template>

            <template #cell-status="{ row }">
              <Badge :variant="getStatusVariant(row.status)">{{ row.status }}</Badge>
            </template>

            <template #cell-quantity="{ row }">
              <span class="font-semibold">{{ row.quantity }}</span>
            </template>

            <template #cell-transfer_date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.transfer_date) }}</span>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewTransfer(row)">View</Button>
              <Button variant="ghost" size="sm" @click="confirmTransfer(row)" :disabled="row.status !== 'pending'">Confirm</Button>
              <Button variant="ghost" size="sm" @click="cancelTransfer(row)" :disabled="!['pending', 'confirmed'].includes(row.status)">Cancel</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteTransfer(row)" :disabled="row.status !== 'pending'">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" title="New Stock Transfer">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">From Warehouse *</label>
                <select v-model="form.from_warehouse_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Warehouse</option>
                  <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                    {{ warehouse.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">To Warehouse *</label>
                <select v-model="form.to_warehouse_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Warehouse</option>
                  <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                    {{ warehouse.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Product *</label>
                <select v-model="form.product_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Product</option>
                  <option v-for="product in products" :key="product.id" :value="product.id">
                    {{ product.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Quantity *</label>
                <input type="number" v-model="form.quantity" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Transfer Date *</label>
                <input type="date" v-model="form.transfer_date" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Reason</label>
                <textarea v-model="form.reason" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
              <Button type="submit">Create</Button>
            </div>
          </form>
        </Modal>

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Stock Transfer Details" size="lg">
          <div v-if="selectedTransfer" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Transfer Number</p>
                <p class="font-semibold">{{ selectedTransfer.transfer_number }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="getStatusVariant(selectedTransfer.status)">{{ selectedTransfer.status }}</Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">From Warehouse</p>
                <p class="font-semibold">{{ selectedTransfer.from_warehouse?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">To Warehouse</p>
                <p class="font-semibold">{{ selectedTransfer.to_warehouse?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Product</p>
                <p class="font-semibold">{{ selectedTransfer.product?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Quantity</p>
                <p class="font-semibold">{{ selectedTransfer.quantity }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Transfer Date</p>
                <p class="font-semibold">{{ formatDate(selectedTransfer.transfer_date) }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Reason</p>
                <p class="font-semibold">{{ selectedTransfer.reason || '—' }}</p>
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
import api from '@/Services/api.js'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import DataTable from '@/Components/UI/DataTable.vue'
import Badge from '@/Components/UI/Badge.vue'
import Modal from '@/Components/UI/Modal.vue'

const transfers = ref([])
const products = ref([])
const warehouses = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const selectedTransfer = ref(null)
const searchQuery = ref('')

const form = ref({
  from_warehouse_id: '',
  to_warehouse_id: '',
  product_id: '',
  quantity: 0,
  transfer_date: new Date().toISOString().split('T')[0],
  reason: ''
})

const columns = [
  { key: 'transfer_number', label: 'Transfer #' },
  { key: 'from', label: 'From → To' },
  { key: 'product', label: 'Product' },
  { key: 'status', label: 'Status' },
  { key: 'quantity', label: 'Quantity', right: true },
  { key: 'transfer_date', label: 'Date' }
]

const fetchTransfers = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/stock-transfers', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    transfers.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch transfers:', error)
  } finally {
    loading.value = false
  }
}

const fetchProducts = async () => {
  try {
    const response = await api.get('/products', { params: { per_page: 100 } })
    products.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch products:', error)
  }
}

const fetchWarehouses = async () => {
  try {
    const response = await api.get('/warehouses', { params: { per_page: 100 } })
    warehouses.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch warehouses:', error)
  }
}

const handleSearch = (query) => {
  searchQuery.value = query
  fetchTransfers(1)
}

const openCreateModal = () => {
  form.value = {
    from_warehouse_id: '',
    to_warehouse_id: '',
    product_id: '',
    quantity: 0,
    transfer_date: new Date().toISOString().split('T')[0],
    reason: ''
  }
  showModal.value = true
}

const viewTransfer = (transfer) => {
  selectedTransfer.value = transfer
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    await api.post('/stock-transfers', form.value)
    closeModal()
    fetchTransfers(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save transfer:', error)
  }
}

const confirmTransfer = async (transfer) => {
  if (!confirm('Are you sure you want to confirm this transfer?')) return
  try {
    await api.post(`/stock-transfers/${transfer.id}/confirm`)
    fetchTransfers(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to confirm transfer:', error)
  }
}

const cancelTransfer = async (transfer) => {
  if (!confirm('Are you sure you want to cancel this transfer?')) return
  try {
    await api.post(`/stock-transfers/${transfer.id}/cancel`)
    fetchTransfers(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to cancel transfer:', error)
  }
}

const deleteTransfer = async (transfer) => {
  if (!confirm('Are you sure you want to delete this transfer?')) return
  try {
    await api.delete(`/stock-transfers/${transfer.id}`)
    fetchTransfers(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete transfer:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  selectedTransfer.value = null
}

const exportData = () => {
  window.print()
}

const getStatusVariant = (status) => {
  const variants = {
    pending: 'secondary',
    confirmed: 'default',
    completed: 'default',
    cancelled: 'destructive'
  }
  return variants[status] || 'secondary'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  fetchTransfers()
  fetchProducts()
  fetchWarehouses()
})
</script>
