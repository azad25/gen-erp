<template>
  <IndexPage
    title="Workflow Instances"
    subtitle="Track workflow processes"
    :columns="columns"
    :rows="workflows"
    :pagination="pagination"
    search-placeholder="Search workflows..."
    :on-row-click="row => $inertia.visit(`/workflows/${row.id}`)"
  >
    <template #cell-workflow_name="{ row }">
      <div class="font-semibold text-black">{{ row.workflow_name }}</div>
      <div class="text-xs text-gray-1">{{ row.entity_type }}: {{ row.entity_name }}</div>
    </template>
    <template #cell-current_status="{ value }">
      <Badge :variant="value">{{ value }}</Badge>
    </template>
    <template #cell-created_at="{ value }">
      <div class="font-mono text-sm">{{ value }}</div>
    </template>
    <template #actions="{ row }">
      <Button v-if="row.can_transition" size="sm" @click="handleTransition(row.id)">Transition</Button>
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
const { post } = useApi()
const { setTotal } = usePagination()

const workflows = ref([])
const pagination = ref({})

const columns = [
  { key: 'workflow_name', label: 'Workflow', bold: true },
  { key: 'current_status', label: 'Status' },
  { key: 'created_at', label: 'Date', mono: true },
]

const fetchWorkflows = async (page = 1) => {
  const response = await get('/workflow-instances', { page, per_page: 15 })
  workflows.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

const handleTransition = async (id) => {
  if (confirm('Are you sure you want to transition this workflow?')) {
    await post(`/workflow-instances/${id}/transition`)
    window.location.reload()
  }
}

onMounted(() => {
  fetchWorkflows()
})
</script>
