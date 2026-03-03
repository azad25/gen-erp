<template>
  <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <!-- Toolbar -->
    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
      <div class="flex items-center gap-3">
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <input 
            v-model="searchQuery" 
            @input="handleSearch"
            :placeholder="placeholder" 
            class="block w-64 rounded-lg border border-gray-300 bg-gray-50 pl-10 pr-3 py-2 text-sm text-gray-900 focus:border-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary dark:focus:ring-primary"
          />
          <button 
            v-if="searchQuery" 
            @click="clearSearch"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <slot name="filters" />
      </div>
      <div class="flex items-center gap-2">
        <slot name="toolbar" />
        <Button v-if="createRoute" :href="route(createRoute)" size="sm" class="bg-primary text-white hover:bg-primary/90">
          <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ createLabel }}
        </Button>
      </div>
    </div>

    <!-- Table -->
    <div class="max-w-full overflow-x-auto">
      <table class="min-w-full">
        <thead>
          <tr class="border-b border-gray-200 dark:border-gray-700">
            <th v-if="selectable" class="w-12 px-6 py-3">
              <input 
                type="checkbox" 
                @change="toggleSelectAll"
                :checked="isAllSelected" 
                :indeterminate="isSomeSelected"
                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-700"
              />
            </th>
            <th 
              v-for="col in columns" 
              :key="col.key" 
              @click="col.sortable !== false && sortBy(col.key)"
              class="px-6 py-3 text-left"
              :class="[
                col.sortable !== false ? 'cursor-pointer select-none hover:bg-gray-50 dark:hover:bg-gray-800/50' : '',
                col.right ? 'text-right' : 'text-left'
              ]"
            >
              <div class="flex items-center gap-2" :class="col.right ? 'justify-end' : 'justify-start'">
                <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                  {{ col.label }}
                </span>
                <div v-if="col.sortable !== false" class="flex flex-col">
                  <svg 
                    class="h-3 w-3 text-gray-400"
                    :class="sortKey === col.key && sortDirection === 'asc' ? 'text-primary' : ''"
                    fill="currentColor" viewBox="0 0 20 20"
                  >
                    <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" />
                  </svg>
                  <svg 
                    class="h-3 w-3 text-gray-400 -mt-1"
                    :class="sortKey === col.key && sortDirection === 'desc' ? 'text-primary' : ''"
                    fill="currentColor" viewBox="0 0 20 20"
                  >
                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                  </svg>
                </div>
              </div>
            </th>
            <th v-if="$slots.actions" class="w-32 px-6 py-3">
              <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-if="!rows?.length" class="bg-white dark:bg-gray-900">
            <td :colspan="totalColumns" class="px-6 py-16 text-center">
              <div class="flex flex-col items-center justify-center">
                <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm font-medium text-gray-900 dark:text-white">No records found</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Get started by creating a new record.</p>
              </div>
            </td>
          </tr>
          <tr 
            v-else 
            v-for="(row, i) in rows" 
            :key="row.id || i"
            class="bg-white hover:bg-gray-50 dark:bg-gray-900 dark:hover:bg-gray-800/50 transition-colors"
            :class="onRowClick ? 'cursor-pointer' : ''"
            @click="onRowClick && onRowClick(row)"
          >
            <td v-if="selectable" class="px-6 py-4">
              <input 
                type="checkbox" 
                :value="row.id" 
                v-model="selectedRows"
                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary dark:border-gray-600 dark:bg-gray-700"
                @click.stop
              />
            </td>
            <td 
              v-for="col in columns" 
              :key="col.key"
              class="px-6 py-4"
              :class="[
                col.mono ? 'font-mono text-xs' : 'text-sm',
                col.bold ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300',
                col.right ? 'text-right' : 'text-left'
              ]"
            >
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                {{ row[col.key] ?? '—' }}
              </slot>
            </td>
            <td v-if="$slots.actions" class="px-6 py-4">
              <div class="flex items-center justify-end gap-1">
                <slot name="actions" :row="row" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.total > 0" class="flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-gray-700">
      <div class="flex items-center">
        <p class="text-sm text-gray-700 dark:text-gray-300">
          Showing 
          <span class="font-medium text-gray-900 dark:text-white">{{ pagination.from }}</span>
          to 
          <span class="font-medium text-gray-900 dark:text-white">{{ pagination.to }}</span>
          of 
          <span class="font-medium text-gray-900 dark:text-white">{{ pagination.total }}</span>
          results
        </p>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="goToPage(pagination.current_page - 1)"
          :disabled="pagination.current_page <= 1"
          class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        
        <template v-for="page in paginationPages" :key="page">
          <button
            v-if="page !== '...'"
            @click="goToPage(page)"
            :class="[
              'relative inline-flex items-center px-4 py-2 text-sm font-medium border',
              page === pagination.current_page
                ? 'bg-primary border-primary text-white'
                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700'
            ]"
          >
            {{ page }}
          </button>
          <span v-else class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-400">
            ...
          </span>
        </template>
        
        <button
          @click="goToPage(pagination.current_page + 1)"
          :disabled="pagination.current_page >= pagination.last_page"
          class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import Button from './Button.vue'

const props = defineProps({
  columns: Array,
  rows: Array,
  pagination: Object,
  placeholder: { type: String, default: 'Search records...' },
  createRoute: String,
  createLabel: { type: String, default: 'New Record' },
  selectable: Boolean,
  onRowClick: Function,
})

const emit = defineEmits(['search', 'sort', 'select'])

const searchQuery = ref('')
const sortKey = ref('')
const sortDirection = ref('asc')
const selectedRows = ref([])

const totalColumns = computed(() => {
  let count = props.columns?.length || 0
  if (props.selectable) count++
  if (props.$slots?.actions) count++
  return count
})

const isAllSelected = computed(() => {
  return props.rows?.length > 0 && selectedRows.value.length === props.rows.length
})

const isSomeSelected = computed(() => {
  return selectedRows.value.length > 0 && selectedRows.value.length < (props.rows?.length || 0)
})

const paginationPages = computed(() => {
  if (!props.pagination) return []
  
  const current = props.pagination.current_page
  const last = props.pagination.last_page
  const pages = []
  
  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) {
        pages.push(i)
      }
      pages.push('...')
      pages.push(last)
    } else if (current >= last - 3) {
      pages.push(1)
      pages.push('...')
      for (let i = last - 4; i <= last; i++) {
        pages.push(i)
      }
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i)
      }
      pages.push('...')
      pages.push(last)
    }
  }
  
  return pages
})

const handleSearch = () => {
  emit('search', searchQuery.value)
}

const clearSearch = () => {
  searchQuery.value = ''
  emit('search', '')
}

const sortBy = (key) => {
  if (sortKey.value === key) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDirection.value = 'asc'
  }
  emit('sort', { key, direction: sortDirection.value })
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedRows.value = []
  } else {
    selectedRows.value = props.rows?.map(row => row.id) || []
  }
  emit('select', selectedRows.value)
}

const goToPage = (page) => {
  if (page >= 1 && page <= (props.pagination?.last_page || 1)) {
    const url = new URL(window.location)
    url.searchParams.set('page', page)
    router.get(url.toString())
  }
}

watch(selectedRows, (newValue) => {
  emit('select', newValue)
}, { deep: true })
</script>
