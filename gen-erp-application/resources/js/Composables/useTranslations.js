import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useTranslations() {
  const page = usePage()
  
  const currentLocale = computed(() => {
    return page.props.locale || 'bn'
  })

  const translations = computed(() => {
    return page.props.translations || {}
  })

  const $t = (key, params = {}) => {
    const keys = key.split('.')
    let translation = translations.value
    
    // Navigate through nested translation object
    for (const k of keys) {
      if (translation && typeof translation === 'object' && k in translation) {
        translation = translation[k]
      } else {
        // Fallback to key if translation not found
        translation = key
        break
      }
    }
    
    // If translation is still an object, return the key
    if (typeof translation === 'object') {
      translation = key
    }
    
    // Simple parameter replacement
    if (typeof translation === 'string' && params) {
      Object.keys(params).forEach(param => {
        translation = translation.replace(`{${param}}`, params[param])
      })
    }
    
    return translation
  }

  return {
    $t,
    currentLocale,
    translations
  }
}