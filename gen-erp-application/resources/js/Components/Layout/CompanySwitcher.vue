<template>
  <div class="relative">
    <button @click="open=!open" class="flex w-full items-center gap-2.5 rounded-xl border border-stroke bg-gray-50 px-3 py-2.5 hover:bg-gray-100 transition-colors text-left">
      <div class="h-7 w-7 rounded-lg bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0">{{ abbr }}</div>
      <div class="flex-1 min-w-0">
        <p class="text-black text-xs font-semibold truncate">{{ company?.name }}</p>
        <p class="text-gray-1 text-[10px]">{{ branch?.name ?? 'All Branches' }}</p>
      </div>
      <span class="text-gray-1">⌄</span>
    </button>
    <Transition enter-from-class="opacity-0 scale-95" enter-active-class="transition duration-100" leave-to-class="opacity-0 scale-95" leave-active-class="transition duration-75">
      <div v-if="open" class="absolute left-0 right-0 top-full z-50 mt-1 rounded-xl border border-stroke bg-white p-1.5 shadow-xl">
        <p class="px-2 py-1 text-[10px] font-mono uppercase tracking-widest text-gray-1">Workspace</p>
        <button 
          v-for="c in companies" 
          :key="c.id" 
          @click="switchCompany(c.id)"
          type="button"
          class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs font-medium transition-colors"
          :class="c.id===company?.id ? 'bg-primary/10 text-primary' : 'text-gray-1 hover:bg-gray-50 hover:text-black'"
        >
          <div class="h-5 w-5 rounded bg-gradient-to-br from-primary/80 to-accent/80 flex items-center justify-center text-white text-[9px] font-bold">
            {{ c.name.charAt(0).toUpperCase() }}
          </div>
          <span class="flex-1 text-left truncate">{{ c.name }}</span>
          <span v-if="c.id===company?.id" class="text-primary">✓</span>
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const page = usePage()
const open = ref(false)

const company = computed(() => page.props.auth?.company)
const branch = computed(() => page.props.auth?.branch)
const user = computed(() => page.props.auth?.user)
const companies = computed(() => user.value?.companies || [])
const abbr = computed(() => company.value?.name?.charAt(0).toUpperCase() || 'G')

const switchCompany = (companyId) => {
  if (companyId === company.value?.id) {
    open.value = false
    return
  }
  
  router.post(`/app/switch-company/${companyId}`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      open.value = false
    }
  })
}
</script>
