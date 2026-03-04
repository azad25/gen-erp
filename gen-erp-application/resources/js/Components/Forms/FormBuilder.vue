<template>
  <div class="form-builder">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('forms.builder.title') }}</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('forms.builder.subtitle') }}</p>
      </div>
      <div class="flex gap-3">
        <button
          @click="previewForm"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
        >
          <EyeIcon class="w-4 h-4 mr-2" />
          {{ $t('forms.builder.preview') }}
        </button>
        <button
          @click="saveForm"
          :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50"
        >
          <template v-if="saving">
            <div class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          </template>
          <template v-else>
            <DocumentCheckIcon class="w-4 h-4 mr-2" />
          </template>
          {{ $t('forms.builder.save') }}
        </button>
      </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
      <!-- Field Library Sidebar -->
      <div class="col-span-3">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('forms.builder.field_library') }}</h3>
          </div>
          
          <div class="p-4">
            <!-- Search Fields -->
            <div class="mb-4">
              <input
                v-model="fieldSearch"
                type="text"
                :placeholder="$t('forms.builder.search_fields')"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>

            <!-- Field Categories -->
            <div class="space-y-4">
              <div v-for="(fields, category) in filteredFieldTypes" :key="category" class="field-category">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 capitalize">
                  {{ $t(`forms.field_categories.${category}`) }}
                </h4>
                <div class="space-y-2">
                  <div
                    v-for="field in fields"
                    :key="field.value"
                    @click="addField(field)"
                    class="field-item p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    draggable="true"
                    @dragstart="onDragStart($event, field)"
                  >
                    <div class="flex items-center">
                      <i :class="`material-icons text-gray-500 mr-2`">{{ field.icon }}</i>
                      <span class="text-sm font-medium text-gray-900 dark:text-white">{{ field.label }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Canvas -->
      <div class="col-span-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 min-h-[600px]">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('forms.builder.form_canvas') }}</h3>
              <div class="flex items-center gap-2">
                <button
                  @click="clearForm"
                  class="px-3 py-1 text-xs font-medium text-red-600 hover:text-red-700"
                >
                  {{ $t('forms.builder.clear_all') }}
                </button>
              </div>
            </div>
          </div>

          <!-- Form Fields -->
          <div
            class="p-4 min-h-[500px]"
            @drop="onDrop"
            @dragover.prevent
            @dragenter.prevent
          >
            <div v-if="formFields.length === 0" class="text-center py-20">
              <div class="text-gray-400 mb-4">
                <i class="material-icons text-6xl">add_circle_outline</i>
              </div>
              <p class="text-gray-500 dark:text-gray-400">{{ $t('forms.builder.empty_form') }}</p>
              <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">{{ $t('forms.builder.drag_fields') }}</p>
            </div>

            <draggable
              v-else
              v-model="formFields"
              group="form-fields"
              item-key="id"
              class="space-y-4"
              @change="onFieldOrderChange"
            >
              <template #item="{ element: field, index }">
                <div
                  class="form-field-item relative group"
                  :class="{ 'ring-2 ring-primary': selectedFieldId === field.id }"
                  @click="selectField(field)"
                >
                  <!-- Field Preview -->
                  <FieldPreview :field="field" />
                  
                  <!-- Field Controls -->
                  <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <div class="flex gap-1">
                      <button
                        @click.stop="duplicateField(field)"
                        class="p-1 text-gray-400 hover:text-gray-600 bg-white rounded shadow-sm"
                        :title="$t('forms.builder.duplicate_field')"
                      >
                        <DocumentDuplicateIcon class="w-4 h-4" />
                      </button>
                      <button
                        @click.stop="removeField(index)"
                        class="p-1 text-red-400 hover:text-red-600 bg-white rounded shadow-sm"
                        :title="$t('forms.builder.remove_field')"
                      >
                        <TrashIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </div>
              </template>
            </draggable>
          </div>
        </div>
      </div>

      <!-- Field Configuration Panel -->
      <div class="col-span-3">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 sticky top-4">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('forms.builder.field_settings') }}</h3>
          </div>
          
          <div class="p-4">
            <FieldConfigPanel
              v-if="selectedField"
              :field="selectedField"
              @update="updateField"
            />
            <div v-else class="text-center py-8">
              <div class="text-gray-400 mb-2">
                <i class="material-icons text-4xl">settings</i>
              </div>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('forms.builder.select_field_to_configure') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Form Preview Modal -->
    <FormPreviewModal
      v-if="showPreview"
      :form="formPreviewData"
      @close="showPreview = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import draggable from 'vuedraggable'
