<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b">
          <h3 class="text-lg font-medium text-gray-900">Edit Lead</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="mt-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Personal Information -->
            <div class="space-y-4">
              <h4 class="text-md font-medium text-gray-900">Personal Information</h4>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Email *</label>
                <input
                  v-model="form.email"
                  type="email"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <input
                  v-model="form.phone"
                  type="tel"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>

            <!-- Company Information -->
            <div class="space-y-4">
              <h4 class="text-md font-medium text-gray-900">Company Information</h4>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Company Name</label>
                <input
                  v-model="form.company_name"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Job Title</label>
                <input
                  v-model="form.job_title"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Website</label>
                <input
                  v-model="form.website"
                  type="url"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>

          <!-- Lead Details -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700">Source *</label>
              <select
                v-model="form.source"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="">Select Source</option>
                <option value="website">Website</option>
                <option value="social_media">Social Media</option>
                <option value="referral">Referral</option>
                <option value="advertisement">Advertisement</option>
                <option value="cold_call">Cold Call</option>
                <option value="trade_show">Trade Show</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Status</label>
              <select
                v-model="form.status"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="qualified">Qualified</option>
                <option value="unqualified">Unqualified</option>
                <option value="converted">Converted</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Score</label>
              <input
                v-model.number="form.score"
                type="number"
                min="0"
                max="100"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Assign To</label>
              <select
                v-model="form.assigned_to"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="">Unassigned</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea
              v-model="form.notes"
              rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              placeholder="Additional notes about this lead..."
            ></textarea>
          </div>

          <!-- Tags -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Tags</label>
            <input
              v-model="tagsString"
              type="text"
              placeholder="Enter tags separated by commas"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p class="mt-1 text-sm text-gray-500">Separate multiple tags with commas</p>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end space-x-3 pt-6 border-t">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
            >
              <span v-if="loading">Updating...</span>
              <span v-else>Update Lead</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  lead: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const { put, get, loading, error } = useApi()
const { showToast } = useToast()

const users = ref([])

const form = ref({
  name: '',
  email: '',
  phone: '',
  company_name: '',
  job_title: '',
  website: '',
  source: '',
  status: 'new',
  score: 0,
  assigned_to: '',
  notes: '',
  tags: []
})

const tagsString = computed({
  get: () => form.value.tags ? form.value.tags.join(', ') : '',
  set: (value) => {
    form.value.tags = value ? value.split(',').map(tag => tag.trim()) : []
  }
})

const fetchUsers = async () => {
  try {
    const data = await get('/api/v1/users')
    users.value = data.data
  } catch (err) {
    console.error('Failed to fetch users:', err)
  }
}

const initializeForm = () => {
  form.value = {
    name: props.lead.name || '',
    email: props.lead.email || '',
    phone: props.lead.phone || '',
    company_name: props.lead.company_name || '',
    job_title: props.lead.job_title || '',
    website: props.lead.website || '',
    source: props.lead.source || '',
    status: props.lead.status || 'new',
    score: props.lead.score || 0,
    assigned_to: props.lead.assigned_to || '',
    notes: props.lead.notes || '',
    tags: props.lead.tags || []
  }
}

const submitForm = async () => {
  try {
    const payload = {
      ...form.value,
      tags: form.value.tags
    }
    
    await put(`/api/v1/crm/leads/${props.lead.uuid}`, payload)
    showToast('Lead updated successfully', 'success')
    emit('updated')
  } catch (err) {
    console.error('Failed to update lead:', err)
    showToast(err.message || 'Failed to update lead', 'error')
  }
}

onMounted(() => {
  fetchUsers()
  initializeForm()
})
</script>