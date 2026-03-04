<template>
  <div class="field-config-panel space-y-6">
    <!-- Basic Settings -->
    <div class="space-y-4">
      <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('forms.config.basic_settings') }}</h4>
      
      <!-- Field Label -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          {{ $t('forms.config.field_label') }}
        </label>
        <input
          v-model="localField.label"
          type="text"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          @input="updateField"
        />
      </div>

      <!-- Field Key -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          {{ $t('forms.config.field_key') }}
        </label>
        <input
          v-model="localField.field_key"
          type="text"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          @input="updateField"
        />
        <p class="text-xs text-gray-500 mt-1">{{ $t('forms.config.field_key_help') }}</p>
      </div>

      <!-- Placeholder -->
      <div v-if="supportsPlaceholder">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          {{ $t('forms.config.placeholder') }}
        </label>
        <input
          v-model="localField.placeholder"
          type="text"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          @input="updateField"
        />
      </div>

      <!-- Help Text -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          {{ $t('forms.config.help_text') }}
        </label>
        <textarea
          v-model="localField.help_text"
          rows="2"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent resize-none"
          @input="updateField"
        ></textarea>
      </div>

      <!-- Required Field -->
      <div class="flex items-center">
        <input
          v-model="localField.is_required"
          type="checkbox"
          class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          @change="updateField"
        />
        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
          {{ $t('forms.config.required_field') }}
        </label>
      </div>
    </div>

    <!-- Field Options (for select, radio, checkbox) -->
    <div v-if="hasOptions" class="space-y-4">
      <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('forms.config.field_options') }}</h4>
      
      <div class="space-y-2">
        <div
          v-for="(option, index) in localField.options"
          :key="index"
          class="flex items-center gap-2"
        >
          <input
            v-model="option.label"
            type="text"
            :placeholder="$t('forms.config.option_label')"
            class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            @input="updateField"
          />
          <input
            v-model="option.value"
            type="text"
            :placeholder="$t('forms.config.option_value')"
            class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            @input="updateField"
          />
          <button
            @click="removeOption(index)"
            class="p-2 text-red-500 hover:text-red-700"
            :title="$t('forms.config.remove_option')"
          >
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
        
        <button
          @click="addOption"
          class="w-full px-3 py-2 text-sm text-primary border border-primary border-dashed rounded-lg hover:bg-primary hover:bg-opacity-5"
        >
          <PlusIcon class="w-4 h-4 mr-1 inline" />
          {{ $t('forms.config.add_option') }}
        </button>
      </div>
    </div>

    <!-- Field Type Specific Settings -->
    <div v-if="hasTypeSpecificSettings" class="space-y-4">
      <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('forms.config.advanced_settings') }}</h4>
      
      <!-- Text/Textarea Settings -->
      <template v-if="localField.field_type === 'text' || localField.field_type === 'textarea'">
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('forms.config.min_length') }}
            </label>
            <input
              v-model.number="localField.settings.minLength"
              type="number"
              min="0"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              @input="updateField"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('forms.config.max_length') }}
            </label>
            <input
              v-model.number="localField.settings.maxLength"
              type="number"
              min="1"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              @input="updateField"
            />
          </div>
        </div>
        
        <div v-if="localField.field_type === 'textarea'">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $t('forms.config.rows') }}
          </label>
          <input
            v-model.number="localField.settings.rows"
            type="number"
            min="2"
            max="20"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            @input="updateField"
          />
        </div>
      </template>

      <!-- Number Settings -->
      <template v-if="localField.field_type === 'number' || localField.field_type === 'integer'">
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('forms.config.min_value') }}
            </label>
            <input
              v-model.number="localField.settings.min"
              type="number"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              @input="updateField"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('forms.config.max_value') }}
            </label>
            <input
              v-model.number="localField.settings.max"
              type="number"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              @input="updateField"
            />
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $t('forms.config.step') }}
          </label>
          <input
            v-model.number="localField.settings.step"
            type="number"
            :step="localField.field_type === 'integer' ? 1 : 0.01"
            min="0.01"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            @input="updateField"
          />
        </div>
      </template>

      <!-- File Upload Settings -->
      <template v-if="localField.field_type === 'file' || localField.field_type === 'image'">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $t('forms.config.max_file_size') }} (KB)
          </label>
          <input
            v-model.number="localField.settings.maxSize"
            type="number"
            min="1"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            @input="updateField"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $t('forms.config.allowed_types') }}
          </label>
          <input
            v-model="allowedTypesString"
            type="text"
            :placeholder="localField.field_type === 'image' ? 'jpg, png, gif' : 'pdf, doc, txt'"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            @input="updateAllowedTypes"
          />
          <p class="text-xs text-gray-500 mt-1">{{ $t('forms.config.allowed_types_help') }}</p>
        </div>
      </template>

      <!-- Rating Settings -->
      <template v-if="localField.field_type === 'rating'">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $t('forms.config.max_rating') }}
          </label>
          <input
            v-model.number="localField.settings.max"
            type="number"
            min="3"
            max="10"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            @input="updateField"
          />
        </div>
        
        <div class="flex items-center">
          <input
            v-model="localField.settings.allowHalf"
            type="checkbox"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
            @change="updateField"
          />
          <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">
            {{ $t('forms.config.allow_half_ratings') }}
          </label>
        </div>
      </template>

      <!-- Slider Settings -->
      <template v-if="localField.field_type === 'slider'">
        <div class="grid grid-cols-3 gap-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('forms.config.min_value') }}
            </label>
            <input
              v-model.number="localField.settings.min"
              type="number"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              @input="updateField"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('forms.config.max_value') }}
            </label>
            <input
              v-model.number="localField.settings.max"
              type="number"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              @input="updateField"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('forms.config.step') }}
            </label>
            <input
              v-model.number="localField.settings.step"
              type="number"
              min="1"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              @input="updateField"
            />
          </div>
        </div>
      </template>
    </div>

    <!-- Validation Rules -->
    <div class="space-y-4">
      <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $t('forms.config.validation') }}</h4>
      
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          {{ $t('forms.config.custom_validation') }}
        </label>
        <textarea
          v-model="validationRulesString"
          rows="3"
          :placeholder="$t('forms.config.validation_placeholder')"
          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent resize-none"
          @input="updateValidationRules"
        ></textarea>
        <p class="text-xs text-gray-500 mt-1">{{ $t('forms.config.validation_help') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import { TrashIcon, PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  field: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update'])

const { $t } = useTranslations()

// Local field copy for editing
const localField = ref({ ...props.field })

// Computed properties
const supportsPlaceholder = computed(() => {
  const supportedTypes = ['text', 'textarea', 'email', 'url', 'phone', 'number', 'integer', 'select']
  return supportedTypes.includes(localField.value.field_type)
})

const hasOptions = computed(() => {
  const optionTypes = ['select', 'multiselect', 'radio', 'checkbox']
  return optionTypes.includes(localField.value.field_type)
})

const hasTypeSpecificSettings = computed(() => {
  const typesWithSettings = ['text', 'textarea', 'number', 'integer', 'file', 'image', 'rating', 'slider']
  return typesWithSettings.includes(localField.value.field_type)
})

// String representations for complex fields
const allowedTypesString = ref('')
const validationRulesString = ref('')

// Initialize string representations
const initializeStringFields = () => {
  if (localField.value.settings?.allowedTypes) {
    allowedTypesString.value = localField.value.settings.allowedTypes.join(', ')
  }
  
  if (localField.value.validation_rules) {
    validationRulesString.value = localField.value.validation_rules.join('\n')
  }
}

// Methods
const updateField = () => {
  emit('update', { ...localField.value })
}

const addOption = () => {
  if (!localField.value.options) {
    localField.value.options = []
  }
  
  const optionNumber = localField.value.options.length + 1
  localField.value.options.push({
    label: `Option ${optionNumber}`,
    value: `option${optionNumber}`
  })
  
  updateField()
}

const removeOption = (index) => {
  localField.value.options.splice(index, 1)
  updateField()
}

const updateAllowedTypes = () => {
  if (!localField.value.settings) {
    localField.value.settings = {}
  }
  
  localField.value.settings.allowedTypes = allowedTypesString.value
    .split(',')
    .map(type => type.trim())
    .filter(type => type.length > 0)
  
  updateField()
}

const updateValidationRules = () => {
  localField.value.validation_rules = validationRulesString.value
    .split('\n')
    .map(rule => rule.trim())
    .filter(rule => rule.length > 0)
  
  updateField()
}

// Initialize settings if they don't exist
const initializeSettings = () => {
  if (!localField.value.settings) {
    localField.value.settings = {}
  }
  
  // Set default settings based on field type
  const defaults = {
    text: { maxLength: 255 },
    textarea: { maxLength: 10000, rows: 4 },
    number: { step: 0.01 },
    integer: { step: 1 },
    file: { maxSize: 10240, allowedTypes: ['pdf', 'doc', 'docx', 'txt'] },
    image: { maxSize: 2048, allowedTypes: ['jpg', 'jpeg', 'png', 'gif', 'webp'] },
    rating: { max: 5, allowHalf: false },
    slider: { min: 0, max: 100, step: 1 }
  }
  
  const fieldDefaults = defaults[localField.value.field_type]
  if (fieldDefaults) {
    Object.keys(fieldDefaults).forEach(key => {
      if (!(key in localField.value.settings)) {
        localField.value.settings[key] = fieldDefaults[key]
      }
    })
  }
}

// Watch for field changes
watch(() => props.field, (newField) => {
  localField.value = { ...newField }
  initializeStringFields()
  initializeSettings()
}, { immediate: true })

// Initialize on mount
initializeStringFields()
initializeSettings()
</script>