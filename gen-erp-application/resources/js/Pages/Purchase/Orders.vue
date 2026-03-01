<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Purchase Orders</h1>
              <p class="text-sm text-gray-1">Manage purchase orders for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Order</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="orders"
            :pagination="pagination"
            placeholder="Search orders..."
            @search="handleSearch"
          >
            <template #cell-order_number="{ row }">
              <span class="font-mono text-sm">{{ row.order_number }}</span>
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

            <template #cell-total_amount="{ row }">
              <span class="font-semibold">{{ formatCurrency(row.total_amount) }}</span>
            </template>

            <template #cell-order_date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.order_date) }}</span>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewOrder(row)">View</Button>
              <Button variant="ghost" size="sm" @click="editOrder(row)" :disabled="row.status !== 'draft'">Edit</Button>
              <Button variant="ghost" size="sm" @click="confirmOrder(row)" :disabled="row.status !== 'draft'">Confirm</Button>
              <Button variant="ghost" size="sm" @click="cancelOrder(row)" :disabled="!['draft', 'confirmed'].includes(row.status)">Cancel</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteOrder(row)" :disabled="row.status !== 'draft'">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" :title="isEditing ? 'Edit Purchase Order' : 'New Purchase Order'">
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
                <label class="block text-sm font-medium mb-1">Warehouse *</label>
                <select v-model="form.warehouse_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Warehouse</option>
                  <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                    {{ warehouse.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Order Date *</label>
                <input type="date" v-model="form.order_date" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Expected Date</label>
                <input type="date" v-model="form.expected_date" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Total Amount *</label>
                <input type="number" v-model="form.total_amount" required class="w-full border rounded-lg px-3 py-2">
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
                <input type="number" v-model="item.unit_price" placeholder="Price" class="w-24 border rounded-lg px-3 py-2">
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

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Purchase Order Details" size="lg">
          <div v-if="selectedOrder" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Order Number</p>
                <p class="font-semibold">{{ selectedOrder.order_number }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="getStatusVariant(selectedOrder.status)">{{ selectedOrder.status }}</Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Supplier</p>
                <p class="font-semibold">{{ selectedOrder.supplier?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Warehouse</p>
                <p class="font-semibold">{{ selectedOrder.warehouse?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Order Date</p>
                <p class="font-semibold">{{ formatDate(selectedOrder.order_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Expected Date</p>
                <p class="font-semibold">{{ formatDate(selectedOrder.expected_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Total Amount</p>
                <p class="font-semibold text-lg">{{ formatCurrency(selectedOrder.total_amount) }}</p>
              </div>
            </div>

            <div>
              <h3 class="font-semibold mb-2">Items</h3>
              <table class="w-full">
                <thead>
                  <tr class="border-b">
                    <th class="text-left py-2">Product</th>
                    <th class="text-right py-2">Qty</th>
                    <th class="text-right py-2">Price</th>
                    <th class="text-right py-2">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in selectedOrder.items" :key="item.id" class="border-b">
                    <td class="py-2">{{ item.product?.name }}</td>
                    <td class="text-right py-2">{{ item.quantity }}</td>
                    <td class="text-right py-2">{{ formatCurrency(item.unit_price) }}</td>
                    <td class="text-right py-2">{{ formatCurrency(item.quantity * item.unit_price) }}</td>
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

const orders = ref([])
const suppliers = ref([])
const warehouses = ref([])
const products = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditing = ref(false)
const selectedOrder = ref(null)
const searchQuery = ref('')

const form = ref({
  supplier_id: '',
  warehouse_id: '',
  order_date: new Date().toISOString().split('T')[0],
  expected_date: '',
  total_amount: 0,
  items: []
})

const columns = [
  { key: 'order_number', label: 'Order #' },
  { key: 'supplier', label: 'Supplier' },
  { key: 'status', label: 'Status' },
  { key: 'total_amount', label: 'Total', right: true },
  { key: 'order_date', label: 'Date' }
]

const fetchOrders = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/purchase-orders', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    orders.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch orders:', error)
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

const handleSearch = (query) => {
  searchQuery.value = query
  fetchOrders(1)
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = {
    supplier_id: '',
    warehouse_id: '',
    order_date: new Date().toISOString().split('T')[0],
    expected_date: '',
    total_amount: 0,
    items: []
  }
  showModal.value = true
}

const editOrder = (order) => {
  isEditing.value = true
  selectedOrder.value = order
  form.value = {
    supplier_id: order.supplier_id,
    warehouse_id: order.warehouse_id,
    order_date: order.order_date,
    expected_date: order.expected_date,
    total_amount: order.total_amount,
    items: order.items?.map(item => ({
      product_id: item.product_id,
      quantity: item.quantity,
      unit_price: item.unit_price
    })) || []
  }
  showModal.value = true
}

const viewOrder = (order) => {
  selectedOrder.value = order
  showViewModal.value = true
}

const addItem = () => {
  form.value.items.push({ product_id: '', quantity: 1, unit_price: 0 })
}

const removeItem = (index) => {
  form.value.items.splice(index, 1)
}

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/v1/purchase-orders/${selectedOrder.value.id}`, form.value)
    } else {
      await axios.post('/api/v1/purchase-orders', form.value)
    }
    closeModal()
    fetchOrders(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save order:', error)
  }
}

const confirmOrder = async (order) => {
  if (!confirm('Are you sure you want to confirm this order?')) return
  try {
    await axios.post(`/api/v1/purchase-orders/${order.id}/confirm`)
    fetchOrders(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to confirm order:', error)
  }
}

const cancelOrder = async (order) => {
  if (!confirm('Are you sure you want to cancel this order?')) return
  try {
    await axios.post(`/api/v1/purchase-orders/${order.id}/cancel`)
    fetchOrders(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to cancel order:', error)
  }
}

const deleteOrder = async (order) => {
  if (!confirm('Are you sure you want to delete this order?')) return
  try {
    await axios.delete(`/api/v1/purchase-orders/${order.id}`)
    fetchOrders(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete order:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  selectedOrder.value = null
}

const exportData = () => {
  window.print()
}

const getStatusVariant = (status) => {
  const variants = {
    draft: 'secondary',
    confirmed: 'default',
    cancelled: 'destructive',
    received: 'default'
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
  fetchOrders()
  fetchSuppliers()
  fetchWarehouses()
  fetchProducts()
})
</script>
