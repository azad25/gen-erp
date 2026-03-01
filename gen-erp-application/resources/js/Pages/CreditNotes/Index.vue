<template>
  <IndexPage
    title="Credit Notes"
    subtitle="Manage credit notes"
    create-route="/credit-notes/create"
    create-label="New Credit Note"
    :columns="columns"
    :rows="creditNotes"
    :pagination="pagination"
    search-placeholder="Search credit notes..."
    :on-row-click="row => $inertia.visit(`/credit-notes/${row.id}`)"
  >
    <template #cell-credit_note_number="{ row }">
      <div class="font-mono font-semibold text-black">{{ row.credit_note_number }}</div>
      <div class="text-xs text-gray-1">{{ row.credit_note_date }}</div>
    </template>
    <template #cell-customer_name="{ value }">
      {{ value }}
    </template>
    <template #cell-amount="{ value }">
      <BanglaAmount :amount="value" />
    </template>
    <template #cell-status="{ value }">
      <Badge :variant="value">{{ value }}</Badge>
    </template>
    <template #actions="{ row }">
      <Button size="sm" variant="ghost" @click="$inertia.visit(`/credit-notes/${row.id}/edit`)">Edit</Button>
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

const creditNotes = ref([])
const pagination = ref({})

const columns = [
  { key: 'credit_note_number', label: 'Credit Note #', bold: true, mono: true },
  { key: 'customer_name', label: 'Customer' },
  { key: 'amount', label: 'Amount', right: true, mono: true },
  { key: 'status', label: 'Status' },
]

const fetchCreditNotes = async (page = 1) => {
  const response = await get('/credit-notes', { page, per_page: 15 })
  creditNotes.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchCreditNotes()
})
</script>
