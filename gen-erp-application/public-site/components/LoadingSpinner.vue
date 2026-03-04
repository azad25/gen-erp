<template>
  <div 
    class="flex items-center justify-center"
    :class="containerClass"
  >
    <div class="relative">
      <!-- Spinner -->
      <div 
        class="animate-spin rounded-full border-4 border-solid border-current border-r-transparent"
        :class="spinnerClass"
        :style="{ 
          borderColor: `${color} transparent ${color} ${color}`,
          width: size,
          height: size
        }"
      ></div>
      
      <!-- Center dot (optional) -->
      <div 
        v-if="showCenterDot"
        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 rounded-full"
        :style="{ 
          backgroundColor: color,
          width: centerDotSize,
          height: centerDotSize
        }"
      ></div>
    </div>
    
    <!-- Loading text -->
    <p 
      v-if="text"
      class="ml-3 font-medium"
      :class="textClass"
      :style="{ color }"
    >
      {{ text }}
    </p>
  </div>
</template>

<script setup>
interface Props {
  size?: string
  color?: string
  text?: string
  showCenterDot?: boolean
  containerClass?: string
  spinnerClass?: string
  textClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: '2rem',
  color: '#3b82f6',
  text: '',
  showCenterDot: false,
  containerClass: 'py-8',
  spinnerClass: '',
  textClass: 'text-gray-600'
})

const centerDotSize = computed(() => {
  const sizeValue = parseFloat(props.size)
  const unit = props.size.replace(/[\d.]/g, '')
  return `${sizeValue * 0.2}${unit}`
})
</script>

<style scoped>
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>