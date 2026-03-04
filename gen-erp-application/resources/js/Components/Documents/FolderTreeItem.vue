<template>
  <div class="folder-tree-item">
    <div
      class="flex items-center p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group cursor-pointer"
      @click="toggleExpanded"
    >
      <!-- Expand/Collapse Icon -->
      <button
        v-if="hasChildren"
        class="mr-2 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        @click.stop="toggleExpanded"
      >
        <ChevronRightIcon
          :class="[
            'w-4 h-4 transition-transform duration-200',
            { 'rotate-90': isExpanded }
          ]"
        />
      </button>
      <div v-else class="w-6 mr-2"></div>

      <!-- Folder Icon -->
      <FolderIcon class="w-5 h-5 text-blue-500 mr-3 flex-shrink-0" />

      <!-- Folder Info -->
      <div class="flex-1 min-w-0" @click.stop="$emit('navigate', folder.id)">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
          {{ folder.name }}
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          {{ folder.documents_count || 0 }} {{ $t('documents.files') }}
          <span v-if="folder.description"> • {{ folder.description }}</span>
        </p>
      </div>

      <!-- Actions -->
      <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
        <button
          @click.stop="$emit('edit', folder)"
          class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
          :title="$t('documents.edit_folder')"
        >
          <PencilIcon class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Children -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-96"
      leave-active-class="transition-all duration-300 ease-in"
      leave-from-class="opacity-100 max-h-96"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-if="isExpanded && hasChildren" class="ml-8 mt-2 space-y-1 overflow-hidden">
        <FolderTreeItem
          v-for="child in children"
          :key="child.id"
          :folder="child"
          :all-folders="allFolders"
          @edit="$emit('edit', $event)"
          @navigate="$emit('navigate', $event)"
        />
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import {
  FolderIcon,
  ChevronRightIcon,
  PencilIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  folder: {
    type: Object,
    required: true
  },
  allFolders: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['edit', 'navigate'])

const { $t } = useTranslations()

// State
const isExpanded = ref(false)

// Computed
const children = computed(() => {
  return props.allFolders.filter(f => f.parent_id === props.folder.id)
})

const hasChildren = computed(() => {
  return children.value.length > 0
})

// Methods
const toggleExpanded = () => {
  if (hasChildren.value) {
    isExpanded.value = !isExpanded.value
  }
}
</script>