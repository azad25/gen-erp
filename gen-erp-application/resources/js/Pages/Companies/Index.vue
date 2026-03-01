<template>
  <IndexPage
    title="Companies"
    subtitle="Manage company settings"
    :columns="columns"
    :rows="companies"
    :pagination="pagination"
    search-placeholder="Search companies..."
    :on-row-click="row => $inertia.visit(`/companies/${row.id}`)"
  >
    <template #cell-name="{ row }">
      <div class="font-semibold text-black">{{ row.name }}</div>
      <div class="text-xs text-gray-1">{{ row.business_type }}</div>
    </template>
    <template #cell-vat_registered="{ value }">
      <Badge :variant="value ? 'success' : 'default'">{{ value ? 'Yes' : 'No' }}</Badge>
    </template>
    <template #cell-status="{ value }">
      <Badge :variant="value === 'active' ? 'success' : 'default'">{{ value }}</Badge>
    </template>
    <template #actions="{ row }">
      <Button size="sm" variant="ghost" @click="$inertia.visit(`/companies/${row.id}/edit`)">Edit</Button>
    </template>
  </IndexPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import IndexPage from '../Shared/IndexPage.vue'
import Badge from '../../Components/UI/Badge.vue'
import Button from '../../Components/UI/Button.vue'
import { useApi } from '../../Composables/useApi.js'
import { usePagination } from '../../Composables/usePagination.js'

const page = usePage()
const { get } = useApi()
const { setTotal } = usePagination()

const companies = ref([])
const pagination = ref({})

const columns = [
  { key: 'name', label: 'Company', bold: true },
  { key: 'vat_registered', label: 'VAT Registered' },
  { key: 'status', label: 'Status' },
]

const fetchCompanies = async (page = 1) => {
  const response = await get('/companies', { page, per_page: 15 })
  companies.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchCompanies()
})
</script>
