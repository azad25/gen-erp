<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Stock Adjustments</h1>
              <p class="text-sm text-gray-1">Manage stock adjustments for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Adjustment</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="adjustments"
            :pagination="pagination"
            placeholder="Search adjustments..."
            @search="handleSearch"
          >
            <template #cell-adjustment_number="{ row }">
              <span class="font-mono text-sm">{{ row.adjustment_number }}</span>
            </template>

            <template #cell-product="{ row }">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold">
                  {{ row.product?.name?.charAt(0) || 'P' }}
                </div>
                <span class="text-sm">{{ row.product?.name || '—' }}</span>
              </div>
            </template>

            <template #cell-warehouse="{ row }">
              <span class="text-sm">{{ row.warehouse?.name || '—' }}</span>
            </template>

            <template #cell-type="{ row }">
              <Badge :variant="getTypeVariant(row.type)">{{ row.type }}</Badge>
            </template>

            <template #cell-quantity="{ row }">
              <span class="font-semibold" :class="row.type === 'decrease' ? 'text-red-500' : 'text-green-500'">
                {{ row.type === 'decrease' ? '-' : '+' }}{{ row.quantity }}
              </span>
            </template>

            <template #cell-adjustment_date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.adjustment_date) }}</span>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewAdjustment(row)">View</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteAdjustment(row)">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" title="New Stock Adjustment">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
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
                <label class="block text-sm font-medium mb-1">Warehouse *</label>
                <select v-model="form.warehouse_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Warehouse</option>
                  <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                    {{ warehouse.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Type *</label>
                <select v-model="form.type" required class="w-full border rounded-lg px-3 py-2">
                  <option value="increase">Increase</option>
                  <option value="decrease">Decrease</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Quantity *</label>
                <input type="number" v-model="form.quantity" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Adjustment Date *</label>
                <input type="date" v-model="form.adjustment_date" required class="w-full border rounded-lg px-3 py-2">
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

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Stock Adjustment Details" size="lg">
          <div v-if="selectedAdjustment" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Adjustment Number</p>
                <p class="font-semibold">{{ selectedAdjustment.adjustment_number }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Type</p>
                <Badge :variant="getTypeVariant(selectedAdjustment.type)">{{ selectedAdjustment.type }}</Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Product</p>
                <p class="font-semibold">{{ selectedAdjustment.product?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Warehouse</p>
                <p class="font-semibold">{{ selectedAdjustment.warehouse?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Quantity</p>
                <p class="font-semibold">{{ selectedAdjustment.quantity }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Adjustment Date</p>
                <p class="font-semibold">{{ formatDate(selectedAdjustment.adjustment_date) }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Reason</p>
                <p class="font-semibold">{{ selectedAdjustment.reason || '—' }}</p>
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

const adjustments = ref([])
const products = ref([])
const warehouses = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const selectedAdjustment = ref(null)
const searchQuery = ref('')

const form = ref({
  product_id: '',
  warehouse_id: '',
  type: 'increase',
  quantity: 0,
  adjustment_date: new Date().toISOString().split('T')[0],
  reason: ''
})

const columns = [
  { key: 'adjustment_number', label: 'Adjustment #' },
  { key: 'product', label: 'Product' },
  { key: 'warehouse', label: 'Warehouse' },
  { key: 'type', label: 'Type' },
  { key: 'quantity', label: 'Quantity', right: true },
  { key: 'adjustment_date', label: 'Date' }
]

const fetchAdjustments = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/stock-adjustments', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    adjustments.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch adjustments:', error)
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
  fetchAdjustments(1)
}

const openCreateModal = () => {
  form.value = {
    product_id: '',
    warehouse_id: '',
    type: 'increase',
    quantity: 0,
    adjustment_date: new Date().toISOString().split('T')[0],
    reason: ''
  }
  showModal.value = true
}

const viewAdjustment = (adjustment) => {
  selectedAdjustment.value = adjustment
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    await api.post('/stock-adjustments', form.value)
    closeModal()
    fetchAdjustments(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save adjustment:', error)
  }
}

const deleteAdjustment = async (adjustment) => {
  if (!confirm('Are you sure you want to delete this adjustment?')) return
  try {
    await api.delete(`/stock-adjustments/${adjustment.id}`)
    fetchAdjustments(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete adjustment:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  selectedAdjustment.value = null
}

const exportData = () => {
  window.print()
}

const getTypeVariant = (type) => {
  return type === 'increase' ? 'default' : 'destructive'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  fetchAdjustments()
  fetchProducts()
  fetchWarehouses()
})
</script>
