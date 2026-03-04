<template>
  <div class="custom-field-manager">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('custom_fields.manager.title') }}</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('custom_fields.manager.subtitle') }}</p>
      </div>
      <div class="flex gap-3">
        <button
          @click="showCreateModal = true"
          class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          {{ $t('custom_fields.manager.add_field') }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $t('custom_fields.manager.filter_domain') }}
          </label>
          <select
            v-model="filters.domain"
            @change="applyFilters"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            <option value="">{{ $t('custom_fields.manager.all_domains') }}</option>
            <option v-for="domain in availableDomains" :key="domain.value" :value="domain.value">
              {{ domain.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $t('custom_fields.manager.filter_entity') }}
          </label>
          <select
            v-model="filters.entity_type"
            @change="applyFilters"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            <option value="">{{ $t('custom_fields.manager.all_entities') }}</option>
            <option v-for="entity in availableEntities" :key="entity.value" :value="entity.value">
              {{ entity.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $t('custom_fields.manager.filter_type') }}
          </label>
          <select
            v-model="filters.field_type"
            @change="applyFilters"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            <option value="">{{ $t('custom_fields.manager.all_types') }}</option>
            <option v-for="type in fieldTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $t('custom_fields.manager.search') }}
          </label>
          <input
            v-model="filters.search"
            @input="debounceSearch"
            type="text"
            :placeholder="$t('custom_fields.manager.search_placeholder')"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>
      </div>
    </div>

    <!-- Custom Fields List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
      <!-- Table Header -->
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ $t('custom_fields.manager.fields_list') }}
          </h3>
          <div class="flex items-center gap-2">
            <button
              v-if="selectedFields.length > 0"
              @click="showBulkActions = !showBulkActions"
              class="px-3 py-1 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
            >
              {{ $t('custom_fields.manager.bulk_actions') }} ({{ selectedFields.length }})
            </button>
          </div>
        </div>

        <!-- Bulk Actions -->
        <div v-if="showBulkActions && selectedFields.length > 0" class="mt-4 p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center gap-2">
            <button
              @click="bulkAction('activate')"
              class="px-3 py-1 text-sm font-medium text-green-700 bg-green-100 rounded hover:bg-green-200"
            >
              {{ $t('custom_fields.manager.activate') }}
            </button>
            <button
              @click="bulkAction('deactivate')"
              class="px-3 py-1 text-sm font-medium text-yellow-700 bg-yellow-100 rounded hover:bg-yellow-200"
            >
              {{ $t('custom_fields.manager.deactivate') }}
            </button>
            <button
              @click="bulkAction('delete')"
              class="px-3 py-1 text-sm font-medium text-red-700 bg-red-100 rounded hover:bg-red-200"
            >
              {{ $t('custom_fields.manager.delete') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Table Content -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left">
                <input
                  type="checkbox"
                  @change="toggleSelectAll"
                  :checked="allSelected"
                  class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t('custom_fields.manager.field_name') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t('custom_fields.manager.domain') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t('custom_fields.manager.entity') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t('custom_fields.manager.type') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t('custom_fields.manager.status') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t('custom_fields.manager.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="field in customFields" :key="field.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4">
                <input
                  type="checkbox"
                  :value="field.id"
                  v-model="selectedFields"
                  class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                />
              </td>
              <td class="px-6 py-4">
                <div>
                  <div class="text-sm font-medium text-gray-900 dark:text-white">{{ field.label }}</div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">{{ field.field_key }}</div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ field.domain || 'Global' }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                {{ field.entity_type }}
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  {{ field.field_type }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span
                  :class="field.is_active 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-red-100 text-red-800'"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ field.is_active ? $t('custom_fields.manager.active') : $t('custom_fields.manager.inactive') }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button
                    @click="editField(field)"
                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                  >
                    {{ $t('custom_fields.manager.edit') }}
                  </button>
                  <button
                    @click="deleteField(field)"
                    class="text-red-600 hover:text-red-900 text-sm font-medium"
                  >
                    {{ $t('custom_fields.manager.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="customFields.length === 0" class="text-center py-12">
        <div class="text-gray-400 mb-4">
          <i class="material-icons text-6xl">settings</i>
        </div>
        <p class="text-gray-500 dark:text-gray-400">{{ $t('custom_fields.manager.no_fields') }}</p>
        <button
          @click="showCreateModal = true"
          class="mt-4 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark"
        >
          {{ $t('custom_fields.manager.create_first_field') }}
        </button>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <CustomFieldModal
      v-if="showCreateModal || editingField"
      :field="editingField"
      :domains="availableDomains"
      :field-types="fieldTypes"
      @close="closeModal"
      @save="handleSave"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import { PlusIcon } from '@heroicons/vue/24/outline'
import CustomFieldModal from './CustomFieldModal.vue'

const props = defineProps({
  initialFields: {
    type: Array,
    default: () => []
  },
  availableDomains: {
    type: Array,
    default: () => []
  },
  availableEntities: {
    type: Array,
    default: () => []
  },
  fieldTypes: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['create', 'update', 'delete', 'bulkAction'])

const { $t } = useTranslations()

// State
const customFields = ref([...props.initialFields])
const selectedFields = ref([])
const showBulkActions = ref(false)
const showCreateModal = ref(false)
const editingField = ref(null)

// Filters
const filters = ref({
  domain: '',
  entity_type: '',
  field_type: '',
  search: ''
})

// Computed
const allSelected = computed(() => {
  return customFields.value.length > 0 && selectedFields.value.length === customFields.value.length
})

// Methods
const applyFilters = () => {
  // Emit filter change event
  emit('filter', filters.value)
}

const debounceSearch = (() => {
  let timeout
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      applyFilters()
    }, 300)
  }
})()

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedFields.value = []
  } else {
    selectedFields.value = customFields.value.map(field => field.id)
  }
}

const editField = (field) => {
  editingField.value = { ...field }
}

const deleteField = (field) => {
  if (confirm($t('custom_fields.manager.confirm_delete'))) {
    emit('delete', field.id)
  }
}

const bulkAction = (action) => {
  if (confirm($t(`custom_fields.manager.confirm_${action}`))) {
    emit('bulkAction', {
      action,
      fieldIds: selectedFields.value
    })
    selectedFields.value = []
    showBulkActions.value = false
  }
}

const closeModal = () => {
  showCreateModal.value = false
  editingField.value = null
}

const handleSave = (fieldData) => {
  if (editingField.value) {
    emit('update', editingField.value.id, fieldData)
  } else {
    emit('create', fieldData)
  }
  closeModal()
}

// Update fields when props change
const updateFields = (newFields) => {
  customFields.value = [...newFields]
}

// Expose methods for parent component
defineExpose({
  updateFields
})
</script>