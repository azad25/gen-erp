<template>
  <IndexPage
    title="Expenses"
    subtitle="Track your business expenses"
    create-route="/expenses/create"
    create-label="New Expense"
    :columns="columns"
    :rows="expenses"
    :pagination="pagination"
    search-placeholder="Search expenses..."
    :on-row-click="row => $inertia.visit(`/expenses/${row.id}`)"
  >
    <template #cell-expense_date="{ row }">
      <div class="font-mono text-sm">{{ row.expense_date }}</div>
    </template>
    <template #cell-amount="{ value }">
      <BanglaAmount :amount="value" />
    </template>
    <template #cell-category="{ value }">
      {{ value || '—' }}
    </template>
    <template #cell-status="{ value }">
      <Badge :variant="value">{{ value }}</Badge>
    </template>
    <template #actions="{ row }">
      <Button size="sm" variant="ghost" @click="$inertia.visit(`/expenses/${row.id}/edit`)">Edit</Button>
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

const expenses = ref([])
const pagination = ref({})

const columns = [
  { key: 'expense_date', label: 'Date', mono: true },
  { key: 'amount', label: 'Amount', right: true, mono: true },
  { key: 'category', label: 'Category' },
  { key: 'status', label: 'Status' },
]

const fetchExpenses = async (page = 1) => {
  const response = await get('/expenses', { page, per_page: 15 })
  expenses.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchExpenses()
})
</script>
