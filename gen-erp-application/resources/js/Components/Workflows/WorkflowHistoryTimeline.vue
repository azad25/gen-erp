<template>
  <div class="space-y-2">
    <h4 class="text-sm font-medium text-gray-900">Workflow History</h4>
    <div v-if="history.length === 0" class="text-sm text-gray-1 py-4">
      No history recorded yet
    </div>
    <div v-else class="space-y-2">
      <div
        v-for="(item, index) in history"
        :key="item.id"
        class="flex items-start gap-3"
      >
        <!-- Timeline Dot -->
        <div class="flex flex-col items-center">
          <div
            class="w-2 h-2 rounded-full mt-1.5"
            :class="getStatusColor(item.to_status_key)"
          ></div>
          <div
            v-if="index < history.length - 1"
            class="w-0.5 h-8 bg-gray-200"
          ></div>
        </div>

        <!-- History Content -->
        <div class="flex-1 pb-2">
          <div class="flex items-center gap-2 mb-1">
            <span class="font-medium">{{ item.triggered_by?.name || 'System' }}</span>
            <Badge :variant="item.to_status_key" size="sm">
              {{ getStatusLabel(item.to_status_key) }}
            </Badge>
          </div>
          <p v-if="item.comment" class="text-gray-600 text-xs">{{ item.comment }}</p>
          <p class="text-gray-500 text-xs">{{ formatDate(item.created_at) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Badge from '@/Components/UI/Badge.vue'

defineProps({
  history: {
    type: Array,
    default: () => []
  }
})

const getStatusColor = (statusKey) => {
  const colors = {
    draft: 'bg-gray-400',
    pending_approval: 'bg-yellow-400',
    approved: 'bg-green-500',
    rejected: 'bg-red-500',
    completed: 'bg-blue-500'
  }
  return colors[statusKey] || 'bg-gray-400'
}

const getStatusLabel = (statusKey) => {
  const labels = {
    draft: 'Draft',
    pending_approval: 'Pending Approval',
    approved: 'Approved',
    rejected: 'Rejected',
    completed: 'Completed'
  }
  return labels[statusKey] || statusKey
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}
</script>
