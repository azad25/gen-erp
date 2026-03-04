<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b">
          <h3 class="text-lg font-medium text-gray-900">{{ getTitle() }}</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Content -->
        <div class="mt-6">
          <p class="text-sm text-gray-600 mb-6">
            {{ getDescription() }}
          </p>

          <!-- Assign Action -->
          <div v-if="action === 'assign'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Assign to User</label>
              <select
                v-model="selectedUser"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="">Select User</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Status Action -->
          <div v-if="action === 'status'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Update Status</label>
              <select
                v-model="selectedStatus"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="">Select Status</option>
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="qualified">Qualified</option>
                <option value="unqualified">Unqualified</option>
                <option value="converted">Converted</option>
              </select>
            </div>
          </div>

          <!-- Delete Action -->
          <div v-if="action === 'delete'" class="space-y-4">
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-red-800">Warning</h3>
                  <div class="mt-2 text-sm text-red-700">
                    <p>This action cannot be undone. All selected leads will be permanently deleted.</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div>
              <label class="flex items-center">
                <input
                  v-model="confirmDelete"
                  type="checkbox"
                  class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-500 focus:ring-red-500"
                />
                <span class="ml-2 text-sm text-gray-700">
                  I understand that this action cannot be undone
                </span>
              </label>
            </div>
          </div>

          <!-- Selected Leads Summary -->
          <div class="mt-6 bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Selected Leads ({{ selectedLeads.length }})</h4>
            <div class="max-h-32 overflow-y-auto">
              <div class="space-y-1">
                <div v-for="leadId in selectedLeads.slice(0, 5)" :key="leadId" class="text-sm text-gray-600">
                  Lead ID: {{ leadId }}
                </div>
                <div v-if="selectedLeads.length > 5" class="text-sm text-gray-500">
                  ... and {{ selectedLeads.length - 5 }} more
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-6 border-t mt-6">
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="executeAction"
            :disabled="!canExecute || loading"
            :class="[
              'px-4 py-2 text-sm font-medium rounded-md',
              action === 'delete'
                ? 'text-white bg-red-600 hover:bg-red-700 disabled:opacity-50'
                : 'text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50'
            ]"
          >
            <span v-if="loading">Processing...</span>
            <span v-else>{{ getActionButtonText() }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  action: {
    type: String,
    required: true,
    validator: (value) => ['assign', 'status', 'delete'].includes(value)
  },
  selectedLeads: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['close', 'completed'])

const { post, get, loading } = useApi()
const { showToast } = useToast()

const users = ref([])
const selectedUser = ref('')
const selectedStatus = ref('')
const confirmDelete = ref(false)

const canExecute = computed(() => {
  if (props.action === 'assign') {
    return selectedUser.value !== ''
  }
  if (props.action === 'status') {
    return selectedStatus.value !== ''
  }
  if (props.action === 'delete') {
    return confirmDelete.value
  }
  return false
})

const fetchUsers = async () => {
  try {
    const data = await get('/api/v1/users')
    users.value = data.data
  } catch (err) {
    console.error('Failed to fetch users:', err)
  }
}

const getTitle = () => {
  const titles = {
    assign: 'Assign Leads',
    status: 'Update Lead Status',
    delete: 'Delete Leads'
  }
  return titles[props.action]
}

const getDescription = () => {
  const descriptions = {
    assign: `Assign ${props.selectedLeads.length} selected leads to a user.`,
    status: `Update the status of ${props.selectedLeads.length} selected leads.`,
    delete: `Delete ${props.selectedLeads.length} selected leads permanently.`
  }
  return descriptions[props.action]
}

const getActionButtonText = () => {
  const texts = {
    assign: 'Assign Leads',
    status: 'Update Status',
    delete: 'Delete Leads'
  }
  return texts[props.action]
}

const executeAction = async () => {
  try {
    let payload = {
      lead_ids: props.selectedLeads
    }

    let endpoint = ''
    
    if (props.action === 'assign') {
      payload.assigned_to = selectedUser.value
      endpoint = '/api/v1/crm/leads/bulk-assign'
    } else if (props.action === 'status') {
      payload.status = selectedStatus.value
      endpoint = '/api/v1/crm/leads/bulk-update-status'
    } else if (props.action === 'delete') {
      endpoint = '/api/v1/crm/leads/bulk-delete'
    }

    await post(endpoint, payload)
    
    const actionMessages = {
      assign: 'Leads assigned successfully',
      status: 'Lead status updated successfully',
      delete: 'Leads deleted successfully'
    }
    
    showToast(actionMessages[props.action], 'success')
    emit('completed')
  } catch (err) {
    console.error(`Failed to ${props.action} leads:`, err)
    showToast(err.message || `Failed to ${props.action} leads`, 'error')
  }
}

onMounted(() => {
  if (props.action === 'assign') {
    fetchUsers()
  }
})
</script>