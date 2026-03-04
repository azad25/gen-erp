<template>
  <Modal :show="show" @close="$emit('close')" max-width="2xl">
    <div class="p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">
        Add Task to Board
      </h3>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Title</label>
          <input
            v-model="form.title"
            type="text"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            placeholder="Short task title"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            placeholder="Optional task details"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Priority</label>
            <select
              v-model="form.priority"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            >
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Due Date</label>
            <input
              v-model="form.due_date"
              type="date"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            />
          </div>
        </div>

        <p class="text-xs text-gray-500">
          This lightweight form only updates the board UI. Hook it up to your tasks API when ready.
        </p>

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
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700"
          >
            Create Task
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { reactive, watch } from 'vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  projectId: { type: [Number, String], default: null },
  columnId: { type: [Number, String], default: null },
})

const emit = defineEmits(['close', 'created'])

const form = reactive({
  title: '',
  description: '',
  priority: 'medium',
  due_date: '',
})

watch(
  () => props.show,
  (show) => {
    if (show) {
      form.title = ''
      form.description = ''
      form.priority = 'medium'
      form.due_date = ''
    }
  },
)

const handleSubmit = () => {
  emit('created', {
    ...form,
    project_id: props.projectId,
    board_column_id: props.columnId,
  })
}
</script>

