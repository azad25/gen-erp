<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Warehouses</h1>
              <p class="text-sm text-gray-1">Manage warehouses for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Warehouse</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="warehouses"
            :pagination="pagination"
            placeholder="Search warehouses..."
            @search="handleSearch"
          >
            <template #cell-name="{ row }">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold">
                  {{ row.name?.charAt(0) || 'W' }}
                </div>
                <div>
                  <p class="font-medium text-sm">{{ row.name }}</p>
                  <p class="text-xs text-gray-1">{{ row.location || '—' }}</p>
                </div>
              </div>
            </template>

            <template #cell-status="{ row }">
              <Badge :variant="row.is_active ? 'default' : 'secondary'">{{ row.is_active ? 'Active' : 'Inactive' }}</Badge>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewWarehouse(row)">View</Button>
              <Button variant="ghost" size="sm" @click="editWarehouse(row)">Edit</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteWarehouse(row)">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" :title="isEditing ? 'Edit Warehouse' : 'New Warehouse'">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Name *</label>
                <input type="text" v-model="form.name" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Location</label>
                <input type="text" v-model="form.location" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Manager</label>
                <input type="text" v-model="form.manager" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Phone</label>
                <input type="text" v-model="form.phone" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Address</label>
                <input type="text" v-model="form.address" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">City</label>
                <input type="text" v-model="form.city" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select v-model="form.is_active" class="w-full border rounded-lg px-3 py-2">
                  <option :value="true">Active</option>
                  <option :value="false">Inactive</option>
                </select>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
              <Button type="submit">{{ isEditing ? 'Update' : 'Create' }}</Button>
            </div>
          </form>
        </Modal>

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Warehouse Details" size="lg">
          <div v-if="selectedWarehouse" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Name</p>
                <p class="font-semibold">{{ selectedWarehouse.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Location</p>
                <p class="font-semibold">{{ selectedWarehouse.location || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Manager</p>
                <p class="font-semibold">{{ selectedWarehouse.manager || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Phone</p>
                <p class="font-semibold">{{ selectedWarehouse.phone || '—' }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Address</p>
                <p class="font-semibold">{{ selectedWarehouse.address || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">City</p>
                <p class="font-semibold">{{ selectedWarehouse.city || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="selectedWarehouse.is_active ? 'default' : 'secondary'">
                  {{ selectedWarehouse.is_active ? 'Active' : 'Inactive' }}
                </Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Created At</p>
                <p class="font-semibold">{{ formatDate(selectedWarehouse.created_at) }}</p>
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

const warehouses = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditing = ref(false)
const selectedWarehouse = ref(null)
const searchQuery = ref('')

const form = ref({
  name: '',
  location: '',
  manager: '',
  phone: '',
  address: '',
  city: '',
  is_active: true
})

const columns = [
  { key: 'name', label: 'Warehouse' },
  { key: 'status', label: 'Status' }
]

const fetchWarehouses = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/warehouses', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    warehouses.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch warehouses:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = (query) => {
  searchQuery.value = query
  fetchWarehouses(1)
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = {
    name: '',
    location: '',
    manager: '',
    phone: '',
    address: '',
    city: '',
    is_active: true
  }
  showModal.value = true
}

const editWarehouse = (warehouse) => {
  isEditing.value = true
  selectedWarehouse.value = warehouse
  form.value = {
    name: warehouse.name,
    location: warehouse.location,
    manager: warehouse.manager,
    phone: warehouse.phone,
    address: warehouse.address,
    city: warehouse.city,
    is_active: warehouse.is_active
  }
  showModal.value = true
}

const viewWarehouse = (warehouse) => {
  selectedWarehouse.value = warehouse
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await api.put(`/warehouses/${selectedWarehouse.value.id}`, form.value)
    } else {
      await api.post('/warehouses', form.value)
    }
    closeModal()
    fetchWarehouses(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save warehouse:', error)
  }
}

const deleteWarehouse = async (warehouse) => {
  if (!confirm('Are you sure you want to delete this warehouse?')) return
  try {
    await api.delete(`/warehouses/${warehouse.id}`)
    fetchWarehouses(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete warehouse:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  selectedWarehouse.value = null
}

const exportData = () => {
  window.print()
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  fetchWarehouses()
})
</script>
