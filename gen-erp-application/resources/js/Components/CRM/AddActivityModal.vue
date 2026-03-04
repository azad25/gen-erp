<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Add Activity</h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submitActivity">
        <!-- Activity Type -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Activity Type</label>
          <select
            v-model="form.type"
            required
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">Select Type</option>
            <option value="call">Phone Call</option>
            <option value="email">Email</option>
            <option value="meeting">Meeting</option>
            <option value="note">Note</option>
            <option value="task">Task</option>
          </select>
        </div>

        <!-- Title -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
          <input
            v-model="form.title"
            type="text"
            required
            placeholder="Activity title"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>

        <!-- Description -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Activity details..."
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          ></textarea>
        </div>

        <!-- Date & Time -->
        <div class="mb-4 grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
            <input
              v-model="form.date"
              type="date"
              required
              class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Time</label>
            <input
              v-model="form.time"
              type="time"
              class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
        </div>

        <!-- Status -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <select
            v-model="form.status"
            required
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="completed">Completed</option>
            <option value="pending">Pending</option>
            <option value="scheduled">Scheduled</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <!-- Additional Fields based on Type -->
        <div v-if="form.type === 'call'" class="mb-4 space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes)</label>
            <input
              v-model="form.duration"
              type="number"
              min="1"
              placeholder="30"
              class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Outcome</label>
            <select
              v-model="form.outcome"
              class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">Select Outcome</option>
              <option value="interested">Interested</option>
              <option value="not_interested">Not Interested</option>
              <option value="callback_requested">Callback Requested</option>
              <option value="meeting_scheduled">Meeting Scheduled</option>
              <option value="no_answer">No Answer</option>
            </select>
          </div>
        </div>

        <div v-if="form.type === 'meeting'" class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes)</label>
          <input
            v-model="form.duration"
            type="number"
            min="1"
            placeholder="60"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>

        <!-- Next Action -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Next Action</label>
          <input
            v-model="form.next_action"
            type="text"
            placeholder="Follow up in 3 days..."
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="loading || !isFormValid"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 rounded-md"
          >
            {{ loading ? 'Saving...' : 'Save Activity' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'

const props = defineProps({
  leadId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['close', 'saved'])

const { post, loading } = useApi()

// Form data
const form = ref({
  type: '',
  title: '',
  description: '',
  date: new Date().toISOString().split('T')[0],
  time: '',
  status: 'completed',
  duration: null,
  outcome: '',
  next_action: ''
})

// Computed properties
const isFormValid = computed(() => {
  return form.value.type && form.value.title && form.value.date && form.value.status
})

// Methods
const submitActivity = async () => {
  if (!isFormValid.value) return
  
  try {
    const payload = {
      type: form.value.type,
      title: form.value.title,
      description: form.value.description,
      date: form.value.date,
      time: form.value.time,
      status: form.value.status,
      metadata: {}
    }
    
    // Add type-specific metadata
    if (form.value.duration) {
      payload.metadata.duration = form.value.duration
    }
    if (form.value.outcome) {
      payload.metadata.outcome = form.value.outcome
    }
    if (form.value.next_action) {
      payload.metadata.next_action = form.value.next_action
    }
    
    const data = await post(`/api/v1/crm/leads/${props.leadId}/activities`, payload)
    emit('saved', data.data)
  } catch (err) {
    console.error('Failed to save activity:', err)
  }
}
</script>