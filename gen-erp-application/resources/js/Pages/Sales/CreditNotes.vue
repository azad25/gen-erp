<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Credit Notes</h1>
              <p class="text-sm text-gray-1">Manage credit notes for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Credit Note</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="creditNotes"
            :pagination="pagination"
            placeholder="Search credit notes..."
            @search="handleSearch"
          >
            <template #cell-credit_note_number="{ row }">
              <span class="font-mono text-sm">{{ row.credit_note_number }}</span>
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

            <template #cell-amount="{ row }">
              <span class="font-semibold">{{ formatCurrency(row.amount) }}</span>
            </template>

            <template #cell-credit_note_date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.credit_note_date) }}</span>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewCreditNote(row)">View</Button>
              <Button variant="ghost" size="sm" @click="editCreditNote(row)" :disabled="row.status !== 'draft'">Edit</Button>
              <Button variant="ghost" size="sm" @click="approveCreditNote(row)" :disabled="row.status !== 'pending'">Approve</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteCreditNote(row)" :disabled="row.status !== 'draft'">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" :title="isEditing ? 'Edit Credit Note' : 'New Credit Note'">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Customer *</label>
                <select v-model="form.customer_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Customer</option>
                  <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                    {{ customer.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Invoice (Optional)</label>
                <select v-model="form.invoice_id" class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Invoice</option>
                  <option v-for="invoice in invoices" :key="invoice.id" :value="invoice.id">
                    {{ invoice.invoice_number }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Credit Note Date *</label>
                <input type="date" v-model="form.credit_note_date" required class="w-full border rounded-lg px-3 py-2">
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

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Credit Note Details" size="lg">
          <div v-if="selectedCreditNote" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Credit Note Number</p>
                <p class="font-semibold">{{ selectedCreditNote.credit_note_number }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="getStatusVariant(selectedCreditNote.status)">{{ selectedCreditNote.status }}</Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Customer</p>
                <p class="font-semibold">{{ selectedCreditNote.customer?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Invoice</p>
                <p class="font-semibold">{{ selectedCreditNote.invoice?.invoice_number || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Credit Note Date</p>
                <p class="font-semibold">{{ formatDate(selectedCreditNote.credit_note_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Amount</p>
                <p class="font-semibold text-lg">{{ formatCurrency(selectedCreditNote.amount) }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Reason</p>
                <p class="font-semibold">{{ selectedCreditNote.reason || '—' }}</p>
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

const creditNotes = ref([])
const customers = ref([])
const invoices = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditing = ref(false)
const selectedCreditNote = ref(null)
const searchQuery = ref('')

const form = ref({
  customer_id: '',
  invoice_id: '',
  credit_note_date: new Date().toISOString().split('T')[0],
  amount: 0,
  reason: ''
})

const columns = [
  { key: 'credit_note_number', label: 'Credit Note #' },
  { key: 'customer', label: 'Customer' },
  { key: 'status', label: 'Status' },
  { key: 'amount', label: 'Amount', right: true },
  { key: 'credit_note_date', label: 'Date' }
]

const fetchCreditNotes = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/credit-notes', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    creditNotes.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch credit notes:', error)
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

const fetchInvoices = async () => {
  try {
    const response = await api.get('/invoices', { params: { per_page: 100 } })
    invoices.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch invoices:', error)
  }
}

const handleSearch = (query) => {
  searchQuery.value = query
  fetchCreditNotes(1)
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = {
    customer_id: '',
    invoice_id: '',
    credit_note_date: new Date().toISOString().split('T')[0],
    amount: 0,
    reason: ''
  }
  showModal.value = true
}

const editCreditNote = (creditNote) => {
  isEditing.value = true
  selectedCreditNote.value = creditNote
  form.value = {
    customer_id: creditNote.customer_id,
    invoice_id: creditNote.invoice_id,
    credit_note_date: creditNote.credit_note_date,
    amount: creditNote.amount,
    reason: creditNote.reason
  }
  showModal.value = true
}

const viewCreditNote = (creditNote) => {
  selectedCreditNote.value = creditNote
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await api.put(`/credit-notes/${selectedCreditNote.value.id}`, form.value)
    } else {
      await api.post('/credit-notes', form.value)
    }
    closeModal()
    fetchCreditNotes(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save credit note:', error)
  }
}

const approveCreditNote = async (creditNote) => {
  if (!confirm('Are you sure you want to approve this credit note?')) return
  try {
    await api.post(`/credit-notes/${creditNote.id}/approve`)
    fetchCreditNotes(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to approve credit note:', error)
  }
}

const deleteCreditNote = async (creditNote) => {
  if (!confirm('Are you sure you want to delete this credit note?')) return
  try {
    await api.delete(`/credit-notes/${creditNote.id}`)
    fetchCreditNotes(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete credit note:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  selectedCreditNote.value = null
}

const exportData = () => {
  window.print()
}

const getStatusVariant = (status) => {
  const variants = {
    draft: 'secondary',
    pending: 'default',
    approved: 'default',
    rejected: 'destructive'
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
  fetchCreditNotes()
  fetchCustomers()
  fetchInvoices()
})
</script>
