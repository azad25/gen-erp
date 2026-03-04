<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-900">Advanced Filters</h3>
        <div class="flex items-center space-x-2">
          <button
            v-if="hasActiveFilters"
            @click="clearAllFilters"
            class="text-xs text-red-600 hover:text-red-500"
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

    <!-- Content -->
    <div v-if="!collapsed" class="p-4">
      <!-- Quick Filters -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Quick Filters</h4>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="quickFilter in quickFilters"
            :key="quickFilter.id"
            @click="applyQuickFilter(quickFilter)"
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
            :class="isQuickFilterActive(quickFilter) ? 
              'bg-indigo-100 text-indigo-800' : 
              'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          >
            {{ quickFilter.label }}
            <span v-if="quickFilter.count" class="ml-1 text-xs">({{ quickFilter.count }})</span>
          </button>
        </div>
      </div>

      <!-- Filter Groups -->
      <div class="space-y-4">
        <!-- Status Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <div class="flex flex-wrap gap-2">
            <label
              v-for="status in statusOptions"
              :key="status.value"
              class="flex items-center"
            >
              <input
                v-model="filters.status"
                :value="status.value"
                type="checkbox"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              />
              <span class="ml-2 text-sm text-gray-700">{{ status.label }}</span>
            </label>
          </div>
        </div>

        <!-- Score Range -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Lead Score</label>
          <div class="flex items-center space-x-3">
            <input
              v-model.number="filters.score_min"
              type="number"
              min="0"
              max="100"
              placeholder="Min"
              class="w-20 text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            />
            <span class="text-gray-500">to</span>
            <input
              v-model.number="filters.score_max"
              type="number"
              min="0"
              max="100"
              placeholder="Max"
              class="w-20 text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
        </div>

        <!-- Date Filters -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <select
                v-model="filters.date_field"
                class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="created_at">Created Date</option>
                <option value="updated_at">Last Updated</option>
                <option value="last_contacted">Last Contacted</option>
              </select>
            </div>
            <div>
              <select
                v-model="filters.date_range"
                class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="">Any time</option>
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="this_week">This week</option>
                <option value="last_week">Last week</option>
                <option value="this_month">This month</option>
                <option value="last_month">Last month</option>
                <option value="custom">Custom range</option>
              </select>
            </div>
          </div>
          
          <!-- Custom Date Range -->
          <div v-if="filters.date_range === 'custom'" class="mt-2 grid grid-cols-2 gap-3">
            <input
              v-model="filters.date_from"
              type="date"
              class="text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            />
            <input
              v-model="filters.date_to"
              type="date"
              class="text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
        </div>

        <!-- Source Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Lead Source</label>
          <select
            v-model="filters.source"
            multiple
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            size="4"
          >
            <option
              v-for="source in sourceOptions"
              :key="source.value"
              :value="source.value"
            >
              {{ source.label }} ({{ source.count }})
            </option>
          </select>
        </div>

        <!-- Assignee Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Assigned To</label>
          <select
            v-model="filters.assigned_to"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">Anyone</option>
            <option value="unassigned">Unassigned</option>
            <option value="me">Me</option>
            <option
              v-for="user in teamMembers"
              :key="user.id"
              :value="user.id"
            >
              {{ user.name }}
            </option>
          </select>
        </div>

        <!-- Tags Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
          <div class="flex flex-wrap gap-1 mb-2">
            <span
              v-for="tag in filters.tags"
              :key="tag"
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            >
              {{ tag }}
              <button
                @click="removeTag(tag)"
                class="ml-1 text-blue-600 hover:text-blue-800"
              >
                <XMarkIcon class="h-3 w-3" />
              </button>
            </span>
          </div>
          <input
            v-model="tagInput"
            type="text"
            placeholder="Type tag and press Enter"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            @keydown.enter="addTag"
          />
        </div>

        <!-- Custom Fields -->
        <div v-if="customFields.length > 0">
          <label class="block text-sm font-medium text-gray-700 mb-2">Custom Fields</label>
          <div class="space-y-3">
            <div
              v-for="field in customFields"
              :key="field.id"
              class="flex items-center space-x-3"
            >
              <span class="text-sm text-gray-600 w-24">{{ field.label }}:</span>
              <input
                v-if="field.type === 'text'"
                v-model="filters.custom_fields[field.id]"
                type="text"
                :placeholder="field.placeholder"
                class="flex-1 text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              />
              <select
                v-else-if="field.type === 'select'"
                v-model="filters.custom_fields[field.id]"
                class="flex-1 text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="">Any</option>
                <option
                  v-for="option in field.options"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Saved Filters -->
      <div class="mt-6 pt-4 border-t border-gray-200">
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-sm font-medium text-gray-900">Saved Filters</h4>
          <button
            @click="showSaveFilterModal = true"
            :disabled="!hasActiveFilters"
            class="text-xs text-indigo-600 hover:text-indigo-500 disabled:text-gray-400"
          >
            Save Current
          </button>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="savedFilter in savedFilters"
            :key="savedFilter.id"
            @click="applySavedFilter(savedFilter)"
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200"
          >
            {{ savedFilter.name }}
            <button
              @click.stop="deleteSavedFilter(savedFilter.id)"
              class="ml-1 text-gray-500 hover:text-red-600"
            >
              <XMarkIcon class="h-3 w-3" />
            </button>
          </button>
        </div>
      </div>

      <!-- Apply Filters -->
      <div class="mt-6 pt-4 border-t border-gray-200">
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-500">
            {{ filteredCount }} leads match your filters
          </span>
          <div class="flex space-x-2">
            <button
              @click="resetFilters"
              class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 px-4 rounded-md"
            >
              Reset
            </button>
            <button
              @click="applyFilters"
              :disabled="loading"
              class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-sm font-medium py-2 px-4 rounded-md"
            >
              {{ loading ? 'Applying...' : 'Apply Filters' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Save Filter Modal -->
    <SaveFilterModal
      v-if="showSaveFilterModal"
      :filters="filters"
      @close="showSaveFilterModal = false"
      @saved="handleFilterSaved"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import {
  ChevronUpIcon,
  ChevronDownIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'
import { useAuth } from '@/Composables/useAuth'
import SaveFilterModal from './SaveFilterModal.vue'

const props = defineProps({
  initialFilters: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['filters-changed'])

const { get, post, delete: del, loading } = useApi()
const { user: currentUser } = useAuth()

// Reactive data
const collapsed = ref(false)
const tagInput = ref('')
const showSaveFilterModal = ref(false)
const filteredCount = ref(0)

const filters = reactive({
  status: [],
  score_min: null,
  score_max: null,
  date_field: 'created_at',
  date_range: '',
  date_from: '',
  date_to: '',
  source: [],
  assigned_to: '',
  tags: [],
  custom_fields: {}
})

const quickFilters = ref([
  { id: 'hot_leads', label: 'Hot Leads', filters: { score_min: 80 }, count: 0 },
  { id: 'new_leads', label: 'New Leads', filters: { status: ['new'] }, count: 0 },
  { id: 'my_leads', label: 'My Leads', filters: { assigned_to: 'me' }, count: 0 },
  { id: 'unassigned', label: 'Unassigned', filters: { assigned_to: 'unassigned' }, count: 0 },
  { id: 'this_week', label: 'This Week', filters: { date_range: 'this_week' }, count: 0 }
])

const statusOptions = ref([
  { value: 'new', label: 'New' },
  { value: 'contacted', label: 'Contacted' },
  { value: 'qualified', label: 'Qualified' },
  { value: 'proposal', label: 'Proposal' },
  { value: 'negotiation', label: 'Negotiation' },
  { value: 'closed_won', label: 'Closed Won' },
  { value: 'closed_lost', label: 'Closed Lost' }
])

const sourceOptions = ref([])
const teamMembers = ref([])
const customFields = ref([])
const savedFilters = ref([])

// Computed properties
const hasActiveFilters = computed(() => {
  return filters.status.length > 0 ||
         filters.score_min !== null ||
         filters.score_max !== null ||
         filters.date_range !== '' ||
         filters.source.length > 0 ||
         filters.assigned_to !== '' ||
         filters.tags.length > 0 ||
         Object.keys(filters.custom_fields).some(key => filters.custom_fields[key])
})

// Methods
const fetchFilterOptions = async () => {
  try {
    const [sourcesData, membersData, fieldsData, savedData] = await Promise.all([
      get('/api/v1/crm/leads/sources'),
      get('/api/v1/users/team-members'),
      get('/api/v1/crm/custom-fields'),
      get('/api/v1/crm/saved-filters')
    ])
    
    sourceOptions.value = sourcesData.data
    teamMembers.value = membersData.data
    customFields.value = fieldsData.data
    savedFilters.value = savedData.data
  } catch (err) {
    console.error('Failed to fetch filter options:', err)
  }
}

const updateFilterCounts = async () => {
  try {
    const data = await get('/api/v1/crm/leads/filter-counts')
    
    // Update quick filter counts
    quickFilters.value.forEach(filter => {
      filter.count = data.data[filter.id] || 0
    })
    
    filteredCount.value = data.data.total || 0
  } catch (err) {
    console.error('Failed to update filter counts:', err)
  }
}

const applyQuickFilter = (quickFilter) => {
  // Reset filters first
  resetFilters()
  
  // Apply quick filter
  Object.assign(filters, quickFilter.filters)
  
  // Apply filters
  applyFilters()
}

const isQuickFilterActive = (quickFilter) => {
  return Object.keys(quickFilter.filters).every(key => {
    const filterValue = filters[key]
    const quickValue = quickFilter.filters[key]
    
    if (Array.isArray(filterValue)) {
      return Array.isArray(quickValue) && 
             quickValue.every(v => filterValue.includes(v))
    }
    
    return filterValue === quickValue
  })
}

const addTag = () => {
  if (tagInput.value.trim() && !filters.tags.includes(tagInput.value.trim())) {
    filters.tags.push(tagInput.value.trim())
    tagInput.value = ''
  }
}

const removeTag = (tag) => {
  const index = filters.tags.indexOf(tag)
  if (index > -1) {
    filters.tags.splice(index, 1)
  }
}

const applyFilters = () => {
  emit('filters-changed', { ...filters })
  updateFilterCounts()
}

const resetFilters = () => {
  Object.assign(filters, {
    status: [],
    score_min: null,
    score_max: null,
    date_field: 'created_at',
    date_range: '',
    date_from: '',
    date_to: '',
    source: [],
    assigned_to: '',
    tags: [],
    custom_fields: {}
  })
}

const clearAllFilters = () => {
  resetFilters()
  applyFilters()
}

const applySavedFilter = (savedFilter) => {
  Object.assign(filters, savedFilter.filters)
  applyFilters()
}

const deleteSavedFilter = async (filterId) => {
  if (!confirm('Delete this saved filter?')) return
  
  try {
    await del(`/api/v1/crm/saved-filters/${filterId}`)
    const index = savedFilters.value.findIndex(f => f.id === filterId)
    if (index > -1) {
      savedFilters.value.splice(index, 1)
    }
  } catch (err) {
    console.error('Failed to delete saved filter:', err)
  }
}

const handleFilterSaved = (savedFilter) => {
  savedFilters.value.push(savedFilter)
  showSaveFilterModal.value = false
}

// Watch for filter changes to update counts
watch(() => filters, updateFilterCounts, { deep: true })

// Lifecycle
onMounted(() => {
  fetchFilterOptions()
  updateFilterCounts()
  
  // Apply initial filters if provided
  if (props.initialFilters) {
    Object.assign(filters, props.initialFilters)
  }
})
</script>