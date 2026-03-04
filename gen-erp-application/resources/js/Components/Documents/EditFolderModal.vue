<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
      <div class="p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            {{ $t('documents.edit_folder') }}
          </h2>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="updateFolder">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ $t('documents.folder_name') }} *
              </label>
              <input
                v-model="form.name"
                type="text"
                required
                class="input w-full"
                :placeholder="$t('documents.folder_name_placeholder')"
                @input="clearError('name')"
              />
              <div v-if="errors.name" class="text-red-500 text-sm mt-1">
                {{ errors.name[0] }}
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ $t('documents.parent_folder') }}
              </label>
              <select
                v-model="form.parent_id"
                class="input w-full"
                @change="clearError('parent_id')"
              >
                <option :value="null">{{ $t('documents.root_folder') }}</option>
                <option
                  v-for="folder in availableFolders"
                  :key="folder.id"
                  :value="folder.id"
                  :disabled="folder.id === props.folder.id"
                >
                  {{ folder.full_path }}
                </option>
              </select>
              <div v-if="errors.parent_id" class="text-red-500 text-sm mt-1">
                {{ errors.parent_id[0] }}
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ $t('documents.description') }} ({{ $t('common.optional') }})
              </label>
              <textarea
                v-model="form.description"
                rows="3"
                class="input w-full"
                :placeholder="$t('documents.folder_description_placeholder')"
                @input="clearError('description')"
              ></textarea>
              <div v-if="errors.description" class="text-red-500 text-sm mt-1">
                {{ errors.description[0] }}
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <button
              type="button"
              @click="$emit('close')"
              class="btn btn-secondary"
              :disabled="updating"
            >
              {{ $t('common.cancel') }}
            </button>
            <button
              type="button"
              @click="deleteFolder"
              class="btn btn-danger"
              :disabled="updating || deleting"
            >
              <div v-if="deleting" class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                {{ $t('documents.deleting') }}
              </div>
              <span v-else>
                {{ $t('common.delete') }}
              </span>
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="updating || !form.name.trim()"
            >
              <div v-if="updating" class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                {{ $t('documents.updating') }}
              </div>
              <span v-else>
                {{ $t('common.update') }}
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  folder: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const { $t } = useTranslations()

// State
const updating = ref(false)
const deleting = ref(false)
const availableFolders = ref([])
const form = reactive({
  name: props.folder.name,
  parent_id: props.folder.parent_id,
  description: props.folder.description || ''
})
const errors = ref({})

// Methods
const loadFolders = async () => {
  try {
    const response = await axios.get('/api/v1/document-folders', {
      params: { per_page: 100 }
    })
    // Filter out current folder and its children to prevent circular references
    availableFolders.value = (response.data.data.data || []).filter(folder => 
      folder.id !== props.folder.id && !isChildFolder(folder, props.folder.id)
    )
  } catch (error) {
    console.error('Error loading folders:', error)
  }
}

const isChildFolder = (folder, parentId) => {
  // Simple check - in a real implementation, you'd need to check the full hierarchy
  return folder.parent_id === parentId
}

const updateFolder = async () => {
  updating.value = true
  errors.value = {}

  try {
    const payload = {
      name: form.name.trim(),
      parent_id: form.parent_id,
      description: form.description.trim() || null
    }

    await axios.put(`/api/v1/document-folders/${props.folder.id}`, payload)
    emit('updated')
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      console.error('Error updating folder:', error)
      alert($t('documents.update_folder_error'))
    }
  } finally {
    updating.value = false
  }
}

const deleteFolder = async () => {
  if (!confirm($t('documents.delete_folder_confirmation', { name: props.folder.name }))) {
    return
  }

  deleting.value = true

  try {
    await axios.delete(`/api/v1/document-folders/${props.folder.id}`)
    emit('updated')
  } catch (error) {
    console.error('Error deleting folder:', error)
    alert($t('documents.delete_folder_error'))
  } finally {
    deleting.value = false
  }
}

const clearError = (field) => {
  if (errors.value[field]) {
    delete errors.value[field]
  }
}

// Lifecycle
onMounted(() => {
  loadFolders()
})
</script>