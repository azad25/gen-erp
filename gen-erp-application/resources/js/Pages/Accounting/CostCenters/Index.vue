<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Cost Centers</h1>
        <p class="text-sm text-gray-600 mt-1">Manage cost centers for dimensional accounting</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2"
      >
        <PlusIcon class="w-4 h-4" />
        Add Cost Center
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search cost centers..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="flex items-end">
          <button
            @click="loadCostCenters"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md"
          >
            Apply Filters
          </button>
        </div>
      </div>
    </div>

    <!-- Cost Centers Table -->
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Code
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Name
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Description
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Manager
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="costCenter in costCenters.data" :key="costCenter.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ costCenter.code }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ costCenter.name }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ costCenter.description || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ costCenter.manager_name || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    costCenter.is_active
                      ? 'bg-green-100 text-green-800'
                      : 'bg-red-100 text-red-800'
                  ]"
                >
                  {{ costCenter.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-2">
                  <button
                    @click="editCostCenter(costCenter)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    <PencilIcon class="w-4 h-4" />
                  </button>
                  <button
                    @click="deleteCostCenter(costCenter)"
                    class="text-red-600 hover:text-red-900"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="costCenters.meta" class="bg-gray-50 px-6 py-3 border-t">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Showing {{ costCenters.meta.from }} to {{ costCenters.meta.to }} of {{ costCenters.meta.total }} results
          </div>
          <div class="flex items-center gap-2">
            <button
              v-for="link in costCenters.meta.links"
              :key="link.label"
              @click="changePage(link.url)"
              :disabled="!link.url"
              :class="[
                'px-3 py-1 text-sm rounded',
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                  ? 'bg-white text-gray-700 hover:bg-gray-50 border'
                  : 'bg-gray-100 text-gray-400 cursor-not-allowed'
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <CostCenterModal
      v-if="showCreateModal || showEditModal"
      :cost-center="selectedCostCenter"
      :is-edit="showEditModal"
      @close="closeModal"
      @saved="handleSaved"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-if="showDeleteModal"
      title="Delete Cost Center"
      :message="`Are you sure you want to delete '${selectedCostCenter?.name}'? This action cannot be undone.`"
      confirm-text="Delete"
      confirm-class="bg-red-600 hover:bg-red-700"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import CostCenterModal from './CostCenterModal.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import { useToast } from '@/Composables/useToast'
import axios from 'axios'

const { showToast } = useToast()

// Data
const costCenters = ref({ data: [], meta: null })
const loading = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedCostCenter = ref(null)

const filters = reactive({
  search: '',
  status: '',
  page: 1
})

// Methods
const loadCostCenters = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.search) params.append('search', filters.search)
    if (filters.status) params.append('status', filters.status)
    params.append('page', filters.page)

    const response = await axios.get(`/api/v1/cost-centers?${params}`)
    costCenters.value = response.data
  } catch (error) {
    showToast('Failed to load cost centers', 'error')
    console.error('Error loading cost centers:', error)
  } finally {
    loading.value = false
  }
}

const editCostCenter = (costCenter) => {
  selectedCostCenter.value = { ...costCenter }
  showEditModal.value = true
}

const deleteCostCenter = (costCenter) => {
  selectedCostCenter.value = costCenter
  showDeleteModal.value = true
}

const confirmDelete = async () => {
  try {
    await axios.delete(`/api/v1/cost-centers/${selectedCostCenter.value.id}`)
    showToast('Cost center deleted successfully', 'success')
    showDeleteModal.value = false
    selectedCostCenter.value = null
    loadCostCenters()
  } catch (error) {
    showToast('Failed to delete cost center', 'error')
    console.error('Error deleting cost center:', error)
  }
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  selectedCostCenter.value = null
}

const handleSaved = () => {
  closeModal()
  loadCostCenters()
  showToast(
    showEditModal.value ? 'Cost center updated successfully' : 'Cost center created successfully',
    'success'
  )
}

const changePage = (url) => {
  if (!url) return
  
  const urlObj = new URL(url)
  filters.page = urlObj.searchParams.get('page') || 1
  loadCostCenters()
}

// Lifecycle
onMounted(() => {
  loadCostCenters()
})
</script>