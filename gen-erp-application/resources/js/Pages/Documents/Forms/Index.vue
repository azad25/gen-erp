<template>
  <AppLayout>
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('forms.index.title') }}</h1>
          <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('forms.index.subtitle') }}</p>
        </div>
        <div class="flex gap-3">
          <Link
            :href="route('documents.forms.create')"
            class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            {{ $t('forms.index.create_form') }}
          </Link>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('forms.index.search') }}
            </label>
            <input
              v-model="filters.search"
              @input="debounceSearch"
              type="text"
              :placeholder="$t('forms.index.search_placeholder')"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('forms.index.visibility') }}
            </label>
            <select
              v-model="filters.is_public"
              @change="applyFilters"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            >
              <option value="">{{ $t('forms.index.all_forms') }}</option>
              <option value="1">{{ $t('forms.index.public_forms') }}</option>
              <option value="0">{{ $t('forms.index.private_forms') }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('forms.index.status') }}
            </label>
            <select
              v-model="filters.is_active"
              @change="applyFilters"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            >
              <option value="">{{ $t('forms.index.all_statuses') }}</option>
              <option value="1">{{ $t('forms.index.active') }}</option>
              <option value="0">{{ $t('forms.index.inactive') }}</option>
            </select>
          </div>

          <div class="flex items-end">
            <button
              @click="clearFilters"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
            >
              {{ $t('forms.index.clear_filters') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Forms Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="form in forms.data"
          :key="form.id"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow"
        >
          <div class="p-6">
            <!-- Form Header -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                  {{ form.name }}
                </h3>
                <p v-if="form.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                  {{ form.description }}
                </p>
              </div>
              <div class="flex items-center gap-2 ml-4">
                <span
                  :class="form.is_active 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-red-100 text-red-800'"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                >
                  {{ form.is_active ? $t('forms.index.active') : $t('forms.index.inactive') }}
                </span>
                <span
                  v-if="form.is_public"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                >
                  {{ $t('forms.index.public') }}
                </span>
              </div>
            </div>

            <!-- Form Stats -->
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ form.fields_count || 0 }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $t('forms.index.fields') }}</div>
              </div>
              <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ form.submissions_count || 0 }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $t('forms.index.submissions') }}</div>
              </div>
            </div>

            <!-- Form Meta -->
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-4">
              <div>{{ $t('forms.index.created_by') }}: {{ form.creator?.name || 'Unknown' }}</div>
              <div>{{ $t('forms.index.created_at') }}: {{ formatDate(form.created_at) }}</div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-2">
                <Link
                  :href="route('documents.forms.show', form.id)"
                  class="text-sm font-medium text-primary hover:text-primary-dark"
                >
                  {{ $t('forms.index.view') }}
                </Link>
                <Link
                  :href="route('documents.forms.edit', form.id)"
                  class="text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                  {{ $t('forms.index.edit') }}
                </Link>
                <Link
                  :href="route('documents.forms.submissions', form.id)"
                  class="text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                  {{ $t('forms.index.submissions') }}
                </Link>
              </div>
              <div class="flex items-center gap-1">
                <button
                  @click="duplicateForm(form)"
                  class="p-1 text-gray-400 hover:text-gray-600"
                  :title="$t('forms.index.duplicate')"
                >
                  <DocumentDuplicateIcon class="w-4 h-4" />
                </button>
                <button
                  @click="deleteForm(form)"
                  class="p-1 text-red-400 hover:text-red-600"
                  :title="$t('forms.index.delete')"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="forms.data.length === 0" class="text-center py-12">
        <div class="text-gray-400 mb-4">
          <i class="material-icons text-6xl">description</i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ $t('forms.index.no_forms') }}</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('forms.index.no_forms_description') }}</p>
        <Link
          :href="route('documents.forms.create')"
          class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark"
        >
          {{ $t('forms.index.create_first_form') }}
        </Link>
      </div>

      <!-- Pagination -->
      <div v-if="forms.data.length > 0" class="mt-6">
        <Pagination :links="forms.links" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useTranslations } from '@/Composables/useTranslations'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { PlusIcon, DocumentDuplicateIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  forms: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const { $t } = useTranslations()

// State
const filters = reactive({
  search: props.filters.search || '',
  is_public: props.filters.is_public || '',
  is_active: props.filters.is_active || ''
})

// Methods
const applyFilters = () => {
  router.get(route('documents.forms.index'), filters, {
    preserveState: true,
    replace: true
  })
}

const debounceSearch = (() => {
  let timeout
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      applyFilters()
    }, 300)
  }
})()

const clearFilters = () => {
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  applyFilters()
}

const duplicateForm = (form) => {
  if (confirm($t('forms.index.confirm_duplicate'))) {
    router.post(route('documents.forms.duplicate', form.id))
  }
}

const deleteForm = (form) => {
  if (confirm($t('forms.index.confirm_delete'))) {
    router.delete(route('documents.forms.destroy', form.id))
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}
</script>