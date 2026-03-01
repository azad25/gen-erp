<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Goods Receipts</h1>
              <p class="text-sm text-gray-1">Manage goods receipts for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Receipt</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="receipts"
            :pagination="pagination"
            placeholder="Search receipts..."
            @search="handleSearch"
          >
            <template #cell-receipt_number="{ row }">
              <span class="font-mono text-sm">{{ row.receipt_number }}</span>
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

            <template #cell-receipt_date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.receipt_date) }}</span>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewReceipt(row)">View</Button>
              <Button variant="ghost" size="sm" @click="editReceipt(row)" :disabled="row.status !== 'draft'">Edit</Button>
              <Button variant="ghost" size="sm" @click="confirmReceipt(row)" :disabled="row.status !== 'draft'">Confirm</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteReceipt(row)" :disabled="row.status !== 'draft'">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" :title="isEditing ? 'Edit Goods Receipt' : 'New Goods Receipt'">
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
                <label class="block text-sm font-medium mb-1">Warehouse *</label>
                <select v-model="form.warehouse_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Warehouse</option>
                  <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                    {{ warehouse.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Receipt Date *</label>
                <input type="date" v-model="form.receipt_date" required class="w-full border rounded-lg px-3 py-2">
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Items</label>
              <div v-for="(item, index) in form.items" :key="index" class="flex gap-2 mb-2">
                <select v-model="item.product_id" class="flex-1 border rounded-lg px-3 py-2">
                  <option value="">Select Product</option>
                  <option v-for="product in products" :key="product.id" :value="product.id">
                    {{ product.name }}
                  </option>
                </select>
                <input type="number" v-model="item.quantity" placeholder="Qty" class="w-20 border rounded-lg px-3 py-2">
                <Button type="button" variant="ghost" @click="removeItem(index)" class="text-red-500">✕</Button>
              </div>
              <Button type="button" variant="secondary" size="sm" @click="addItem">+ Add Item</Button>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
              <Button type="submit">{{ isEditing ? 'Update' : 'Create' }}</Button>
            </div>
          </form>
        </Modal>

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Goods Receipt Details" size="lg">
          <div v-if="selectedReceipt" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Receipt Number</p>
                <p class="font-semibold">{{ selectedReceipt.receipt_number }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="getStatusVariant(selectedReceipt.status)">{{ selectedReceipt.status }}</Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Supplier</p>
                <p class="font-semibold">{{ selectedReceipt.supplier?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Warehouse</p>
                <p class="font-semibold">{{ selectedReceipt.warehouse?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Receipt Date</p>
                <p class="font-semibold">{{ formatDate(selectedReceipt.receipt_date) }}</p>
              </div>
            </div>

            <div>
              <h3 class="font-semibold mb-2">Items</h3>
              <table class="w-full">
                <thead>
                  <tr class="border-b">
                    <th class="text-left py-2">Product</th>
                    <th class="text-right py-2">Qty</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in selectedReceipt.items" :key="item.id" class="border-b">
                    <td class="py-2">{{ item.product?.name }}</td>
                    <td class="text-right py-2">{{ item.quantity }}</td>
                  </tr>
                </tbody>
              </table>
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

const receipts = ref([])
const suppliers = ref([])
const warehouses = ref([])
const products = ref([])
const purchaseOrders = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditing = ref(false)
const selectedReceipt = ref(null)
const searchQuery = ref('')

const form = ref({
  supplier_id: '',
  purchase_order_id: '',
  warehouse_id: '',
  receipt_date: new Date().toISOString().split('T')[0],
  items: []
})

const columns = [
  { key: 'receipt_number', label: 'Receipt #' },
  { key: 'supplier', label: 'Supplier' },
  { key: 'status', label: 'Status' },
  { key: 'receipt_date', label: 'Date' }
]

const fetchReceipts = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/goods-receipts', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    receipts.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch receipts:', error)
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

const fetchWarehouses = async () => {
  try {
    const response = await axios.get('/api/v1/warehouses', { params: { per_page: 100 } })
    warehouses.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch warehouses:', error)
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
  fetchReceipts(1)
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = {
    supplier_id: '',
    purchase_order_id: '',
    warehouse_id: '',
    receipt_date: new Date().toISOString().split('T')[0],
    items: []
  }
  showModal.value = true
}

const editReceipt = (receipt) => {
  isEditing.value = true
  selectedReceipt.value = receipt
  form.value = {
    supplier_id: receipt.supplier_id,
    purchase_order_id: receipt.purchase_order_id,
    warehouse_id: receipt.warehouse_id,
    receipt_date: receipt.receipt_date,
    items: receipt.items?.map(item => ({
      product_id: item.product_id,
      quantity: item.quantity
    })) || []
  }
  showModal.value = true
}

const viewReceipt = (receipt) => {
  selectedReceipt.value = receipt
  showViewModal.value = true
}

const addItem = () => {
  form.value.items.push({ product_id: '', quantity: 1 })
}

const removeItem = (index) => {
  form.value.items.splice(index, 1)
}

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/v1/goods-receipts/${selectedReceipt.value.id}`, form.value)
    } else {
      await axios.post('/api/v1/goods-receipts', form.value)
    }
    closeModal()
    fetchReceipts(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save receipt:', error)
  }
}

const confirmReceipt = async (receipt) => {
  if (!confirm('Are you sure you want to confirm this receipt?')) return
  try {
    await axios.post(`/api/v1/goods-receipts/${receipt.id}/confirm`)
    fetchReceipts(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to confirm receipt:', error)
  }
}

const deleteReceipt = async (receipt) => {
  if (!confirm('Are you sure you want to delete this receipt?')) return
  try {
    await axios.delete(`/api/v1/goods-receipts/${receipt.id}`)
    fetchReceipts(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete receipt:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  selectedReceipt.value = null
}

const exportData = () => {
  window.print()
}

const getStatusVariant = (status) => {
  const variants = {
    draft: 'secondary',
    confirmed: 'default',
    cancelled: 'destructive'
  }
  return variants[status] || 'secondary'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  fetchReceipts()
  fetchSuppliers()
  fetchWarehouses()
  fetchProducts()
  fetchPurchaseOrders()
})
</script>
