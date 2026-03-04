<template>
  <div 
    class="rounded-lg p-6 text-center"
    :class="[
      containerClass,
      {
        'bg-red-50 border border-red-200': variant === 'error',
        'bg-yellow-50 border border-yellow-200': variant === 'warning',
        'bg-blue-50 border border-blue-200': variant === 'info',
        'bg-gray-50 border border-gray-200': variant === 'default'
      }
    ]"
  >
    <!-- Icon -->
    <div class="mb-4">
      <component 
        :is="iconComponent" 
        class="w-12 h-12 mx-auto"
        :class="{
          'text-red-500': variant === 'error',
          'text-yellow-500': variant === 'warning',
          'text-blue-500': variant === 'info',
          'text-gray-500': variant === 'default'
        }"
      />
    </div>
    
    <!-- Title -->
    <h3 
      v-if="title"
      class="text-lg font-semibold mb-2"
      :class="{
        'text-red-900': variant === 'error',
        'text-yellow-900': variant === 'warning',
        'text-blue-900': variant === 'info',
        'text-gray-900': variant === 'default'
      }"
    >
      {{ title }}
    </h3>
    
    <!-- Message -->
    <p 
      class="mb-4"
      :class="{
        'text-red-700': variant === 'error',
        'text-yellow-700': variant === 'warning',
        'text-blue-700': variant === 'info',
        'text-gray-700': variant === 'default'
      }"
    >
      {{ message }}
    </p>
    
    <!-- Actions -->
    <div v-if="showRetry || showHome" class="flex flex-col sm:flex-row gap-3 justify-center">
      <button
        v-if="showRetry"
        @click="$emit('retry')"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
        :class="{
          'bg-red-600 hover:bg-red-700 focus:ring-red-500': variant === 'error',
          'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500': variant === 'warning',
          'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500': variant === 'info',
          'bg-gray-600 hover:bg-gray-700 focus:ring-gray-500': variant === 'default'
        }"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        Try Again
      </button>
      
      <NuxtLink
        v-if="showHome"
        to="/"
        class="inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
        :class="{
          'border-red-300 text-red-700 bg-red-50 hover:bg-red-100 focus:ring-red-500': variant === 'error',
          'border-yellow-300 text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:ring-yellow-500': variant === 'warning',
          'border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100 focus:ring-blue-500': variant === 'info',
          'border-gray-300 text-gray-700 bg-gray-50 hover:bg-gray-100 focus:ring-gray-500': variant === 'default'
        }"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
        </svg>
        Go Home
      </NuxtLink>
    </div>
  </div>
</template>

<script setup>
interface Props {
  variant?: 'error' | 'warning' | 'info' | 'default'
  title?: string
  message: string
  showRetry?: boolean
  showHome?: boolean
  containerClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'error',
  title: '',
  showRetry: false,
  showHome: false,
  containerClass: 'max-w-md mx-auto'
})

defineEmits<{
  retry: []
}>()

// Icon component based on variant
const iconComponent = computed(() => {
  const icons = {
    error: 'IconError',
    warning: 'IconWarning',
    info: 'IconInfo',
    default: 'IconDefault'
  }
  return icons[props.variant]
})
</script>

<script>
// Icon components
const IconError = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
  `
}

const IconWarning = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
  `
}

const IconInfo = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
  `
}

const IconDefault = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.291.94-5.709 2.291"/>
    </svg>
  `
}
</script>

<style scoped>
/* Focus styles for accessibility */
button:focus,
a:focus {
  outline: none;
}

button:focus-visible,
a:focus-visible {
  outline: 2px solid;
  outline-offset: 2px;
}
</style>