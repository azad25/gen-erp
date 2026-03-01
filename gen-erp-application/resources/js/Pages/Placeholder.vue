<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <!-- Page Header -->
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">{{ title }}</h1>
              <p class="text-sm text-gray-1">{{ description }}</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm">Export</Button>
              <Button size="sm">+ New</Button>
            </div>
          </div>

          <!-- Placeholder Content -->
          <Card>
            <div class="flex flex-col items-center justify-center py-16">
              <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <span class="text-3xl">🚧</span>
              </div>
              <h3 class="text-lg font-semibold text-black mb-2">Under Development</h3>
              <p class="text-sm text-gray-1 text-center max-w-md">
                This page is currently under development. The full functionality will be available soon.
              </p>
              <div class="mt-6 flex gap-2">
                <Button variant="secondary" size="sm" @click="$inertia.visit('/dashboard')">
                  Back to Dashboard
                </Button>
              </div>
            </div>
          </Card>
        </div>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'

const page = usePage()
const route = computed(() => page.props.url || window.location.pathname)

// Extract page title from route
const title = computed(() => {
  const path = route.value
  const segments = path.split('/').filter(s => s)
  if (segments.length > 0) {
    return segments[segments.length - 1]
      .split('-')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ')
  }
  return 'Page'
})

const description = computed(() => {
  return `Manage ${title.value.toLowerCase()} for your business`
})
</script>
