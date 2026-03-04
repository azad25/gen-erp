<template>
  <div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <!-- Header -->
    <div v-if="title || $slots.header" class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div>
          <h3 v-if="title" class="text-lg font-medium text-gray-900">{{ title }}</h3>
          <slot name="header"></slot>
        </div>
        <div class="flex items-center space-x-3">
          <slot name="actions"></slot>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div v-if="$slots.filters" class="px-6 py-4 bg-gray-50 border-b border-gray-200">
      <slot name="filters"></slot>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      <span class="ml-2 text-gray-600">Loading...</span>
    </div>

    <!-- Empty State -->
    <div v-else-if="data.length === 0" class="text-center py-12">
      <slot name="empty">
        <div class="text-gray-500">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-2-2m0 0l-2 2m2-2v6m-6-4h.01M12 16h.01" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No data found</h3>
          <p class="mt-1 text-sm text-gray-500">{{ emptyMessage || 'No items to display.' }}</p>
        </div>
      </slot>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <!-- Table Header -->
        <thead class="bg-gray-50">
          <tr>
            <!-- Select All Checkbox -->
            <th v-if="selectable" class="px-6 py-3 text-left">
              <input
                type="checkbox"
                :checked="isAllSelected"
                @change="toggleSelectAll"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
            </th>
            
            <!-- Column Headers -->
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              :class="{ 'cursor-pointer hover:bg-gray-100': column.sortable }"
              @click="column.sortable ? handleSort(column.key) : null"
            >
              <div class="flex items-center space-x-1">
                <span>{{ column.label }}</span>
                <div v-if="column.sortable" class="flex flex-col">
                  <ChevronUpIcon 
                    class="h-3 w-3" 
                    :class="{ 
                      'text-indigo-600': sortBy === column.key && sortOrder === 'asc',
                      'text-gray-400': sortBy !== column.key || sortOrder !== 'asc'
                    }" 
                  />
                  <ChevronDownIcon 
                    class="h-3 w-3 -mt-1" 
                    :class="{ 
                      'text-indigo-600': sortBy === column.key && sortOrder === 'desc',
                      'text-gray-400': sortBy !== column.key || sortOrder !== 'desc'
                    }" 
                  />
                </div>
              </div>
            </th>
            
            <!-- Actions Column -->
            <th v-if="$slots.actions || showActions" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        
        <!-- Table Body -->
        <tbody class="bg-white divide-y divide-gray-200">
          <tr 
            v-for="(item, index) in data" 
            :key="getItemKey(item, index)"
            class="hover:bg-gray-50 transition-colors"
            :class="{ 'bg-indigo-50': selectedItems.includes(getItemKey(item, index)) }"
          >
            <!-- Select Checkbox -->
            <td v-if="selectable" class="px-6 py-4 whitespace-nowrap">
              <input
                type="checkbox"
                :value="getItemKey(item, index)"
                v-model="selectedItems"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
            </td>
            
            <!-- Data Columns -->
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 whitespace-nowrap"
              :class="column.class || 'text-sm text-gray-900'"
            >
              <!-- Custom Column Slot -->
              <slot 
                v-if="$slots[`column-${column.key}`]" 
                :name="`column-${column.key}`" 
                :item="item" 
                :value="getNestedValue(item, column.key)"
                :index="index"
              ></slot>
              
              <!-- Default Column Rendering -->
              <div v-else>
                <!-- Boolean Values -->
                <span v-if="typeof getNestedValue(item, column.key) === 'boolean'">
                  <span 
                    :class="getNestedValue(item, column.key) ? 'text-green-600' : 'text-red-600'"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="getNestedValue(item, column.key) ? 'bg-green-100' : 'bg-red-100'"
                  >
                    {{ getNestedValue(item, column.key) ? 'Yes' : 'No' }}
                  </span>
                </span>
                
                <!-- Date Values -->
                <span v-else-if="column.type === 'date'">
                  {{ formatDate(getNestedValue(item, column.key)) }}
                </span>
                
                <!-- Currency Values -->
                <span v-else-if="column.type === 'currency'">
                  {{ formatCurrency(getNestedValue(item, column.key)) }}
                </span>
                
                <!-- Badge Values -->
                <span v-else-if="column.type === 'badge'">
                  <span 
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="getBadgeClass(getNestedValue(item, column.key), column.badgeColors)"
                  >
                    {{ formatBadgeText(getNestedValue(item, column.key)) }}
                  </span>
                </span>
                
                <!-- Default Text -->
                <span v-else>
                  {{ getNestedValue(item, column.key) || '-' }}
                </span>
              </div>
            </td>
            
            <!-- Actions Column -->
            <td v-if="$slots.actions || showActions" class="px-6 py-4 whitespace-nowrap text-sm font-medium">
              <slot name="actions" :item="item" :index="index">
                <div class="flex items-center space-x-2">
                  <button
                    @click="$emit('view', item)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    View
                  </button>
                  <button
                    @click="$emit('edit', item)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Edit
                  </button>
                  <button
                    @click="$emit('delete', item)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Delete
                  </button>
                </div>
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectable && selectedItems.length > 0" class="bg-gray-50 px-6 py-3 border-t border-gray-200">
      <div class="flex items-center justify-between">
        <span class="text-sm text-gray-700">{{ selectedItems.length }} item(s) selected</span>
        <div class="flex space-x-2">
          <slot name="bulk-actions" :selected="selectedItems">
            <button
              @click="$emit('bulk-delete', selectedItems)"
              class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm"
            >
              Delete Selected
            </button>
          </slot>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.total > pagination.per_page" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
        </div>
        <div class="flex space-x-2">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="$emit('page-change', page)"
            :disabled="page === pagination.current_page"
            :class="[
              'px-3 py-2 text-sm font-medium rounded-md',
              page === pagination.current_page
                ? 'bg-indigo-600 text-white'
                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  title: String,
  data: {
    type: Array,
    default: () => []
  },
  columns: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  selectable: {
    type: Boolean,
    default: false
  },
  showActions: {
    type: Boolean,
    default: true
  },
  pagination: {
    type: Object,
    default: null
  },
  emptyMessage: String,
  itemKey: {
    type: String,
    default: 'id'
  }
})

