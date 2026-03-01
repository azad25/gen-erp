<template>
  <IndexPage
    title="Suppliers"
    subtitle="Manage your suppliers"
    create-route="/suppliers/create"
    create-label="New Supplier"
    :columns="columns"
    :rows="suppliers"
    :pagination="pagination"
    search-placeholder="Search suppliers..."
    :on-row-click="row => $inertia.visit(`/suppliers/${row.id}`)"
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
    <template #actions="{ row }">
      <Button size="sm" variant="ghost" @click="$inertia.visit(`/suppliers/${row.id}/edit`)">Edit</Button>
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

const suppliers = ref([])
const pagination = ref({})

const columns = [
  { key: 'name', label: 'Supplier', bold: true },
  { key: 'phone', label: 'Phone' },
  { key: 'district', label: 'District' },
]

const fetchSuppliers = async (page = 1) => {
  const response = await get('/suppliers', { page, per_page: 15 })
  suppliers.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchSuppliers()
})
</script>
