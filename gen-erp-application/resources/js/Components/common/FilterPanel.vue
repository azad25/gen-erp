<template>
  <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">
          {{ title || 'Filters' }}
        </h3>
        <div class="flex items-center space-x-2">
          <button
            v-if="hasActiveFilters"
            @click="clearAllFilters"
            class="text-xs text-indigo-600 hover:text-indigo-500 font-medium"
          >
            Clear All
          </button>
          <button
            @click="collapsed = !collapsed"
            class="text-gray-400 hover:text-gray-600"
          >
            <ChevronUpIcon v-if="!collapsed" class="h-4 w-4" />
            <ChevronDownIcon v-else class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Filter Content -->
    <div v-if="!collapsed" class="p-4">
      <div class="space-y-4">
        <!-- Search Filter -->
        <div v-if="showSearch">
          <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input
              :value="filters.search"
              @input="updateFilter('search', $event.target.value)"
              type="text"
              :placeholder="searchPlaceholder"
              class="pl-10 pr-4 py-2 w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
        </div>

        <!-- Dynamic Filter Fields -->
        <div v-for="field in filterFields" :key="field.key" class="space-y-1">
          <label class="block text-xs font-medium text-gray-700">
            {{ field.label }}
            <span v-if="field.required" class="text-red-500">*</span>
          </label>

          <!-- Text Input -->
          <input
            v-if="field.type === 'text'"
            :value="filters[field.key]"
            @input="updateFilter(field.key, $event.target.value)"
            type="text"
            :placeholder="field.placeholder"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          />

          <!-- Number Input -->
          <input
            v-else-if="field.type === 'number'"
            :value="filters[field.key]"
            @input="updateFilter(field.key, $event.target.value)"
            type="number"
            :placeholder="field.placeholder"
            :min="field.min"
            :max="field.max"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          />

          <!-- Select Dropdown -->
          <select
            v-else-if="field.type === 'select'"
            :value="filters[field.key]"
            @change="updateFilter(field.key, $event.target.value)"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">{{ field.placeholder || `All ${field.label}` }}</option>
            <option
              v-for="option in field.options"
              :key="getOptionValue(option)"
              :value="getOptionValue(option)"
            >
              {{ getOptionLabel(option) }}
            </option>
          </select>

          <!-- Multi-Select -->
          <div v-else-if="field.type === 'multiselect'" class="space-y-2">
            <div class="max-h-32 overflow-y-auto border border-gray-300 rounded-md p-2">
              <label
                v-for="option in field.options"
                :key="getOptionValue(option)"
                class="flex items-center space-x-2 text-sm"
              >
                <input
                  type="checkbox"
                  :value="getOptionValue(option)"
                  :checked="(filters[field.key] || []).includes(getOptionValue(option))"
                  @change="updateMultiSelectFilter(field.key, getOptionValue(option), $event.target.checked)"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <span>{{ getOptionLabel(option) }}</span>
              </label>
            </div>
          </div>

          <!-- Date Input -->
          <input
            v-else-if="field.type === 'date'"
            :value="filters[field.key]"
            @input="updateFilter(field.key, $event.target.value)"
            type="date"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          />

          <!-- Date Range -->
          <div v-else-if="field.type === 'daterange'" class="grid grid-cols-2 gap-2">
            <input
              :value="filters[`${field.key}_from`]"
              @input="updateFilter(`${field.key}_from`, $event.target.value)"
              type="date"
              placeholder="From"
              class="text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            />
            <input
              :value="filters[`${field.key}_to`]"
              @input="updateFilter(`${field.key}_to`, $event.target.value)"
              type="date"
              placeholder="To"
              class="text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <!-- Range Slider -->
          <div v-else-if="field.type === 'range'" class="space-y-2">
            <div class="flex items-center justify-between text-xs text-gray-500">
              <span>{{ field.min || 0 }}</span>
              <span>{{ filters[field.key] || field.min || 0 }}</span>
              <span>{{ field.max || 100 }}</span>
            </div>
            <input
              :value="filters[field.key]"
              @input="updateFilter(field.key, $event.target.value)"
              type="range"
              :min="field.min || 0"
              :max="field.max || 100"
              :step="field.step || 1"
              class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
            />
          </div>

          <!-- Boolean Checkbox -->
          <label
            v-else-if="field.type === 'boolean'"
            class="flex items-center space-x-2 text-sm"
          >
            <input
              type="checkbox"
              :checked="filters[field.key]"
              @change="updateFilter(field.key, $event.target.checked)"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span>{{ field.checkboxLabel || field.label }}</span>
          </label>

          <!-- Custom Slot -->
          <div v-else-if="field.type === 'custom'">
            <slot :name="`filter-${field.key}`" :field="field" :value="filters[field.key]" :update="updateFilter"></slot>
          </div>
        </div>

        <!-- Preset Filters -->
        <div v-if="presets && presets.length > 0" class="pt-4 border-t border-gray-200">
          <label class="block text-xs font-medium text-gray-700 mb-2">Quick Filters</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="preset in presets"
              :key="preset.key"
              @click="applyPreset(preset)"
              class="px-3 py-1 text-xs font-medium rounded-full border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              :class="{ 'bg-indigo-100 border-indigo-300 text-indigo-700': isPresetActive(preset) }"
            >
              {{ preset.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-4 border-t border-gray-200 mt-4">
        <div class="text-xs text-gray-500">
          {{ activeFiltersCount }} filter{{ activeFiltersCount !== 1 ? 's' : '' }} active
        </div>
        <div class="flex space-x-2">
          <button
            v-if="hasActiveFilters"
            @click="clearAllFilters"
            class="px-3 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Clear
          </button>
          <button
            @click="applyFilters"
            class="px-3 py-1 text-xs font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700"
          >
            Apply
          </button>
        </div>
      </div>
    </div>

    <!-- Active Filters Summary (when collapsed) -->
    <div v-if="collapsed && hasActiveFilters" class="px-4 py-2 bg-gray-50 border-t border-gray-200">
      <div class="flex flex-wrap gap-1">
        <span
          v-for="(value, key) in activeFilters"
          :key="key"
          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
        >
          {{ getFilterLabel(key) }}: {{ formatFilterValue(key, value) }}
          <button
            @click="clearFilter(key)"
            class="ml-1 text-indigo-600 hover:text-indigo-800"
          >
            <XMarkIcon class="h-3 w-3" />
          </button>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  MagnifyingGlassIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { debounce } from 'lodash'

const props = defineProps({
  title: String,
  modelValue: {
    type: Object,
    default: () => ({})
  },
  filterFields: {
    type: Array,
    default: () => []
  },
  presets: {
    type: Array,
    default: () => []
  },
  showSearch: {
    type: Boolean,
    default: true
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...'
  },
  autoApply: {
    type: Boolean,
    default: false
  },
  debounceMs: {
    type: Number,
    default: 300
  }
})

const emit = defineEmits(['update:modelValue', 'apply', 'clear'])

// Reactive data
const collapsed = ref(false)
const filters = ref({ ...props.modelValue })

// Computed properties
const hasActiveFilters = computed(() => {
  return Object.keys(activeFilters.value).length > 0
})

const activeFilters = computed(() => {
  const active = {}
  Object.entries(filters.value).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '' && 
        !(Array.isArray(value) && value.length === 0)) {
      active[key] = value
    }
  })
  return active
})

