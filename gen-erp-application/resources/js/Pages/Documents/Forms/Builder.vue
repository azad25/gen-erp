<template>
  <AppLayout>
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ form ? $t('forms.builder.edit_title') : $t('forms.builder.create_title') }}
          </h1>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ form ? $t('forms.builder.edit_subtitle') : $t('forms.builder.create_subtitle') }}
          </p>
        </div>
        <div class="flex gap-3">
          <Link
            :href="route('documents.forms.index')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            {{ $t('forms.builder.back_to_forms') }}
          </Link>
        </div>
      </div>

      <!-- Form Settings -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $t('forms.builder.form_settings') }}</h3>
        
        <form @submit.prevent="saveFormSettings" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ $t('forms.builder.form_name') }} *
              </label>
              <input
                v-model="formSettings.name"
                type="text"
                required
                :placeholder="$t('forms.builder.form_name_placeholder')"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ $t('forms.builder.form_slug') }}
              </label>
              <input
                v-model="formSettings.slug"
                type="text"
                readonly
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('forms.builder.form_description') }}
            </label>
            <textarea
              v-model="formSettings.description"
              rows="3"
              :placeholder="$t('forms.builder.form_description_placeholder')"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="flex items-center">
              <input
                v-model="formSettings.is_public"
                type="checkbox"
                class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
              />
              <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $t('forms.builder.public_form') }}</label>
            </div>

            <div class="flex items-center">
              <input
                v-model="formSettings.is_active"
                type="checkbox"
                class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
              />
              <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $t('forms.builder.active_form') }}</label>
            </div>
          </div>

          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="savingSettings"
              class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50"
            >
              <template v-if="savingSettings">
                <div class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              </template>
              {{ $t('forms.builder.save_settings') }}
            </button>
          </div>
        </form>
      </div>

      <!-- Form Builder -->
      <FormBuilder
        :initial-form="form"
        :field-types="fieldTypes"
        @save="saveForm"
        @preview="previewForm"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useTranslations } from '@/Composables/useTranslations'
import AppLayout from '@/Layouts/AppLayout.vue'
import FormBuilder from '@/Components/Forms/FormBuilder.vue'

const props = defineProps({
  form: {
    type: Object,
    default: null
  },
  fieldTypes: {
    type: Object,
    required: true
  }
})

const { $t } = useTranslations()

// State
const savingSettings = ref(false)

// Form settings
const formSettings = reactive({
  name: props.form?.name || '',
  slug: props.form?.slug || '',
  description: props.form?.description || '',
  is_public: props.form?.is_public || false,
  is_active: props.form?.is_active !== undefined ? props.form.is_active : true,
  settings: props.form?.settings || {}
})

// Methods
const saveFormSettings = async () => {
  savingSettings.value = true
  try {
    if (props.form) {
      // Update existing form settings
      router.put(route('documents.forms.update', props.form.id), formSettings, {
        onSuccess: () => {
          // Settings saved successfully
        }
      })
    } else {
      // For new forms, we'll save settings when the form is created
    }
  } finally {
    savingSettings.value = false
  }
}

const saveForm = async (formData) => {
  const payload = {
    ...formSettings,
    ...formData
  }

  if (props.form) {
    // Update existing form
    router.put(route('documents.forms.update', props.form.id), payload, {
      onSuccess: () => {
        router.visit(route('documents.forms.show', props.form.id))
      }
    })
  } else {
    // Create new form
    router.post(route('documents.forms.store'), payload, {
      onSuccess: (page) => {
        // Redirect to the new form's show page
        const formId = page.props.flash?.form_id || page.props.form?.id
        if (formId) {
          router.visit(route('documents.forms.show', formId))
        } else {
          router.visit(route('documents.forms.index'))
        }
      }
    })
  }
}

const previewForm = (formData) => {
  // Open preview in new tab/window
  const previewData = {
    ...formSettings,
    ...formData
  }
  
  // Store preview data in session storage for the preview window
  sessionStorage.setItem('form_preview_data', JSON.stringify(previewData))
  
  // Open preview window
  window.open('/forms/preview', '_blank', 'width=800,height=600')
}

// Generate slug from name
watch(() => formSettings.name, (newName) => {
  if (newName && !props.form) { // Only auto-generate for new forms
    formSettings.slug = newName.toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim('-')
  }
})
</script>