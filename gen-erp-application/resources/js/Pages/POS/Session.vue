<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">POS Session</h1>
              <p class="text-sm text-gray-1">Manage POS session for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Session</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="sessions"
            :pagination="pagination"
            placeholder="Search sessions..."
            @search="handleSearch"
          >
            <template #cell-session_number="{ row }">
              <span class="font-mono text-sm">{{ row.session_number }}</span>
            </template>

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

            <template #cell-total="{ row }">
              <span class="font-semibold">{{ formatCurrency(row.total || 0) }}</span>
            </template>

            <template #cell-status="{ row }">
              <Badge :variant="getStatusVariant(row.status)">{{ row.status }}</Badge>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewSession(row)">View</Button>
              <Button variant="ghost" size="sm" @click="closeSession(row)" :disabled="row.status !== 'open'">Close</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" title="New POS Session">
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
                <label class="block text-sm font-medium mb-1">Warehouse *</label>
                <select v-model="form.warehouse_id" required class="w-full border rounded-lg px-3 py-2">
                  <option value="">Select Warehouse</option>
                  <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                    {{ warehouse.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Opening Cash</label>
                <input type="number" v-model="form.opening_cash" class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Notes</label>
                <textarea v-model="form.notes" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
              <Button type="submit">Create</Button>
            </div>
          </form>
        </Modal>

        <Modal v-if="showViewModal" @close="showViewModal = false" title="POS Session Details" size="lg">
          <div v-if="selectedSession" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Session Number</p>
                <p class="font-semibold">{{ selectedSession.session_number }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Employee</p>
                <p class="font-semibold">{{ selectedSession.employee?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Warehouse</p>
                <p class="font-semibold">{{ selectedSession.warehouse?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Start Date</p>
                <p class="font-semibold">{{ formatDate(selectedSession.start_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Total Sales</p>
                <p class="font-semibold">{{ formatCurrency(selectedSession.total || 0) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="getStatusVariant(selectedSession.status)">{{ selectedSession.status }}</Badge>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-1">Notes</p>
                <p class="font-semibold">{{ selectedSession.notes || '—' }}</p>
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

const sessions = ref([])
const employees = ref([])
const warehouses = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const selectedSession = ref(null)
const searchQuery = ref('')

const form = ref({
  employee_id: '',
  warehouse_id: '',
  opening_cash: 0,
  notes: ''
})

const columns = [
  { key: 'session_number', label: 'Session #' },
  { key: 'employee', label: 'Employee' },
  { key: 'start_date', label: 'Start Date' },
  { key: 'total', label: 'Total', right: true },
  { key: 'status', label: 'Status' }
]

const fetchSessions = async (page = 1) => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/pos-sessions', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    sessions.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch sessions:', error)
  } finally {
    loading.value = false
  }
}

const fetchEmployees = async () => {
  try {
    const response = await axios.get('/api/v1/employees', { params: { per_page: 100 } })
    employees.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch employees:', error)
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

const handleSearch = (query) => {
  searchQuery.value = query
  fetchSessions(1)
}

const openCreateModal = () => {
  form.value = {
    employee_id: '',
    warehouse_id: '',
    opening_cash: 0,
    notes: ''
  }
  showModal.value = true
}

const viewSession = (session) => {
  selectedSession.value = session
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    await axios.post('/api/v1/pos-sessions', form.value)
    closeModal()
    fetchSessions(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save session:', error)
  }
}

const closeSession = async (session) => {
  if (!confirm('Are you sure you want to close this session?')) return
  try {
    await axios.post(`/api/v1/pos-sessions/${session.id}/close`)
    fetchSessions(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to close session:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  selectedSession.value = null
}

const exportData = () => {
  window.print()
}

const getStatusVariant = (status) => {
  const variants = {
    open: 'default',
    closed: 'secondary'
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
  fetchSessions()
  fetchEmployees()
  fetchWarehouses()
})
</script>
