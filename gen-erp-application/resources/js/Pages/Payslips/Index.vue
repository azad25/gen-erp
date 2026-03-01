<template>
  <IndexPage
    title="Payslips"
    subtitle="View employee payslips"
    :columns="columns"
    :rows="payslips"
    :pagination="pagination"
    search-placeholder="Search payslips..."
    :on-row-click="row => $inertia.visit(`/payslips/${row.id}`)"
  >
    <template #cell-employee_name="{ row }">
      <div class="font-semibold text-black">{{ row.employee_name }}</div>
      <div class="text-xs text-gray-1">{{ row.period }}</div>
    </template>
    <template #cell-net_pay="{ value }">
      <BanglaAmount :amount="value" />
    </template>
    <template #cell-status="{ value }">
      <Badge :variant="value === 'paid' ? 'success' : 'default'">{{ value }}</Badge>
    </template>
    <template #actions="{ row }">
      <Button size="sm" @click="handleDownload(row.id)">Download</Button>
    </template>
  </IndexPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import IndexPage from '../Shared/IndexPage.vue'
import Badge from '../../Components/UI/Badge.vue'
import BanglaAmount from '../../Components/Bangla/BanglaAmount.vue'
import Button from '../../Components/UI/Button.vue'
import { useApi } from '../../Composables/useApi.js'
import { usePagination } from '../../Composables/usePagination.js'

const page = usePage()
const { get } = useApi()
const { setTotal } = usePagination()

const payslips = ref([])
const pagination = ref({})

const columns = [
  { key: 'employee_name', label: 'Employee', bold: true },
  { key: 'net_pay', label: 'Net Pay', right: true, mono: true },
  { key: 'status', label: 'Status' },
]

const fetchPayslips = async (page = 1) => {
  const response = await get('/payslips', { page, per_page: 15 })
  payslips.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

const handleDownload = async (id) => {
  window.open(`/payslips/${id}/download`, '_blank')
}

onMounted(() => {
  fetchPayslips()
})
</script>
