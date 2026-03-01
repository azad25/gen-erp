<template>
  <Transition enter-from-class="opacity-0 -translate-y-2" enter-active-class="transition duration-300" leave-to-class="opacity-0 translate-y-2" leave-active-class="transition duration-200">
    <div v-if="message" class="mb-4">
      <div :class="[
        'flex items-center gap-3 px-4 py-3 rounded-lg border',
        type === 'success' ? 'bg-success/10 border-success/30 text-success' : 'bg-danger/10 border-danger/30 text-danger'
      ]">
        <span class="text-lg">{{ type === 'success' ? '✓' : '✕' }}</span>
        <p class="text-sm font-medium">{{ message }}</p>
        <button @click="close" class="ml-auto hover:opacity-70">✕</button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const message = computed(() => {
  if (page.props.flash?.success) return { text: page.props.flash.success, type: 'success' }
  if (page.props.flash?.error) return { text: page.props.flash.error, type: 'error' }
  return null
})

const type = computed(() => message.value?.type)
const text = computed(() => message.value?.text)

const close = () => {
  page.props.flash = {}
}

onMounted(() => {
  if (message.value) {
    setTimeout(close, 5000)
  }
})
</script>
