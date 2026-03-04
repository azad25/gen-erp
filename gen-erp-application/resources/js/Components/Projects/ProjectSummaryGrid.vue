<template>
  <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
    <StatCard
      label="Total Projects"
      :value="stats.total_projects"
      subtitle="All projects in the workspace"
      color="teal"
    >
      <template #icon>📁</template>
    </StatCard>

    <StatCard
      label="Active"
      :value="stats.active_projects"
      subtitle="Currently in progress"
      color="green"
    >
      <template #icon>🚀</template>
    </StatCard>

    <StatCard
      label="Overdue"
      :value="stats.overdue_projects"
      subtitle="Past due date"
      color="amber"
    >
      <template #icon>⏰</template>
    </StatCard>

    <StatCard
      label="Completed"
      :value="stats.completed_projects"
      subtitle="Successfully delivered"
      color="teal"
    >
      <template #icon>✅</template>
    </StatCard>
  </div>
</template>

<script setup>
import StatCard from '@/Components/UI/StatCard.vue'

defineProps({
  stats: {
    type: Object,
    default: () => ({
      total_projects: 0,
      active_projects: 0,
      overdue_projects: 0,
      completed_projects: 0,
    }),
  },
})
</script>

