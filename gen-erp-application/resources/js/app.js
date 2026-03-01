import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import VueApexCharts from 'vue3-apexcharts'
import ThemeProvider from './Components/Layout/ThemeProvider.vue'
import '../css/app.css'

createInertiaApp({
  title: title => `${title} — GenERP BD`,
  resolve: name => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    createApp({
      render: () => {
        return h(ThemeProvider, {}, () => h(App, props))
      }
    })
      .use(plugin).use(createPinia()).use(VueApexCharts).mount(el)
  },
  progress: { color: '#14B8A6', showSpinner: false },
})
