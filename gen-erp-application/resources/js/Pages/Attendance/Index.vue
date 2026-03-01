<template>
  <IndexPage
    title="Attendance"
    subtitle="Track employee attendance"
    :columns="columns"
    :rows="attendance"
    :pagination="pagination"
    search-placeholder="Search attendance..."
    :on-row-click="row => $inertia.visit(`/attendance/${row.id}`)"
  >
    <template #cell-employee_name="{ value }">
      {{ value }}
    </template>
    <template #cell-date="{ value }">
      <div class="font-mono text-sm">{{ value }}</div>
    </template>
    <template #cell-check_in="{ value }">
      <div class="font-mono text-sm">{{ value }}</div>
    </template>
    <template #cell-check_out="{ value }">
      <div class="font-mono text-sm">{{ value }}</div>
    </template>
    <template #cell-status="{ value }">
      <Badge :variant="value">{{ value }}</Badge>
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

const attendance = ref([])
const pagination = ref({})

const columns = [
  { key: 'employee_name', label: 'Employee' },
  { key: 'date', label: 'Date', mono: true },
  { key: 'check_in', label: 'Check In', mono: true },
  { key: 'check_out', label: 'Check Out', mono: true },
  { key: 'status', label: 'Status' },
]

const fetchAttendance = async (page = 1) => {
  const response = await get('/attendance', { page, per_page: 15 })
  attendance.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchAttendance()
})
</script>
