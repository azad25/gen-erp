<template>
  <SidebarProvider>
    <AppLayout>
      <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">
              Project Management
            </h1>
            <p class="text-sm text-gray-1 dark:text-gray-400">
              Jira-like overview of project throughput, focus work, and board status.
            </p>
          </div>
          <div class="flex items-center gap-3">
            <Button
              size="sm"
              variant="outline"
              @click="$inertia.visit('/projects')"
            >
              View all projects
            </Button>
            <Button
              size="sm"
              @click="$inertia.visit('/projects/create')"
            >
              New project
            </Button>
          </div>
        </div>

        <!-- Summary metrics -->
        <ProjectSummaryGrid :stats="dashboardData" />

        <!-- Main grid: focus project + my work + distribution -->
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
          <div class="space-y-6 xl:col-span-2">
            <FocusProjectPanel :project="focusProject" />
            <ProjectListCard :projects="dashboardData.recent_projects || []" />
          </div>
          <div class="space-y-6">
            <ProjectStatusPanels
              :by-status="dashboardData.projects_by_status || {}"
              :by-priority="dashboardData.projects_by_priority || {}"
            />
            <MyWorkList :tasks="myTasks" />
          </div>
        </div>
      </div>
    </AppLayout>
  </SidebarProvider>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import SidebarProvider from '@/Components/Layout/SidebarProvider.vue'
import Button from '@/Components/UI/Button.vue'
import ProjectSummaryGrid from '@/Components/Projects/ProjectSummaryGrid.vue'
import ProjectListCard from '@/Components/Projects/ProjectListCard.vue'
import ProjectStatusPanels from '@/Components/Projects/ProjectStatusPanels.vue'
import MyWorkList from '@/Components/Projects/MyWorkList.vue'
import FocusProjectPanel from '@/Components/Projects/FocusProjectPanel.vue'

const props = defineProps({
  dashboardData: {
    type: Object,
    default: () => ({
      total_projects: 0,
      active_projects: 0,
      completed_projects: 0,
      overdue_projects: 0,
      recent_projects: [],
      projects_by_status: {},
      projects_by_priority: {},
      my_tasks: [],
      focus_project: null,
    }),
  },
})

const dashboardData = computed(() => props.dashboardData || {})
const myTasks = computed(() => dashboardData.value.my_tasks || [])
const focusProject = computed(() => dashboardData.value.focus_project || null)
</script>