<template>
  <i :class="iconClass" />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  name: {
    type: String,
    required: true
  },
  class: {
    type: String,
    default: ''
  }
})

const iconClass = computed(() => {
  // Simple icon mapping - in a real app you'd use a proper icon library
  const iconMap = {
    'heroicons:photo': 'fas fa-image',
    'heroicons:document-text': 'fas fa-file-text',
    'heroicons:play': 'fas fa-play',
    'heroicons:minus': 'fas fa-minus',
    'heroicons:squares-2x2': 'fas fa-th',
    'heroicons:briefcase': 'fas fa-briefcase',
    'heroicons:users': 'fas fa-users',
    'heroicons:newspaper': 'fas fa-newspaper',
    'heroicons:chat-bubble-left-right': 'fas fa-comments',
    'heroicons:chart-bar': 'fas fa-chart-bar',
    'heroicons:question-mark-circle': 'fas fa-question-circle',
    'heroicons:megaphone': 'fas fa-bullhorn',
    'heroicons:envelope': 'fas fa-envelope',
    'heroicons:map': 'fas fa-map',
    'heroicons:star': 'fas fa-star',
    'heroicons:currency-dollar': 'fas fa-dollar-sign',
    'heroicons:code-bracket': 'fas fa-code',
    'heroicons:cube': 'fas fa-cube',
    'heroicons:shopping-cart': 'fas fa-shopping-cart',
    'heroicons:shopping-bag': 'fas fa-shopping-bag',
    'heroicons:credit-card': 'fas fa-credit-card',
    'heroicons:check-circle': 'fas fa-check-circle',
    'heroicons:truck': 'fas fa-truck',
    'heroicons:pencil-square': 'fas fa-edit',
    'heroicons:magnifying-glass': 'fas fa-search',
    'heroicons:tag': 'fas fa-tag',
    'heroicons:fire': 'fas fa-fire',
    'heroicons:sparkles': 'fas fa-sparkles',
    'heroicons:trophy': 'fas fa-trophy',
    'heroicons:heart': 'fas fa-heart',
    'heroicons:user-circle': 'fas fa-user-circle',
    'heroicons:clipboard-document-list': 'fas fa-clipboard-list',
    'heroicons:arrow-right-on-rectangle': 'fas fa-sign-in-alt',
    'heroicons:user-plus': 'fas fa-user-plus',
    'heroicons:user': 'fas fa-user',
    'heroicons:chat-bubble-left-ellipsis': 'fas fa-comment-dots',
    'heroicons:document-duplicate': 'fas fa-copy',
    'heroicons:chevron-up': 'fas fa-chevron-up',
    'heroicons:chevron-down': 'fas fa-chevron-down',
    'heroicons:trash': 'fas fa-trash',
    'heroicons:plus-circle': 'fas fa-plus-circle',
    'heroicons:cursor-arrow-rays': 'fas fa-mouse-pointer',
    'heroicons:cog-6-tooth': 'fas fa-cog',
    'heroicons:square-3-stack-3d': 'fas fa-layer-group'
  }
  
  const iconClass = iconMap[props.name] || 'fas fa-question'
  return `${iconClass} ${props.class}`
})
</script>