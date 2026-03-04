<template>
  <Modal :show="show" @close="$emit('close')" max-width="2xl">
    <div class="p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Log Time</h3>

      <div v-if="task" class="bg-gray-50 p-4 rounded-lg mb-4">
        <h4 class="font-medium text-gray-900">
          Task #{{ task.task_number }}: {{ task.title }}
        </h4>
        <p class="text-sm text-gray-600" v-if="task.project">
          {{ task.project.name }}
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Date</label>
          <input
            v-model="form.entry_date"
            type="date"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Hours</label>
            <input
              v-model.number="form.hours"
              type="number"
              step="0.25"
              min="0"
              max="24"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Entry Type</label>
            <select
              v-model="form.entry_type"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="work">Work</option>
              <option value="meeting">Meeting</option>
              <option value="training">Training</option>
              <option value="research">Research</option>
              <option value="documentation">Documentation</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="What did you work on?"
          />
        </div>

        <div class="flex items-center">
          <input
            v-model="form.is_billable"
            type="checkbox"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
          />
          <label class="ml-2 block text-sm text-gray-900">
            Billable hours
          </label>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <button
            type="button"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            @click="$emit('close')"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="processing"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
          >
            {{ processing ? 'Saving...' : 'Log Time' }}
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { ref, watch } from 'vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  task: { type: Object, default: null },
})

const emit = defineEmits(['close', 'logged'])

const processing = ref(false)

const form = ref({
  entry_date: new Date().toISOString().split('T')[0],
  hours: 1,
  description: '',
  entry_type: 'work',
  is_billable: true,
})

watch(
  () => props.show,
  (show) => {
    if (show) {
      form.value = {
        entry_date: new Date().toISOString().split('T')[0],
        hours: 1,
        description: '',
        entry_type: 'work',
        is_billable: true,
      }
    }
  },
)

const submit = async () => {
  if (!props.task) return

  processing.value = true

  try {
    const response = await fetch('/api/v1/time-entries', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        task_id: props.task.id ?? props.task.task_id ?? null,
        hours: form.value.hours,
        description: form.value.description,
        entry_date: form.value.entry_date,
        entry_type: form.value.entry_type,
        is_billable: form.value.is_billable,
      }),
    })

    if (response.ok) {
      emit('logged')
    }
  } catch (error) {
    console.error('Failed to log time from modal:', error)
  } finally {
    processing.value = false
  }
}
</script>

