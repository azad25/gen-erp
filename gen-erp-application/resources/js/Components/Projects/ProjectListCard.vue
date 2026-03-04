<template>
  <Card>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-black dark:text-white">
          Recent Projects
        </h3>
        <Link
          :href="indexHref"
          class="text-sm font-medium text-primary hover:text-primary-dark"
        >
          View all
        </Link>
      </div>
    </template>

    <div class="space-y-3">
      <div
        v-for="project in projects"
        :key="project.id"
        class="flex items-center justify-between rounded-lg border border-stroke px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors"
      >
        <div class="flex items-center gap-3">
          <div
            class="h-2.5 w-2.5 rounded-full"
            :class="statusDotClass(project.status)"
          />
          <div>
            <p class="text-sm font-medium text-black dark:text-white">
              {{ project.name }}
            </p>
            <p class="text-xs text-gray-1">
              Manager:
              {{ project.project_manager?.name || 'Unassigned' }}
            </p>
          </div>
        </div>
        <div class="flex flex-col items-end gap-1">
          <div class="flex items-center gap-2">
            <span
              class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold capitalize"
              :class="priorityChipClass(project.priority)"
            >
              {{ project.priority || 'normal' }}
            </span>
          </div>
          <div class="flex items-center gap-2">
            <div class="h-1.5 w-20 rounded-full bg-gray-200 dark:bg-gray-800 overflow-hidden">
              <div
                class="h-full rounded-full bg-primary"
                :style="{ width: `${project.progress_percentage || 0}%` }"
              />
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-300">
              {{ project.progress_percentage || 0 }}%
            </span>
          </div>
        </div>
      </div>

      <div
        v-if="!projects?.length"
        class="py-6 text-center text-sm text-gray-500 dark:text-gray-400"
      >
        No recent projects
      </div>
    </div>
  </Card>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import Card from '@/Components/UI/Card.vue'

const props = defineProps({
  projects: {
    type: Array,
    default: () => [],
  },
  indexHref: {
    type: String,
    default: '/projects',
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

