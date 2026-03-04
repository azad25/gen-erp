<template>
  <div 
    class="relative overflow-hidden"
    :class="containerClass"
    :style="{ aspectRatio: aspectRatio }"
  >
    <!-- Loading Placeholder -->
    <div 
      v-if="loading"
      class="absolute inset-0 bg-gray-200 animate-pulse flex items-center justify-center"
    >
      <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
      </svg>
    </div>

    <!-- Error Placeholder -->
    <div 
      v-else-if="error"
      class="absolute inset-0 bg-gray-100 flex items-center justify-center"
    >
      <div class="text-center text-gray-500">
        <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-xs">Failed to load</p>
      </div>
    </div>

    <!-- Optimized Image -->
    <NuxtImg
      v-else
      :src="src"
      :alt="alt"
      :width="width"
      :height="height"
      :quality="quality"
      :format="format"
      :sizes="sizes"
      :placeholder="placeholder"
      :loading="eager ? 'eager' : 'lazy'"
      :class="[
        'transition-opacity duration-300',
        imageClass,
        {
          'opacity-0': !imageLoaded,
          'opacity-100': imageLoaded
        }
      ]"
      @load="onImageLoad"
      @error="onImageError"
    />

    <!-- Overlay Content -->
    <div v-if="$slots.overlay" class="absolute inset-0">
      <slot name="overlay" />
    </div>

    <!-- Caption -->
    <div 
      v-if="caption"
      class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4"
    >
      <p class="text-white text-sm">{{ caption }}</p>
    </div>
  </div>
</template>

<script setup>
interface Props {
  src: string
  alt: string
  width?: number
  height?: number
  quality?: number
  format?: string | string[]
  sizes?: string
  placeholder?: string | boolean
  eager?: boolean
  aspectRatio?: string
  caption?: string
  containerClass?: string
  imageClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  width: undefined,
  height: undefined,
  quality: 80,
  format: 'webp',
  sizes: undefined,
  placeholder: true,
  eager: false,
  aspectRatio: undefined,
  caption: '',
  containerClass: '',
  imageClass: 'w-full h-full object-cover'
})

const loading = ref(true)
const error = ref(false)
const imageLoaded = ref(false)

// Handle image load
const onImageLoad = () => {
  loading.value = false
  error.value = false
  imageLoaded.value = true
}

// Handle image error
const onImageError = () => {
  loading.value = false
  error.value = true
  imageLoaded.value = false
}

// Generate responsive sizes if not provided
const sizes = computed(() => {
  if (props.sizes) return props.sizes
  
  // Default responsive sizes
  return '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw'
})

// Reset state when src changes
watch(() => props.src, () => {
  loading.value = true
  error.value = false
  imageLoaded.value = false
})
</script>

<style scoped>
/* Ensure smooth transitions */
.transition-opacity {
  transition-property: opacity;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Loading animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>