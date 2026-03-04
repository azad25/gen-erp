import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import VueApexCharts from 'vue3-apexcharts'
import ThemeProvider from './Components/Layout/ThemeProvider.vue'
import '../css/app.css'
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'
import 'jsvectormap/dist/jsvectormap.css'
import 'flatpickr/dist/flatpickr.css'

createInertiaApp({
  title: title => `${title} — GenERP BD`,
  resolve: name => {
    console.log('[Inertia] Resolving page:', name)
    return resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'))
      .then(page => {
        console.log('[Inertia] Page resolved successfully:', name)
        return page
      })
      .catch(error => {
        console.error('[Inertia] Failed to resolve page component:', name, error)
        throw error
      })
  },
  setup({ el, App, props, plugin }) {
    console.log('[Inertia] Setup called, current page:', props.initialPage.component)
    console.log('[Inertia] Page URL:', props.initialPage.url)
    console.log('[Inertia] Page props:', props.initialPage.props)

    // Sync company ID from server to sessionStorage for API calls
    const companyId = props.initialPage.props.auth?.company?.id
    if (companyId) {
      sessionStorage.setItem('active_company_id', companyId)
      console.log('[Company Sync] Set active_company_id to:', companyId)
    } else {
      console.warn('[Company Sync] No company ID found in props')
    }

    const app = createApp({
      render: () => {
        return h(ThemeProvider, {}, () => h(App, props))
      }
    })

    // Add global error handler
    app.config.errorHandler = (err, instance, info) => {
      console.error('Vue error:', err)
      console.error('Component:', instance)
      console.error('Info:', info)
    }

    app.use(plugin).use(createPinia()).use(VueApexCharts).mount(el)
  },
  progress: { color: '#14B8A6', showSpinner: false },
})

// Add Inertia error handler
import { router } from '@inertiajs/vue3'

router.on('error', (event) => {
  console.error('[Inertia] Navigation error:', event.detail.errors)
})

router.on('navigate', (event) => {
  console.log('[Inertia] Navigating to:', event.detail.page.url)
})

router.on('finish', (event) => {
  console.log('[Inertia] Navigation finished:', event.detail.visit.url)
})

router.on('exception', (event) => {
  console.error('[Inertia] Exception during navigation:', event.detail.exception)
  event.preventDefault() // Prevent default error handling
})
