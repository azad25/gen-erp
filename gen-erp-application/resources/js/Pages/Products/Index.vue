<template>
  <IndexPage
    title="Products"
    subtitle="Manage your product inventory"
    create-route="/products/create"
    create-label="New Product"
    :columns="columns"
    :rows="products"
    :pagination="pagination"
    search-placeholder="Search products..."
    :on-row-click="row => $inertia.visit(`/products/${row.id}`)"
  >
    <template #cell-name="{ row }">
      <div class="font-semibold text-black">{{ row.name }}</div>
      <div class="text-xs text-gray-1">{{ row.sku }}</div>
    </template>
    <template #cell-selling_price="{ value }">
      <BanglaAmount :amount="value" />
    </template>
    <template #cell-stock_level="{ value }">
      <Badge :variant="value < 10 ? 'warning' : 'success'">{{ value }}</Badge>
    </template>
    <template #actions="{ row }">
      <Button size="sm" variant="ghost" @click="$inertia.visit(`/products/${row.id}/edit`)">Edit</Button>
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

const products = ref([])
const pagination = ref({})

const columns = [
  { key: 'name', label: 'Product', bold: true },
  { key: 'sku', label: 'SKU', mono: true },
  { key: 'selling_price', label: 'Price', right: true, mono: true },
  { key: 'stock_level', label: 'Stock' },
]

const fetchProducts = async (page = 1) => {
  const response = await get('/products', { page, per_page: 15 })
  products.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchProducts()
})
</script>
