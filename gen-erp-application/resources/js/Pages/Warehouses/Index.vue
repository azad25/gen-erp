<template>
  <IndexPage
    title="Warehouses"
    subtitle="Manage your warehouses"
    create-route="/warehouses/create"
    create-label="New Warehouse"
    :columns="columns"
    :rows="warehouses"
    :pagination="pagination"
    search-placeholder="Search warehouses..."
    :on-row-click="row => $inertia.visit(`/warehouses/${row.id}`)"
  >
    <template #cell-name="{ row }">
      <div class="font-semibold text-black">{{ row.name }}</div>
      <div class="text-xs text-gray-1">{{ row.address }}</div>
    </template>
    <template #cell-district="{ value }">
      {{ value || '—' }}
    </template>
    <template #cell-phone="{ value }">
      {{ value || '—' }}
    </template>
    <template #actions="{ row }">
      <Button size="sm" variant="ghost" @click="$inertia.visit(`/warehouses/${row.id}/edit`)">Edit</Button>
    </template>
  </IndexPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import IndexPage from '../Shared/IndexPage.vue'
import { useApi } from '../../Composables/useApi.js'
import { usePagination } from '../../Composables/usePagination.js'

const page = usePage()
const { get } = useApi()
const { setTotal } = usePagination()

const warehouses = ref([])
const pagination = ref({})

const columns = [
  { key: 'name', label: 'Warehouse', bold: true },
  { key: 'district', label: 'District' },
  { key: 'phone', label: 'Phone' },
]

const fetchWarehouses = async (page = 1) => {
  const response = await get('/warehouses', { page, per_page: 15 })
  warehouses.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchWarehouses()
})
</script>
