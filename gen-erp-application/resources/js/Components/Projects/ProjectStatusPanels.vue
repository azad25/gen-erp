<template>
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <!-- By Status -->
    <Card>
      <template #header>
        <h3 class="text-lg font-semibold text-black dark:text-white">
          Projects by Status
        </h3>
      </template>
      <div class="space-y-3">
        <div
          v-for="(count, status) in byStatus"
          :key="status"
          class="flex items-center justify-between"
        >
          <div class="flex items-center gap-3">
            <div
              class="h-2.5 w-2.5 rounded-full"
              :class="statusDotClass(status)"
            />
            <span class="text-sm text-gray-800 dark:text-gray-100 capitalize">
              {{ status.replace('_', ' ') }}
            </span>
          </div>
          <span class="text-sm font-semibold text-black dark:text-white">
            {{ count }}
          </span>
        </div>

        <div
          v-if="!Object.keys(byStatus || {}).length"
          class="py-4 text-center text-sm text-gray-500 dark:text-gray-400"
        >
          No project status data available
        </div>
      </div>
    </Card>

    <!-- By Priority -->
    <Card>
      <template #header>
        <h3 class="text-lg font-semibold text-black dark:text-white">
          Projects by Priority
        </h3>
      </template>
      <div class="space-y-3">
        <div
          v-for="(count, priority) in byPriority"
          :key="priority"
          class="flex items-center justify-between"
        >
          <div class="flex items-center gap-3">
            <div
              class="h-2.5 w-2.5 rounded-full"
              :class="priorityDotClass(priority)"
            />
            <span class="text-sm text-gray-800 dark:text-gray-100 capitalize">
              {{ priority }}
            </span>
          </div>
          <span class="text-sm font-semibold text-black dark:text-white">
            {{ count }}
          </span>
        </div>

        <div
          v-if="!Object.keys(byPriority || {}).length"
          class="py-4 text-center text-sm text-gray-500 dark:text-gray-400"
        >
          No priority distribution available
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import Card from '@/Components/UI/Card.vue'

defineProps({
  byStatus: {
    type: Object,
    default: () => ({}),
  },
  byPriority: {
    type: Object,
    default: () => ({}),
  },
})

const statusDotClass = (status) => {
  const map = {
    planning: 'bg-gray-400',
    active: 'bg-success',
    on_hold: 'bg-warning',
    completed: 'bg-primary',
    cancelled: 'bg-danger',
  }
  return map[status] || 'bg-gray-400'
}

const priorityDotClass = (priority) => {
  const map = {
    low: 'bg-success',
    medium: 'bg-warning',
    high: 'bg-orange-500',
    urgent: 'bg-danger',
  }
  return map[(priority || '').toLowerCase()] || 'bg-gray-400'
}
</script>

