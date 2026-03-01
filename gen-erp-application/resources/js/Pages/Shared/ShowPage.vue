<template>
  <AppLayout>
    <template #header>
      <h2 class="text-[15px] font-bold text-black tracking-tight">{{ title }}</h2>
      <p class="text-[11px] text-gray-1">{{ subtitle }}</p>
    </template>

    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-black">{{ title }}</h1>
        <div class="flex items-center gap-2">
          <Button v-if="editRoute" :href="editRoute" variant="secondary" size="sm">Edit</Button>
          <Button v-if="deleteAction" @click="deleteAction" variant="danger" size="sm">Delete</Button>
        </div>
      </div>

      <Card>
        <slot />
      </Card>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '../../Components/Layout/AppLayout.vue'
import Card from '../../Components/UI/Card.vue'
import Button from '../../Components/UI/Button.vue'

const props = defineProps({
  title: String,
  subtitle: { type: String, default: '' },
  editRoute: String,
  deleteAction: Function,
})

const page = usePage()
const company = computed(() => page.props.auth?.company)
</script>
