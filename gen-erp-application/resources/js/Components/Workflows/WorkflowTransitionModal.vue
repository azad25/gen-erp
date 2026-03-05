<template>
  <Modal :show="show" @close="$emit('close')">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <div class="p-6 border-b">
        <h2 class="text-xl font-bold text-black">Execute Transition</h2>
      </div>
      <div class="p-6">
        <p class="text-sm text-gray-1 mb-4">
          Are you sure you want to execute "{{ transition?.label }}"?
        </p>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
          <textarea
            v-model="notes"
            rows="3"
            class="w-full border rounded-lg px-3 py-2"
            placeholder="Add any notes about this action..."
          ></textarea>
        </div>
      </div>
      <div class="p-6 border-t flex justify-end gap-2">
        <Button variant="secondary" @click="$emit('close')">Cancel</Button>
        <Button @click="handleConfirm" :disabled="processing">
          {{ processing ? 'Processing...' : 'Confirm' }}
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, watch } from 'vue'
import Modal from '@/Components/UI/Modal.vue'
import Button from '@/Components/ui/Button.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  transition: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'confirm'])

const notes = ref('')
const processing = ref(false)

watch(() => props.show, (newVal) => {
  if (newVal) {
    notes.value = ''
  }
})

const handleConfirm = () => {
  processing.value = true
  emit('confirm', {
    transition: props.transition,
    notes: notes.value
  })
  // Reset processing after a delay
  setTimeout(() => {
    processing.value = false
  }, 1000)
}
</script>
