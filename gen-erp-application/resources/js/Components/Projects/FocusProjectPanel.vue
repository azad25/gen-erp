<template>
  <Card v-if="project">
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-black dark:text-white">
            Focus Project
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            High impact project you’re tracking closely
          </p>
        </div>
        <Badge
          :variant="'light'"
          :color="statusBadgeColor(project.status)"
          size="sm"
        >
          {{ project.status || 'active' }}
        </Badge>
      </div>
    </template>

    <div class="space-y-4">
      <div class="flex items-start justify-between gap-4">
        <div class="space-y-1">
          <p class="text-sm font-semibold text-black dark:text-white">
            {{ project.name }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ project.description || 'No description provided.' }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            Owner:
            <span class="font-medium text-gray-800 dark:text-gray-100">
              {{ project.project_manager?.name || 'Unassigned' }}
            </span>
          </p>
        </div>
        <div class="flex flex-col items-end gap-2">
          <div class="flex items-center gap-2">
            <div class="h-1.5 w-24 rounded-full bg-gray-200 dark:bg-gray-800 overflow-hidden">
              <div
                class="h-full rounded-full bg-primary"
                :style="{ width: `${project.progress_percentage || 0}%` }"
              />
            </div>
            <span class="text-xs text-gray-600 dark:text-gray-200">
              {{ project.progress_percentage || 0 }}%
            </span>
          </div>
          <p v-if="project.due_date" class="text-xs text-gray-500 dark:text-gray-400">
            Due {{ formatDate(project.due_date) }}
          </p>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <Button
          v-if="project.show_href"
          size="sm"
          @click="$inertia.visit(project.show_href)"
        >
          Open project
        </Button>
        <Button
          v-if="project.board_href"
          size="sm"
          variant="outline"
          @click="$inertia.visit(project.board_href)"
        >
          Open Kanban board
        </Button>
      </div>
    </div>
  </Card>
</template>

<script setup>
import Card from '@/Components/UI/Card.vue'
import Badge from '@/Components/UI/Badge.vue'
import Button from '@/Components/UI/Button.vue'

defineProps({
  project: {
    type: Object,
    default: null,
  },
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-BD', {
    month: 'short',
    day: 'numeric',
  })
}

const statusBadgeColor = (status) => {
  const map = {
    planning: 'light',
    active: 'primary',
    on_hold: 'warning',
    completed: 'success',
    cancelled: 'error',
  }
  return map[(status || '').toLowerCase()] || 'primary'
}
</script>