import FieldPreview from './FieldPreview.vue'
import FieldConfigPanel from './FieldConfigPanel.vue'
import FormPreviewModal from './FormPreviewModal.vue'
import { EyeIcon, DocumentCheckIcon, DocumentDuplicateIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  initialForm: {
    type: Object,
    default: () => ({})
  },
  fieldTypes: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['save', 'preview'])

const { $t } = useTranslations()

// State
const formFields = ref([])
const selectedFieldId = ref(null)
const fieldSearch = ref('')
const saving = ref(false)
const showPreview = ref(false)

// Computed
const selectedField = computed(() => {
  return formFields.value.find(field => field.id === selectedFieldId.value)
})

const filteredFieldTypes = computed(() => {
  if (!fieldSearch.value) return props.fieldTypes
  
  const filtered = {}
  Object.keys(props.fieldTypes).forEach(category => {
    const fields = props.fieldTypes[category].filter(field =>
      field.label.toLowerCase().includes(fieldSearch.value.toLowerCase())
    )
    if (fields.length > 0) {
      filtered[category] = fields
    }
  })
  return filtered
})

const formPreviewData = computed(() => ({
  name: 'Form Preview',
  fields: formFields.value
}))

// Methods
const addField = (fieldType) => {
  const newField = {
    id: generateFieldId(),
    field_key: generateFieldKey(fieldType.value),
    field_type: fieldType.value,
    label: fieldType.label,
    placeholder: '',
    help_text: '',
    is_required: false,
    validation_rules: [],
    options: fieldType.value === 'select' || fieldType.value === 'radio' || fieldType.value === 'checkbox' ? [
      { label: 'Option 1', value: 'option1' },
      { label: 'Option 2', value: 'option2' }
    ] : [],
    settings: {},
    display_order: formFields.value.length,
    is_active: true
  }
  
  formFields.value.push(newField)
  selectField(newField)
}

const selectField = (field) => {
  selectedFieldId.value = field.id
}

const updateField = (updatedField) => {
  const index = formFields.value.findIndex(field => field.id === updatedField.id)
  if (index !== -1) {
    formFields.value[index] = { ...updatedField }
  }
}

const removeField = (index) => {
  const removedField = formFields.value[index]
  formFields.value.splice(index, 1)
  
  if (selectedFieldId.value === removedField.id) {
    selectedFieldId.value = null
  }
}

const duplicateField = (field) => {
  const duplicatedField = {
    ...field,
    id: generateFieldId(),
    field_key: generateFieldKey(field.field_type),
    label: field.label + ' (Copy)'
  }
  
  const index = formFields.value.findIndex(f => f.id === field.id)
  formFields.value.splice(index + 1, 0, duplicatedField)
}

const clearForm = () => {
  if (confirm($t('forms.builder.confirm_clear'))) {
    formFields.value = []
    selectedFieldId.value = null
  }
}

const saveForm = async () => {
  saving.value = true
  try {
    await emit('save', {
      fields: formFields.value
    })
  } finally {
    saving.value = false
  }
}

const previewForm = () => {
  showPreview.value = true
}

// Drag and Drop
const onDragStart = (event, field) => {
  event.dataTransfer.setData('field-type', JSON.stringify(field))
}

const onDrop = (event) => {
  event.preventDefault()
  const fieldData = event.dataTransfer.getData('field-type')
  if (fieldData) {
    const field = JSON.parse(fieldData)
    addField(field)
  }
}

const onFieldOrderChange = () => {
  // Update display_order for all fields
  formFields.value.forEach((field, index) => {
    field.display_order = index
  })
}

// Utility functions
const generateFieldId = () => {
  return 'field_' + Math.random().toString(36).substr(2, 9)
}

const generateFieldKey = (fieldType) => {
  const timestamp = Date.now()
  return `${fieldType}_${timestamp}`
}

// Initialize form if provided
watch(() => props.initialForm, (newForm) => {
  if (newForm && newForm.fields) {
    formFields.value = newForm.fields.map(field => ({
      ...field,
      id: field.id || generateFieldId()
    }))
  }
}, { immediate: true })
</script>

<style scoped>
.form-field-item {
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  padding: 1rem;
  cursor: pointer;
  transition: border-color 0.2s;
}

.form-field-item:hover {
  border-color: #9ca3af;
}

.dark .form-field-item {
  border-color: #4b5563;
}

.dark .form-field-item:hover {
  border-color: #6b7280;
}

.field-item:hover {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.sortable-ghost {
  opacity: 0.5;
}

.sortable-chosen {
  box-shadow: 0 0 0 2px var(--primary-color, #3b82f6);
}
</style>