<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
      <div class="p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            {{ $t('documents.create_folder') }}
          </h2>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="createFolder">
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
              :disabled="creating"
            >
              {{ $t('common.cancel') }}
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="creating || !form.name.trim()"
            >
              <div v-if="creating" class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                {{ $t('documents.creating') }}
              </div>
              <span v-else>
                {{ $t('documents.create_folder') }}
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useTranslations } from '@/Composables/useTranslations'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  parentId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['close', 'created'])

const { $t } = useTranslations()

// State
const creating = ref(false)
const form = reactive({
  name: '',
  description: ''
})
const errors = ref({})

// Methods
const createFolder = async () => {
  creating.value = true
  errors.value = {}

  try {
    const payload = {
      name: form.name.trim(),
      description: form.description.trim() || null
    }

    if (props.parentId) {
      payload.parent_id = props.parentId
    }

    await axios.post('/api/v1/document-folders', payload)
    emit('created')
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      console.error('Error creating folder:', error)
      alert($t('documents.create_folder_error'))
    }
  } finally {
    creating.value = false
  }
}

const clearError = (field) => {
  if (errors.value[field]) {
    delete errors.value[field]
  }
}
</script>