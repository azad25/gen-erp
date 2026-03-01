<template>
  <IndexPage
    title="Stock Movements"
    subtitle="Track inventory movements"
    create-route="/stock-movements/create"
    create-label="New Movement"
    :columns="columns"
    :rows="stockMovements"
    :pagination="pagination"
    search-placeholder="Search movements..."
    :on-row-click="row => $inertia.visit(`/stock-movements/${row.id}`)"
  >
    <template #cell-movement_date="{ row }">
      <div class="font-mono text-sm">{{ row.movement_date }}</div>
    </template>
    <template #cell-product_name="{ value }">
      {{ value }}
    </template>
    <template #cell-movement_type="{ value }">
      <Badge :variant="value === 'in' ? 'success' : 'danger'">{{ value }}</Badge>
    </template>
    <template #cell-quantity="{ value }">
      {{ value }}
    </template>
  </IndexPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import IndexPage from '../Shared/IndexPage.vue'
import Badge from '../../Components/UI/Badge.vue'
import { useApi } from '../../Composables/useApi.js'
import { usePagination } from '../../Composables/usePagination.js'

const page = usePage()
const { get } = useApi()
const { setTotal } = usePagination()

const stockMovements = ref([])
const pagination = ref({})

const columns = [
  { key: 'movement_date', label: 'Date', mono: true },
  { key: 'product_name', label: 'Product' },
  { key: 'movement_type', label: 'Type' },
  { key: 'quantity', label: 'Quantity' },
]

const fetchStockMovements = async (page = 1) => {
  const response = await get('/stock-movements', { page, per_page: 15 })
  stockMovements.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchStockMovements()
})
</script>
