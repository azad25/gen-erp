<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Stock</h1>
              <p class="text-sm text-gray-1">Manage stock movements for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Movement</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="movements"
            :pagination="pagination"
            placeholder="Search movements..."
            @search="handleSearch"
          >
            <template #cell-movement_number="{ row }">
              <span class="font-mono text-sm">{{ row.movement_number }}</span>
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
              <span class="font-semibold" :class="row.type === 'out' ? 'text-red-500' : 'text-green-500'">
                {{ row.type === 'out' ? '-' : '+' }}{{ row.quantity }}
              </span>
            </template>

            <template #cell-movement_date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.movement_date) }}</span>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewMovement(row)">View</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteMovement(row)">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" title="New Stock Movement">
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
                  <option value="in">Stock In</option>
                  <option value="out">Stock Out</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Quantity *</label>
                <input type="number" v-model="form.quantity" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Movement Date *</label>
                <input type="date" v-model="form.movement_date" required class="w-full border rounded-lg px-3 py-2">
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

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Stock Movement Details" size="lg">
          <div v-if="selectedMovement" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Movement Number</p>
                <p class="font-semibold">{{ selectedMovement.movement_number }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Type</p>
                <Badge :variant="getTypeVariant(selectedMovement.type)">{{ selectedMovement.type }}</Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Product</p>
                <p class="font-semibold">{{ selectedMovement.product?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Warehouse</p>
                <p class="font-semibold">{{ selectedMovement.warehouse?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Quantity</p>
                <p class="font-semibold">{{ selectedMovement.quantity }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Movement Date</p>
                <p class="font-semibold">{{ formatDate(selectedMovement.movement_date) }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Reason</p>
                <p class="font-semibold">{{ selectedMovement.reason || '—' }}</p>
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

const movements = ref([])
const products = ref([])
const warehouses = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const selectedMovement = ref(null)
const searchQuery = ref('')

const form = ref({
  product_id: '',
  warehouse_id: '',
  type: 'in',
  quantity: 0,
  movement_date: new Date().toISOString().split('T')[0],
  reason: ''
})

const columns = [
  { key: 'movement_number', label: 'Movement #' },
  { key: 'product', label: 'Product' },
  { key: 'warehouse', label: 'Warehouse' },
  { key: 'type', label: 'Type' },
  { key: 'quantity', label: 'Quantity', right: true },
  { key: 'movement_date', label: 'Date' }
]

const fetchMovements = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/stock-movements', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    movements.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch movements:', error)
  } finally {
    loading.value = false
  }
}

const fetchProducts = async () => {
  try {
    const response = await axios.get('/api/v1/products', { params: { per_page: 100 } })
    products.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch products:', error)
  }
}

const fetchWarehouses = async () => {
  try {
    const response = await axios.get('/api/v1/warehouses', { params: { per_page: 100 } })
    warehouses.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch warehouses:', error)
  }
}

const handleSearch = (query) => {
  searchQuery.value = query
  fetchMovements(1)
}

const openCreateModal = () => {
  form.value = {
    product_id: '',
    warehouse_id: '',
    type: 'in',
    quantity: 0,
    movement_date: new Date().toISOString().split('T')[0],
    reason: ''
  }
  showModal.value = true
}

const viewMovement = (movement) => {
  selectedMovement.value = movement
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    await axios.post('/api/v1/stock-movements', form.value)
    closeModal()
    fetchMovements(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save movement:', error)
  }
}

const deleteMovement = async (movement) => {
  if (!confirm('Are you sure you want to delete this movement?')) return
  try {
    await axios.delete(`/api/v1/stock-movements/${movement.id}`)
    fetchMovements(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete movement:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  selectedMovement.value = null
}

const exportData = () => {
  window.print()
}

const getTypeVariant = (type) => {
  return type === 'in' ? 'default' : 'destructive'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  fetchMovements()
  fetchProducts()
  fetchWarehouses()
})
</script>
