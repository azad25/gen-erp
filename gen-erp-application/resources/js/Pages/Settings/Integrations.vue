<template>
  <ThemeProvider>
    <SidebarProvider>
      <AdminLayout>
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-black">Integrations</h1>
              <p class="text-sm text-gray-1">Manage integrations for your business</p>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="secondary" size="sm">Export</Button>
              <Button size="sm">+ New Integration</Button>
            </div>
          </div>
          <Card>
            <div class="flex flex-col items-center justify-center py-16">
              <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <span class="text-3xl">🚧</span>
              </div>
              <h3 class="text-lg font-semibold text-black mb-2">Under Development</h3>
              <p class="text-sm text-gray-1 text-center max-w-md">This page is currently under development.</p>
            </div>
          </Card>
        </div>
      </AdminLayout>
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import ThemeProvider from '@/Components/Layout/ThemeProvider.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import AdminLayout from '@/Components/layout/AdminLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
</script>
