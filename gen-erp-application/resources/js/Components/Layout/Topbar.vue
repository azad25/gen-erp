<template>
  <header class="sticky top-0 z-30 flex w-full bg-white border-b border-stroke">
    <div class="flex w-full items-center justify-between px-4 py-3 md:px-6 gap-4">
      <div class="flex items-center gap-3">
        <button @click="$emit('toggle')" class="lg:hidden text-gray-1 hover:text-primary">☰</button>
        <div class="hidden lg:block">
          <h2 class="text-[15px] font-bold text-black tracking-tight">{{ title }}</h2>
          <p class="text-[11px] text-gray-1">{{ $page.props.auth?.company?.name }}</p>
        </div>
      </div>
      <div class="flex items-center gap-2 ml-auto">
        <div class="hidden md:flex items-center gap-2 bg-gray-3 border border-stroke rounded-lg px-3 h-9 w-52 hover:border-primary/40 cursor-text transition-colors">
          <span class="text-gray-2">⌕</span>
          <span class="text-[12px] text-gray-1 flex-1">Search...</span>
          <kbd class="text-[10px] bg-white border border-stroke rounded px-1.5 font-mono">⌘K</kbd>
        </div>
        <button class="relative h-9 w-9 flex items-center justify-center rounded-lg border border-stroke bg-white text-gray-1 hover:border-primary/40 hover:text-primary transition-all">
          🔔 <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-danger border-2 border-white" />
        </button>
        <div class="flex items-center gap-2">
          <img 
            src="/user.jpg" 
            alt="User Profile" 
            class="h-9 w-9 rounded-lg object-cover border border-stroke"
          />
        </div>
        <div v-if="$page.props.auth?.branch" class="hidden md:flex items-center gap-1.5 bg-primary/8 text-primary text-[11px] font-semibold px-3 h-9 rounded-lg border border-primary/20">
          🏢 {{ $page.props.auth.branch.name }}
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

defineEmits(['toggle'])

const page = usePage()
const title = computed(() => page.props.pageTitle || 'Dashboard')

const userProfileImage = computed(() => {
  const userImage = page.props.auth?.user?.profile_image
  if (userImage) {
    return userImage.startsWith('http') ? userImage : `/storage/${userImage}`
  }
  return '/user.jpg?v=' + Date.now()
})
</script>
