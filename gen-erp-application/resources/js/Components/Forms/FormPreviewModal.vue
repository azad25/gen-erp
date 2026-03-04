<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ $t('forms.preview.title') }}</h3>
            <p class="text-sm text-gray-500">{{ form.name }}</p>
          </div>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 focus:outline-none"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <!-- Form Preview -->
        <div class="bg-gray-50 rounded-lg p-6">
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <div v-for="field in form.fields" :key="field.id" class="form-field">
              <!-- Text Input -->
              <div v-if="field.field_type === 'text'" class="form-group">
                <label :for="field.field_key" class="block text-sm font-medium text-gray-700 mb-1">
                  {{ field.label }}
                  <span v-if="field.is_required" class="text-red-500">*</span>
                </label>
                <input
                  :id="field.field_key"
                  v-model="formData[field.field_key]"
                  type="text"
                  :placeholder="field.placeholder"
                  :required="field.is_required"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                />
                <p v-if="field.help_text" class="mt-1 text-xs text-gray-500">{{ field.help_text }}</p>
              </div>

              <!-- Email Input -->
              <div v-else-if="field.field_type === 'email'" class="form-group">
                <label :for="field.field_key" class="block text-sm font-medium text-gray-700 mb-1">
                  {{ field.label }}
                  <span v-if="field.is_required" class="text-red-500">*</span>
                </label>
                <input
                  :id="field.field_key"
                  v-model="formData[field.field_key]"
                  type="email"
                  :placeholder="field.placeholder"
                  :required="field.is_required"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                />
                <p v-if="field.help_text" class="mt-1 text-xs text-gray-500">{{ field.help_text }}</p>
              </div>

              <!-- Number Input -->
              <div v-else-if="field.field_type === 'number'" class="form-group">
                <label :for="field.field_key" class="block text-sm font-medium text-gray-700 mb-1">
                  {{ field.label }}
                  <span v-if="field.is_required" class="text-red-500">*</span>
                </label>
                <input
                  :id="field.field_key"
                  v-model="formData[field.field_key]"
                  type="number"
                  :placeholder="field.placeholder"
                  :required="field.is_required"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                />
                <p v-if="field.help_text" class="mt-1 text-xs text-gray-500">{{ field.help_text }}</p>
              </div>

              <!-- Textarea -->
              <div v-else-if="field.field_type === 'textarea'" class="form-group">
                <label :for="field.field_key" class="block text-sm font-medium text-gray-700 mb-1">
                  {{ field.label }}
                  <span v-if="field.is_required" class="text-red-500">*</span>
                </label>
                <textarea
                  :id="field.field_key"
                  v-model="formData[field.field_key]"
                  :placeholder="field.placeholder"
                  :required="field.is_required"
                  rows="4"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                ></textarea>
                <p v-if="field.help_text" class="mt-1 text-xs text-gray-500">{{ field.help_text }}</p>
              </div>

              <!-- Select Dropdown -->
              <div v-else-if="field.field_type === 'select'" class="form-group">
                <label :for="field.field_key" class="block text-sm font-medium text-gray-700 mb-1">
                  {{ field.label }}
                  <span v-if="field.is_required" class="text-red-500">*</span>
                </label>
                <select
                  :id="field.field_key"
                  v-model="formData[field.field_key]"
                  :required="field.is_required"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                >
                  <option value="">{{ $t('forms.preview.select_option') }}</option>
                  <option v-for="option in field.options" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
                <p v-if="field.help_text" class="mt-1 text-xs text-gray-500">{{ field.help_text }}</p>
              </div>

              <!-- Radio Buttons -->
              <div v-else-if="field.field_type === 'radio'" class="form-group">
                <fieldset>
                  <legend class="block text-sm font-medium text-gray-700 mb-2">
                    {{ field.label }}
                    <span v-if="field.is_required" class="text-red-500">*</span>
                  </legend>
                  <div class="space-y-2">
                    <div v-for="option in field.options" :key="option.value" class="flex items-center">
                      <input
                        :id="`${field.field_key}_${option.value}`"
                        v-model="formData[field.field_key]"
                        :value="option.value"
                        type="radio"
                        :name="field.field_key"
                        :required="field.is_required"
                        class="w-4 h-4 text-primary border-gray-300 focus:ring-primary"
                      />
                      <label :for="`${field.field_key}_${option.value}`" class="ml-2 text-sm text-gray-700">
                        {{ option.label }}
                      </label>
                    </div>
                  </div>
                  <p v-if="field.help_text" class="mt-1 text-xs text-gray-500">{{ field.help_text }}</p>
                </fieldset>
              </div>

              <!-- Checkboxes -->
              <div v-else-if="field.field_type === 'checkbox'" class="form-group">
                <fieldset>
                  <legend class="block text-sm font-medium text-gray-700 mb-2">
                    {{ field.label }}
                    <span v-if="field.is_required" class="text-red-500">*</span>
                  </legend>
                  <div class="space-y-2">
                    <div v-for="option in field.options" :key="option.value" class="flex items-center">
                      <input
                        :id="`${field.field_key}_${option.value}`"
                        v-model="formData[field.field_key]"
                        :value="option.value"
                        type="checkbox"
                        class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                      />
                      <label :for="`${field.field_key}_${option.value}`" class="ml-2 text-sm text-gray-700">
                        {{ option.label }}
                      </label>
                    </div>
                  </div>
                  <p v-if="field.help_text" class="mt-1 text-xs text-gray-500">{{ field.help_text }}</p>
                </fieldset>
              </div>

              <!-- Date Input -->
              <div v-else-if="field.field_type === 'date'" class="form-group">
                <label :for="field.field_key" class="block text-sm font-medium text-gray-700 mb-1">
                  {{ field.label }}
                  <span v-if="field.is_required" class="text-red-500">*</span>
                </label>
                <input
                  :id="field.field_key"
                  v-model="formData[field.field_key]"
                  type="date"
                  :required="field.is_required"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                />
                <p v-if="field.help_text" class="mt-1 text-xs text-gray-500">{{ field.help_text }}</p>
              </div>

              <!-- File Upload -->
              <div v-else-if="field.field_type === 'file'" class="form-group">
                <label :for="field.field_key" class="block text-sm font-medium text-gray-700 mb-1">
                  {{ field.label }}
                  <span v-if="field.is_required" class="text-red-500">*</span>
                </label>
                <input
                  :id="field.field_key"
                  type="file"
                  :required="field.is_required"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                />
                <p v-if="field.help_text" class="mt-1 text-xs text-gray-500">{{ field.help_text }}</p>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-gray-200">
              <button
                type="submit"
                class="px-6 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
              >
                {{ $t('forms.preview.submit') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  form: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close'])

const { $t } = useTranslations()

// Initialize form data
const formData = reactive({})

// Initialize form data with default values
props.form.fields?.forEach(field => {
  if (field.field_type === 'checkbox') {
    formData[field.field_key] = []
  } else {
    formData[field.field_key] = field.default_value || ''
  }
})

const handleSubmit = () => {
  // This is just a preview, so we'll show an alert
  alert($t('forms.preview.submit_message'))
}
</script>