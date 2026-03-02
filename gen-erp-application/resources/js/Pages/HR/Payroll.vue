<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Payroll</h1>
              <p class="text-sm text-gray-1">Manage payroll for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm" @click="exportData">Export</Button>
              <Button size="sm" @click="openCreateModal">+ New Payroll</Button>
            </div>
          </div>

          <DataTable
            :columns="columns"
            :rows="payrolls"
            :pagination="pagination"
            placeholder="Search payrolls..."
            @search="handleSearch"
          >
            <template #cell-period="{ row }">
              <span class="text-sm text-gray-1">{{ row.period }}</span>
            </template>

            <template #cell-employee="{ row }">
              <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold">
                  {{ row.employee?.name?.charAt(0) || 'E' }}
                </div>
                <span class="text-sm">{{ row.employee?.name || '—' }}</span>
              </div>
            </template>

            <template #cell-gross="{ row }">
              <span class="font-semibold">{{ formatCurrency(row.gross_pay || 0) }}</span>
            </template>

            <template #cell-net="{ row }">
              <span class="font-semibold">{{ formatCurrency(row.net_pay || 0) }}</span>
            </template>

            <template #cell-status="{ row }">
              <Badge :variant="getStatusVariant(row.status)">{{ row.status }}</Badge>
            </template>

            <template #actions="{ row }">
              <Button variant="ghost" size="sm" @click="viewPayroll(row)">View</Button>
              <Button variant="ghost" size="sm" @click="processPayroll(row)" :disabled="row.status !== 'pending'">Process</Button>
            </template>
          </DataTable>
        </div>

        <Modal v-if="showModal" @close="closeModal" title="New Payroll">
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
                <label class="block text-sm font-medium mb-1">Period *</label>
                <input type="month" v-model="form.period" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Gross Pay *</label>
                <input type="number" v-model="form.gross_pay" required class="w-full border rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Deductions</label>
                <input type="number" v-model="form.deductions" class="w-full border rounded-lg px-3 py-2">
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
              <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
              <Button type="submit">Create</Button>
            </div>
          </form>
        </Modal>

        <Modal v-if="showViewModal" @close="showViewModal = false" title="Payroll Details" size="lg">
          <div v-if="selectedPayroll" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-1">Period</p>
                <p class="font-semibold">{{ selectedPayroll.period }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Employee</p>
                <p class="font-semibold">{{ selectedPayroll.employee?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Gross Pay</p>
                <p class="font-semibold">{{ formatCurrency(selectedPayroll.gross_pay) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Deductions</p>
                <p class="font-semibold">{{ formatCurrency(selectedPayroll.deductions) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Net Pay</p>
                <p class="font-semibold text-lg">{{ formatCurrency(selectedPayroll.net_pay) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-1">Status</p>
                <Badge :variant="getStatusVariant(selectedPayroll.status)">{{ selectedPayroll.status }}</Badge>
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

const payrolls = ref([])
const employees = ref([])
const pagination = ref({ current_page: 1, per_page: 15, total: 0 })
const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const selectedPayroll = ref(null)
const searchQuery = ref('')

const form = ref({
  employee_id: '',
  period: '',
  gross_pay: 0,
  deductions: 0
})

const columns = [
  { key: 'period', label: 'Period' },
  { key: 'employee', label: 'Employee' },
  { key: 'gross', label: 'Gross Pay', right: true },
  { key: 'net', label: 'Net Pay', right: true },
  { key: 'status', label: 'Status' }
]

const fetchPayrolls = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/payroll', {
      params: { page, per_page: 15, search: searchQuery.value }
    })
    payrolls.value = response.data.data
    pagination.value = response.data
  } catch (error) {
    console.error('Failed to fetch payrolls:', error)
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
  fetchPayrolls(1)
}

const openCreateModal = () => {
  form.value = {
    employee_id: '',
    period: new Date().toISOString().slice(0, 7),
    gross_pay: 0,
    deductions: 0
  }
  showModal.value = true
}

const viewPayroll = (payroll) => {
  selectedPayroll.value = payroll
  showViewModal.value = true
}

const handleSubmit = async () => {
  try {
    await api.post('/payroll', form.value)
    closeModal()
    fetchPayrolls(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to save payroll:', error)
  }
}

const processPayroll = async (payroll) => {
  if (!confirm('Are you sure you want to process this payroll?')) return
  try {
    await api.post(`/payroll/${payroll.id}/process`)
    fetchPayrolls(pagination.value.current_page)
  } catch (error) {
    console.error('Failed to process payroll:', error)
  }
}

const closeModal = () => {
  showModal.value = false
  selectedPayroll.value = null
}

const exportData = () => {
  window.print()
}

const getStatusVariant = (status) => {
  const variants = {
    pending: 'secondary',
    processed: 'default',
    paid: 'default'
  }
  return variants[status] || 'secondary'
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount / 100)
}

onMounted(() => {
  fetchPayrolls()
  fetchEmployees()
})
</script>
