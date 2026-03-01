<template>
  <IndexPage
    title="Approval Requests"
    subtitle="Review and approve pending requests"
    :columns="columns"
    :rows="approvals"
    :pagination="pagination"
    search-placeholder="Search approvals..."
    :on-row-click="row => $inertia.visit(`/approvals/${row.id}`)"
  >
    <template #cell-request_type="{ row }">
      <Badge :variant="row.color || 'default'">{{ row.request_type }}</Badge>
    </template>
    <template #cell-requester_name="{ value }">
      {{ value }}
    </template>
    <template #cell-created_at="{ value }">
      <div class="font-mono text-xs">{{ value }}</div>
    </template>
    <template #actions="{ row }">
      <Button v-if="row.status === 'pending'" size="sm" @click="handleApprove(row.id)">Approve</Button>
      <Button v-if="row.status === 'pending'" size="sm" variant="danger" @click="handleReject(row.id)">Reject</Button>
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
const { post } = useApi()
const { setTotal } = usePagination()

const approvals = ref([])
const pagination = ref({})

const columns = [
  { key: 'request_type', label: 'Request Type' },
  { key: 'requester_name', label: 'Requested By' },
  { key: 'created_at', label: 'Date', mono: true },
]

const fetchApprovals = async (page = 1) => {
  const response = await get('/approval-requests', { page, per_page: 15 })
  approvals.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

const handleApprove = async (id) => {
  if (confirm('Are you sure you want to approve this request?')) {
    await post(`/approval-requests/${id}/approve`)
    window.location.reload()
  }
}

const handleReject = async (id) => {
  if (confirm('Are you sure you want to reject this request?')) {
    await post(`/approval-requests/${id}/reject`)
    window.location.reload()
  }
}

onMounted(() => {
  fetchApprovals()
})
</script>
