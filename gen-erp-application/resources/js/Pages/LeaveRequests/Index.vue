<template>
  <IndexPage
    title="Leave Requests"
    subtitle="Manage employee leave requests"
    :columns="columns"
    :rows="leaveRequests"
    :pagination="pagination"
    search-placeholder="Search leave requests..."
    :on-row-click="row => $inertia.visit(`/leave-requests/${row.id}`)"
  >
    <template #cell-employee_name="{ value }">
      {{ value }}
    </template>
    <template #cell-leave_type_name="{ value }">
      {{ value || '—' }}
    </template>
    <template #cell-start_date="{ row }">
      <div class="font-mono text-sm">{{ row.start_date }}</div>
    </template>
    <template #cell-status="{ value }">
      <Badge :variant="value">{{ value }}</Badge>
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
import Button from '../../Components/UI/Button.vue'
import { useApi } from '../../Composables/useApi.js'
import { usePagination } from '../../Composables/usePagination.js'

const page = usePage()
const { post } = useApi()
const { setTotal } = usePagination()

const leaveRequests = ref([])
const pagination = ref({})

const columns = [
  { key: 'employee_name', label: 'Employee' },
  { key: 'leave_type_name', label: 'Leave Type' },
  { key: 'start_date', label: 'Start Date', mono: true },
  { key: 'status', label: 'Status' },
]

const fetchLeaveRequests = async (page = 1) => {
  const response = await get('/leave-requests', { page, per_page: 15 })
  leaveRequests.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

const handleApprove = async (id) => {
  if (confirm('Are you sure you want to approve this leave request?')) {
    await post(`/leave-requests/${id}/approve`)
    window.location.reload()
  }
}

const handleReject = async (id) => {
  if (confirm('Are you sure you want to reject this leave request?')) {
    await post(`/leave-requests/${id}/reject`)
    window.location.reload()
  }
}

onMounted(() => {
  fetchLeaveRequests()
})
</script>
