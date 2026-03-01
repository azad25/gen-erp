<template>
  <IndexPage
    title="Employees"
    subtitle="Manage your workforce"
    create-route="/employees/create"
    create-label="New Employee"
    :columns="columns"
    :rows="employees"
    :pagination="pagination"
    search-placeholder="Search employees..."
    :on-row-click="row => $inertia.visit(`/employees/${row.id}`)"
  >
    <template #cell-name="{ row }">
      <div class="font-semibold text-black">{{ row.name }}</div>
      <div class="text-xs text-gray-1">{{ row.email }}</div>
    </template>
    <template #cell-department_name="{ value }">
      {{ value ?? '—' }}
    </template>
    <template #cell-designation_name="{ value }">
      {{ value ?? '—' }}
    </template>
    <template #cell-phone="{ value }">
      {{ value ?? '—' }}
    </template>
    <template #actions="{ row }">
      <Button size="sm" variant="ghost" @click="$inertia.visit(`/employees/${row.id}/edit`)">Edit</Button>
    </template>
  </IndexPage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import IndexPage from '../Shared/IndexPage.vue'
import { useApi } from '../../Composables/useApi.js'
import { usePagination } from '../../Composables/usePagination.js'

const page = usePage()
const { get } = useApi()
const { setTotal } = usePagination()

const employees = ref([])
const pagination = ref({})

const columns = [
  { key: 'name', label: 'Employee', bold: true },
  { key: 'department_name', label: 'Department' },
  { key: 'designation_name', label: 'Designation' },
  { key: 'phone', label: 'Phone' },
]

const fetchEmployees = async (page = 1) => {
  const response = await get('/employees', { page, per_page: 15 })
  employees.value = response.data
  pagination.value = response.meta
  setTotal(response.meta.total)
}

onMounted(() => {
  fetchEmployees()
})
</script>
