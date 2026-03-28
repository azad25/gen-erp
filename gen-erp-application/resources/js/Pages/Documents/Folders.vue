<template>
  
    <AppLayout :title="$t('documents.folders')">
      <div class="p-6">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ $t('documents.folders') }}
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ $t('documents.manage_folders_description') }}
          </p>
        </div>
        <div class="flex gap-3">
          <button
            @click="showCreateFolder = true"
            class="btn btn-primary"
          >
            <FolderPlusIcon class="w-4 h-4 mr-2" />
            {{ $t('documents.create_folder') }}
          </button>
        </div>
      </div>

      <!-- Search -->
      <div class="mb-6">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="$t('documents.search_folders_placeholder')"
          class="input w-full max-w-md"
          @input="debouncedSearch"
        />
      </div>

      <!-- Folders Tree -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6">
          <div v-if="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          </div>
          
          <div v-else-if="folders.length === 0" class="text-center py-12">
            <FolderIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
              {{ $t('documents.no_folders') }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">
              {{ $t('documents.create_first_folder') }}
            </p>
            <button
              @click="showCreateFolder = true"
              class="btn btn-primary"
            >
              {{ $t('documents.create_folder') }}
            </button>
          </div>

          <div v-else class="space-y-2">
            <FolderTreeItem
              v-for="folder in rootFolders"
              :key="folder.id"
              :folder="folder"
              :all-folders="folders"
              @edit="editFolder"
              @navigate="navigateToFolder"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Create Folder Modal -->
    <CreateFolderModal
      v-if="showCreateFolder"
      @close="showCreateFolder = false"
      @created="handleFolderCreated"
    />

    <!-- Edit Folder Modal -->
    <EditFolderModal
      v-if="editingFolder"
      :folder="editingFolder"
      @close="editingFolder = null"
      @updated="handleFolderUpdated"
    />
  </AppLayout>

</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import AppLayout from '@/Layouts/AppLayout.vue'
import CreateFolderModal from '@/Components/Documents/CreateFolderModal.vue'
import EditFolderModal from '@/Components/Documents/EditFolderModal.vue'
import FolderTreeItem from '@/Components/Documents/FolderTreeItem.vue'
import {
  FolderIcon,
  FolderPlusIcon,
} from '@heroicons/vue/24/outline'
import { debounce } from 'lodash'

// Props
const props = defineProps({
  company: {
    type: Object,
    default: () => ({})
  }
})

const { $t } = useTranslations()

// State
const loading = ref(false)
const folders = ref([])
const searchQuery = ref('')
const showCreateFolder = ref(false)
const editingFolder = ref(null)

// Computed
const rootFolders = computed(() => {
  return folders.value.filter(folder => !folder.parent_id)
})

// Methods
const loadFolders = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/v1/document-folders', {
      params: {
        search: searchQuery.value || undefined,
        per_page: 100
      }
    })
    folders.value = response.data.data.data || []
  } catch (error) {
    console.error('Error loading folders:', error)
  } finally {
    loading.value = false
  }
}

const editFolder = (folder) => {
  editingFolder.value = folder
}

const navigateToFolder = (folderId) => {
  // Navigate to main documents page with folder selected
  window.location.href = `/documents?folder=${folderId}`
}

const handleFolderCreated = () => {
  showCreateFolder.value = false
  loadFolders()
}

const handleFolderUpdated = () => {
  editingFolder.value = null
  loadFolders()
}

const debouncedSearch = debounce(() => {
  loadFolders()
}, 300)

// Lifecycle
onMounted(() => {
  loadFolders()
})
</script>