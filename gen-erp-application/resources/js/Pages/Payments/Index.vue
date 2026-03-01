<template>
  <IndexPage
    title="Payments"
    subtitle="Track all payments"
    create-route="/payments/create"
    create-label="New Payment"
    :columns="columns"
    :rows="payments"
    :pagination="pagination"
    search-placeholder="Search payments..."
    :on-row-click="row => $inertia.visit(`/payments/${row.id}`)"
  >
    <template #cell-payment_number="{ row }">
      <div class="font-mono font-semibold text-black">{{ row.payment_number }}</div>
      <div class="text-xs text-gray-1">{{ row.payment_date }}</div>
    </template>
    <template #cell-amount="{ value }">
      <BanglaAmount :amount="value" />
    </template>
    <template #cell-payment_method="{ value }">
      {{ value || '—' }}
    </template>
    <template #cell-status="{ value }">
      <Badge :variant="value">{{ value }}</Badge>
    </template>
    <template #actions="{ row }">
      <Button size="sm" variant="ghost" @click="handleAllocate(row.id)">Allocate</Button>
    </template>
  </IndexPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import IndexPage from '../Shared/IndexPage.vue'
import Badge from '../../Components/UI/Badge.vue'
import BanglaAmount from '../../Components/Bangla/BanglaAmount.vue'
import { useApi } from '../../Composables/useApi.js'
import { usePagination } from '../../Composables/usePagination.js'

const page = usePage()
const { post } = useApi()
const { setTotal } = usePagination()

const payments = ref([])
const pagination = ref({})

const columns = [
  { key: 'payment_number', label: 'Payment #', bold: true, mono: true },
  { key: 'amount', label: 'Amount', right: true, mono: true },
  { key: 'payment_method', label: 'Method' },
  { key: 'status', label: 'Status' },
]

const fetchPayments = async (page = 1) => {
  const response = await get('/payments', { page, per_page: 15 })
  payments.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

const handleAllocate = async (id) => {
  if (confirm('Are you sure you want to allocate this payment?')) {
    await post(`/payments/${id}/allocate`)
    window.location.reload()
  }
}

onMounted(() => {
  fetchPayments()
})
</script>
