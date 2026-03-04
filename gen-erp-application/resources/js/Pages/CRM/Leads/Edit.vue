<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Edit Lead</h1>
          <p class="mt-1 text-sm text-gray-600">
            Update lead information and details
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="$router.go(-1)"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loadingLead" class="bg-white shadow rounded-lg p-6">
      <div class="animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
        <div class="space-y-3">
          <div class="h-4 bg-gray-200 rounded"></div>
          <div class="h-4 bg-gray-200 rounded w-5/6"></div>
          <div class="h-4 bg-gray-200 rounded w-4/6"></div>
        </div>
      </div>
    </div>

    <!-- Form -->
    <div v-else-if="lead" class="bg-white shadow rounded-lg p-6">
      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Basic Information -->
        <div>
          <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700">Name *</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Enter lead name"
              />
              <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Email *</label>
              <input
                v-model="form.email"
                type="email"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Enter email address"
              />
              <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Phone</label>
              <input
                v-model="form.phone"
                type="tel"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Enter phone number"
              />
              <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone[0] }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Job Title</label>
              <input
                v-model="form.job_title"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Enter job title"
              />
            </div>
          </div>
        </div>

        <!-- Company Information -->
        <div>
          <h3 class="text-lg font-medium text-gray-900 mb-4">Company Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700">Company Name</label>
              <input
                v-model="form.company_name"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Enter company name"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Industry</label>
              <input
                v-model="form.industry"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Enter industry"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Company Size</label>
              <select
                v-model="form.company_size"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="">Select company size</option>
                <option value="1-10">1-10 employees</option>
                <option value="11-50">11-50 employees</option>
                <option value="51-200">51-200 employees</option>
                <option value="201-500">201-500 employees</option>
                <option value="501-1000">501-1000 employees</option>
                <option value="1000+">1000+ employees</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Website</label>
              <input
                v-model="form.website"
                type="url"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="https://example.com"
              />
            </div>
          </div>
        </div>

        <!-- Lead Details -->
        <div>
          <h3 class="text-lg font-medium text-gray-900 mb-4">Lead Details</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700">Source *</label>
              <select
                v-model="form.source"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="">Select source</option>
                <option value="website">Website</option>
                <option value="social_media">Social Media</option>
                <option value="referral">Referral</option>
                <option value="advertisement">Advertisement</option>
                <option value="cold_call">Cold Call</option>
                <option value="trade_show">Trade Show</option>
                <option value="email_campaign">Email Campaign</option>
                <option value="other">Other</option>
              </select>
              <p v-if="errors.source" class="mt-1 text-sm text-red-600">{{ errors.source[0] }}</p>
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

            <div>
              <label class="block text-sm font-medium text-gray-700">Expected Value (BDT)</label>
              <input
                v-model.number="form.expected_value"
                type="number"
                min="0"
                step="0.01"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="0.00"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Lead Score</label>
              <input
                v-model.number="form.score"
                type="number"
                min="0"
                max="100"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="0-100"
              />
            </div>
          </div>
        </div>

        <!-- Address -->
        <div>
          <h3 class="text-lg font-medium text-gray-900 mb-4">Address</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Street Address</label>
              <input
                v-model="form.address"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Enter street address"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">City</label>
              <input
                v-model="form.city"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Enter city"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">State/Province</label>
              <input
                v-model="form.state"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Enter state/province"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Postal Code</label>
              <input
                v-model="form.postal_code"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Enter postal code"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Country</label>
              <select
                v-model="form.country"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="">Select country</option>
                <option value="BD">Bangladesh</option>
                <option value="IN">India</option>
                <option value="US">United States</option>
                <option value="GB">United Kingdom</option>
                <option value="CA">Canada</option>
                <option value="AU">Australia</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Information</h3>
          <div>
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea
              v-model="form.notes"
              rows="4"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              placeholder="Enter any additional notes about this lead..."
            ></textarea>
          </div>
        </div>

        <!-- Tags -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
          <div class="flex flex-wrap gap-2 mb-2">
            <span
              v-for="tag in form.tags"
              :key="tag"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
            >
              {{ tag }}
              <button
                type="button"
                @click="removeTag(tag)"
                class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full text-indigo-400 hover:bg-indigo-200 hover:text-indigo-500"
              >
                ×
              </button>
            </span>
          </div>
          <div class="flex">
            <input
              v-model="newTag"
              @keydown.enter.prevent="addTag"
              type="text"
              class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              placeholder="Add a tag and press Enter"
            />
            <button
              type="button"
              @click="addTag"
              class="px-4 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Add
            </button>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
          <button
            type="button"
            @click="$router.go(-1)"
            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="bg-indigo-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
          >
            <span v-if="loading">Updating...</span>
            <span v-else>Update Lead</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Error State -->
    <div v-else class="bg-white shadow rounded-lg p-6">
      <div class="text-center">
        <p class="text-gray-500">Lead not found or failed to load.</p>
        <button
          @click="$router.go(-1)"
          class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
        >
          Go Back
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from '@/Composables/useToast'

const router = useRouter()
const route = useRoute()
const { showToast } = useToast()

// Reactive data
const lead = ref(null)
const users = ref([])
const loading = ref(false)
const loadingLead = ref(true)
const newTag = ref('')
const errors = ref({})

const form = reactive({
  name: '',
  email: '',
  phone: '',
  job_title: '',
  company_name: '',
  industry: '',
  company_size: '',
  website: '',
  source: '',
  status: '',
  assigned_to: '',
  expected_value: null,
  score: 0,
  address: '',
  city: '',
  state: '',
  postal_code: '',
  country: '',
  notes: '',
  tags: []
})

// Methods
const fetchLead = async () => {
  try {
    const response = await fetch(`/api/v1/crm/leads/${route.params.uuid}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      lead.value = data.data
      
      // Populate form
      Object.keys(form).forEach(key => {
        if (lead.value[key] !== undefined) {
          form[key] = lead.value[key]
        }
      })
      
      // Handle tags
      if (lead.value.tags && Array.isArray(lead.value.tags)) {
        form.tags = lead.value.tags.map(tag => tag.name || tag)
      }
    } else {
      showToast('Failed to load lead', 'error')
    }
  } catch (error) {
    console.error('Failed to fetch lead:', error)
    showToast('Failed to load lead', 'error')
  } finally {
    loadingLead.value = false
  }
}

const fetchUsers = async () => {
  try {
    const response = await fetch('/api/v1/users', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      users.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch users:', error)
  }
}

const addTag = () => {
  if (newTag.value.trim() && !form.tags.includes(newTag.value.trim())) {
    form.tags.push(newTag.value.trim())
    newTag.value = ''
  }
}

const removeTag = (tag) => {
  const index = form.tags.indexOf(tag)
  if (index > -1) {
    form.tags.splice(index, 1)
  }
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}
  
  try {
    const response = await fetch(`/api/v1/crm/leads/${route.params.uuid}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(form)
    })
    
    const data = await response.json()
    
    if (response.ok) {
      showToast('Lead updated successfully', 'success')
      router.push('/crm/leads')
    } else {
      if (data.errors) {
        errors.value = data.errors
      } else {
        showToast(data.message || 'Failed to update lead', 'error')
      }
    }
  } catch (error) {
    console.error('Failed to update lead:', error)
    showToast('Failed to update lead', 'error')
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchLead()
  fetchUsers()
})
</script>