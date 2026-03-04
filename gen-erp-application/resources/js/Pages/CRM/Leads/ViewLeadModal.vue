<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b">
          <div class="flex items-center space-x-4">
            <div class="flex-shrink-0 h-12 w-12">
              <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center">
                <span class="text-lg font-medium text-indigo-700">
                  {{ getInitials(lead.name) }}
                </span>
              </div>
            </div>
            <div>
              <h3 class="text-lg font-medium text-gray-900">{{ lead.name }}</h3>
              <p class="text-sm text-gray-500">{{ lead.email }}</p>
            </div>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Content -->
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Main Information -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Contact Information -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-md font-medium text-gray-900 mb-4">Contact Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-500">Email</label>
                  <p class="mt-1 text-sm text-gray-900">{{ lead.email || 'Not provided' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Phone</label>
                  <p class="mt-1 text-sm text-gray-900">{{ lead.phone || 'Not provided' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Company</label>
                  <p class="mt-1 text-sm text-gray-900">{{ lead.company_name || 'Not provided' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Job Title</label>
                  <p class="mt-1 text-sm text-gray-900">{{ lead.job_title || 'Not provided' }}</p>
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-500">Website</label>
                  <p class="mt-1 text-sm text-gray-900">
                    <a v-if="lead.website" :href="lead.website" target="_blank" class="text-indigo-600 hover:text-indigo-500">
                      {{ lead.website }}
                    </a>
                    <span v-else>Not provided</span>
                  </p>
                </div>
              </div>
            </div>

            <!-- Lead Details -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-md font-medium text-gray-900 mb-4">Lead Details</h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-500">Source</label>
                  <p class="mt-1 text-sm text-gray-900">{{ formatSource(lead.source) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Status</label>
                  <span :class="getStatusBadgeClass(lead.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                    {{ formatStatus(lead.status) }}
                  </span>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Assigned To</label>
                  <p class="mt-1 text-sm text-gray-900">{{ lead.assigned_user?.name || 'Unassigned' }}</p>
                </div>
              </div>
            </div>

            <!-- Score -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-md font-medium text-gray-900 mb-4">Lead Score</h4>
              <div class="flex items-center space-x-4">
                <div class="flex-1 bg-gray-200 rounded-full h-4">
                  <div
                    :class="getScoreBarClass(lead.score)"
                    class="h-4 rounded-full transition-all duration-300"
                    :style="{ width: `${lead.score}%` }"
                  ></div>
                </div>
                <span class="text-lg font-medium text-gray-900">{{ lead.score }}/100</span>
              </div>
              <p class="mt-2 text-sm text-gray-500">
                {{ getScoreDescription(lead.score) }}
              </p>
            </div>

            <!-- Notes -->
            <div v-if="lead.notes" class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-md font-medium text-gray-900 mb-4">Notes</h4>
              <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ lead.notes }}</p>
            </div>

            <!-- Tags -->
            <div v-if="lead.tags && lead.tags.length > 0" class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-md font-medium text-gray-900 mb-4">Tags</h4>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="tag in lead.tags"
                  :key="tag"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
                >
                  {{ tag }}
                </span>
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white border rounded-lg p-4">
              <h4 class="text-md font-medium text-gray-900 mb-4">Quick Actions</h4>
              <div class="space-y-2">
                <button
                  @click="editLead"
                  class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md"
                >
                  Edit Lead
                </button>
                <button
                  v-if="lead.status !== 'converted'"
                  @click="convertLead"
                  class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md"
                >
                  Convert to Customer
                </button>
                <button
                  @click="sendEmail"
                  class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md"
                >
                  Send Email
                </button>
                <button
                  @click="addNote"
                  class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md"
                >
                  Add Note
                </button>
              </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white border rounded-lg p-4">
              <h4 class="text-md font-medium text-gray-900 mb-4">Timeline</h4>
              <div class="space-y-3">
                <div class="flex items-start space-x-3">
                  <div class="flex-shrink-0 w-2 h-2 bg-green-400 rounded-full mt-2"></div>
                  <div>
                    <p class="text-sm text-gray-900">Lead created</p>
                    <p class="text-xs text-gray-500">{{ formatDate(lead.created_at) }}</p>
                  </div>
                </div>
                <div v-if="lead.last_activity_at" class="flex items-start space-x-3">
                  <div class="flex-shrink-0 w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
                  <div>
                    <p class="text-sm text-gray-900">Last activity</p>
                    <p class="text-xs text-gray-500">{{ formatDate(lead.last_activity_at) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Lead Information -->
            <div class="bg-white border rounded-lg p-4">
              <h4 class="text-md font-medium text-gray-900 mb-4">Lead Information</h4>
              <div class="space-y-3">
                <div>
                  <label class="block text-xs font-medium text-gray-500">Lead ID</label>
                  <p class="text-sm text-gray-900">{{ lead.uuid }}</p>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500">Created</label>
                  <p class="text-sm text-gray-900">{{ formatDate(lead.created_at) }}</p>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500">Last Updated</label>
                  <p class="text-sm text-gray-900">{{ formatDate(lead.updated_at) }}</p>
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
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import { useApi } from '@/Composables/useApi'

const props = defineProps({
  lead: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'edit', 'convert'])

const { post } = useApi()
const { showToast } = useToast()

// Utility functions
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getStatusBadgeClass = (status) => {
  const classes = {
    new: 'bg-blue-100 text-blue-800',
    contacted: 'bg-yellow-100 text-yellow-800',
    qualified: 'bg-green-100 text-green-800',
    unqualified: 'bg-red-100 text-red-800',
    converted: 'bg-purple-100 text-purple-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getScoreBarClass = (score) => {
  if (score >= 80) return 'bg-green-500'
  if (score >= 50) return 'bg-yellow-500'
  return 'bg-red-500'
}

const getScoreDescription = (score) => {
  if (score >= 80) return 'Hot lead - High conversion potential'
  if (score >= 50) return 'Warm lead - Good conversion potential'
  return 'Cold lead - Needs nurturing'
}

const formatStatus = (status) => {
  return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatSource = (source) => {
  return source.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (date) => {
  if (!date) return 'Unknown'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Actions
const editLead = () => {
  emit('edit', props.lead)
}

const convertLead = async () => {
  if (!confirm('Are you sure you want to convert this lead to a customer?')) return
  
  try {
    await post(`/api/v1/crm/leads/${props.lead.uuid}/convert`)
    showToast('Lead converted successfully', 'success')
    emit('convert', props.lead)
  } catch (err) {
    console.error('Failed to convert lead:', err)
    showToast(err.message || 'Failed to convert lead', 'error')
  }
}

const sendEmail = () => {
  // TODO: Implement email functionality
  showToast('Email functionality coming soon', 'info')
}

const addNote = () => {
  // TODO: Implement add note functionality
  showToast('Add note functionality coming soon', 'info')
}
</script>