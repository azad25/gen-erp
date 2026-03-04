<template>
  <div class="relative">
    <button
      @click="isOpen = !isOpen"
      class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
    >
      <span class="text-lg">{{ currentLanguage.flag }}</span>
      <span class="hidden sm:block">{{ currentLanguage.name }}</span>
      <ChevronDownIcon class="w-4 h-4" />
    </button>

    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
      >
        <button
          v-for="language in languages"
          :key="language.code"
          @click="switchLanguage(language.code)"
          class="flex items-center gap-3 w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          :class="{ 'bg-gray-50 dark:bg-gray-700': currentLocale === language.code }"
        >
          <span class="text-lg">{{ language.flag }}</span>
          <span>{{ language.name }}</span>
          <CheckIcon v-if="currentLocale === language.code" class="w-4 h-4 ml-auto text-primary" />
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { ChevronDownIcon, CheckIcon } from '@heroicons/vue/24/outline'

const page = usePage()
const isOpen = ref(false)

const languages = [
  { code: 'bn', name: 'বাংলা', flag: '🇧🇩' },
  { code: 'en', name: 'English', flag: '🇺🇸' },
]

const currentLocale = computed(() => page.props.locale || 'bn')

const currentLanguage = computed(() => {
  return languages.find(lang => lang.code === currentLocale.value) || languages[0]
})

const switchLanguage = (locale) => {
  if (locale === currentLocale.value) {
    isOpen.value = false
    return
  }
  
  // Close the dropdown
  isOpen.value = false
  
  // Make a request to switch language
  router.post('/language/switch', { locale }, {
    preserveState: false, // Allow full page reload to apply language changes
    preserveScroll: true,
    onSuccess: () => {
      // Update document language
      document.documentElement.lang = locale
    },
    onError: (errors) => {
      console.error('Language switch failed:', errors)
    }
  })
}

const closeOnClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeOnClickOutside)
  // Set document language on mount
  document.documentElement.lang = currentLocale.value
})

onUnmounted(() => {
  document.removeEventListener('click', closeOnClickOutside)
})
</script>