const activeFiltersCount = computed(() => {
  return Object.keys(activeFilters.value).length
})

// Methods
const updateFilter = (key, value) => {
  filters.value[key] = value
  emit('update:modelValue', { ...filters.value })
  
  if (props.autoApply) {
    debouncedApply()
  }
}

const updateMultiSelectFilter = (key, value, checked) => {
  if (!filters.value[key]) {
    filters.value[key] = []
  }
  
  if (checked) {
    if (!filters.value[key].includes(value)) {
      filters.value[key].push(value)
    }
  } else {
    const index = filters.value[key].indexOf(value)
    if (index > -1) {
      filters.value[key].splice(index, 1)
    }
  }
  
  emit('update:modelValue', { ...filters.value })
  
  if (props.autoApply) {
    debouncedApply()
  }
}

const clearFilter = (key) => {
  if (key.endsWith('_from') || key.endsWith('_to')) {
    // Handle date range filters
    const baseKey = key.replace(/_from$|_to$/, '')
    delete filters.value[`${baseKey}_from`]
    delete filters.value[`${baseKey}_to`]
  } else {
    delete filters.value[key]
  }
  
  emit('update:modelValue', { ...filters.value })
  
  if (props.autoApply) {
    applyFilters()
  }
}

const clearAllFilters = () => {
  filters.value = {}
  emit('update:modelValue', {})
  emit('clear')
  
  if (props.autoApply) {
    applyFilters()
  }
}

const applyFilters = () => {
  emit('apply', { ...filters.value })
}

const applyPreset = (preset) => {
  filters.value = { ...preset.filters }
  emit('update:modelValue', { ...filters.value })
  
  if (props.autoApply) {
    applyFilters()
  }
}

const isPresetActive = (preset) => {
  return Object.entries(preset.filters).every(([key, value]) => {
    return filters.value[key] === value
  })
}

const getOptionValue = (option) => {
  return typeof option === 'object' ? option.value : option
}

const getOptionLabel = (option) => {
  return typeof option === 'object' ? option.label : option
}

const getFilterLabel = (key) => {
  if (key === 'search') return 'Search'
  
  const field = props.filterFields.find(f => f.key === key || key.startsWith(f.key))
  return field ? field.label : key
}

const formatFilterValue = (key, value) => {
  if (Array.isArray(value)) {
    return value.length > 3 ? `${value.slice(0, 3).join(', ')}...` : value.join(', ')
  }
  
  if (typeof value === 'boolean') {
    return value ? 'Yes' : 'No'
  }
  
  return String(value)
}

// Debounced apply for auto-apply mode
const debouncedApply = debounce(() => {
  applyFilters()
}, props.debounceMs)

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  filters.value = { ...newValue }
}, { deep: true })
</script>