const emit = defineEmits([
  'sort',
  'page-change',
  'view',
  'edit',
  'delete',
  'bulk-delete',
  'selection-change'
])

const selectedItems = ref([])
const sortBy = ref('')
const sortOrder = ref('asc')

// Computed properties
const isAllSelected = computed(() => {
  return props.data.length > 0 && selectedItems.value.length === props.data.length
})

const visiblePages = computed(() => {
  if (!props.pagination) return []
  
  const current = props.pagination.current_page
  const last = props.pagination.last_page
  const pages = []
  
  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i)
  }
  
  return pages
})

// Methods
const getItemKey = (item, index) => {
  return item[props.itemKey] || index
}

const getNestedValue = (obj, path) => {
  return path.split('.').reduce((current, key) => current?.[key], obj)
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedItems.value = []
  } else {
    selectedItems.value = props.data.map((item, index) => getItemKey(item, index))
  }
}

const handleSort = (column) => {
  if (sortBy.value === column) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = column
    sortOrder.value = 'asc'
  }
  
  emit('sort', { column, order: sortOrder.value })
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  if (!amount) return '-'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const getBadgeClass = (value, colors = {}) => {
  const defaultColors = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-red-100 text-red-800',
    pending: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-blue-100 text-blue-800'
  }
  
  const colorMap = { ...defaultColors, ...colors }
  return colorMap[value] || 'bg-gray-100 text-gray-800'
}

const formatBadgeText = (value) => {
  if (!value) return '-'
  return value.toString().replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

// Watch for selection changes
watch(selectedItems, (newSelection) => {
  emit('selection-change', newSelection)
}, { deep: true })
</script>