<template>
  <IndexPage
    title="Reports"
    subtitle="Generate and view reports"
    :columns="columns"
    :rows="reports"
    :pagination="pagination"
    search-placeholder="Search reports..."
    :on-row-click="row => handleGenerate(row.id)"
  >
    <template #cell-name="{ row }">
      <div class="font-semibold text-black">{{ row.name }}</div>
      <div class="text-xs text-gray-1">{{ row.description }}</div>
    </template>
    <template #cell-report_type="{ value }">
      {{ value }}
    </template>
    <template #actions="{ row }">
      <Button size="sm" @click="handleGenerate(row.id)">Generate</Button>
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

const reports = ref([])
const pagination = ref({})

const columns = [
  { key: 'name', label: 'Report Name', bold: true },
  { key: 'report_type', label: 'Type' },
]

const fetchReports = async (page = 1) => {
  const response = await get('/reports', { page, per_page: 15 })
  reports.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

const handleGenerate = async (id) => {
  window.open(`/reports/${id}/generate`, '_blank')
}

onMounted(() => {
  fetchReports()
})
</script>
