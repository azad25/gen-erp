<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Save Filter</h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="saveFilter">
        <!-- Filter Name -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Filter Name
          </label>
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="Enter filter name"
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>

        <!-- Description -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Description (Optional)
          </label>
          <textarea
            v-model="form.description"
            rows="2"
            placeholder="Describe this filter..."
            class="w-full text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
          ></textarea>
        </div>

        <!-- Visibility -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Visibility
          </label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input
                v-model="form.is_public"
                :value="false"
                type="radio"
                class="border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              />
              <span class="ml-2 text-sm text-gray-700">Private (only me)</span>
            </label>
            <label class="flex items-center">
              <input
                v-model="form.is_public"
                :value="true"
                type="radio"
                class="border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              />
              <span class="ml-2 text-sm text-gray-700">Public (team can use)</span>
            </label>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="loading || !form.name.trim()"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 rounded-md"
          >
            {{ loading ? 'Saving...' : 'Save Filter' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useApi } from '@/Composables/useApi'

const props = defineProps({
  filters: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'saved'])

const { post, loading } = useApi()

// Form data
const form = reactive({
  name: '',
  description: '',
  is_public: false
})

// Methods
const saveFilter = async () => {
  try {
    const data = await post('/api/v1/crm/saved-filters', {
      name: form.name,
      description: form.description,
      is_public: form.is_public,
      filters: props.filters
    })
    
    emit('saved', data.data)
  } catch (err) {
    console.error('Failed to save filter:', err)
  }
}
</script>