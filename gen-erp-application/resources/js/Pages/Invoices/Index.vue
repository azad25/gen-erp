<template>
  <IndexPage
    title="Invoices"
    subtitle="Manage your sales invoices"
    create-route="/invoices/create"
    create-label="New Invoice"
    :columns="columns"
    :rows="invoices"
    :pagination="pagination"
    search-placeholder="Search invoices..."
    :on-row-click="row => $inertia.visit(`/invoices/${row.id}`)"
  >
    <template #cell-invoice_number="{ row }">
      <div class="font-mono font-semibold text-black">{{ row.invoice_number }}</div>
      <div class="text-xs text-gray-1">{{ row.invoice_date }}</div>
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
      <Button size="sm" variant="ghost" @click="$inertia.visit(`/invoices/${row.id}/edit`)">Edit</Button>
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
const { get } = useApi()
const { setTotal } = usePagination()

const invoices = ref([])
const pagination = ref({})

const columns = [
  { key: 'invoice_number', label: 'Invoice #', bold: true, mono: true },
  { key: 'customer_name', label: 'Customer' },
  { key: 'total_amount', label: 'Amount', right: true, mono: true },
  { key: 'status', label: 'Status' },
]

const fetchInvoices = async (page = 1) => {
  const response = await get('/invoices', { page, per_page: 15 })
  invoices.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchInvoices()
})
</script>
