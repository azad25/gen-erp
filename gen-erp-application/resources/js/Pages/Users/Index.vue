<template>
  <IndexPage
    title="Users"
    subtitle="Manage user accounts and permissions"
    create-route="/users/create"
    create-label="New User"
    :columns="columns"
    :rows="users"
    :pagination="pagination"
    search-placeholder="Search users..."
    :on-row-click="row => $inertia.visit(`/users/${row.id}`)"
  >
    <template #cell-name="{ row }">
      <div class="font-semibold text-black">{{ row.name }}</div>
      <div class="text-xs text-gray-1">{{ row.email }}</div>
    </template>
    <template #cell-role="{ value }">
      <Badge :variant="value === 'owner' ? 'success' : 'default'">{{ value }}</Badge>
    </template>
    <template #cell-status="{ value }">
      <Badge :variant="value === 'active' ? 'success' : 'default'">{{ value }}</Badge>
    </template>
    <template #actions="{ row }">
      <Button size="sm" variant="ghost" @click="$inertia.visit(`/users/${row.id}/edit`)">Edit</Button>
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
const { get } = useApi()
const { setTotal } = usePagination()

const users = ref([])
const pagination = ref({})

const columns = [
  { key: 'name', label: 'User', bold: true },
  { key: 'role', label: 'Role' },
  { key: 'status', label: 'Status' },
]

const fetchUsers = async (page = 1) => {
  const response = await get('/users', { page, per_page: 15 })
  users.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchUsers()
})
</script>
