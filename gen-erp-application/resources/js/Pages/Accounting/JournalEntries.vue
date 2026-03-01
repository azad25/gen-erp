<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Journal Entries</h1>
              <p class="text-sm text-gray-1">Manage journal entries for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Entry</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="entries"
            :pagination="pagination"
            placeholder="Search entries..."
            @search="handleSearch"
          >
            <template #cell-entry_number="{ row }">
              <span class="font-mono text-sm">{{ row.entry_number }}</span>
            </template>

            <template #cell-date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.entry_date) }}</span>
            </template>

            <template #cell-debit="{ row }">
              <span class="font-semibold text-green-600">{{ formatCurrency(row.total_debit || 0) }}</span>
            </template>

            <template #cell-credit="{ row }">
              <span class="font-semibold text-red-600">{{ formatCurrency(row.total_credit || 0) }}</span>
            </template>

            <template #cell-status="{ row }">
              <Badge :variant="row.status === 'posted' ? 'default' : 'secondary'">{{ row.status }}</Badge>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewEntry(row)">View</Button>
              <Button variant="ghost" size="sm" @click="postEntry(row)" :disabled="row.status === 'posted'">Post</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteEntry(row)" :disabled="row.status === 'posted'">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" title="New Journal Entry">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Entry Date *</label>
                <input type="date" v-model="form.entry_date" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Reference</label>
                <input type="text" v-model="form.reference" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea v-model="form.description" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Lines</label>
              <div v-for="(line, index) in form.lines" :key="index" class="grid grid-cols-4 gap-2 mb-2">
                <select v-model="line.account_id" class="border rounded-lg px-3 py-2">
                  <option value="">Select Account</option>
                  <option v-for="account in accounts" :key="account.id" :value="account.id">
                    {{ account.code }} - {{ account.name }}
                  </option>
                </select>
                <select v-model="line.type" class="border rounded-lg px-3 py-2">
                  <option value="debit">Debit</option>
                  <option value="credit">Credit</option>
                </select>
                <input type="number" v-model="line.amount" placeholder="Amount" class="border rounded-lg px-3 py-2">
                <Button type="button" variant="ghost" @click="removeLine(index)" class="text-red-500">✕</Button>
              </div>
              <Button type="button" variant="secondary" size="sm" @click="addLine">+ Add Line</Button>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
              <Button type="submit">Create</Button>
            </div>
          </form>
        </Modal>

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Journal Entry Details" size="lg">
          <div v-if="selectedEntry" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Entry Number</p>
                <p class="font-semibold">{{ selectedEntry.entry_number }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="selectedEntry.status === 'posted' ? 'default' : 'secondary'">{{ selectedEntry.status }}</Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Entry Date</p>
                <p class="font-semibold">{{ formatDate(selectedEntry.entry_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Reference</p>
                <p class="font-semibold">{{ selectedEntry.reference || '—' }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Description</p>
                <p class="font-semibold">{{ selectedEntry.description || '—' }}</p>
              </div>
            </div>

            <div>
              <h3 class="font-semibold mb-2">Lines</h3>
              <table class="w-full">
                <thead>
                  <tr class="border-b">
                    <th class="text-left py-2">Account</th>
                    <th class="text-left py-2">Type</th>
                    <th class="text-right py-2">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="line in selectedEntry.lines" :key="line.id" class="border-b">
                    <td class="py-2">{{ line.account?.name }}</td>
                    <td class="py-2">{{ line.type }}</td>
                    <td class="text-right py-2">{{ formatCurrency(line.amount) }}</td>
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

const entries = ref([])
const accounts = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const selectedEntry = ref(null)
const searchQuery = ref('')

const form = ref({
  entry_date: new Date().toISOString().split('T')[0],
  reference: '',
  description: '',
  lines: []
})

const columns = [
  { key: 'entry_number', label: 'Entry #' },
  { key: 'date', label: 'Date' },
  { key: 'debit', label: 'Total Debit', right: true },
  { key: 'credit', label: 'Total Credit', right: true },
  { key: 'status', label: 'Status' }
]

const fetchEntries = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/journal-entries', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    entries.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch entries:', error)
  } finally {
    loading.value = false
  }
}

const fetchAccounts = async () => {
  try {
    const response = await axios.get('/api/v1/accounts', { params: { per_page: 100 } })
    accounts.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch accounts:', error)
  }
}

const handleSearch = (query) => {
  searchQuery.value = query
  fetchEntries(1)
}

const openCreateModal = () => {
  form.value = {
    entry_date: new Date().toISOString().split('T')[0],
    reference: '',
    description: '',
    lines: []
  }
  showModal.value = true
}

const addLine = () => {
  form.value.lines.push({ account_id: '', type: 'debit', amount: 0 })
}

const removeLine = (index) => {
  form.value.lines.splice(index, 1)
}

const viewEntry = (entry) => {
  selectedEntry.value = entry
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    await axios.post('/api/v1/journal-entries', form.value)
    closeModal()
    fetchEntries(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save entry:', error)
  }
}

const postEntry = async (entry) => {
  if (!confirm('Are you sure you want to post this entry?')) return
  try {
    await axios.post(`/api/v1/journal-entries/${entry.id}/post`)
    fetchEntries(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to post entry:', error)
  }
}

const deleteEntry = async (entry) => {
  if (!confirm('Are you sure you want to delete this entry?')) return
  try {
    await axios.delete(`/api/v1/journal-entries/${entry.id}`)
    fetchEntries(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete entry:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  selectedEntry.value = null
}

const exportData = () => {
  window.print()
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount / 100)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  fetchEntries()
  fetchAccounts()
})
</script>
