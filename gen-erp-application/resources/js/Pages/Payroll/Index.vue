<template>
  <IndexPage
    title="Payroll"
    subtitle="Manage payroll runs"
    :columns="columns"
    :rows="payroll"
    :pagination="pagination"
    search-placeholder="Search payroll..."
    :on-row-click="row => $inertia.visit(`/payroll/${row.id}`)"
  >
    <template #cell-period="{ row }">
      <div class="font-semibold text-black">{{ row.period }}</div>
      <div class="text-xs text-gray-1">{{ row.run_date }}</div>
    </template>
    <template #cell-total_amount="{ value }">
      <BanglaAmount :amount="value" />
    </template>
    <template #cell-status="{ value }">
      <Badge :variant="value">{{ value }}</Badge>
    </template>
    <template #actions="{ row }">
      <Button v-if="row.status === 'draft'" size="sm" @click="handleRun(row.id)">Run</Button>
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
const { post } = useApi()
const { setTotal } = usePagination()

const payroll = ref([])
const pagination = ref({})

const columns = [
  { key: 'period', label: 'Period', bold: true },
  { key: 'total_amount', label: 'Total Amount', right: true, mono: true },
  { key: 'status', label: 'Status' },
]

const fetchPayroll = async (page = 1) => {
  const response = await get('/payroll', { page, per_page: 15 })
  payroll.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

const handleRun = async (id) => {
  if (confirm('Are you sure you want to run this payroll?')) {
    await post(`/payroll/${id}/run`)
    window.location.reload()
  }
}

onMounted(() => {
  fetchPayroll()
})
</script>
