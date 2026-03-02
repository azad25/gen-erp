<template>
  <slot></slot>
</template>

<script setup lang="ts">
import { ref, provide, onMounted, watch, computed } from 'vue'

type Theme = 'light' | 'dark'

const theme = ref<Theme>('light')
const isInitialized = ref(false)

const isDarkMode = computed(() => theme.value === 'dark')

const toggleTheme = () => {
  console.log('toggleTheme called, current theme:', theme.value)
  theme.value = theme.value === 'light' ? 'dark' : 'light'
  console.log('toggleTheme new theme:', theme.value)
}

const applyTheme = (newTheme: Theme) => {
  console.log('applyTheme called with:', newTheme)
  console.log('document.documentElement classes:', document.documentElement.classList.toString())
  if (newTheme === 'dark') {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
  console.log('After applyTheme, classes:', document.documentElement.classList.toString())
}

onMounted(() => {
  const savedTheme = localStorage.getItem('theme') as Theme | null
  const initialTheme = savedTheme || 'light'

  theme.value = initialTheme
  applyTheme(initialTheme)
  isInitialized.value = true
})

watch(theme, (newTheme) => {
  if (isInitialized.value) {
    localStorage.setItem('theme', newTheme)
    applyTheme(newTheme)
  }
})

provide('theme', {
  isDarkMode,
  toggleTheme,
})
</script>

<script lang="ts">
import { inject } from 'vue'

export function useTheme() {
  const theme = inject('theme')
  if (!theme) {
    throw new Error('useTheme must be used within a ThemeProvider')
  }
  return theme
}
</script>
