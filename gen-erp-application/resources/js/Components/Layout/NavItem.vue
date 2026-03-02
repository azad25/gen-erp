<template>
  <button
    type="button"
    @click="handleClick"
    class="relative flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors cursor-pointer w-full text-left border-0 bg-transparent"
    :class="isActive ? 'bg-accent/12 text-accent' : 'text-white/40 hover:bg-white/5 hover:text-white/80'"
  >
    <span v-if="isActive" class="absolute left-0 w-[3px] h-5 bg-accent rounded-r-full" />
    <span>{{ icon }}</span>
    <span class="flex-1">{{ label }}</span>
    <span v-if="badge" class="rounded-full px-1.5 py-0.5 text-[10px] font-mono" :class="badgeVariant === 'warning' ? 'bg-warning/20 text-warning' : 'bg-accent/20 text-accent'">
      {{ badge }}
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  icon: String,
  label: String,
  route: String,
  pageUrl: String,
  badge: Number,
  badgeVariant: {
    type: String,
    default: 'default'
  }
})

const isActive = computed(() => {
  if (!props.route || !props.pageUrl) return false
  return props.pageUrl.startsWith(props.route)
})

const handleClick = () => {
  if (props.route) {
    router.visit(props.route)
  }
}
</script>
