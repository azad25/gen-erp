<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">
            {{ field ? $t('custom_fields.modal.edit_title') : $t('custom_fields.modal.create_title') }}
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 focus:outline-none"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Basic Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $t('custom_fields.modal.domain') }}
              </label>
              <select
                v-model="formData.domain"
                @change="onDomainChange"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              >
                <option value="">{{ $t('custom_fields.modal.select_domain') }}</option>
                <option v-for="domain in domains" :key="domain.value" :value="domain.value">
                  {{ domain.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $t('custom_fields.modal.entity_type') }} *
              </label>
              <select
                v-model="formData.entity_type"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              >
                <option value="">{{ $t('custom_fields.modal.select_entity') }}</option>
                <option v-for="entity in availableEntities" :key="entity.value" :value="entity.value">
                  {{ entity.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $t('custom_fields.modal.field_key') }} *
              </label>
              <input
                v-model="formData.field_key"
                type="text"
                required
                :placeholder="$t('custom_fields.modal.field_key_placeholder')"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              />
              <p class="mt-1 text-xs text-gray-500">{{ $t('custom_fields.modal.field_key_help') }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $t('custom_fields.modal.field_type') }} *
              </label>
              <select
                v-model="formData.field_type"
                @change="onFieldTypeChange"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              >
                <option value="">{{ $t('custom_fields.modal.select_type') }}</option>
                <optgroup v-for="(types, category) in fieldTypes" :key="category" :label="category">
                  <option v-for="type in types" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </optgroup>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('custom_fields.modal.label') }} *
            </label>
            <input
              v-model="formData.label"
              type="text"
              required
              :placeholder="$t('custom_fields.modal.label_placeholder')"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $t('custom_fields.modal.help_text') }}
            </label>
            <textarea
              v-model="formData.help_text"
              rows="3"
              :placeholder="$t('custom_fields.modal.help_text_placeholder')"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            ></textarea>
          </div>

          <!-- Field Options (for select, radio, checkbox) -->
          <div v-if="needsOptions" class="space-y-3">
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('custom_fields.modal.options') }}
            </label>
            <div v-for="(option, index) in formData.options" :key="index" class="flex items-center gap-2">
              <input
                v-model="option.label"
                type="text"
                :placeholder="$t('custom_fields.modal.option_label')"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              />
              <input
                v-model="option.value"
                type="text"
                :placeholder="$t('custom_fields.modal.option_value')"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              />
              <button
                type="button"
                @click="removeOption(index)"
                class="p-2 text-red-600 hover:text-red-800"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
            <button
              type="button"
              @click="addOption"
              class="px-3 py-2 text-sm font-medium text-primary border border-primary rounded-lg hover:bg-primary hover:text-white"
            >
              {{ $t('custom_fields.modal.add_option') }}
            </button>
          </div>

          <!-- Field Settings -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="flex items-center">
              <input
                v-model="formData.is_required"
                type="checkbox"
                class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
              />
              <label class="ml-2 text-sm text-gray-700">{{ $t('custom_fields.modal.required') }}</label>
            </div>

            <div class="flex items-center">
              <input
                v-model="formData.is_filterable"
                type="checkbox"
                class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
              />
              <label class="ml-2 text-sm text-gray-700">{{ $t('custom_fields.modal.filterable') }}</label>
            </div>

            <div class="flex items-center">
              <input
                v-model="formData.is_searchable"
                type="checkbox"
                class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
              />
              <label class="ml-2 text-sm text-gray-700">{{ $t('custom_fields.modal.searchable') }}</label>
            </div>

            <div class="flex items-center">
              <input
                v-model="formData.show_in_list"
                type="checkbox"
                class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
              />
              <label class="ml-2 text-sm text-gray-700">{{ $t('custom_fields.modal.show_in_list') }}</label>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $t('custom_fields.modal.default_value') }}
              </label>
              <input
                v-model="formData.default_value"
                type="text"
                :placeholder="$t('custom_fields.modal.default_value_placeholder')"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $t('custom_fields.modal.security_level') }} *
              </label>
              <select
                v-model="formData.security_level"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              >
                <option value="public">{{ $t('custom_fields.modal.security_public') }}</option>
                <option value="internal">{{ $t('custom_fields.modal.security_internal') }}</option>
                <option value="restricted">{{ $t('custom_fields.modal.security_restricted') }}</option>
              </select>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            >
              {{ $t('custom_fields.modal.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50"
            >
              <template v-if="saving">
                <div class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              </template>
              {{ field ? $t('custom_fields.modal.update') : $t('custom_fields.modal.create') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import { XMarkIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  field: {
    type: Object,
    default: null
  },
  domains: {
    type: Array,
    required: true
  },
  fieldTypes: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'save'])

const { $t } = useTranslations()

// State
const saving = ref(false)
const availableEntities = ref([])

// Form data
const formData = reactive({
  domain: '',
  entity_type: '',
  field_key: '',
  label: '',
  field_type: '',
  help_text: '',
  is_required: false,
  is_filterable: false,
  is_searchable: false,
  show_in_list: false,
  default_value: '',
  options: [],
  security_level: 'public',
  display_order: 0,
  is_active: true
})

// Initialize form data if editing
if (props.field) {
  Object.assign(formData, {
    ...props.field,
    options: props.field.options || []
  })
}

// Computed
const needsOptions = computed(() => {
  return ['select', 'radio', 'checkbox'].includes(formData.field_type)
})

// Methods
const onDomainChange = async () => {
  // Fetch entity types for selected domain
  if (formData.domain) {
    try {
      const response = await fetch(`/documents/custom-fields/api/entity-types?domain=${formData.domain}`)
      availableEntities.value = await response.json()
    } catch (error) {
      console.error('Failed to fetch entity types:', error)
    }
  } else {
    availableEntities.value = []
  }
  formData.entity_type = ''
}

const onFieldTypeChange = () => {
  // Initialize options for field types that need them
  if (needsOptions.value && formData.options.length === 0) {
    formData.options = [
      { label: 'Option 1', value: 'option1' },
      { label: 'Option 2', value: 'option2' }
    ]
  } else if (!needsOptions.value) {
    formData.options = []
  }
}

const addOption = () => {
  formData.options.push({
    label: `Option ${formData.options.length + 1}`,
    value: `option${formData.options.length + 1}`
  })
}

const removeOption = (index) => {
  formData.options.splice(index, 1)
}

const handleSubmit = async () => {
  saving.value = true
  try {
    // Generate field key if not provided
    if (!formData.field_key && formData.label) {
      formData.field_key = formData.label.toLowerCase().replace(/[^a-z0-9]/g, '_')
    }

    emit('save', { ...formData })
  } finally {
    saving.value = false
  }
}

// Watch for field key generation
watch(() => formData.label, (newLabel) => {
  if (newLabel && !props.field) { // Only auto-generate for new fields
    formData.field_key = newLabel.toLowerCase().replace(/[^a-z0-9]/g, '_')
  }
})

// Initialize entity types if domain is already selected
if (formData.domain) {
  onDomainChange()
}
</script>