<template>
  <div class="flex flex-col h-full">
    <!-- Board Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">{{ title || 'Kanban Board' }}</h2>
        <p v-if="subtitle" class="text-sm text-gray-600">{{ subtitle }}</p>
      </div>
      <div class="flex items-center space-x-3">
        <slot name="actions"></slot>
        <button
          @click="showColumnModal = true"
          class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-md text-sm font-medium"
        >
          Add Column
        </button>
      </div>
    </div>

    <!-- Board Columns -->
    <div class="flex-1 overflow-x-auto">
      <div class="flex space-x-6 h-full min-w-max pb-6">
        <!-- Column -->
        <div
          v-for="column in columns"
          :key="column.id"
          class="flex-shrink-0 w-80 bg-gray-50 rounded-lg"
        >
          <!-- Column Header -->
          <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <h3 class="font-medium text-gray-900">{{ column.title }}</h3>
                <span class="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded-full">
                  {{ getColumnItemCount(column.id) }}
                </span>
              </div>
              <div class="flex items-center space-x-1">
                <button
                  @click="addItem(column.id)"
                  class="p-1 text-gray-400 hover:text-gray-600 rounded"
                  :title="`Add ${itemType} to ${column.title}`"
                >
                  <PlusIcon class="h-4 w-4" />
                </button>
                <div class="relative" ref="columnDropdownRef">
                  <button
                    @click="toggleColumnDropdown(column.id)"
                    class="p-1 text-gray-400 hover:text-gray-600 rounded"
                  >
                    <EllipsisVerticalIcon class="h-4 w-4" />
                  </button>
                  
                  <!-- Column Dropdown -->
                  <div
                    v-if="activeColumnDropdown === column.id"
                    class="absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200"
                  >
                    <div class="py-1">
                      <button
                        @click="editColumn(column)"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                      >
                        Edit Column
                      </button>
                      <button
                        @click="duplicateColumn(column)"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                      >
                        Duplicate Column
                      </button>
                      <div class="border-t border-gray-100"></div>
                      <button
                        @click="deleteColumn(column)"
                        class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                      >
                        Delete Column
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <p v-if="column.description" class="text-sm text-gray-600 mt-1">{{ column.description }}</p>
          </div>

          <!-- Column Items -->
          <div
            class="p-4 min-h-96 max-h-96 overflow-y-auto"
            @drop="onDrop($event, column.id)"
            @dragover.prevent
            @dragenter.prevent
            :class="{ 'bg-blue-50 border-2 border-dashed border-blue-300': dragOverColumn === column.id }"
          >
            <div class="space-y-3">
              <!-- Item -->
              <div
                v-for="item in getColumnItems(column.id)"
                :key="item.id"
                :draggable="!readonly"
                @dragstart="onDragStart($event, item)"
                @dragend="onDragEnd"
                class="cursor-move"
                :class="{ 'opacity-50': draggedItem?.id === item.id }"
              >
                <!-- Custom Item Slot -->
                <slot name="item" :item="item" :column="column">
                  <!-- Default Item Card -->
                  <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                      <h4 class="text-sm font-medium text-gray-900 line-clamp-2">
                        {{ item.title || item.name }}
                      </h4>
                      <div class="ml-2 flex-shrink-0">
                        <span v-if="item.priority" :class="getPriorityColor(item.priority)" class="w-2 h-2 rounded-full"></span>
                      </div>
                    </div>
                    
                    <p v-if="item.description" class="text-xs text-gray-600 line-clamp-2 mb-3">
                      {{ item.description }}
                    </p>
                    
                    <!-- Item Metadata -->
                    <div class="flex items-center justify-between text-xs text-gray-500">
                      <div class="flex items-center space-x-2">
                        <span v-if="item.assignee">{{ getInitials(item.assignee.name) }}</span>
                        <span v-if="item.due_date" :class="{ 'text-red-600': isOverdue(item.due_date) }">
                          {{ formatDueDate(item.due_date) }}
                        </span>
                      </div>
                      <div class="flex items-center space-x-1">
                        <span v-if="item.comments_count" class="flex items-center">
                          <ChatBubbleLeftIcon class="h-3 w-3 mr-1" />
                          {{ item.comments_count }}
                        </span>
                        <span v-if="item.attachments_count" class="flex items-center">
                          <PaperClipIcon class="h-3 w-3 mr-1" />
                          {{ item.attachments_count }}
                        </span>
                      </div>
                    </div>
                  </div>
                </slot>
              </div>
              
              <!-- Empty Column Message -->
              <div v-if="getColumnItems(column.id).length === 0" class="text-center py-8">
                <div class="text-gray-400">
                  <svg class="mx-auto h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2" />
                  </svg>
                  <p class="text-sm">No {{ itemType }}s</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Add Column Button -->
        <div class="flex-shrink-0 w-80">
          <button
            @click="showColumnModal = true"
            class="w-full h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center text-gray-500 hover:border-gray-400 hover:text-gray-600 transition-colors"
          >
            <div class="text-center">
              <PlusIcon class="h-8 w-8 mx-auto mb-2" />
              <span class="text-sm font-medium">Add Column</span>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Column Modal -->
    <div v-if="showColumnModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ editingColumn ? 'Edit Column' : 'Add Column' }}
          </h3>
          
          <form @submit.prevent="saveColumn">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Title</label>
                <input
                  v-model="columnForm.title"
                  type="text"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea
                  v-model="columnForm.description"
                  rows="2"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Color</label>
                <div class="mt-2 flex space-x-2">
                  <button
                    v-for="color in columnColors"
                    :key="color"
                    type="button"
                    @click="columnForm.color = color"
                    :class="[
                      'w-8 h-8 rounded-full border-2',
                      columnForm.color === color ? 'border-gray-900' : 'border-gray-300',
                      color
                    ]"
                  ></button>
                </div>
              </div>
            </div>
            
            <div class="flex items-center justify-end space-x-3 mt-6">
              <button
                type="button"
                @click="closeColumnModal"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700"
              >
                {{ editingColumn ? 'Update' : 'Create' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
  PlusIcon,
  EllipsisVerticalIcon,
  ChatBubbleLeftIcon,
  PaperClipIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  title: String,
  subtitle: String,
  columns: {
    type: Array,
    required: true
  },
  items: {
    type: Array,
    required: true
  },
  itemType: {
    type: String,
    default: 'item'
  },
  columnKey: {
    type: String,
    default: 'column_id'
  },
  readonly: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits([
  'item-moved',
  'item-add',
  'column-add',
  'column-edit',
  'column-delete'
])

const { showToast } = useToast()

// Reactive data
const draggedItem = ref(null)
const dragOverColumn = ref(null)
const activeColumnDropdown = ref(null)
const showColumnModal = ref(false)
const editingColumn = ref(null)
const columnDropdownRef = ref(null)

const columnForm = ref({
  title: '',
  description: '',
  color: 'bg-gray-100'
})

const columnColors = [
  'bg-gray-100',
  'bg-red-100',
  'bg-yellow-100',
  'bg-green-100',
  'bg-blue-100',
  'bg-indigo-100',
  'bg-purple-100',
  'bg-pink-100'
]

// Computed properties
const getColumnItems = (columnId) => {
  return props.items.filter(item => item[props.columnKey] === columnId)
}

const getColumnItemCount = (columnId) => {
  return getColumnItems(columnId).length
}

// Methods
const onDragStart = (event, item) => {
  if (props.readonly) return
  draggedItem.value = item
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/html', event.target)
}

const onDragEnd = () => {
  draggedItem.value = null
  dragOverColumn.value = null
}

const onDrop = (event, columnId) => {
  if (props.readonly) return
  
  event.preventDefault()
  dragOverColumn.value = null
  
  if (draggedItem.value && draggedItem.value[props.columnKey] !== columnId) {
    emit('item-moved', {
      item: draggedItem.value,
      fromColumn: draggedItem.value[props.columnKey],
      toColumn: columnId
    })
  }
  
  draggedItem.value = null
}

const addItem = (columnId) => {
  emit('item-add', columnId)
}

const toggleColumnDropdown = (columnId) => {
  activeColumnDropdown.value = activeColumnDropdown.value === columnId ? null : columnId
}

const editColumn = (column) => {
  editingColumn.value = column
  columnForm.value = {
    title: column.title,
    description: column.description || '',
    color: column.color || 'bg-gray-100'
  }
  showColumnModal.value = true
  activeColumnDropdown.value = null
}

const duplicateColumn = (column) => {
  const newColumn = {
    ...column,
    title: `${column.title} (Copy)`,
    id: Date.now() // Temporary ID
  }
  emit('column-add', newColumn)
  activeColumnDropdown.value = null
}

const deleteColumn = (column) => {
  if (confirm(`Are you sure you want to delete the "${column.title}" column?`)) {
    emit('column-delete', column.id)
  }
  activeColumnDropdown.value = null
}

const saveColumn = () => {
  if (editingColumn.value) {
    emit('column-edit', {
      ...editingColumn.value,
      ...columnForm.value
    })
  } else {
    emit('column-add', {
      ...columnForm.value,
      id: Date.now() // Temporary ID
    })
  }
  closeColumnModal()
}

const closeColumnModal = () => {
  showColumnModal.value = false
  editingColumn.value = null
  columnForm.value = {
    title: '',
    description: '',
    color: 'bg-gray-100'
  }
}

// Utility functions
const getPriorityColor = (priority) => {
  const colors = {
    low: 'bg-green-400',
    medium: 'bg-yellow-400',
    high: 'bg-orange-400',
    urgent: 'bg-red-400'
  }
  return colors[priority] || 'bg-gray-400'
}

const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const formatDueDate = (date) => {
  if (!date) return ''
  const dueDate = new Date(date)
  const now = new Date()
  const diffTime = dueDate - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) return `${Math.abs(diffDays)}d overdue`
  if (diffDays === 0) return 'Due today'
  if (diffDays === 1) return 'Due tomorrow'
  return `${diffDays}d left`
}

const isOverdue = (date) => {
  if (!date) return false
  return new Date(date) < new Date()
}

// Handle click outside for dropdown
const handleClickOutside = (event) => {
  if (columnDropdownRef.value && !columnDropdownRef.value.contains(event.target)) {
    activeColumnDropdown.value = null
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  
  // Handle drag over for columns
  document.addEventListener('dragover', (event) => {
    if (draggedItem.value) {
      const columnElement = event.target.closest('[data-column-id]')
      if (columnElement) {
        const columnId = columnElement.dataset.columnId
        dragOverColumn.value = columnId
      }
    }
  })
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>