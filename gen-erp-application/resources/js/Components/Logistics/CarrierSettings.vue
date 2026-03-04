<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Carrier Settings</h3>
        <button
          @click="showAddModal = true"
          class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-3 rounded-md"
        >
          Add Carrier
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      <span class="ml-2 text-gray-600">Loading carriers...</span>
    </div>

    <!-- Carriers List -->
    <div v-else-if="carriers.length > 0" class="divide-y divide-gray-200">
      <div
        v-for="carrier in carriers"
        :key="carrier.id"
        class="p-4 hover:bg-gray-50"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <!-- Carrier Logo/Icon -->
            <div class="flex-shrink-0">
              <img
                v-if="carrier.logo_url"
                :src="carrier.logo_url"
                :alt="carrier.name"
                class="h-10 w-10 rounded-lg object-cover"
              />
              <div
                v-else
                class="h-10 w-10 bg-gray-200 rounded-lg flex items-center justify-center"
              >
                <TruckIcon class="h-6 w-6 text-gray-500" />
              </div>
            </div>

            <!-- Carrier Info -->
            <div>
              <h4 class="text-sm font-medium text-gray-900">{{ carrier.name }}</h4>
              <div class="flex items-center space-x-4 mt-1">
                <span class="text-xs text-gray-500">{{ carrier.code }}</span>
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="carrier.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                >
                  {{ carrier.is_active ? 'Active' : 'Inactive' }}
                </span>
                <span
                  v-if="carrier.is_default"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                >
                  Default
                </span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center space-x-2">
            <button
              @click="testConnection(carrier)"
              :disabled="testingCarrier === carrier.id"
              class="text-sm text-indigo-600 hover:text-indigo-500"
            >
              {{ testingCarrier === carrier.id ? 'Testing...' : 'Test' }}
            </button>
            <button
              @click="editCarrier(carrier)"
              class="text-sm text-gray-600 hover:text-gray-500"
            >
              Edit
            </button>
            <button
              @click="toggleCarrier(carrier)"
              class="text-sm text-gray-600 hover:text-gray-500"
            >
              {{ carrier.is_active ? 'Disable' : 'Enable' }}
            </button>
          </div>
        </div>

        <!-- Carrier Details -->
        <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div>
            <span class="text-gray-500">Services:</span>
            <div class="mt-1">
              <div
                v-for="service in carrier.services"
                :key="service.code"
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1 mb-1"
              >
                {{ service.name }}
              </div>
            </div>
          </div>
          <div>
            <span class="text-gray-500">API Status:</span>
            <div class="mt-1 flex items-center">
              <div
                class="w-2 h-2 rounded-full mr-2"
                :class="getApiStatusColor(carrier.api_status)"
              ></div>
              <span class="capitalize">{{ carrier.api_status || 'unknown' }}</span>
            </div>
          </div>
          <div>
            <span class="text-gray-500">Last Sync:</span>
            <div class="mt-1">{{ formatDate(carrier.last_sync_at) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <TruckIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No carriers configured</h3>
      <p class="mt-1 text-sm text-gray-500">Add your first shipping carrier to get started.</p>
    </div>

    <!-- Add/Edit Carrier Modal -->
    <div
      v-if="showAddModal || editingCarrier"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
    >
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <!-- Modal Header -->
          <div class="flex items-center justify-between pb-4 border-b">
            <h3 class="text-lg font-medium text-gray-900">
              {{ editingCarrier ? 'Edit Carrier' : 'Add Carrier' }}
            </h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>

          <!-- Carrier Form -->
          <form @submit.prevent="saveCarrier" class="mt-6 space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700">Carrier Name *</label>
                <input
                  v-model="carrierForm.name"
                  type="text"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Carrier Code *</label>
                <input
                  v-model="carrierForm.code"
                  type="text"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>

            <!-- API Configuration -->
            <div>
              <h4 class="text-md font-medium text-gray-900 mb-4">API Configuration</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700">API URL</label>
                  <input
                    v-model="carrierForm.api_url"
                    type="url"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">API Key</label>
                  <input
                    v-model="carrierForm.api_key"
                    type="password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Username</label>
                  <input
                    v-model="carrierForm.username"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Password</label>
                  <input
                    v-model="carrierForm.password"
                    type="password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>
              </div>
            </div>

            <!-- Services -->
            <div>
              <h4 class="text-md font-medium text-gray-900 mb-4">Available Services</h4>
              <div class="space-y-3">
                <div
                  v-for="(service, index) in carrierForm.services"
                  :key="index"
                  class="flex items-center space-x-3 p-3 border border-gray-200 rounded-md"
                >
                  <div class="flex-1 grid grid-cols-3 gap-3">
                    <input
                      v-model="service.name"
                      type="text"
                      placeholder="Service Name"
                      class="text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    <input
                      v-model="service.code"
                      type="text"
                      placeholder="Service Code"
                      class="text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    <input
                      v-model.number="service.max_weight"
                      type="number"
                      placeholder="Max Weight (kg)"
                      class="text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>
                  <button
                    type="button"
                    @click="removeService(index)"
                    class="text-red-400 hover:text-red-600"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
                <button
                  type="button"
                  @click="addService"
                  class="text-sm text-indigo-600 hover:text-indigo-500"
                >
                  + Add Service
                </button>
              </div>
            </div>

            <!-- Settings -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="flex items-center">
                <input
                  v-model="carrierForm.is_active"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                />
                <label class="ml-2 text-sm text-gray-700">Active</label>
              </div>
              <div class="flex items-center">
                <input
                  v-model="carrierForm.is_default"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                />
                <label class="ml-2 text-sm text-gray-700">Default Carrier</label>
              </div>
              <div class="flex items-center">
                <input
                  v-model="carrierForm.supports_tracking"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                />
                <label class="ml-2 text-sm text-gray-700">Supports Tracking</label>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-3 pt-6 border-t">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
              >
                {{ editingCarrier ? 'Update Carrier' : 'Add Carrier' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { TruckIcon, XMarkIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const { get, post, put, delete: del, loading } = useApi()
const { showSuccess, showError } = useToast()

// Reactive data
const carriers = ref([])
const showAddModal = ref(false)
const editingCarrier = ref(null)
const testingCarrier = ref(null)

const carrierForm = reactive({
  name: '',
  code: '',
  api_url: '',
  api_key: '',
  username: '',
  password: '',
  is_active: true,
  is_default: false,
  supports_tracking: true,
  services: []
})

// Methods
const fetchCarriers = async () => {
  try {
    const data = await get('/api/v1/logistics/carriers')
    carriers.value = data.data
  } catch (err) {
    console.error('Failed to fetch carriers:', err)
    showError('Failed to load carriers')
  }
}

const editCarrier = (carrier) => {
  editingCarrier.value = carrier
  Object.assign(carrierForm, {
    name: carrier.name,
    code: carrier.code,
    api_url: carrier.api_url || '',
    api_key: carrier.api_key || '',
    username: carrier.username || '',
    password: '', // Don't pre-fill password
    is_active: carrier.is_active,
    is_default: carrier.is_default,
    supports_tracking: carrier.supports_tracking,
    services: [...(carrier.services || [])]
  })
}

const saveCarrier = async () => {
  try {
    if (editingCarrier.value) {
      await put(`/api/v1/logistics/carriers/${editingCarrier.value.id}`, carrierForm)
      showSuccess('Carrier updated successfully')
    } else {
      await post('/api/v1/logistics/carriers', carrierForm)
      showSuccess('Carrier added successfully')
    }
    
    closeModal()
    fetchCarriers()
  } catch (err) {
    console.error('Failed to save carrier:', err)
    showError('Failed to save carrier')
  }
}

const toggleCarrier = async (carrier) => {
  try {
    await put(`/api/v1/logistics/carriers/${carrier.id}`, {
      is_active: !carrier.is_active
    })
    
    carrier.is_active = !carrier.is_active
    showSuccess(`Carrier ${carrier.is_active ? 'enabled' : 'disabled'}`)
  } catch (err) {
    console.error('Failed to toggle carrier:', err)
    showError('Failed to update carrier status')
  }
}

const testConnection = async (carrier) => {
  testingCarrier.value = carrier.id
  
  try {
    const data = await post(`/api/v1/logistics/carriers/${carrier.id}/test`)
    
    if (data.success) {
      showSuccess('Connection test successful')
      carrier.api_status = 'connected'
    } else {
      showError('Connection test failed: ' + data.message)
      carrier.api_status = 'error'
    }
  } catch (err) {
    console.error('Connection test failed:', err)
    showError('Connection test failed')
    carrier.api_status = 'error'
  } finally {
    testingCarrier.value = null
  }
}

const addService = () => {
  carrierForm.services.push({
    name: '',
    code: '',
    max_weight: null
  })
}

const removeService = (index) => {
  carrierForm.services.splice(index, 1)
}

const closeModal = () => {
  showAddModal.value = false
  editingCarrier.value = null
  
  // Reset form
  Object.assign(carrierForm, {
    name: '',
    code: '',
    api_url: '',
    api_key: '',
    username: '',
    password: '',
    is_active: true,
    is_default: false,
    supports_tracking: true,
    services: []
  })
}

const getApiStatusColor = (status) => {
  const colors = {
    'connected': 'bg-green-500',
    'error': 'bg-red-500',
    'warning': 'bg-yellow-500'
  }
  return colors[status] || 'bg-gray-400'
}

const formatDate = (date) => {
  if (!date) return 'Never'
  return new Date(date).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  fetchCarriers()
})
</script>