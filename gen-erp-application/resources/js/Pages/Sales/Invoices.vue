<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Invoices</h1>
              <p class="text-sm text-gray-1">Manage invoices for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Invoice</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="invoices"
            :pagination="pagination"
            placeholder="Search invoices..."
            @search="handleSearch"
          >
            <template #cell-invoice_number="{ row }">
              <span class="font-mono text-sm">{{ row.invoice_number }}</span>
            </template>

            <template #cell-customer="{ row }">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold">
                  {{ row.customer?.name?.charAt(0) || 'C' }}
                </div>
                <span class="text-sm">{{ row.customer?.name || '—' }}</span>
              </div>
            </template>

            <template #cell-status="{ row }">
              <Badge :variant="getStatusVariant(row.status)">{{ row.status }}</Badge>
            </template>

            <template #cell-total_amount="{ row }">
              <span class="font-semibold">{{ formatCurrency(row.total_amount) }}</span>
            </template>

            <template #cell-invoice_date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.invoice_date) }}</span>
            </template>

            <template #cell-due_date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.due_date) }}</span>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewInvoice(row)">View</Button>
              <Button variant="ghost" size="sm" @click="editInvoice(row)" :disabled="row.status !== 'draft'">Edit</Button>
              <Button variant="ghost" size="sm" @click="sendInvoice(row)" :disabled="row.status === 'sent'">Send</Button>
              <Button variant="ghost" size="sm" @click="markPaid(row)" :disabled="!['sent', 'partial'].includes(row.status)">Mark Paid</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteInvoice(row)" :disabled="row.status !== 'draft'">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" :title="isEditing ? 'Edit Invoice' : 'New Invoice'">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Customer</label>
                <select v-model="form.customer_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Customer</option>
                  <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                    {{ customer.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Invoice Date</label>
                <input type="date" v-model="form.invoice_date" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Due Date</label>
                <input type="date" v-model="form.due_date" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Total Amount</label>
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

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Invoice Details" size="lg">
          <div v-if="selectedInvoice" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Invoice Number</p>
                <p class="font-semibold">{{ selectedInvoice.invoice_number }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="getStatusVariant(selectedInvoice.status)">{{ selectedInvoice.status }}</Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Customer</p>
                <p class="font-semibold">{{ selectedInvoice.customer?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Invoice Date</p>
                <p class="font-semibold">{{ formatDate(selectedInvoice.invoice_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Due Date</p>
                <p class="font-semibold">{{ formatDate(selectedInvoice.due_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Total Amount</p>
                <p class="font-semibold text-lg">{{ formatCurrency(selectedInvoice.total_amount) }}</p>
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
                  <tr v-for="item in selectedInvoice.items" :key="item.id" class="border-b">
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
import api from '@/Services/api.js'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import DataTable from '@/Components/UI/DataTable.vue'
import Badge from '@/Components/UI/Badge.vue'
import Modal from '@/Components/UI/Modal.vue'

const invoices = ref([])
const customers = ref([])
const products = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditing = ref(false)
const selectedInvoice = ref(null)
const searchQuery = ref('')

const form = ref({
  customer_id: '',
  invoice_date: new Date().toISOString().split('T')[0],
  due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  total_amount: 0,
  items: []
})

const columns = [
  { key: 'invoice_number', label: 'Invoice #' },
  { key: 'customer', label: 'Customer' },
  { key: 'status', label: 'Status' },
  { key: 'total_amount', label: 'Total', right: true },
  { key: 'invoice_date', label: 'Invoice Date' },
  { key: 'due_date', label: 'Due Date' }
]

const fetchInvoices = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/invoices', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    invoices.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch invoices:', error)
  } finally {
    loading.value = false
  }
}

const fetchCustomers = async () => {
  try {
    const response = await api.get('/customers', { params: { per_page: 100 } })
    customers.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch customers:', error)
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

const handleSearch = (query) => {
  searchQuery.value = query
  fetchInvoices(1)
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = {
    customer_id: '',
    invoice_date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    total_amount: 0,
    items: []
  }
  showModal.value = true
}

const editInvoice = (invoice) => {
  isEditing.value = true
  selectedInvoice.value = invoice
  form.value = {
    customer_id: invoice.customer_id,
    invoice_date: invoice.invoice_date,
    due_date: invoice.due_date,
    total_amount: invoice.total_amount,
    items: invoice.items?.map(item => ({
      product_id: item.product_id,
      quantity: item.quantity,
      unit_price: item.unit_price
    })) || []
  }
  showModal.value = true
}

const viewInvoice = (invoice) => {
  selectedInvoice.value = invoice
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
      await api.put(`/invoices/${selectedInvoice.value.id}`, form.value)
    } else {
      await api.post('/invoices', form.value)
    }
    closeModal()
    fetchInvoices(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save invoice:', error)
  }
}

const sendInvoice = async (invoice) => {
  if (!confirm('Are you sure you want to send this invoice?')) return
  try {
    await api.post(`/invoices/${invoice.id}/send`)
    fetchInvoices(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to send invoice:', error)
  }
}

const markPaid = async (invoice) => {
  if (!confirm('Are you sure you want to mark this invoice as paid?')) return
  try {
    await api.post(`/invoices/${invoice.id}/mark-paid`)
    fetchInvoices(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to mark invoice as paid:', error)
  }
}

const deleteInvoice = async (invoice) => {
  if (!confirm('Are you sure you want to delete this invoice?')) return
  try {
    await api.delete(`/invoices/${invoice.id}`)
    fetchInvoices(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete invoice:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  selectedInvoice.value = null
}

const exportData = () => {
  window.print()
}

const getStatusVariant = (status) => {
  const variants = {
    draft: 'secondary',
    sent: 'default',
    paid: 'default',
    partial: 'default',
    overdue: 'destructive'
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
  fetchInvoices()
  fetchCustomers()
  fetchProducts()
})
</script>
