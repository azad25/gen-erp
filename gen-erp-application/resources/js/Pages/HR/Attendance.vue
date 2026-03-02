<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Attendance</h1>
              <p class="text-sm text-gray-1">Manage attendance for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Attendance</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="attendance"
            :pagination="pagination"
            placeholder="Search attendance..."
            @search="handleSearch"
          >
            <template #cell-date="{ row }">
              <span class="text-sm text-gray-1">{{ formatDate(row.date) }}</span>
            </template>

            <template #cell-employee="{ row }">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold">
                  {{ row.employee?.name?.charAt(0) || 'E' }}
                </div>
                <span class="text-sm">{{ row.employee?.name || '—' }}</span>
              </div>
            </template>

            <template #cell-check_in="{ row }">
              <span class="text-sm">{{ row.check_in || '—' }}</span>
            </template>

            <template #cell-check_out="{ row }">
              <span class="text-sm">{{ row.check_out || '—' }}</span>
            </template>

            <template #cell-status="{ row }">
              <Badge :variant="getStatusVariant(row.status)">{{ row.status }}</Badge>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewAttendance(row)">View</Button>
              <Button variant="ghost" size="sm" class="text-red-500" @click="deleteAttendance(row)">Delete</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" title="New Attendance">
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
                <label class="block text-sm font-medium mb-1">Date *</label>
                <input type="date" v-model="form.date" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Check In</label>
                <input type="time" v-model="form.check_in" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Check Out</label>
                <input type="time" v-model="form.check_out" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select v-model="form.status" required class="w-full border rounded-lg px-3 py-2">
                  <option value="present">Present</option>
                  <option value="absent">Absent</option>
                  <option value="leave">On Leave</option>
                </select>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
              <Button type="submit">Create</Button>
            </div>
          </form>
        </Modal>

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Attendance Details" size="lg">
          <div v-if="selectedAttendance" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Date</p>
                <p class="font-semibold">{{ formatDate(selectedAttendance.date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Employee</p>
                <p class="font-semibold">{{ selectedAttendance.employee?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Check In</p>
                <p class="font-semibold">{{ selectedAttendance.check_in || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Check Out</p>
                <p class="font-semibold">{{ selectedAttendance.check_out || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="getStatusVariant(selectedAttendance.status)">{{ selectedAttendance.status }}</Badge>
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

const attendance = ref([])
const employees = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const selectedAttendance = ref(null)
const searchQuery = ref('')

const form = ref({
  employee_id: '',
  date: new Date().toISOString().split('T')[0],
  check_in: '',
  check_out: '',
  status: 'present'
})

const columns = [
  { key: 'date', label: 'Date' },
  { key: 'employee', label: 'Employee' },
  { key: 'check_in', label: 'Check In' },
  { key: 'check_out', label: 'Check Out' },
  { key: 'status', label: 'Status' }
]

const fetchAttendance = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/attendance', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    attendance.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch attendance:', error)
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
  fetchAttendance(1)
}

const openCreateModal = () => {
  form.value = {
    employee_id: '',
    date: new Date().toISOString().split('T')[0],
    check_in: '',
    check_out: '',
    status: 'present'
  }
  showModal.value = true
}

const viewAttendance = (attendance) => {
  selectedAttendance.value = attendance
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    await api.post('/attendance', form.value)
    closeModal()
    fetchAttendance(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save attendance:', error)
  }
}

const deleteAttendance = async (attendance) => {
  if (!confirm('Are you sure you want to delete this attendance record?')) return
  try {
    await api.delete(`/attendance/${attendance.id}`)
    fetchAttendance(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to delete attendance:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  selectedAttendance.value = null
}

const exportData = () => {
  window.print()
}

const getStatusVariant = (status) => {
  const variants = {
    present: 'default',
    absent: 'destructive',
    leave: 'secondary'
  }
  return variants[status] || 'secondary'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  fetchAttendance()
  fetchEmployees()
})
</script>
