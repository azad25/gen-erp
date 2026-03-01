<template>
  <IndexPage
    title="Branches"
    subtitle="Manage your business branches"
    create-route="/branches/create"
    create-label="New Branch"
    :columns="columns"
    :rows="branches"
    :pagination="pagination"
    search-placeholder="Search branches..."
    :on-row-click="row => $inertia.visit(`/branches/${row.id}`)"
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
      <Button size="sm" variant="ghost" @click="$inertia.visit(`/branches/${row.id}/edit`)">Edit</Button>
    </template>
  </IndexPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import IndexPage from '../Shared/IndexPage.vue'
import Button from '../../Components/UI/Button.vue'
import { useApi } from '../../Composables/useApi.js'
import { usePagination } from '../../Composables/usePagination.js'

const page = usePage()
const { get } = useApi()
const { setTotal } = usePagination()

const branches = ref([])
const pagination = ref({})

const columns = [
  { key: 'name', label: 'Branch', bold: true },
  { key: 'district', label: 'District' },
  { key: 'phone', label: 'Phone' },
]

const fetchBranches = async (page = 1) => {
  const response = await get('/branches', { page, per_page: 15 })
  branches.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchBranches()
})
</script>
