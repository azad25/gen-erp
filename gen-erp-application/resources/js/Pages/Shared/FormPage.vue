<template>
  <AppLayout>
    <template #header>
      <h2 class="text-[15px] font-bold text-black tracking-tight">{{ title }}</h2>
      <p class="text-[11px] text-gray-1">{{ subtitle }}</p>
    </template>

    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-black">{{ title }}</h1>
        <Button v-if="cancelRoute" :href="cancelRoute" variant="secondary" size="sm">Cancel</Button>
      </div>

      <Card>
        <form @submit.prevent="handleSubmit">
          <div class="p-5 space-y-6">
            <slot />
          </div>
          <div class="rounded-b-[14px] border-t border-stroke bg-gray-3/40 px-5 py-3 flex items-center justify-end gap-2">
            <Button type="button" variant="secondary" @click="$inertia.visit(cancelRoute)">Cancel</Button>
            <Button type="submit" :disabled="loading">{{ submitLabel }}</Button>
          </div>
        </form>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '../../Components/Layout/AppLayout.vue'
import Card from '../../Components/UI/Card.vue'
import Button from '../../Components/UI/Button.vue'

const props = defineProps({
  title: String,
  subtitle: { type: String, default: '' },
  cancelRoute: String,
  submitLabel: { type: String, default: 'Save' },
  onSubmit: Function,
})

const emit = defineEmits(['submit'])

const loading = ref(false)
const page = usePage()
const company = computed(() => page.props.auth?.company)

const handleSubmit = async () => {
  loading.value = true
  try {
    await props.onSubmit()
  } finally {
    loading.value = false
  }
}
</script>
