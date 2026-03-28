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
        console.error('[Inertia] Error details:', {
          message: error.message,
          stack: error.stack,
          pageName: name
        })
        // Re-throw to let Inertia handle it
        throw error
      })
  },
  progress: {
    delay: 250,
    color: '#14B8A6',
    includeCSS: true,
    showSpinner: false,
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
      console.warn('[Company Sync] Auth props:', props.initialPage.props.auth)
      
      // Try to get company ID from user's companies
      const userCompanies = props.initialPage.props.auth?.user?.companies
      if (userCompanies && userCompanies.length > 0) {
        const firstCompanyId = userCompanies[0].id
        sessionStorage.setItem('active_company_id', firstCompanyId)
        console.log('[Company Sync] Using first company ID:', firstCompanyId)
      } else {
        console.error('[Company Sync] No companies found for user')
      }
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

// Add Inertia error handler and scroll restoration
import { router } from '@inertiajs/vue3'

router.on('error', (event) => {
  console.error('[Inertia] Navigation error:', event.detail.errors)
  console.error('[Inertia] Error page component:', event.detail.page?.component)
  console.error('[Inertia] Error page URL:', event.detail.page?.url)
})

router.on('navigate', (event) => {
  console.log('[Inertia] Navigating to:', event.detail.page.url)
  console.log('[Inertia] Page component:', event.detail.page.component)
  // Scroll to top on navigation
  window.scrollTo({ top: 0, behavior: 'smooth' })
})

router.on('finish', (event) => {
  console.log('[Inertia] Navigation finished:', event.detail.visit.url)
  console.log('[Inertia] Final page component:', event.detail.page?.component)
})

router.on('exception', (event) => {
  console.error('[Inertia] Exception during navigation:', event.detail.exception)
  console.error('[Inertia] Exception stack:', event.detail.exception?.stack)
  console.error('[Inertia] Page component:', event.detail.page?.component)
  console.error('[Inertia] Page URL:', event.detail.page?.url)
  console.error('[Inertia] Visit details:', event.detail.visit)
  // DON'T prevent default - let the error show
  // event.preventDefault()
})

router.on('start', (event) => {
  console.log('[Inertia] Navigation started:', event.detail.visit.url)
})

router.on('progress', (event) => {
  console.log('[Inertia] Navigation progress:', event.detail.percentage + '%')
})

router.on('success', (event) => {
  console.log('[Inertia] Navigation success:', event.detail.page.url)
  console.log('[Inertia] Success page component:', event.detail.page.component)
})
