<template>
  <IndexPage
    title="Chart of Accounts"
    subtitle="Manage your chart of accounts"
    create-route="/accounts/create"
    create-label="New Account"
    :columns="columns"
    :rows="accounts"
    :pagination="pagination"
    search-placeholder="Search accounts..."
    :on-row-click="row => $inertia.visit(`/accounts/${row.id}`)"
  >
    <template #cell-account_number="{ row }">
      <div class="font-mono font-semibold text-black">{{ row.account_number }}</div>
      <div class="text-xs text-gray-1">{{ row.account_name }}</div>
    </template>
    <template #cell-account_type="{ value }">
      {{ value || '—' }}
    </template>
    <template #cell-balance="{ value }">
      <BanglaAmount :amount="value" />
    </template>
    <template #actions="{ row }">
      <Button size="sm" variant="ghost" @click="$inertia.visit(`/accounts/${row.id}/edit`)">Edit</Button>
    </template>
  </IndexPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import IndexPage from '../Shared/IndexPage.vue'
import BanglaAmount from '../../Components/Bangla/BanglaAmount.vue'
import { useApi } from '../../Composables/useApi.js'
import { usePagination } from '../../Composables/usePagination.js'

const page = usePage()
const { get } = useApi()
const { setTotal } = usePagination()

const accounts = ref([])
const pagination = ref({})

const columns = [
  { key: 'account_number', label: 'Account #', bold: true, mono: true },
  { key: 'account_name', label: 'Account Name' },
  { key: 'account_type', label: 'Type' },
  { key: 'balance', label: 'Balance', right: true, mono: true },
]

const fetchAccounts = async (page = 1) => {
  const response = await get('/accounts', { page, per_page: 15 })
  accounts.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchAccounts()
})
</script>
