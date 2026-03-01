<template>
  <IndexPage
    title="Sales Orders"
    subtitle="Manage your sales orders"
    create-route="/sales-orders/create"
    create-label="New Sales Order"
    :columns="columns"
    :rows="salesOrders"
    :pagination="pagination"
    search-placeholder="Search sales orders..."
    :on-row-click="row => $inertia.visit(`/sales-orders/${row.id}`)"
  >
    <template #cell-order_number="{ row }">
      <div class="font-mono font-semibold text-black">{{ row.order_number }}</div>
      <div class="text-xs text-gray-1">{{ row.order_date }}</div>
    </template>
    <template #cell-customer_name="{ value }">
      {{ value }}
    </template>
    <template #cell-total_amount="{ value }">
      <BanglaAmount :amount="value" />
    </template>
    <template #cell-status="{ value }">
      <Badge :variant="value">{{ value }}</Badge>
    </template>
    <template #actions="{ row }">
      <Button v-if="row.status === 'draft'" size="sm" variant="ghost" @click="handleConfirm(row.id)">Confirm</Button>
      <Button v-if="row.status === 'confirmed'" size="sm" variant="ghost" @click="handleConvert(row.id)">Convert to Invoice</Button>
      <Button v-if="row.status !== 'cancelled'" size="sm" variant="ghost" @click="handleCancel(row.id)">Cancel</Button>
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

const salesOrders = ref([])
const pagination = ref({})

const columns = [
  { key: 'order_number', label: 'Order #', bold: true, mono: true },
  { key: 'customer_name', label: 'Customer' },
  { key: 'total_amount', label: 'Amount', right: true, mono: true },
  { key: 'status', label: 'Status' },
]

const fetchSalesOrders = async (page = 1) => {
  const response = await get('/sales-orders', { page, per_page: 15 })
  salesOrders.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

const handleConfirm = async (id) => {
  if (confirm('Are you sure you want to confirm this sales order?')) {
    await post(`/sales-orders/${id}/confirm`)
    window.location.reload()
  }
}

const handleConvert = async (id) => {
  if (confirm('Are you sure you want to convert this sales order to invoice?')) {
    await post(`/sales-orders/${id}/convert-to-invoice`)
    window.location.reload()
  }
}

const handleCancel = async (id) => {
  if (confirm('Are you sure you want to cancel this sales order?')) {
    await post(`/sales-orders/${id}/cancel`)
    window.location.reload()
  }
}

onMounted(() => {
  fetchSalesOrders()
})
</script>
