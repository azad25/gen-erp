<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Employees</h1>
              <p class="text-sm text-gray-1">Manage employees for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Employee</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="employees"
            :pagination="pagination"
            placeholder="Search employees..."
            @search="handleSearch"
          >
            <template #cell-name="{ row }">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold">
                  {{ row.name?.charAt(0) || 'E' }}
                </div>
                <div>
                  <p class="font-medium text-sm">{{ row.name }}</p>
                  <p class="text-xs text-gray-1">{{ row.email }}</p>
                </div>
              </div>
            </template>

            <template #cell-department="{ row }">
              <span class="text-sm">{{ row.department || '—' }}</span>
            </template>

            <template #cell-position="{ row }">
              <span class="text-sm">{{ row.position || '—' }}</span>
            </template>

            <template #cell-status="{ row }">
              <Badge :variant="row.is_active ? 'default' : 'secondary'">{{ row.is_active ? 'Active' : 'Inactive' }}</Badge>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewEmployee(row)">View</Button>
              <Button variant="ghost" size="sm" @click="editEmployee(row)">Edit</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteEmployee(row)">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" :title="isEditing ? 'Edit Employee' : 'New Employee'">
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
                <label class="block text-sm font-medium mb-1">Department</label>
                <input type="text" v-model="form.department" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Position</label>
                <input type="text" v-model="form.position" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Hire Date</label>
                <input type="date" v-model="form.hire_date" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Salary</label>
                <input type="number" v-model="form.salary" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Address</label>
                <input type="text" v-model="form.address" class="w-full border rounded-lg px-3 py-2">
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

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Employee Details" size="lg">
          <div v-if="selectedEmployee" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Name</p>
                <p class="font-semibold">{{ selectedEmployee.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Email</p>
                <p class="font-semibold">{{ selectedEmployee.email }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Phone</p>
                <p class="font-semibold">{{ selectedEmployee.phone || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Department</p>
                <p class="font-semibold">{{ selectedEmployee.department || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Position</p>
                <p class="font-semibold">{{ selectedEmployee.position || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Hire Date</p>
                <p class="font-semibold">{{ formatDate(selectedEmployee.hire_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Salary</p>
                <p class="font-semibold">{{ formatCurrency(selectedEmployee.salary || 0) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="selectedEmployee.is_active ? 'default' : 'secondary'">
                  {{ selectedEmployee.is_active ? 'Active' : 'Inactive' }}
                </Badge>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Address</p>
                <p class="font-semibold">{{ selectedEmployee.address || '—' }}</p>
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

const employees = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditing = ref(false)
const selectedEmployee = ref(null)
const searchQuery = ref('')

const form = ref({
  name: '',
  email: '',
  phone: '',
  department: '',
  position: '',
  hire_date: '',
  salary: 0,
  address: '',
  is_active: true
})

const columns = [
  { key: 'name', label: 'Employee' },
  { key: 'department', label: 'Department' },
  { key: 'position', label: 'Position' },
  { key: 'status', label: 'Status' }
]

const fetchEmployees = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/employees', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    employees.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch employees:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = (query) => {
  searchQuery.value = query
  fetchEmployees(1)
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = {
    name: '',
    email: '',
    phone: '',
    department: '',
    position: '',
    hire_date: '',
    salary: 0,
    address: '',
    is_active: true
  }
  showModal.value = true
}

const editEmployee = (employee) => {
  isEditing.value = true
  selectedEmployee.value = employee
  form.value = {
    name: employee.name,
    email: employee.email,
    phone: employee.phone,
    department: employee.department,
    position: employee.position,
    hire_date: employee.hire_date,
    salary: employee.salary,
    address: employee.address,
    is_active: employee.is_active
  }
  showModal.value = true
}

const viewEmployee = (employee) => {
  selectedEmployee.value = employee
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await api.put(`/employees/${selectedEmployee.value.id}`, form.value)
    } else {
      await api.post('/employees', form.value)
    }
    closeModal()
    fetchEmployees(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save employee:', error)
  }
}

const deleteEmployee = async (employee) => {
  if (!confirm('Are you sure you want to delete this employee?')) return
  try {
    await api.delete(`/employees/${employee.id}`)
    fetchEmployees(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete employee:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  selectedEmployee.value = null
}

const exportData = () => {
  window.print()
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount / 100)
}

onMounted(() => {
  fetchEmployees()
})
</script>
