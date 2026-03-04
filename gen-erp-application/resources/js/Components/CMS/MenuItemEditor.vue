<template>
  <div
    :class="{
      'bg-blue-50 border-blue-200': isSelected,
      'bg-white border-gray-200': !isSelected
    }"
    class="border-b transition-colors"
  >
    <!-- Main Item -->
    <div
      @click="selectItem"
      :style="{ paddingLeft: `${level * 20 + 16}px` }"
      class="flex items-center justify-between py-3 pr-4 cursor-pointer hover:bg-gray-50"
    >
      <!-- Drag Handle -->
      <div class="flex items-center space-x-3">
        <div class="cursor-move text-gray-400 hover:text-gray-600">
          <Icon name="heroicons:bars-3" class="w-4 h-4" />
        </div>
        
        <!-- Expand/Collapse Button -->
        <button
          v-if="item.children && item.children.length > 0"
          @click.stop="toggleExpanded"
          class="text-gray-400 hover:text-gray-600"
        >
          <Icon
            :name="isExpanded ? 'heroicons:chevron-down' : 'heroicons:chevron-right'"
            class="w-4 h-4"
          />
        </button>
        <div v-else class="w-4"></div>
        
        <!-- Item Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center space-x-2">
            <span
              :class="{
                'text-gray-900 font-medium': isSelected,
                'text-gray-700': !isSelected,
                'opacity-50': !item.is_visible
              }"
              class="text-sm truncate"
            >
              {{ item.label }}
            </span>
            
            <!-- Visibility Indicator -->
            <Icon
              v-if="!item.is_visible"
              name="heroicons:eye-slash"
              class="w-4 h-4 text-gray-400"
              title="Hidden"
            />
          </div>
          
          <p class="text-xs text-gray-500 truncate mt-1">
            {{ item.url }}
          </p>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="flex items-center space-x-1">
        <!-- Add Child -->
        <button
          @click.stop="$emit('add-child', item.id)"
          class="p-1 text-gray-400 hover:text-blue-600 transition-colors"
          title="Add Child Item"
        >
          <Icon name="heroicons:plus" class="w-4 h-4" />
        </button>
        
        <!-- Edit -->
        <button
          @click.stop="selectItem"
          class="p-1 text-gray-400 hover:text-blue-600 transition-colors"
          title="Edit Item"
        >
          <Icon name="heroicons:pencil" class="w-4 h-4" />
        </button>
        
        <!-- Delete -->
        <button
          @click.stop="deleteItem"
          class="p-1 text-gray-400 hover:text-red-600 transition-colors"
          title="Delete Item"
        >
          <Icon name="heroicons:trash" class="w-4 h-4" />
        </button>
      </div>
    </div>
    
    <!-- Child Items -->
    <div v-if="isExpanded && item.children && item.children.length > 0">
      <draggable
        v-model="item.children"
        group="menu-items"
        item-key="id"
        @change="onChildReorder"
      >
        <template #item="{ element: childItem, index: childIndex }">
          <MenuItemEditor
            :key="childItem.id"
            :item="childItem"
            :index="childIndex"
            :level="level + 1"
            :selected-item="selectedItem"
            @update="$emit('update', $event, arguments[1])"
            @delete="$emit('delete', $event)"
            @add-child="$emit('add-child', $event)"
            @select="$emit('select', $event)"
          />
        </template>
      </draggable>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import draggable from 'vuedraggable'
import Icon from '@/Components/UI/Icon.vue'

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  index: {
    type: Number,
    required: true
  },
  level: {
    type: Number,
    default: 0
  },
  selectedItem: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update', 'delete', 'add-child', 'select'])

const isExpanded = ref(true)

const isSelected = computed(() => {
  return props.selectedItem?.id === props.item.id
})

// Methods
const selectItem = () => {
  emit('select', props.item)
}

const deleteItem = () => {
  if (confirm('Are you sure you want to delete this menu item and all its children?')) {
    emit('delete', props.item.id)
  }
}

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
}

const onChildReorder = () => {
  if (props.item.children) {
    props.item.children.forEach((child, index) => {
      child.sort_order = index
    })
  }
}
</script>

<style scoped>
/* Ensure proper nesting visual hierarchy */
.menu-item-editor {
  position: relative;
}

.menu-item-editor::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 2px;
  background-color: #e5e7eb;
}

.menu-item-editor.level-1::before {
  left: 20px;
}

.menu-item-editor.level-2::before {
  left: 40px;
}

.menu-item-editor.level-3::before {
  left: 60px;
}
</style>