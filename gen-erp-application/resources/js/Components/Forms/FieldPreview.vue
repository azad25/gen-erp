<template>
  <div class="field-preview">
    <!-- Field Label -->
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
      {{ field.label }}
      <span v-if="field.is_required" class="text-red-500">*</span>
    </label>

    <!-- Field Input Based on Type -->
    <div class="field-input">
      <!-- Text Input -->
      <input
        v-if="field.field_type === 'text' || field.field_type === 'email' || field.field_type === 'url' || field.field_type === 'phone'"
        :type="getInputType(field.field_type)"
        :placeholder="field.placeholder"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent disabled:bg-gray-100"
        disabled
      />

      <!-- Textarea -->
      <textarea
        v-else-if="field.field_type === 'textarea'"
        :placeholder="field.placeholder"
        :rows="field.settings?.rows || 4"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent disabled:bg-gray-100 resize-none"
        disabled
      ></textarea>

      <!-- Number Input -->
      <input
        v-else-if="field.field_type === 'number' || field.field_type === 'integer'"
        type="number"
        :placeholder="field.placeholder"
        :min="field.settings?.min"
        :max="field.settings?.max"
        :step="field.settings?.step"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent disabled:bg-gray-100"
        disabled
      />

      <!-- Date Input -->
      <input
        v-else-if="field.field_type === 'date'"
        type="date"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent disabled:bg-gray-100"
        disabled
      />

      <!-- DateTime Input -->
      <input
        v-else-if="field.field_type === 'datetime'"
        type="datetime-local"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent disabled:bg-gray-100"
        disabled
      />

      <!-- Time Input -->
      <input
        v-else-if="field.field_type === 'time'"
        type="time"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent disabled:bg-gray-100"
        disabled
      />

      <!-- Select Dropdown -->
      <select
        v-else-if="field.field_type === 'select'"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent disabled:bg-gray-100"
        disabled
      >
        <option value="">{{ field.placeholder || $t('forms.select_option') }}</option>
        <option v-for="option in field.options" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>

      <!-- Multi-Select -->
      <div v-else-if="field.field_type === 'multiselect'" class="space-y-2">
        <div v-for="option in field.options" :key="option.value" class="flex items-center">
          <input
            type="checkbox"
            :id="`${field.id}_${option.value}`"
            :value="option.value"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded disabled:opacity-50"
            disabled
          />
          <label :for="`${field.id}_${option.value}`" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
            {{ option.label }}
          </label>
        </div>
      </div>

      <!-- Radio Buttons -->
      <div v-else-if="field.field_type === 'radio'" class="space-y-2">
        <div v-for="option in field.options" :key="option.value" class="flex items-center">
          <input
            type="radio"
            :name="`${field.id}_radio`"
            :id="`${field.id}_${option.value}`"
            :value="option.value"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 disabled:opacity-50"
            disabled
          />
          <label :for="`${field.id}_${option.value}`" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
            {{ option.label }}
          </label>
        </div>
      </div>

      <!-- Checkbox Group -->
      <div v-else-if="field.field_type === 'checkbox'" class="space-y-2">
        <div v-for="option in field.options" :key="option.value" class="flex items-center">
          <input
            type="checkbox"
            :id="`${field.id}_${option.value}`"
            :value="option.value"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded disabled:opacity-50"
            disabled
          />
          <label :for="`${field.id}_${option.value}`" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
            {{ option.label }}
          </label>
        </div>
      </div>

      <!-- Boolean Toggle -->
      <div v-else-if="field.field_type === 'boolean'" class="flex items-center">
        <input
          type="checkbox"
          :id="field.id"
          class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded disabled:opacity-50"
          disabled
        />
        <label :for="field.id" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
          {{ field.placeholder || $t('forms.yes_no') }}
        </label>
      </div>

      <!-- File Upload -->
      <div v-else-if="field.field_type === 'file' || field.field_type === 'image'" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
        <div class="text-gray-400 mb-2">
          <i class="material-icons text-3xl">{{ field.field_type === 'image' ? 'image' : 'attach_file' }}</i>
        </div>
        <p class="text-sm text-gray-500">{{ $t('forms.upload_file') }}</p>
        <p class="text-xs text-gray-400 mt-1">
          {{ field.field_type === 'image' ? $t('forms.image_formats') : $t('forms.file_formats') }}
        </p>
      </div>

      <!-- Rich Text Editor -->
      <div v-else-if="field.field_type === 'rich_text'" class="border border-gray-300 rounded-lg">
        <div class="border-b border-gray-300 p-2 bg-gray-50">
          <div class="flex gap-1">
            <button class="p-1 text-gray-500 hover:text-gray-700 disabled:opacity-50" disabled>
              <i class="material-icons text-sm">format_bold</i>
            </button>
            <button class="p-1 text-gray-500 hover:text-gray-700 disabled:opacity-50" disabled>
              <i class="material-icons text-sm">format_italic</i>
            </button>
            <button class="p-1 text-gray-500 hover:text-gray-700 disabled:opacity-50" disabled>
              <i class="material-icons text-sm">format_underlined</i>
            </button>
          </div>
        </div>
        <div class="p-3 min-h-[100px] bg-gray-50 text-gray-400">
          {{ field.placeholder || $t('forms.rich_text_placeholder') }}
        </div>
      </div>

      <!-- Rating -->
      <div v-else-if="field.field_type === 'rating'" class="flex items-center gap-1">
        <i
          v-for="n in (field.settings?.max || 5)"
          :key="n"
          class="material-icons text-gray-300 cursor-pointer hover:text-yellow-400"
        >
          star_border
        </i>
      </div>

      <!-- Slider -->
      <div v-else-if="field.field_type === 'slider'" class="space-y-2">
        <input
          type="range"
          :min="field.settings?.min || 0"
          :max="field.settings?.max || 100"
          :step="field.settings?.step || 1"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer disabled:opacity-50"
          disabled
        />
        <div class="flex justify-between text-xs text-gray-500">
          <span>{{ field.settings?.min || 0 }}</span>
          <span>{{ field.settings?.max || 100 }}</span>
        </div>
      </div>

      <!-- Color Picker -->
      <div v-else-if="field.field_type === 'color'" class="flex items-center gap-2">
        <input
          type="color"
          class="w-12 h-10 border border-gray-300 rounded cursor-pointer disabled:opacity-50"
          disabled
        />
        <input
          type="text"
          placeholder="#000000"
          class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent disabled:bg-gray-100"
          disabled
        />
      </div>

      <!-- Signature -->
      <div v-else-if="field.field_type === 'signature'" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center bg-gray-50">
        <div class="text-gray-400 mb-2">
          <i class="material-icons text-3xl">draw</i>
        </div>
        <p class="text-sm text-gray-500">{{ $t('forms.signature_placeholder') }}</p>
      </div>

      <!-- Hidden Field -->
      <div v-else-if="field.field_type === 'hidden'" class="text-sm text-gray-500 italic">
        {{ $t('forms.hidden_field') }}: {{ field.field_key }}
      </div>

      <!-- Fallback for unknown types -->
      <div v-else class="text-sm text-gray-500 italic">
        {{ $t('forms.unknown_field_type') }}: {{ field.field_type }}
      </div>
    </div>

    <!-- Help Text -->
    <p v-if="field.help_text" class="mt-1 text-xs text-gray-500">
      {{ field.help_text }}
    </p>
  </div>
</template>

<script setup>
import { useTranslations } from '@/Composables/useTranslations'

const props = defineProps({
  field: {
    type: Object,
    required: true
  }
})

const { $t } = useTranslations()

const getInputType = (fieldType) => {
  const typeMap = {
    text: 'text',
    email: 'email',
    url: 'url',
    phone: 'tel'
  }
  return typeMap[fieldType] || 'text'
}
</script>

<style scoped>
.field-preview {
  @apply pointer-events-none;
}

/* Custom styles for disabled form elements */
input:disabled,
textarea:disabled,
select:disabled {
  @apply cursor-not-allowed;
}
</style>