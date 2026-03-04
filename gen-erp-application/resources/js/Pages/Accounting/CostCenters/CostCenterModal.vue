<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">
            {{ isEdit ? 'Edit Cost Center' : 'Create Cost Center' }}
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-4">
          <!-- Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Code <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.code"
              type="text"
              required
              :disabled="isEdit"
              placeholder="e.g., CC001"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
              :class="{ 'border-red-500': errors.code }"
            />
            <p v-if="errors.code" class="text-red-500 text-xs mt-1">{{ errors.code[0] }}</p>
          </div>

          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Name <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="e.g., Marketing Department"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': errors.name }"
            />
            <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Description
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Optional description..."
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': errors.description }"
            />
            <p v-if="errors.description" class="text-red-500 text-xs mt-1">{{ errors.description[0] }}</p>
          </div>

          <!-- Manager -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Manager
            </label>
            <select
              v-model="form.manager_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': errors.manager_id }"
            >
              <option value="">Select Manager</option>
              <option
                v-for="employee in employees"
                :key="employee.id"
                :value="employee.id"
              >
                {{ employee.name }}
              </option>
            </select>
            <p v-if="errors.manager_id" class="text-red-500 text-xs mt-1">{{ errors.manager_id[0] }}</p>
          </div>

          <!-- Budget -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Annual Budget
            </label>
            <div class="relative">
              <span class="absolute left-3 top-2 text-gray-500">$</span>
              <input
                v-model="form.budget"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :class="{ 'border-red-500': errors.budget }"
              />
            </div>
            <p v-if="errors.budget" class="text-red-500 text-xs mt-1">{{ errors.budget[0] }}</p>
          </div>

          <!-- Status -->
          <div>
            <label class="flex items-center">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
              />
              <span class="ml-2 text-sm text-gray-700">Active</span>
            </label>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-50"
            >
              {{ loading ? 'Saving...' : (isEdit ? 'Update' : 'Create') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
  costCenter: {
    type: Object,
    default: null
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'saved'])

// Data
const loading = ref(false)
const employees = ref([])
const errors = ref({})

const form = reactive({
  code: '',
  name: '',
  description: '',
  manager_id: '',
  budget: '',
  is_active: true
})

// Methods
const loadEmployees = async () => {
  try {
    const response = await axios.get('/api/v1/employees?per_page=100')
    employees.value = response.data.data || []
  } catch (error) {
    console.error('Error loading employees:', error)
  }
}

const submit = async () => {
  loading.value = true
  errors.value = {}

  try {
    const data = {
      code: form.code,
      name: form.name,
      description: form.description || null,
      manager_id: form.manager_id || null,
      budget: form.budget ? parseFloat(form.budget) : null,
      is_active: form.is_active
    }

    if (props.isEdit) {
      await axios.put(`/api/v1/cost-centers/${props.costCenter.id}`, data)
    } else {
      await axios.post('/api/v1/cost-centers', data)
    }

    emit('saved')
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      console.error('Error saving cost center:', error)
    }
  } finally {
    loading.value = false
  }
}

// Watchers
watch(() => props.costCenter, (newValue) => {
  if (newValue && props.isEdit) {
    form.code = newValue.code || ''
    form.name = newValue.name || ''
    form.description = newValue.description || ''
    form.manager_id = newValue.manager_id || ''
    form.budget = newValue.budget || ''
    form.is_active = newValue.is_active ?? true
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  loadEmployees()
})
</script>