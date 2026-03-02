<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Leave Management</h1>
              <p class="text-sm text-gray-1">Manage leave requests for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Request</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="leaves"
            :pagination="pagination"
            placeholder="Search leave requests..."
            @search="handleSearch"
          >
            <template #cell-employee="{ row }">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold">
                  {{ row.employee?.name?.charAt(0) || 'E' }}
                </div>
                <span class="text-sm">{{ row.employee?.name || '—' }}</span>
              </div>
            </template>

            <template #cell-start_date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.start_date) }}</span>
            </template>

            <template #cell-end_date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.end_date) }}</span>
            </template>

            <template #cell-type="{ row }">
              <Badge :variant="getTypeVariant(row.type)">{{ row.type }}</Badge>
            </template>

            <template #cell-status="{ row }">
              <Badge :variant="getStatusVariant(row.status)">{{ row.status }}</Badge>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewLeave(row)">View</Button>
              <Button variant="ghost" size="sm" @click="approveLeave(row)" :disabled="row.status !== 'pending'">Approve</Button>
              <Button variant="ghost" size="sm" @click="rejectLeave(row)" :disabled="row.status !== 'pending'">Reject</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" title="New Leave Request">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Employee *</label>
                <select v-model="form.employee_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Employee</option>
                  <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                    {{ employee.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Leave Type *</label>
                <select v-model="form.type" required class="w-full border rounded-lg px-3 py-2">
                  <option value="annual">Annual</option>
                  <option value="sick">Sick</option>
                  <option value="personal">Personal</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Start Date *</label>
                <input type="date" v-model="form.start_date" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">End Date *</label>
                <input type="date" v-model="form.end_date" required class="w-full border rounded-lg px-3 py-2">
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

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Leave Request Details" size="lg">
          <div v-if="selectedLeave" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Employee</p>
                <p class="font-semibold">{{ selectedLeave.employee?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Type</p>
                <Badge :variant="getTypeVariant(selectedLeave.type)">{{ selectedLeave.type }}</Badge>
              </div>
              <div>
                <p class="text-sm text-gray-1">Start Date</p>
                <p class="font-semibold">{{ formatDate(selectedLeave.start_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">End Date</p>
                <p class="font-semibold">{{ formatDate(selectedLeave.end_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="getStatusVariant(selectedLeave.status)">{{ selectedLeave.status }}</Badge>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Reason</p>
                <p class="font-semibold">{{ selectedLeave.reason || '—' }}</p>
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

const leaves = ref([])
const employees = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const selectedLeave = ref(null)
const searchQuery = ref('')

const form = ref({
  employee_id: '',
  type: 'annual',
  start_date: '',
  end_date: '',
  reason: ''
})

const columns = [
  { key: 'employee', label: 'Employee' },
  { key: 'start_date', label: 'Start Date' },
  { key: 'end_date', label: 'End Date' },
  { key: 'type', label: 'Type' },
  { key: 'status', label: 'Status' }
]

const fetchLeaves = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/leave-requests', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    leaves.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch leaves:', error)
  } finally {
    loading.value = false
  }
}

const fetchEmployees = async () => {
  try {
    const response = await api.get('/employees', { params: { per_page: 100 } })
    employees.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch employees:', error)
  }
}

const handleSearch = (query) => {
  searchQuery.value = query
  fetchLeaves(1)
}

const openCreateModal = () => {
  form.value = {
    employee_id: '',
    type: 'annual',
    start_date: '',
    end_date: '',
    reason: ''
  }
  showModal.value = true
}

const viewLeave = (leave) => {
  selectedLeave.value = leave
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    await api.post('/leave-requests', form.value)
    closeModal()
    fetchLeaves(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save leave:', error)
  }
}

const approveLeave = async (leave) => {
  if (!confirm('Are you sure you want to approve this leave request?')) return
  try {
    await api.post(`/leave-requests/${leave.id}/approve`)
    fetchLeaves(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to approve leave:', error)
  }
}

const rejectLeave = async (leave) => {
  if (!confirm('Are you sure you want to reject this leave request?')) return
  try {
    await api.post(`/leave-requests/${leave.id}/reject`)
    fetchLeaves(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to reject leave:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  selectedLeave.value = null
}

const exportData = () => {
  window.print()
}

const getTypeVariant = (type) => {
  const variants = {
    annual: 'default',
    sick: 'secondary',
    personal: 'secondary'
  }
  return variants[type] || 'secondary'
}

const getStatusVariant = (status) => {
  const variants = {
    pending: 'secondary',
    approved: 'default',
    rejected: 'destructive'
  }
  return variants[status] || 'secondary'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  fetchLeaves()
  fetchEmployees()
})
</script>
