<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Suppliers</h1>
              <p class="text-sm text-gray-1">Manage suppliers for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Supplier</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="suppliers"
            :pagination="pagination"
            placeholder="Search suppliers..."
            @search="handleSearch"
          >
            <template #cell-name="{ row }">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold">
                  {{ row.name?.charAt(0) || 'S' }}
                </div>
                <div>
                  <p class="font-medium text-sm">{{ row.name }}</p>
                  <p class="text-xs text-gray-1">{{ row.email }}</p>
                </div>
              </div>
            </template>

            <template #cell-phone="{ row }">
              <span class="text-sm">{{ row.phone || '—' }}</span>
            </template>

            <template #cell-address="{ row }">
              <span class="text-sm text-gray-1">{{ row.city || '—' }}, {{ row.country || '—' }}</span>
            </template>

            <template #cell-status="{ row }">
              <Badge :variant="row.is_active ? 'default' : 'secondary'">{{ row.is_active ? 'Active' : 'Inactive' }}</Badge>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewSupplier(row)">View</Button>
              <Button variant="ghost" size="sm" @click="editSupplier(row)">Edit</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteSupplier(row)">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" :title="isEditing ? 'Edit Supplier' : 'New Supplier'">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Name *</label>
                <input type="text" v-model="form.name" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Email *</label>
                <input type="email" v-model="form.email" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Phone</label>
                <input type="text" v-model="form.phone" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Tax ID</label>
                <input type="text" v-model="form.tax_id" class="w-full border rounded-lg px-3 py-2">
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
                <label class="block text-sm font-medium mb-1">Country</label>
                <input type="text" v-model="form.country" class="w-full border rounded-lg px-3 py-2">
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

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Supplier Details" size="lg">
          <div v-if="selectedSupplier" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Name</p>
                <p class="font-semibold">{{ selectedSupplier.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Email</p>
                <p class="font-semibold">{{ selectedSupplier.email }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Phone</p>
                <p class="font-semibold">{{ selectedSupplier.phone || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Tax ID</p>
                <p class="font-semibold">{{ selectedSupplier.tax_id || '—' }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Address</p>
                <p class="font-semibold">{{ selectedSupplier.address || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">City</p>
                <p class="font-semibold">{{ selectedSupplier.city || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Country</p>
                <p class="font-semibold">{{ selectedSupplier.country || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="selectedSupplier.is_active ? 'default' : 'secondary'">
                  {{ selectedSupplier.is_active ? 'Active' : 'Inactive' }}
                </Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Created At</p>
                <p class="font-semibold">{{ formatDate(selectedSupplier.created_at) }}</p>
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

const suppliers = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditing = ref(false)
const selectedSupplier = ref(null)
const searchQuery = ref('')

const form = ref({
  name: '',
  email: '',
  phone: '',
  tax_id: '',
  address: '',
  city: '',
  country: '',
  is_active: true
})

const columns = [
  { key: 'name', label: 'Supplier' },
  { key: 'phone', label: 'Phone' },
  { key: 'address', label: 'Location' },
  { key: 'status', label: 'Status' }
]

const fetchSuppliers = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/suppliers', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    suppliers.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch suppliers:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = (query) => {
  searchQuery.value = query
  fetchSuppliers(1)
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = {
    name: '',
    email: '',
    phone: '',
    tax_id: '',
    address: '',
    city: '',
    country: '',
    is_active: true
  }
  showModal.value = true
}

const editSupplier = (supplier) => {
  isEditing.value = true
  selectedSupplier.value = supplier
  form.value = {
    name: supplier.name,
    email: supplier.email,
    phone: supplier.phone,
    tax_id: supplier.tax_id,
    address: supplier.address,
    city: supplier.city,
    country: supplier.country,
    is_active: supplier.is_active
  }
  showModal.value = true
}

const viewSupplier = (supplier) => {
  selectedSupplier.value = supplier
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/v1/suppliers/${selectedSupplier.value.id}`, form.value)
    } else {
      await axios.post('/api/v1/suppliers', form.value)
    }
    closeModal()
    fetchSuppliers(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save supplier:', error)
  }
}

const deleteSupplier = async (supplier) => {
  if (!confirm('Are you sure you want to delete this supplier?')) return
  try {
    await axios.delete(`/api/v1/suppliers/${supplier.id}`)
    fetchSuppliers(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete supplier:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  selectedSupplier.value = null
}

const exportData = () => {
  window.print()
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  fetchSuppliers()
})
</script>
