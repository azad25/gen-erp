<template>
  <IndexPage
    title="Customers"
    subtitle="Manage your customer database"
    create-route="/customers/create"
    create-label="New Customer"
    :columns="columns"
    :rows="customers"
    :pagination="pagination"
    search-placeholder="Search customers..."
    :on-row-click="row => $inertia.visit(`/customers/${row.id}`)"
  >
    <template #cell-name="{ row }">
      <div class="font-semibold text-black">{{ row.name }}</div>
      <div class="text-xs text-gray-1">{{ row.email }}</div>
    </template>
    <template #cell-phone="{ value }">
      {{ value ?? '—' }}
    </template>
    <template #cell-district="{ value }">
      {{ value ?? '—' }}
    </template>
    <template #cell-credit_limit="{ value }">
      <BanglaAmount v-if="value" :amount="value" />
    </template>
    <template #actions="{ row }">
      <Button size="sm" variant="ghost" @click="$inertia.visit(`/customers/${row.id}/edit`)">Edit</Button>
    </template>
  </IndexPage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import IndexPage from '../Shared/IndexPage.vue'
import BanglaAmount from '../../Components/Bangla/BanglaAmount.vue'
import { useApi } from '../../Composables/useApi.js'
import { usePagination } from '../../Composables/usePagination.js'

const page = usePage()
const { get } = useApi()
const { setTotal, from, to, links, setPage } = usePagination()

const customers = ref([])
const pagination = ref({})

const columns = [
  { key: 'name', label: 'Customer', bold: true },
  { key: 'phone', label: 'Phone', mono: false },
  { key: 'district', label: 'District' },
  { key: 'credit_limit', label: 'Credit Limit', right: true, mono: true },
]

const fetchCustomers = async (page = 1) => {
  const response = await get('/customers', { page, per_page: 15 })
  customers.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchCustomers()
})
</script>
