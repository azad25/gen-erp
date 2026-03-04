<template>
  <SidebarProvider>
    <AppLayout>
      <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">
              Task Dashboard
            </h1>
            <p class="text-sm text-gray-1 dark:text-gray-400">
              Manage your assigned tasks and track progress
            </p>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard
            label="Total Tasks"
            :value="stats.total_tasks"
            subtitle="All assigned tasks"
            color="teal"
          >
            <template #icon>
              <ClipboardDocumentListIcon class="w-5 h-5" />
            </template>
          </StatCard>

          <StatCard
            label="In Progress"
            :value="stats.in_progress_tasks"
            subtitle="Currently being worked on"
            color="amber"
          >
            <template #icon>
              <ClockIcon class="w-5 h-5" />
            </template>
          </StatCard>

          <StatCard
            label="Completed"
            :value="stats.completed_tasks"
            subtitle="Finished tasks"
            color="green"
          >
            <template #icon>
              <CheckCircleIcon class="w-5 h-5" />
            </template>
          </StatCard>

          <StatCard
            label="Hours Logged"
            :value="stats.total_hours"
            subtitle="Total tracked time"
            color="teal"
          >
            <template #icon>
              <ClockIcon class="w-5 h-5" />
            </template>
          </StatCard>
        </div>

        <!-- Task List -->
        <Card>
          <template #header>
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-black dark:text-white">
                My Tasks
              </h2>
              <div class="flex gap-2">
                <select
                  v-model="filters.status"
                  class="rounded-md border border-stroke bg-transparent px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                >
                  <option value="">All Status</option>
                  <option value="assigned">Assigned</option>
                  <option value="in_progress">In Progress</option>
                  <option value="completed">Completed</option>
                  <option value="on_hold">On Hold</option>
                </select>
                <select
                  v-model="filters.priority"
                  class="rounded-md border border-stroke bg-transparent px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                >
                  <option value="">All Priority</option>
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>
          </template>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stroke text-sm">
              <thead class="bg-gray-3/40 dark:bg-gray-900/40">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Task
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Project
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Priority
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Due Date
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Progress
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-stroke bg-white dark:bg-gray-900">
                <tr v-for="task in filteredTasks" :key="task.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                  <td class="px-6 py-4">
                    <div>
                      <div class="text-sm font-medium text-black dark:text-white">
                        {{ task.title }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ task.description?.substring(0, 80) }}<span v-if="task.description?.length > 80">...</span>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    {{ task.project?.name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="getStatusClass(task.status)"
                      class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold"
                    >
                      {{ task.status.replace('_', ' ').toUpperCase() }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="getPriorityClass(task.priority)"
                      class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold"
                    >
                      {{ task.priority?.toUpperCase() }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    {{ task.due_date ? formatDate(task.due_date) : '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <div class="w-20 bg-gray-200 dark:bg-gray-800 rounded-full h-2">
                        <div
                          class="h-2 rounded-full bg-primary"
                          :style="{ width: `${task.progress}%` }"
                        ></div>
                      </div>
                      <span class="text-xs text-gray-600 dark:text-gray-300">
                        {{ task.progress }}%
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-xs font-medium">
                    <button
                      v-if="task.status === 'assigned'"
                      @click="startTask(task)"
                      class="text-primary hover:text-primary-dark mr-3"
                    >
                      Start
                    </button>
                    <button
                      @click="logTime(task)"
                      class="text-success hover:text-success/80 mr-3"
                    >
                      Log Time
                    </button>
                    <button
                      @click="viewTask(task)"
                      class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                    >
                      View
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </Card>
      </div>

      <!-- Time Logging Modal -->
      <TimeLogModal
        :show="showTimeModal"
        :task="selectedTask"
        @close="showTimeModal = false"
        @saved="handleTimeLogged"
      />
    </AppLayout>
  </SidebarProvider>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import StatCard from '@/Components/UI/StatCard.vue'
import Card from '@/Components/UI/Card.vue'
import TimeLogModal from '@/Components/HR/TimeLogModal.vue'
import { 
    ClipboardDocumentListIcon, 
    ClockIcon, 
    CheckCircleIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tasks: Array,
    stats: Object
})

const filters = ref({
    status: '',
    priority: ''
})

const showTimeModal = ref(false)
const selectedTask = ref(null)

const filteredTasks = computed(() => {
    let filtered = props.tasks

    if (filters.value.status) {
        filtered = filtered.filter(task => task.status === filters.value.status)
    }

    if (filters.value.priority) {
        filtered = filtered.filter(task => task.priority === filters.value.priority)
    }

    return filtered
})

const getStatusClass = (status) => {
    const classes = {
        assigned: 'bg-gray-100 text-gray-800',
        in_progress: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-green-100 text-green-800',
        on_hold: 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityClass = (priority) => {
    const classes = {
        low: 'bg-blue-100 text-blue-800',
        medium: 'bg-yellow-100 text-yellow-800',
        high: 'bg-orange-100 text-orange-800',
        urgent: 'bg-red-100 text-red-800'
    }
    return classes[priority] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const startTask = (task) => {
    router.put(`/api/v1/hr/employees/${task.employee_id}/tasks/${task.id}`, {
        status: 'in_progress',
        started_at: new Date().toISOString()
    })
}

const logTime = (task) => {
    selectedTask.value = task
    showTimeModal.value = true
}

const viewTask = (task) => {
    router.visit(`/hr/tasks/${task.id}`)
}

const handleTimeLogged = () => {
    showTimeModal.value = false
    router.reload()
}
</script>