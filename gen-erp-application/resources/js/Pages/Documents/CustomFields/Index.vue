<template>
  <AppLayout>
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('custom_fields.index.title') }}</h1>
          <p class="text-sm text-gray-600 dark:text-gray-400">{{ $t('custom_fields.index.subtitle') }}</p>
        </div>
        <div class="flex gap-3">
          <Link
            :href="route('documents.custom-fields.overview')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            <ChartBarIcon class="w-4 h-4 mr-2" />
            {{ $t('custom_fields.index.overview') }}
          </Link>
          <Link
            :href="route('documents.custom-fields.create')"
            class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            {{ $t('custom_fields.index.create_field') }}
          </Link>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="material-icons text-blue-600 text-lg">settings</i>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('custom_fields.index.total_fields') }}</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_fields || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="material-icons text-green-600 text-lg">check_circle</i>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('custom_fields.index.active_fields') }}</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.active_fields || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="material-icons text-purple-600 text-lg">domain</i>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('custom_fields.index.domains') }}</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.domains_count || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="material-icons text-orange-600 text-lg">category</i>
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('custom_fields.index.entities') }}</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.entities_count || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Custom Field Manager -->
      <CustomFieldManager
        ref="customFieldManager"
        :initial-fields="customFields.data"
        :available-domains="domains"
        :available-entities="[]"
        :field-types="fieldTypes"
        @create="createField"
        @update="updateField"
        @delete="deleteField"
        @bulk-action="handleBulkAction"
        @filter="handleFilter"
      />

      <!-- Pagination -->
      <div v-if="customFields.data.length > 0" class="mt-6">
        <Pagination :links="customFields.links" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useTranslations } from '@/Composables/useTranslations'
import AppLayout from '@/Layouts/AppLayout.vue'
import CustomFieldManager from '@/Components/Forms/CustomFieldManager.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import { PlusIcon, ChartBarIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  customFields: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  stats: {
    type: Object,
    default: () => ({})
  },
  domains: {
    type: Array,
    default: () => []
  },
  fieldTypes: {
    type: Array,
    default: () => []
  }
})

const { $t } = useTranslations()

// Refs
const customFieldManager = ref(null)

// Methods
const createField = (fieldData) => {
  router.post(route('documents.custom-fields.store'), fieldData, {
    onSuccess: () => {
      // Refresh the page to show the new field
      router.reload()
    },
    onError: (errors) => {
      console.error('Failed to create custom field:', errors)
    }
  })
}

const updateField = (fieldId, fieldData) => {
  router.put(route('documents.custom-fields.update', fieldId), fieldData, {
    onSuccess: () => {
      // Refresh the page to show the updated field
      router.reload()
    },
    onError: (errors) => {
      console.error('Failed to update custom field:', errors)
    }
  })
}

const deleteField = (fieldId) => {
  router.delete(route('documents.custom-fields.destroy', fieldId), {
    onSuccess: () => {
      // Refresh the page to remove the deleted field
      router.reload()
    },
    onError: (errors) => {
      console.error('Failed to delete custom field:', errors)
    }
  })
}

const handleBulkAction = (actionData) => {
  router.post(route('documents.custom-fields.bulk-action'), {
    action: actionData.action,
    field_ids: actionData.fieldIds
  }, {
    onSuccess: () => {
      // Refresh the page to show the changes
      router.reload()
    },
    onError: (errors) => {
      console.error('Failed to perform bulk action:', errors)
    }
  })
}

const handleFilter = (filters) => {
  router.get(route('documents.custom-fields.index'), filters, {
    preserveState: true,
    replace: true,
    onSuccess: (page) => {
      // Update the custom field manager with new data
      if (customFieldManager.value) {
        customFieldManager.value.updateFields(page.props.customFields.data)
      }
    }
  })
}
</script>