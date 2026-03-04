<template>
  <Card>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-black dark:text-white">
          My Work
        </h3>
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
          {{ tasks.length }} open
        </span>
      </div>
    </template>

    <div class="space-y-3">
      <div
        v-for="task in tasks"
        :key="task.id"
        class="flex items-start justify-between rounded-lg border border-stroke px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors"
      >
        <div class="space-y-1">
          <p class="text-sm font-medium text-black dark:text-white">
            {{ task.title }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ task.project?.name || 'Unassigned project' }}
            <span v-if="task.key" class="mx-1 text-gray-400">•</span>
            <span v-if="task.key" class="font-mono text-[11px] text-gray-500">
              {{ task.key }}
            </span>
          </p>
          <div class="flex flex-wrap gap-2">
            <span
              class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold capitalize"
              :class="statusChipClass(task.status)"
            >
              {{ task.status || 'backlog' }}
            </span>
            <span
              class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold capitalize"
              :class="priorityChipClass(task.priority)"
            >
              {{ task.priority || 'normal' }}
            </span>
            <span
              v-if="task.due_date"
              class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-white/5 dark:text-gray-200"
            >
              <span>
                {{ formatDate(task.due_date) }}
              </span>
            </span>
          </div>
        </div>
        <div class="flex flex-col items-end gap-2">
          <div class="flex items-center gap-2">
            <div class="h-1.5 w-16 rounded-full bg-gray-200 dark:bg-gray-800 overflow-hidden">
              <div
                class="h-full rounded-full bg-primary"
                :style="{ width: `${task.progress || 0}%` }"
              />
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-300">
              {{ task.progress || 0 }}%
            </span>
          </div>
          <Button
            v-if="task.href"
            size="sm"
            variant="outline"
            class="text-xs"
            @click="$inertia.visit(task.href)"
          >
            Open
          </Button>
        </div>
      </div>

      <div
        v-if="!tasks.length"
        class="py-6 text-center text-sm text-gray-500 dark:text-gray-400"
      >
        No tasks assigned to you.
      </div>
    </div>
  </Card>
</template>

<script setup>
import Card from '@/Components/UI/Card.vue'
import Button from '@/Components/UI/Button.vue'

defineProps({
  tasks: {
    type: Array,
    default: () => [],
  },
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-BD', {
    month: 'short',
    day: 'numeric',
  })
}

const statusChipClass = (status) => {
  const map = {
    backlog: 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-100',
    todo: 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-100',
    in_progress: 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400',
    review: 'bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400',
    done: 'bg-success/10 text-success',
    blocked: 'bg-danger/10 text-danger',
  }

  return map[(status || '').toLowerCase()] || map.backlog
}

const priorityChipClass = (priority) => {
  const map = {
    low: 'bg-success/10 text-success',
    medium: 'bg-warning/10 text-warning',
    high: 'bg-orange-500/10 text-orange-500',
    urgent: 'bg-danger/10 text-danger',
  }
  return map[(priority || '').toLowerCase()] || 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-100'
}
</script>

