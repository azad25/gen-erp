<template>
  <div class="flex h-screen overflow-hidden bg-bodybg">
    <div v-if="open" @click="open=false" class="fixed inset-0 z-40 bg-black/50 lg:hidden" />
    <AppSidebar />
    <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">
      <Topbar @toggle="open=!open" />
      <main class="mx-auto w-full max-w-screen-2xl p-4 md:p-6">
        <FlashMessage />
        <slot />
      </main>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import AppSidebar from './AppSidebar.vue'
import Topbar from './Topbar.vue'
import FlashMessage from '../UI/FlashMessage.vue'
const open = ref(false)
</script>
