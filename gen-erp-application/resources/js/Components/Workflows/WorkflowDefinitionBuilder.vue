<template>
  <div class="space-y-6">
    <!-- Workflow Info -->
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Workflow Name</label>
        <input
          v-model="localForm.name"
          type="text"
          class="w-full border rounded-lg px-3 py-2"
          placeholder="e.g., Purchase Order Approval"
          required
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
        <select
          v-model="localForm.document_type"
          class="w-full border rounded-lg px-3 py-2"
          required
        >
          <option value="">Select document type...</option>
          <option value="purchase_order">Purchase Order</option>
          <option value="sales_order">Sales Order</option>
          <option value="expense_claim">Expense Claim</option>
          <option value="invoice">Invoice</option>
          <option value="leave_request">Leave Request</option>
        </select>
      </div>

      <div class="flex items-center gap-4">
        <label class="flex items-center gap-2">
          <input
            v-model="localForm.is_active"
            type="checkbox"
            class="rounded"
          />
          <span class="text-sm text-gray-700">Active</span>
        </label>

        <label class="flex items-center gap-2">
          <input
            v-model="localForm.is_default"
            type="checkbox"
            class="rounded"
            :disabled="!localForm.is_active"
          />
          <span class="text-sm text-gray-700">Default Workflow</span>
        </label>
      </div>
    </div>

    <!-- Statuses Section -->
    <div class="border-t pt-4">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold text-black">Statuses</h3>
        <Button size="sm" variant="secondary" @click="addStatus">+ Add Status</Button>
      </div>

      <div v-if="localForm.statuses.length === 0" class="text-sm text-gray-1 py-4">
        No statuses defined. Add at least one status.
      </div>

      <div v-else class="space-y-2">
        <div
          v-for="(status, index) in localForm.statuses"
          :key="index"
          class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg"
        >
          <div class="flex-1 grid grid-cols-3 gap-2">
            <input
              v-model="status.key"
              type="text"
              class="border rounded px-2 py-1 text-sm"
              placeholder="Key (e.g., draft)"
              required
            />
            <input
              v-model="status.label"
              type="text"
              class="border rounded px-2 py-1 text-sm"
              placeholder="Label (e.g., Draft)"
              required
            />
            <select
              v-model="status.color"
              class="border rounded px-2 py-1 text-sm"
            >
              <option value="gray">Gray</option>
              <option value="blue">Blue</option>
              <option value="green">Green</option>
              <option value="yellow">Yellow</option>
              <option value="red">Red</option>
            </select>
          </div>

          <label class="flex items-center gap-1">
            <input
              v-model="status.is_initial"
              type="checkbox"
              class="rounded"
              :disabled="localForm.statuses.some((s, i) => i !== index && s.is_initial)"
            />
            <span class="text-xs">Initial</span>
          </label>

          <label class="flex items-center gap-1">
            <input
              v-model="status.is_terminal"
              type="checkbox"
              class="rounded"
            />
            <span class="text-xs">Terminal</span>
          </label>

          <Button
            variant="ghost"
            size="sm"
            @click="removeStatus(index)"
            class="text-red-600"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </Button>
        </div>
      </div>
    </div>

    <!-- Transitions Section -->
    <div class="border-t pt-4">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold text-black">Transitions</h3>
        <Button size="sm" variant="secondary" @click="addTransition">+ Add Transition</Button>
      </div>

      <div v-if="localForm.transitions.length === 0" class="text-sm text-gray-1 py-4">
        No transitions defined. Add transitions to connect statuses.
      </div>

      <div v-else class="space-y-2">
        <div
          v-for="(transition, index) in localForm.transitions"
          :key="index"
          class="p-3 bg-gray-50 rounded-lg"
        >
          <div class="grid grid-cols-2 gap-2 mb-2">
            <select
              v-model="transition.from_status_key"
              class="border rounded px-2 py-1 text-sm"
              required
            >
              <option value="">From Status</option>
              <option
                v-for="status in localForm.statuses"
                :key="status.key"
                :value="status.key"
              >
                {{ status.label }}
              </option>
            </select>

            <select
              v-model="transition.to_status_key"
              class="border rounded px-2 py-1 text-sm"
              required
            >
              <option value="">To Status</option>
              <option
                v-for="status in localForm.statuses"
                :key="status.key"
                :value="status.key"
              >
                {{ status.label }}
              </option>
            </select>
          </div>

          <input
            v-model="transition.label"
            type="text"
            class="border rounded px-2 py-1 text-sm mb-2 w-full"
            placeholder="Transition Label (e.g., Submit for Approval)"
            required
          />

          <div class="flex items-center gap-2 mb-2">
            <label class="flex items-center gap-1">
              <input
                v-model="transition.requires_approval"
                type="checkbox"
                class="rounded"
              />
              <span class="text-xs">Requires Approval</span>
            </label>

            <div v-if="transition.requires_approval" class="flex-1">
              <select
                v-model="transition.approver_roles"
                multiple
                class="border rounded px-2 py-1 text-sm w-full"
              >
                <option value="owner">Owner</option>
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="employee">Employee</option>
              </select>
            </div>
          </div>

          <div class="flex items-center justify-between">
            <Button
              variant="ghost"
              size="sm"
              @click="removeTransition(index)"
              class="text-red-600"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Validation Errors -->
    <div v-if="errors.length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <h4 class="font-semibold text-red-800 mb-2">Please fix the following errors:</h4>
      <ul class="list-disc list-inside text-sm text-red-700">
        <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import Button from '@/Components/ui/Button.vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      name: '',
      document_type: '',
      is_active: true,
      is_default: false,
      statuses: [],
      transitions: []
    })
  }
})

const emit = defineEmits(['update:modelValue'])

const localForm = ref({ ...props.modelValue })
const errors = ref([])

watch(() => props.modelValue, (newValue) => {
  localForm.value = { ...newValue }
}, { deep: true })

watch(localForm, (newValue) => {
  emit('update:modelValue', { ...newValue })
}, { deep: true })

const addStatus = () => {
  localForm.value.statuses.push({
    key: '',
    label: '',
    color: 'gray',
    is_initial: false,
    is_terminal: false
  })
}

const removeStatus = (index) => {
  localForm.value.statuses.splice(index, 1)
}

const addTransition = () => {
  localForm.value.transitions.push({
    from_status_key: '',
    to_status_key: '',
    label: '',
    requires_approval: false,
    approver_roles: []
  })
}

const removeTransition = (index) => {
  localForm.value.transitions.splice(index, 1)
}

const validate = () => {
  errors.value = []

  if (!localForm.value.name) {
    errors.value.push('Workflow name is required')
  }

  if (!localForm.value.document_type) {
    errors.value.push('Document type is required')
  }

  if (localForm.value.statuses.length === 0) {
    errors.value.push('At least one status is required')
  }

  if (!localForm.value.statuses.some(s => s.is_initial)) {
    errors.value.push('One status must be marked as initial')
  }

  if (localForm.value.statuses.some(s => !s.key || !s.label)) {
    errors.value.push('All statuses must have a key and label')
  }

  if (localForm.value.transitions.some(t => !t.label || !t.from_status_key || !t.to_status_key)) {
    errors.value.push('All transitions must have a label, from status, and to status')
  }

  return errors.value.length === 0
}

defineExpose({
  validate
})
</script>
