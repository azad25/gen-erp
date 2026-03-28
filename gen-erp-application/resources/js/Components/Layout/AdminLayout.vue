<template>
  <SidebarProvider>
    <div class="min-h-screen xl:flex bg-gray-50 dark:bg-gray-900 dark:text-gray-100 text-gray-900">
      <app-sidebar />
      <Backdrop />
      <AppLayoutMain>
        <slot></slot>
      </AppLayoutMain>
    </div>
  </SidebarProvider>
</template>

<script setup>
import AppSidebar from '@/Components/Layout/AppSidebar.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import Backdrop from '@/Components/Layout/Backdrop.vue'
import AppLayoutMain from '@/Components/Layout/AppLayout.vue'
</script>
