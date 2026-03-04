<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Board Settings</h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 focus:outline-none"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <!-- Content -->
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Board Name
            </label>
            <input
              v-model="boardName"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Description
            </label>
            <textarea
              v-model="boardDescription"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            ></textarea>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 mt-6">
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="saveSettings"
            class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark"
          >
            Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  board: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'save'])

const boardName = ref('')
const boardDescription = ref('')

const saveSettings = () => {
  emit('save', {
    name: boardName.value,
    description: boardDescription.value
  })
}

watch(() => props.board, (newBoard) => {
  if (newBoard) {
    boardName.value = newBoard.name || ''
    boardDescription.value = newBoard.description || ''
  }
}, { immediate: true })
</script>