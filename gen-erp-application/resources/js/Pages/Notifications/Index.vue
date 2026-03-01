<template>
  <IndexPage
    title="Notifications"
    subtitle="View your notifications"
    :columns="columns"
    :rows="notifications"
    :pagination="pagination"
    search-placeholder="Search notifications..."
    :on-row-click="row => handleMarkRead(row.id)"
  >
    <template #cell-title="{ row }">
      <div class="font-semibold text-black">{{ row.title }}</div>
      <div class="text-xs text-gray-1">{{ row.message }}</div>
    </template>
    <template #cell-created_at="{ value }">
      <div class="font-mono text-sm">{{ value }}</div>
    </template>
    <template #cell-read="{ value }">
      <Badge :variant="value ? 'default' : 'success'">{{ value ? 'Read' : 'Unread' }}</Badge>
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

const notifications = ref([])
const pagination = ref({})

const columns = [
  { key: 'title', label: 'Notification', bold: true },
  { key: 'created_at', label: 'Date', mono: true },
  { key: 'read', label: 'Status' },
]

const fetchNotifications = async (page = 1) => {
  const response = await get('/notifications', { page, per_page: 15 })
  notifications.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

const handleMarkRead = async (id) => {
  await post(`/notifications/${id}/mark-read`)
  window.location.reload()
}

onMounted(() => {
  fetchNotifications()
})
</script>
