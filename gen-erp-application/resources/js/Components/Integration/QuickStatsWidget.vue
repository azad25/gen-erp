<template>
  <CrossDomainWidget
    title="Quick Stats"
    endpoint="/api/v1/integration/quick-stats"
    :auto-refresh="60"
  >
    <template #default="{ data }">
      <div v-if="data" class="grid grid-cols-2 gap-4">
        <!-- CRM Stats -->
        <div class="space-y-3">
          <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">CRM</h4>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Active Leads</span>
              <span class="text-sm font-semibold text-blue-600">{{ data.crm?.active_leads || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Open Opportunities</span>
              <span class="text-sm font-semibold text-green-600">{{ data.crm?.open_opportunities || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">This Month Revenue</span>
              <span class="text-sm font-semibold text-purple-600">
                ৳{{ formatNumber(data.crm?.monthly_revenue || 0) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Logistics Stats -->
        <div class="space-y-3">
          <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Logistics</h4>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Active Shipments</span>
              <span class="text-sm font-semibold text-orange-600">{{ data.logistics?.active_shipments || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Pending Returns</span>
              <span class="text-sm font-semibold text-red-600">{{ data.logistics?.pending_returns || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">COD Collections</span>
              <span class="text-sm font-semibold text-yellow-600">
                ৳{{ formatNumber(data.logistics?.cod_collections || 0) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Projects Stats -->
        <div class="space-y-3">
          <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Projects</h4>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Active Projects</span>
              <span class="text-sm font-semibold text-teal-600">{{ data.projects?.active_projects || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Overdue Tasks</span>
              <span class="text-sm font-semibold text-red-600">{{ data.projects?.overdue_tasks || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Completion Rate</span>
              <span class="text-sm font-semibold text-green-600">
                {{ data.projects?.completion_rate || 0 }}%
              </span>
            </div>
          </div>
        </div>

        <!-- Notifications Stats -->
        <div class="space-y-3">
          <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">System</h4>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Unread Notifications</span>
              <span class="text-sm font-semibold text-indigo-600">{{ data.system?.unread_notifications || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Active Users</span>
              <span class="text-sm font-semibold text-blue-600">{{ data.system?.active_users || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">System Health</span>
              <div class="flex items-center space-x-1">
                <div
                  class="w-2 h-2 rounded-full"
                  :class="getHealthColor(data.system?.health_status)"
                ></div>
                <span class="text-sm font-semibold capitalize">
                  {{ data.system?.health_status || 'unknown' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-8">
        <ChartBarIcon class="mx-auto h-8 w-8 text-gray-400" />
        <p class="mt-2 text-sm text-gray-500">No statistics available</p>
      </div>
    </template>
  </CrossDomainWidget>
</template>

<script setup>
import { ChartBarIcon } from '@heroicons/vue/24/outline'
import CrossDomainWidget from './CrossDomainWidget.vue'

// Methods
const formatNumber = (num) => {
  if (num >= 100000) {
    return (num / 100000).toFixed(1) + 'L'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}

const getHealthColor = (status) => {
  const colors = {
    'healthy': 'bg-green-500',
    'warning': 'bg-yellow-500',
    'critical': 'bg-red-500'
  }
  return colors[status] || 'bg-gray-400'
}
</script>