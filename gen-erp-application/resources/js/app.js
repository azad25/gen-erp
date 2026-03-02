import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import VueApexCharts from 'vue3-apexcharts'
import ThemeProvider from './Components/Layout/ThemeProvider.vue'
import '../css/app.css'

createInertiaApp({
  title: title => `${title} — GenERP BD`,
  resolve: name => {
    console.log('Loading page:', name)
    return resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'))
  },
  setup({ el, App, props, plugin }) {
    console.log('Inertia setup, current page:', props.initialPage.component)
    
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
