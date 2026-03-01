<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Chart of Accounts</h1>
              <p class="text-sm text-gray-1">Manage chart of accounts for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Account</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="accounts"
            :pagination="pagination"
            placeholder="Search accounts..."
            @search="handleSearch"
          >
            <template #cell-code="{ row }">
              <span class="font-mono text-sm">{{ row.code }}</span>
            </template>

            <template #cell-name="{ row }">
              <div class="flex items-center gap-2">
                <span class="text-sm">{{ row.name }}</span>
              </div>
            </template>

            <template #cell-type="{ row }">
              <Badge :variant="getTypeVariant(row.type)">{{ row.type }}</Badge>
            </template>

            <template #cell-balance="{ row }">
              <span class="font-semibold">{{ formatCurrency(row.balance || 0) }}</span>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewAccount(row)">View</Button>
              <Button variant="ghost" size="sm" @click="editAccount(row)">Edit</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteAccount(row)">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" :title="isEditing ? 'Edit Account' : 'New Account'">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Code *</label>
                <input type="text" v-model="form.code" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Name *</label>
                <input type="text" v-model="form.name" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Type *</label>
                <select v-model="form.type" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Type</option>
                  <option value="asset">Asset</option>
                  <option value="liability">Liability</option>
                  <option value="equity">Equity</option>
                  <option value="revenue">Revenue</option>
                  <option value="expense">Expense</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Parent Account</label>
                <select v-model="form.parent_id" class="w-full border rounded-lg px-3 py-2">
                  <option value="">None</option>
                  <option v-for="account in accounts" :key="account.id" :value="account.id">
                    {{ account.code }} - {{ account.name }}
                  </option>
                </select>
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea v-model="form.description" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
              <Button type="submit">{{ isEditing ? 'Update' : 'Create' }}</Button>
            </div>
          </form>
        </Modal>

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Account Details" size="lg">
          <div v-if="selectedAccount" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Code</p>
                <p class="font-semibold">{{ selectedAccount.code }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Name</p>
                <p class="font-semibold">{{ selectedAccount.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Type</p>
                <Badge :variant="getTypeVariant(selectedAccount.type)">{{ selectedAccount.type }}</Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Balance</p>
                <p class="font-semibold text-lg">{{ formatCurrency(selectedAccount.balance || 0) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Parent Account</p>
                <p class="font-semibold">{{ selectedAccount.parent?.name || '—' }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Description</p>
                <p class="font-semibold">{{ selectedAccount.description || '—' }}</p>
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

const accounts = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditing = ref(false)
const selectedAccount = ref(null)
const searchQuery = ref('')

const form = ref({
  code: '',
  name: '',
  type: '',
  parent_id: '',
  description: ''
})

const columns = [
  { key: 'code', label: 'Code' },
  { key: 'name', label: 'Name' },
  { key: 'type', label: 'Type' },
  { key: 'balance', label: 'Balance', right: true }
]

const fetchAccounts = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/accounts', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    accounts.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch accounts:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = (query) => {
  searchQuery.value = query
  fetchAccounts(1)
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = {
    code: '',
    name: '',
    type: '',
    parent_id: '',
    description: ''
  }
  showModal.value = true
}

const editAccount = (account) => {
  isEditing.value = true
  selectedAccount.value = account
  form.value = {
    code: account.code,
    name: account.name,
    type: account.type,
    parent_id: account.parent_id,
    description: account.description
  }
  showModal.value = true
}

const viewAccount = (account) => {
  selectedAccount.value = account
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/v1/accounts/${selectedAccount.value.id}`, form.value)
    } else {
      await axios.post('/api/v1/accounts', form.value)
    }
    closeModal()
    fetchAccounts(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save account:', error)
  }
}

const deleteAccount = async (account) => {
  if (!confirm('Are you sure you want to delete this account?')) return
  try {
    await axios.delete(`/api/v1/accounts/${account.id}`)
    fetchAccounts(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete account:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  selectedAccount.value = null
}

const exportData = () => {
  window.print()
}

const getTypeVariant = (type) => {
  const variants = {
    asset: 'default',
    liability: 'secondary',
    equity: 'secondary',
    revenue: 'default',
    expense: 'destructive'
  }
  return variants[type] || 'secondary'
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount / 100)
}

onMounted(() => {
  fetchAccounts()
})
</script>
