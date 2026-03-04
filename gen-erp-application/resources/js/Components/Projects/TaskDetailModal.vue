<template>
  <Modal :show="show" @close="$emit('close')" max-width="2xl">
    <div class="p-6">
      <div class="flex items-start justify-between mb-4">
        <div>
          <p class="text-xs text-gray-500 mb-1" v-if="task">Task #{{ task.task_number }}</p>
          <h3 class="text-lg font-semibold text-gray-900">
            {{ task?.title || 'Task Details' }}
          </h3>
          <p class="mt-1 text-sm text-gray-500" v-if="task?.project">
            Project: {{ task.project.name }}
          </p>
        </div>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600"
          @click="$emit('close')"
        >
          <span class="sr-only">Close</span>
          <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
            <path
              fill-rule="evenodd"
              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
        </button>
      </div>

      <div v-if="task" class="space-y-4">
        <div v-if="task.description">
          <h4 class="text-sm font-medium text-gray-700 mb-1">Description</h4>
          <p class="text-sm text-gray-700 whitespace-pre-wrap">
            {{ task.description }}
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-gray-500">Priority</p>
            <p class="font-medium capitalize">{{ task.priority || 'medium' }}</p>
          </div>
          <div>
            <p class="text-gray-500">Status</p>
            <p class="font-medium capitalize">{{ task.status || 'open' }}</p>
          </div>
          <div>
            <p class="text-gray-500">Due Date</p>
            <p class="font-medium">
              {{ task.due_date ? formatDate(task.due_date) : 'Not set' }}
            </p>
          </div>
        </div>

        <div v-if="task.assignee" class="pt-2 border-t border-gray-100">
          <h4 class="text-sm font-medium text-gray-700 mb-1">Assignee</h4>
          <div class="flex items-center space-x-2 text-sm text-gray-700">
            <div
              class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-xs font-medium"
            >
              {{ task.assignee.name.charAt(0) }}
            </div>
            <span>{{ task.assignee.name }}</span>
          </div>
        </div>
      </div>

      <div v-else class="py-6 text-sm text-gray-500">
        Task data is not available.
      </div>

      <div class="mt-6 flex justify-end space-x-3">
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          @click="$emit('close')"
        >
          Close
        </button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  task: { type: Object, default: null },
})

defineEmits(['close', 'updated'])

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}
</script>

